<?php
// controllers/RegistrarAsistencia.php

// 1. Configuración de cabeceras
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST');

// 2. Importar los modelos
require_once __DIR__ . '/../models/ColaboradorDAO.php';
require_once __DIR__ . '/../models/AsistenciaDAO.php';

// 3. Obtener los datos enviados
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['cedula']) || !isset($input['sede'])) {
    echo json_encode(['status' => 'error', 'message' => 'Datos incompletos (Falta cédula o sede)']);
    exit;
}

$cedula = $input['cedula'];
$sede = $input['sede']; 
$tipoManual = isset($input['tipo_manual']) ? $input['tipo_manual'] : null;

$colaboradorDAO = new ColaboradorDAO();
$asistenciaDAO = new AsistenciaDAO();

try {
    // 4. Buscar al colaborador
    $colaborador = $colaboradorDAO->obtenerPorCedula($cedula);

    if (!$colaborador) {
        echo json_encode(['status' => 'error', 'message' => 'Colaborador no encontrado']);
        exit;
    }

    // =================================================================================
    // 🛡️ LÓGICA ANTI-REBOTE (Anti-Double Scan)
    // Evita registros duplicados si el usuario deja el carnet frente a la cámara
    // =================================================================================
    
    // Obtener la ultimísima vez que marcó (requiere que hayas agregado la función al DAO)
    $ultima = $asistenciaDAO->obtenerUltimaAsistencia($colaborador['id']);

    if ($ultima) {
        $tiempoUltimo = strtotime($ultima['fecha_hora']);
        $tiempoActual = time();
        $diferencia = $tiempoActual - $tiempoUltimo;

        // Si pasaron menos de 120 segundos (2 minutos)
        if ($diferencia < 120) {
            // Si es un intento manual forzado, permitimos el paso (opcional)
            // Si es escaneo automático, lo bloqueamos
            if (!$tipoManual) {
                echo json_encode([
                    'status' => 'warning', 
                    'message' => 'Ya registraste asistencia hace un momento. Espera unos minutos.'
                ]);
                exit; // DETENER EJECUCIÓN
            }
        }
    }
    // =================================================================================

    // 5. Determinar Tipo de Asistencia (Entrada/Salida)
    
    // a) Si es Manual (Botones forzados)
    if ($tipoManual) {
        $tipoAsistencia = $tipoManual; // 'ENTRADA' o 'SALIDA'
        $nuevoEstado = ($tipoManual === 'ENTRADA') ? 'DENTRO' : 'FUERA';
        $modoRegistro = 'QR_MANUAL'; // O ADMIN_MANUAL según prefieras
    } 
    // b) Si es Automático (Inteligente)
    else {
        // Verificar si es el primer registro del día
        $hoy = date('Y-m-d');
        $fechaUltima = $ultima ? date('Y-m-d', strtotime($ultima['fecha_hora'])) : '';
        $esNuevoDia = ($fechaUltima !== $hoy);

        if ($esNuevoDia || $colaborador['estado_actual'] === 'FUERA') {
            $tipoAsistencia = 'ENTRADA';
            $nuevoEstado = 'DENTRO';
        } else {
            $tipoAsistencia = 'SALIDA';
            $nuevoEstado = 'FUERA';
        }
        $modoRegistro = 'QR_AUTO';
    }

    // 6. Registrar en Base de Datos
    $guardado = $asistenciaDAO->registrar(
        $colaborador['id'], 
        $tipoAsistencia, 
        $sede, 
        $modoRegistro,
        null // Observación opcional
    );

    // 7. Actualizar estado y responder
    if ($guardado) {
        $colaboradorDAO->actualizarEstado($colaborador['id'], $nuevoEstado);

        echo json_encode([
            'status' => 'success',
            'tipo' => $tipoAsistencia,
            'colaborador' => $colaborador['nombre_completo'],
            'hora' => date('H:i:s'),
            'mensaje' => 'Registro Exitoso'
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al guardar en BD']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error del servidor: ' . $e->getMessage()]);
}