<?php
// controllers/CambiarEstadoColaborador.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../models/ColaboradorDAO.php';

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? null;
$activo = $input['activo'] ?? null; // 1 o 0

if (!$id || $activo === null) {
    echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
    exit;
}

try {
    $dao = new ColaboradorDAO();
    if ($dao->cambiarEstadoActivo($id, $activo)) {
        $msg = $activo ? 'Colaborador habilitado' : 'Colaborador deshabilitado';
        echo json_encode(['status' => 'success', 'message' => $msg]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se pudo actualizar']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}