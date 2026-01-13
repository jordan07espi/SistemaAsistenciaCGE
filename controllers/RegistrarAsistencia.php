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
    $horaActual = intval($ahora->format('H')); // Hora en formato 0-23
    
    // Obtener fecha de última acción (si existe)
    $ultimaAccion = $col['ultima_accion'] ? new DateTime($col['ultima_accion']) : null;
    $ultimaFechaYMD = $ultimaAccion ? $ultimaAccion->format('Y-m-d') : '';
    
    $estadoActual = $col['estado_actual']; // 'DENTRO' o 'FUERA'

    // 5. LÓGICA DE REGISTRO
    $nuevoTipo = '';

    // CASO A: Modo Manual (Botones Maestros) - PRIORIDAD ABSOLUTA
    if ($tipoManual) {
        // Validación básica de spam (5 seg) para manual
        if ($ultimaAccion && ($ahora->getTimestamp() - $ultimaAccion->getTimestamp()) < 5) {
            echo json_encode(['status' => 'warning', 'message' => 'Procesando... espera un momento.']);
            exit;
        }
        $nuevoTipo = $tipoManual;
    } 
    else {
        // CASO B: Modo Automático (QR)

        // 1. PROTECCIÓN GENERAL ANTI-DOBLE SCAN (60 segundos)
        // Se aplica siempre en automático para evitar registros accidentales seguidos
        if ($ultimaAccion) {
            $segundosPasados = $ahora->getTimestamp() - $ultimaAccion->getTimestamp();
            if ($segundosPasados < 60) { 
                echo json_encode([
                    'status' => 'warning', 
                    'message' => 'Ya registraste tu asistencia hace un momento.'
                ]);
                exit;
            }
        }

        // 2. DEFINICIÓN DEL TIPO SEGÚN HORARIO (CEREBRO INTELIGENTE)
        
        if ($horaActual >= 12) {
            // --- TARDE (12:00 PM en adelante) ---
            // REGLA: La pantalla dice "MARCANDO SALIDA", así que el sistema OBEDECE.
            // No importa si se olvidó marcar entrada en la mañana, aquí cerramos el ciclo.
            // Si el usuario quiere entrar, debe usar "Forzar Entrada".
            $nuevoTipo = 'SALIDA';

        } else {
            // --- MAÑANA (00:00 AM - 11:59 AM) ---
            // REGLA: Prioridad ENTRADA, pero inteligente.

            if ($ultimaFechaYMD !== $hoyYMD) {
                // Es el primer registro de un NUEVO DÍA en la mañana -> Obligatorio ENTRADA.
                // (Esto corrige si ayer se quedó marcado como DENTRO)
                $nuevoTipo = 'ENTRADA';
            } else {
                // Ya registró algo hoy en la mañana. Aplicamos alternancia.
                // Si marcó entrada a las 8am y sale a las 10am -> Marca SALIDA.
                if ($estadoActual === 'DENTRO') {
                    $nuevoTipo = 'SALIDA';
                } else {
                    $nuevoTipo = 'ENTRADA';
                }
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