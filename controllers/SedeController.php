<?php
// controllers/SedeController.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../models/SedeDAO.php';

$method = $_SERVER['REQUEST_METHOD'];
$dao = new SedeDAO();

try {
    // 1. LISTAR (GET) - Público y Admin
    if ($method === 'GET') {
        echo json_encode($dao->listarTodas());
        exit;
    }

    // 2. CREAR/ELIMINAR (POST) - Solo Admin
    if ($method === 'POST') {
        // Validar seguridad (Solo admin logueado puede modificar)
        if (!isset($_SESSION['admin_id'])) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $accion = $input['accion'] ?? '';

        if ($accion === 'crear') {
            if (empty($input['nombre'])) throw new Exception("El nombre es obligatorio");
            
            $color = $input['color'] ?? 'bg-blue-600';
            if ($dao->crear($input['nombre'], $color)) {
                echo json_encode(['status' => 'success', 'message' => 'Sede creada']);
            } else {
                throw new Exception("Error al crear. ¿Ya existe?");
            }
        } 
        elseif ($accion === 'eliminar') {
            if (empty($input['id'])) throw new Exception("ID faltante");
            
            $dao->eliminar($input['id']);
            echo json_encode(['status' => 'success', 'message' => 'Sede eliminada']);
        }
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}