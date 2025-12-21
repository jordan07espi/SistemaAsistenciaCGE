<?php
// controllers/GestionarColaborador.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../models/ColaboradorDAO.php';

// Solo Admin puede crear
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $cedula = trim($input['cedula'] ?? '');
    $nombre = trim($input['nombre'] ?? '');
    $origen = trim($input['origen'] ?? '');

    if (empty($cedula) || empty($nombre) || empty($origen)) {
        echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios']);
        exit;
    }

    $dao = new ColaboradorDAO();
    
    try {
        if ($dao->crear($cedula, $nombre, $origen)) {
            echo json_encode(['status' => 'success', 'message' => 'Colaborador registrado correctamente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error: La cédula ya existe en el sistema']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error interno: ' . $e->getMessage()]);
    }
}