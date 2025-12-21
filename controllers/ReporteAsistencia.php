<?php
// controllers/ReporteAsistencia.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../models/AsistenciaDAO.php';

$input = json_decode(file_get_contents('php://input'), true);

$inicio = $input['fecha_inicio'] ?? date('Y-m-d');
$fin    = $input['fecha_fin']    ?? date('Y-m-d');
$sede   = $input['sede']         ?? 'TODAS';

// Paginación (Valores por defecto)
$page   = isset($input['page']) ? (int)$input['page'] : 1;
$limit  = isset($input['limit']) ? (int)$input['limit'] : 10; // Por defecto 10
$offset = ($page - 1) * $limit;

try {
    $dao = new AsistenciaDAO();
    
    // 1. Obtener datos paginados
    $datos = $dao->filtrarReporte($inicio, $fin, $sede, $limit, $offset);
    
    // 2. Contar total real para saber cuántas páginas son
    $totalRegistros = $dao->contarReporte($inicio, $fin, $sede);
    $totalPaginas = ceil($totalRegistros / $limit);
    
    echo json_encode([
        'status' => 'success', 
        'data' => $datos,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total_records' => $totalRegistros,
            'total_pages' => $totalPaginas
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}