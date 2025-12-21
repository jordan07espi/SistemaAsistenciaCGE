<?php
require_once __DIR__ . '/../config/db.php';

class UsuarioDAO {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function obtenerPorCedula($cedula) {
        $sql = "SELECT * FROM usuarios_admin WHERE cedula = :cedula LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':cedula', $cedula);
        $stmt->execute();
        return $stmt->fetch();
    }

    // --- NUEVAS FUNCIONES PARA SUPERADMIN ---

    public function listarTodos() {
        // Ocultamos el hash del password por seguridad
        $sql = "SELECT id, cedula, nombre, rol, created_at FROM usuarios_admin ORDER BY nombre ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function crear($cedula, $nombre, $password, $rol = 'ADMIN') {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $sql = "INSERT INTO usuarios_admin (cedula, nombre, password, rol) VALUES (:cedula, :nombre, :pass, :rol)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':cedula'=>$cedula, ':nombre'=>$nombre, ':pass'=>$hash, ':rol'=>$rol]);
    }

    public function cambiarPassword($id, $nuevoPassword) {
        $hash = password_hash($nuevoPassword, PASSWORD_BCRYPT);
        $sql = "UPDATE usuarios_admin SET password = :pass WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':pass'=>$hash, ':id'=>$id]);
    }

    public function eliminar($id) {
        $sql = "DELETE FROM usuarios_admin WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id'=>$id]);
    }
}