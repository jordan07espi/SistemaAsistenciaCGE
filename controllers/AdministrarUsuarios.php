<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../models/UsuarioDAO.php';

// 1. BLINDAJE DE SEGURIDAD: Solo Superadmin puede entrar aquí
if (!isset($_SESSION['admin_rol']) || $_SESSION['admin_rol'] !== 'SUPERADMIN') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. Se requieren permisos de Superadmin.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$dao = new UsuarioDAO();

// LISTAR USUARIOS (GET)
if ($method === 'GET') {
    echo json_encode($dao->listarTodos());
    exit;
}

// CREAR O ACTUALIZAR (POST)
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $accion = $input['accion'] ?? '';

    try {
        if ($accion === 'crear') {
            if (empty($input['cedula']) || empty($input['password'])) {
                throw new Exception("Datos incompletos");
            }
            // Por defecto creamos ADMINs, no Superadmins
            if ($dao->crear($input['cedula'], $input['nombre'], $input['password'], 'ADMIN')) {
                echo json_encode(['status' => 'success', 'message' => 'Administrador creado']);
            } else {
                throw new Exception("Error al crear. ¿Cédula duplicada?");
            }
        } 
        elseif ($accion === 'cambiar_pass') {
            if (empty($input['id']) || empty($input['password'])) {
                throw new Exception("Faltan datos");
            }
            // Evitar que te borres o cambies a ti mismo por error (opcional)
            if ($input['id'] == $_SESSION['admin_id']) {
                // Puedes permitirlo o bloquearlo. Aquí lo permitimos.
            }
            
            if ($dao->cambiarPassword($input['id'], $input['password'])) {
                echo json_encode(['status' => 'success', 'message' => 'Contraseña actualizada']);
            } else {
                throw new Exception("Error al actualizar");
            }
        }
        elseif ($accion === 'eliminar') {
            if ($input['id'] == $_SESSION['admin_id']) {
                throw new Exception("No puedes eliminarte a ti mismo");
            }
            $dao->eliminar($input['id']);
            echo json_encode(['status' => 'success', 'message' => 'Usuario eliminado']);
        }

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}