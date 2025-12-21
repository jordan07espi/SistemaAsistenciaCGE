<?php
// controllers/ListarColaboradores.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../models/ColaboradorDAO.php';

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$busqueda = isset($_GET['search']) ? trim($_GET['search']) : ''; // Nuevo parámetro

$offset = ($page - 1) * $limit;

try {
    $dao = new ColaboradorDAO();
    
    // Pasamos la búsqueda al DAO
    $colaboradores = $dao->listarPaginado($limit, $offset, $busqueda);
    $totalRegistros = $dao->contarTotal($busqueda);
    $totalPaginas = ceil($totalRegistros / $limit);

    echo json_encode([
        'status' => 'success',
        'data' => $colaboradores,
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