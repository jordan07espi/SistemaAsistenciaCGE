<?php
// app/config/db.php

class Database {
    private static $instance = null;
    private $conn;

    // Credenciales extraídas de tu docker-compose.yml
    private $host = 'db'; 
    private $user = 'root';
    private $pass = 'Tics.2025*';
    private $dbname = 'cge_asistencia_db';

    private function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->user, $this->pass);
            
            // Configuración de errores y modo de fetch por defecto (Array Asociativo)
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            // En producción, esto debería ir a un log, no a pantalla
            die("Error de conexión a BD: " . $e->getMessage());
        }
    }

    public static function getConnection() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance->conn;
    }
}