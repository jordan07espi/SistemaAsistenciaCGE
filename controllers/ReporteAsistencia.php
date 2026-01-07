<?php
// controllers/ReporteAsistencia.php

session_start();
header('Content-Type: application/json');

// 1. Validar Sesión
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado']);
    exit;
}

require_once __DIR__ . '/../models/AsistenciaDAO.php';

// Configurar zona horaria
date_default_timezone_set('America/Guayaquil');

// 2. OBTENER FILTROS VÍA GET
// Usamos $_GET para que coincida con la petición del JavaScript
$inicio = $_GET['inicio'] ?? date('Y-m-d');
$fin    = $_GET['fin']    ?? date('Y-m-d');
$sede   = $_GET['sede']   ?? ''; 

// 3. PAGINACIÓN
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit  = 10; // <--- AQUÍ ESTÁ EL LÍMITE DE 10 REGISTROS
$offset = ($page - 1) * $limit;

try {
    $dao = new AsistenciaDAO();
    
    // 4. Obtener datos (Usamos filtrarReporte tal cual lo tienes en tu DAO)
    $datos = $dao->filtrarReporte($inicio, $fin, $sede, $limit, $offset);
    
    // 5. Contar total para saber cuántas páginas hay
    $totalRegistros = $dao->contarReporte($inicio, $fin, $sede);
    $totalPaginas = ceil($totalRegistros / $limit);
    
    // Enviar respuesta JSON
    echo json_encode([
        'status' => 'success', 
        'data' => $datos,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPaginas,
            'total_records' => $totalRegistros
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>