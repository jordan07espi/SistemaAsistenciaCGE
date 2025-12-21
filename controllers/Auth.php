<?php
// controllers/Auth.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../models/UsuarioDAO.php';

// Detectar acción (login o logout)
$accion = $_GET['action'] ?? 'login';

// LOGOUT
if ($accion === 'logout') {
    session_destroy();
    header('Location: ../admin/login.php');
    exit;
}

// LOGIN (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $cedula = $input['cedula'] ?? '';
    $password = $input['password'] ?? '';

    if (empty($cedula) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Complete todos los campos']);
        exit;
    }

    $dao = new UsuarioDAO();
    $usuario = $dao->obtenerPorCedula($cedula);

    // AQUÍ OCURRE LA MAGIA: password_verify compara el texto plano con el hash de la BD
    if ($usuario && password_verify($password, $usuario['password'])) {
        $_SESSION['admin_id'] = $usuario['id'];
        $_SESSION['admin_nombre'] = $usuario['nombre'];
        
        // --- AGREGAR ESTA LÍNEA ---
        $_SESSION['admin_rol'] = $usuario['rol']; 
        // --------------------------

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Cédula o contraseña incorrectas']);
    }
    exit;
}