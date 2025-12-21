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
     * * @param int $colaboradorId ID del colaborador
     * @param string $tipo 'ENTRADA' o 'SALIDA'
     * @param string $sede 'INSTITUTO' o 'CAPACITADORA'
     * @param string $modo 'QR_AUTO', 'QR_MANUAL' o 'ADMIN_MANUAL'
     * @param string|null $observacion Texto opcional
     * @return bool True si se guardó correctamente
     */
    public function registrar($colaboradorId, $tipo, $sede, $modo = 'QR_AUTO', $observacion = null) {
        // Usamos NOW() para que la fecha sea exactamente la del servidor de base de datos
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
     * Obtiene el historial de hoy (útil para el Dashboard del Admin en tiempo real).
     * Incluye el nombre del colaborador haciendo JOIN.
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
     * Obtiene el último registro de un colaborador (útil para validaciones extra o correcciones)
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
     * Modificado para soportar paginación opcional
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

        // Si mandamos límites, aplicamos paginación
        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Cuenta el total de registros con los mismos filtros (para la paginación)
     */
    public function contarReporte($inicio, $fin, $sede = '') {
        $sql = "SELECT COUNT(*) as total
                FROM asistencias a
                WHERE DATE(a.fecha_hora) BETWEEN :inicio AND :fin";
        
        $params = [':inicio' => $inicio, ':fin' => $fin];

        if (!empty($sede) && $sede !== 'TODAS') {
            $sql .= " AND a.sede_registro = :sede";
            $params[':sede'] = $sede;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch()['total'];
    }

    /**
     * Obtiene el último registro de asistencia de un colaborador
     * (Usado para la lógica anti-rebote)
     */
    public function obtenerUltimaAsistencia($colaboradorId) {
        $sql = "SELECT * FROM asistencias WHERE colaborador_id = :id ORDER BY fecha_hora DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $colaboradorId]);
        return $stmt->fetch();
    }

    /**
     * DATOS PARA GRÁFICOS (Lo usaremos en el siguiente paso)
     */
    public function obtenerEstadisticasHoy() {
        // Contar presentes (DENTRO) vs ausentes (FUERA)
        $sql = "SELECT estado_actual, COUNT(*) as total FROM colaboradores GROUP BY estado_actual";
        return $this->db->query($sql)->fetchAll();
    }

    public function obtenerAsistenciasSemana() {
        // Contar entradas de los últimos 7 días
        $sql = "SELECT DATE(fecha_hora) as fecha, COUNT(*) as total 
                FROM asistencias 
                WHERE tipo = 'ENTRADA' 
                AND fecha_hora >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY DATE(fecha_hora) 
                ORDER BY fecha ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * SSE: Obtener el ID más alto actual (punto de partida)
     */
    public function obtenerUltimoId() {
        $sql = "SELECT MAX(id) as max_id FROM asistencias";
        $row = $this->db->query($sql)->fetch();
        return $row['max_id'] ?? 0;
    }

    /**
     * SSE: Buscar registros nuevos posteriores a un ID dado
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
}