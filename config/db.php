<?php
// config/db.php

// 1. CONFIGURAR ZONA HORARIA (IMPORTANTE PARA ECUADOR)
date_default_timezone_set('America/Guayaquil');

class Database {
    private static $host = 'db'; // O 'localhost' según tu entorno
    private static $db_name = 'cge_asistencia_db';
    private static $username = 'root'; // Ajusta según tus credenciales
    private static $password = 'Tics.2025*'; // Ajusta según tus credenciales
    public static $conn;

    public static function getConnection() {
        self::$conn = null;

        try {
            self::$conn = new PDO(
                "mysql:host=" . self::$host . ";dbname=" . self::$db_name,
                self::$username,
                self::$password
            );
            self::$conn->exec("set names utf8mb4");
            
            // Opcional: Asegurar que MySQL también use la misma zona horaria en esta sesión
            self::$conn->exec("SET time_zone = '-05:00'"); 
            
        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }

        return self::$conn;
    }
}
?>