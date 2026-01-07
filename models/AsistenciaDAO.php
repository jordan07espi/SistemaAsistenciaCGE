<?php
// models/AsistenciaDAO.php
require_once __DIR__ . '/../config/db.php';

class AsistenciaDAO {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Registra una nueva asistencia en la base de datos.
     */
    public function registrar($colaboradorId, $tipo, $sede, $modo = 'QR_AUTO', $observacion = null) {
        $sql = "INSERT INTO asistencias (colaborador_id, tipo, sede_registro, modo_registro, observacion, fecha_hora) 
                VALUES (:id, :tipo, :sede, :modo, :obs, NOW())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id'   => $colaboradorId,
            ':tipo' => $tipo,
            ':sede' => $sede,
            ':modo' => $modo,
            ':obs'  => $observacion
        ]);
    }

    /**
     * Obtiene el historial de hoy (útil para el Dashboard en tiempo real).
     */
    public function obtenerHistorialHoy() {
        $sql = "SELECT a.*, c.nombre_completo, c.cedula, c.origen 
                FROM asistencias a
                INNER JOIN colaboradores c ON a.colaborador_id = c.id
                WHERE DATE(a.fecha_hora) = CURDATE()
                ORDER BY a.fecha_hora DESC";
        
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Obtiene el último registro de un colaborador.
     */
    public function obtenerUltimoRegistro($colaboradorId) {
        $sql = "SELECT * FROM asistencias 
                WHERE colaborador_id = :id 
                ORDER BY fecha_hora DESC 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $colaboradorId]);
        return $stmt->fetch();
    }

    /**
     * Filtra el reporte de asistencias con paginación opcional.
     * Mantiene la estructura original de la base de datos.
     */
    public function filtrarReporte($inicio, $fin, $sede = '', $limit = null, $offset = null) {
        $sql = "SELECT a.*, c.nombre_completo, c.cedula, c.origen as colaborador_origen
                FROM asistencias a
                INNER JOIN colaboradores c ON a.colaborador_id = c.id
                WHERE DATE(a.fecha_hora) BETWEEN :inicio AND :fin";
        
        $params = [':inicio' => $inicio, ':fin' => $fin];

        if (!empty($sede) && $sede !== 'TODAS') {
            $sql .= " AND a.sede_registro = :sede";
            $params[':sede'] = $sede;
        }

        $sql .= " ORDER BY a.fecha_hora DESC";

        // Aplicar Paginación si se envían los límites
        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * CORRECCIÓN IMPORTANTE: 
     * Cuenta el total de registros usando la misma lógica (INNER JOIN) que el reporte.
     * Esto asegura que la paginación sea exacta.
     */
    public function contarReporte($inicio, $fin, $sede = '') {
        $sql = "SELECT COUNT(*) as total
                FROM asistencias a
                INNER JOIN colaboradores c ON a.colaborador_id = c.id
                WHERE DATE(a.fecha_hora) BETWEEN :inicio AND :fin";
        
        $params = [':inicio' => $inicio, ':fin' => $fin];

        if (!empty($sede) && $sede !== 'TODAS') {
            $sql .= " AND a.sede_registro = :sede";
            $params[':sede'] = $sede;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $resultado = $stmt->fetch();
        return $resultado ? $resultado['total'] : 0;
    }

    /**
     * Obtiene estadísticas para los gráficos (Pastel: Dentro/Fuera).
     */
    public function obtenerEstadisticasHoy() {
        $sql = "SELECT estado_actual, COUNT(*) as total FROM colaboradores GROUP BY estado_actual";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Obtiene estadísticas para los gráficos (Barras: Últimos 7 días).
     */
    public function obtenerAsistenciasSemana() {
        $sql = "SELECT DATE(fecha_hora) as fecha, COUNT(*) as total 
                FROM asistencias 
                WHERE tipo = 'ENTRADA' 
                AND fecha_hora >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY DATE(fecha_hora) 
                ORDER BY fecha ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * SSE: Obtener el ID más alto actual.
     */
    public function obtenerUltimoId() {
        $sql = "SELECT MAX(id) as max_id FROM asistencias";
        $row = $this->db->query($sql)->fetch();
        return $row['max_id'] ?? 0;
    }

    /**
     * SSE: Buscar registros nuevos para actualizaciones en tiempo real.
     */
    public function obtenerNuevosRegistros($ultimoIdConocido) {
        $sql = "SELECT a.*, c.nombre_completo, c.cedula, c.origen 
                FROM asistencias a
                INNER JOIN colaboradores c ON a.colaborador_id = c.id
                WHERE a.id > :id
                ORDER BY a.id ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $ultimoIdConocido]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene datos para el Excel Consolidado.
     * Ordena por nombre y fecha para poder calcular horas trabajadas.
     */
    public function obtenerReporteParaExcel($inicio, $fin, $sede = '') {
        $sql = "SELECT a.*, c.nombre_completo, c.cedula, c.origen as colaborador_origen
                FROM asistencias a
                INNER JOIN colaboradores c ON a.colaborador_id = c.id
                WHERE DATE(a.fecha_hora) BETWEEN :inicio AND :fin";
        
        $params = [':inicio' => $inicio, ':fin' => $fin];

        if (!empty($sede) && $sede !== 'TODAS') {
            $sql .= " AND a.sede_registro = :sede";
            $params[':sede'] = $sede;
        }

        // Orden vital para el algoritmo de consolidación (Entrada-Salida)
        $sql .= " ORDER BY c.nombre_completo ASC, a.fecha_hora ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
?>