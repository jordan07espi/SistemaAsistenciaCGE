<?php
// controllers/RegistrarAsistencia.php

// 1. Configuración y Conexión
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/ColaboradorDAO.php';
require_once __DIR__ . '/../models/AsistenciaDAO.php';

// Asegurar Zona Horaria (Vital para cálculos de tiempo)
date_default_timezone_set('America/Guayaquil');

try {
    // 2. Obtener datos del Request
    $input = json_decode(file_get_contents('php://input'), true);
    $cedula = $input['cedula'] ?? '';
    $sede = $input['sede'] ?? 'Sede Principal';
    $tipoManual = $input['tipo_manual'] ?? null; // Puede ser 'ENTRADA', 'SALIDA' o null

    if (empty($cedula)) {
        throw new Exception("Cédula no detectada");
    }

    // 3. Buscar Colaborador
    $colaboradorDAO = new ColaboradorDAO();
    $colaborador = $colaboradorDAO->obtenerPorCedula($cedula);

    if (!$colaborador) {
        echo json_encode(['status' => 'error', 'message' => 'Colaborador no encontrado']);
        exit;
    }

    if ($colaborador['activo'] != 1) {
        echo json_encode(['status' => 'error', 'message' => 'Colaborador inactivo']);
        exit;
    }

    // 4. Lógica de Tiempos y Estado
    $ahora = new DateTime();
    $ultimaAccion = $colaborador['ultima_accion'] ? new DateTime($colaborador['ultima_accion']) : null;
    $estadoActual = $colaborador['estado_actual']; // 'DENTRO' o 'FUERA'

    // Calcular tiempo transcurrido (si existe registro previo)
    $minutosPasados = 999999;
    $horasPasadas = 999999;

    if ($ultimaAccion) {
        $intervalo = $ultimaAccion->diff($ahora); //
        $horasPasadas = $intervalo->h + ($intervalo->days * 24); // Total horas
        $minutosPasados = $intervalo->i + ($horasPasadas * 60);  // Total minutos
    }

    // 5. Anti-Rebote (Evitar doble marca accidental en menos de 2 min)
    if ($tipoManual === null && $minutosPasados < 2) {
        echo json_encode([
            'status' => 'warning',
            'message' => 'Ya registraste tu ' . ($estadoActual == 'DENTRO' ? 'entrada' : 'salida') . ' hace un momento.'
        ]);
        exit;
    }

    // 6. Determinación del Tipo de Registro (El Cerebro de la Lógica)
    $nuevoTipo = '';

    if ($tipoManual) {
        // A) MODO MANUAL: La orden del usuario es ley
        $nuevoTipo = $tipoManual;
    } else {
        // B) MODO AUTOMÁTICO (Inteligente)
        if ($estadoActual == 'FUERA') {
            // Si está fuera, entra.
            $nuevoTipo = 'ENTRADA';
        } else {
            // Si está dentro...
            // REGLA DE SEGURIDAD (16 HORAS):
            // Si pasaron más de 16 horas, asumimos que olvidó marcar salida ayer.
            // Por lo tanto, esto cuenta como una NUEVA ENTRADA (reset).
            if ($horasPasadas > 16) {
                $nuevoTipo = 'ENTRADA';
            } else {
                // Si es un turno normal (menos de 16h), cierra el ciclo.
                $nuevoTipo = 'SALIDA';
            }
        }
    }

    // 7. Definir Nuevo Estado para BD
    $nuevoEstado = ($nuevoTipo == 'ENTRADA') ? 'DENTRO' : 'FUERA';

    // 8. Guardar en Base de Datos
    $asistenciaDAO = new AsistenciaDAO();
    $registroExitoso = $asistenciaDAO->registrar($colaborador['id'], $nuevoTipo, $sede, ($tipoManual ? 'QR_MANUAL' : 'QR_AUTO'));

    if ($registroExitoso) {
        // Actualizar estado del colaborador
        $colaboradorDAO->actualizarEstado($colaborador['id'], $nuevoEstado);

        // Respuesta Exitosa
        echo json_encode([
            'status' => 'success',
            'tipo' => $nuevoTipo,
            'colaborador' => $colaborador['nombre_completo'],
            'hora' => date('H:i:s'), // Hora del servidor (Zona Horaria Ecuador)
            'mensaje' => 'Registro Exitoso'
        ]);

        // (Opcional) Notificar por SSE si lo usas
        // enviarNotificacionSSE(...); 

    } else {
        throw new Exception("Error al guardar en base de datos");
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>