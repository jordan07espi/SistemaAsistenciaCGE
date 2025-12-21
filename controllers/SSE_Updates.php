<?php
// controllers/SSE_Updates.php

// 1. Cabeceras obligatorias para SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

// Evitar bloqueos de sesión y tiempo de ejecución
session_start();
session_write_close(); // IMPORTANTE: Libera la sesión para que el navegador no se congele
set_time_limit(0);     // El script puede correr indefinidamente

require_once __DIR__ . '/../models/AsistenciaDAO.php';

$dao = new AsistenciaDAO();

// 2. Punto de partida
// Si el navegador se reconecta, suele enviar 'Last-Event-ID'. Si no, buscamos el actual.
$ultimoId = $dao->obtenerUltimoId();

// Manejo de reconexión automática del navegador
if (isset($_SERVER["HTTP_LAST_EVENT_ID"])) {
    $ultimoId = intval($_SERVER["HTTP_LAST_EVENT_ID"]);
}

// 3. Bucle infinito de escucha
while (true) {
    // Consultar BD
    $nuevos = $dao->obtenerNuevosRegistros($ultimoId);

    if (!empty($nuevos)) {
        foreach ($nuevos as $asistencia) {
            // Preparar datos para enviar
            $data = json_encode([
                'nombre' => $asistencia['nombre_completo'],
                'tipo'   => $asistencia['tipo'], // ENTRADA o SALIDA
                'hora'   => date('H:i:s', strtotime($asistencia['fecha_hora'])),
                'sede'   => $asistencia['sede_registro']
            ]);

            // Formato estricto de SSE:
            echo "id: " . $asistencia['id'] . "\n";
            echo "data: " . $data . "\n\n";

            $ultimoId = $asistencia['id'];
        }
        
        // Forzar envío inmediato al navegador
        if (ob_get_level() > 0) ob_flush();
        flush();
    }

    // Esperar X segundos antes de volver a consultar (Polling al servidor DB)
    // 3 segundos es un buen balance entre "tiempo real" y rendimiento.
    sleep(3); 
    
    // Verificar si el cliente cerró la conexión para matar el script PHP
    if (connection_aborted()) {
        break;
    }
}