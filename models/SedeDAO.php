<?php
// models/SedeDAO.php
require_once __DIR__ . '/../config/db.php';

class SedeDAO {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function listarTodas() {
        $sql = "SELECT * FROM sedes ORDER BY nombre ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function crear($nombre, $color) {
        $sql = "INSERT INTO sedes (nombre, color) VALUES (:nombre, :color)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':nombre' => $nombre, ':color' => $color]);
    }

    public function eliminar($id) {
        $sql = "DELETE FROM sedes WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}