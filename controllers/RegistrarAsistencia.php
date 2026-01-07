<?php
// controllers/RegistrarAsistencia.php

// 1. Configuración
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/ColaboradorDAO.php';
require_once __DIR__ . '/../models/AsistenciaDAO.php';

date_default_timezone_set('America/Guayaquil');

try {
    // 2. Obtener input
    $input = json_decode(file_get_contents('php://input'), true);
    $cedula = $input['cedula'] ?? '';
    $sede = $input['sede'] ?? 'Sede Principal';
    $tipoManual = $input['tipo_manual'] ?? null; // 'ENTRADA', 'SALIDA' o null

    if (empty($cedula)) throw new Exception("Cédula no detectada");

    // 3. Buscar Colaborador
    $colDAO = new ColaboradorDAO();
    $col = $colDAO->obtenerPorCedula($cedula);

    if (!$col) {
        echo json_encode(['status' => 'error', 'message' => 'Colaborador no encontrado']);
        exit;
    }
    if ($col['activo'] != 1) {
        echo json_encode(['status' => 'error', 'message' => 'Colaborador inactivo']);
        exit;
    }

    // 4. Variables de Tiempo
    $ahora = new DateTime();
    $hoyYMD = $ahora->format('Y-m-d');
    
    // Obtener fecha de última acción (si existe)
    $ultimaAccion = $col['ultima_accion'] ? new DateTime($col['ultima_accion']) : null;
    $ultimaFechaYMD = $ultimaAccion ? $ultimaAccion->format('Y-m-d') : '';
    
    $estadoActual = $col['estado_actual']; // 'DENTRO' o 'FUERA'

    // 5. LÓGICA "CEREBRO INTELIGENTE" MEJORADA
    $nuevoTipo = '';

    // CASO A: Modo Manual (Botones Maestros) - Tienen prioridad absoluta
    if ($tipoManual) {
        // Solo validamos anti-spam básico de 5 segundos
        if ($ultimaAccion && ($ahora->getTimestamp() - $ultimaAccion->getTimestamp()) < 5) {
            echo json_encode(['status' => 'warning', 'message' => 'Espera unos segundos...']);
            exit;
        }
        $nuevoTipo = $tipoManual;
    } 
    else {
        // CASO B: Modo Automático (QR)
        
        // REGLA 1: ¿Es un NUEVO DÍA?
        // Si la última marca no fue hoy, NO IMPORTA el estado anterior, hoy empieza de cero.
        if ($ultimaFechaYMD !== $hoyYMD) {
            $nuevoTipo = 'ENTRADA';
        } 
        else {
            // Es el MISMO DÍA. Aplicamos lógica de alternancia y protección.

            // REGLA 2: PROTECCIÓN DE TIEMPO (Anti-Rebote 60 segundos)
            // Evita que marque salida si acaba de marcar entrada hace 59s o menos.
            $segundosPasados = $ahora->getTimestamp() - $ultimaAccion->getTimestamp();
            if ($segundosPasados < 60) { 
                echo json_encode([
                    'status' => 'warning', 
                    'message' => 'Ya registraste tu ' . ($estadoActual == 'DENTRO' ? 'entrada' : 'salida') . ' hace un momento.'
                ]);
                exit;
            }

            // REGLA 3: Alternancia Simple (Dentro -> Salida, Fuera -> Entrada)
            // La validación visual del kiosco (texto imponente) ayuda al usuario, 
            // pero el sistema confía en el estado actual para cerrar ciclos.
            if ($estadoActual === 'DENTRO') {
                $nuevoTipo = 'SALIDA';
            } else {
                // Si está FUERA y vuelve a marcar hoy, es un reingreso (ENTRADA)
                $nuevoTipo = 'ENTRADA';
            }
        }
    }

    // 6. Guardar y Actualizar
    $nuevoEstado = ($nuevoTipo == 'ENTRADA') ? 'DENTRO' : 'FUERA';

    $asistenciaDAO = new AsistenciaDAO();
    if ($asistenciaDAO->registrar($col['id'], $nuevoTipo, $sede, ($tipoManual ? 'QR_MANUAL' : 'QR_AUTO'))) {
        
        $colDAO->actualizarEstado($col['id'], $nuevoEstado);

        echo json_encode([
            'status' => 'success',
            'tipo' => $nuevoTipo,
            'colaborador' => $col['nombre_completo'],
            'hora' => $ahora->format('H:i:s'),
            'mensaje' => 'Registro Exitoso'
        ]);
    } else {
        throw new Exception("Error al guardar en base de datos");
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>