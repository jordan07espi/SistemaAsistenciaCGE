<?php
// controllers/ObtenerEstadisticas.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../models/AsistenciaDAO.php';

// Solo admin
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    exit;
}

try {
    $dao = new AsistenciaDAO();
    
    // 1. Datos para el Pastel (Presentes vs Ausentes)
    $rawEstados = $dao->obtenerEstadisticasHoy(); // Ya lo creamos en el paso anterior
    $statsEstados = [
        'DENTRO' => 0,
        'FUERA' => 0
    ];
    foreach($rawEstados as $fila) {
        $statsEstados[$fila['estado_actual']] = $fila['total'];
    }

    // 2. Datos para Barras (Asistencia Semanal)
    $rawSemana = $dao->obtenerAsistenciasSemana();
    $fechas = [];
    $conteos = [];
    
    foreach($rawSemana as $dia) {
        $fechas[] = date('d/m', strtotime($dia['fecha'])); // Ej: 19/12
        $conteos[] = $dia['total'];
    }

    echo json_encode([
        'status' => 'success',
        'pie' => [
            'labels' => ['Presentes (Dentro)', 'Ausentes (Fuera)'],
            'data' => [$statsEstados['DENTRO'], $statsEstados['FUERA']]
        ],
        'bar' => [
            'labels' => $fechas,
            'data' => $conteos
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}