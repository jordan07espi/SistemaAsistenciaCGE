<?php
// models/ColaboradorDAO.php
require_once __DIR__ . '/../config/db.php';

class ColaboradorDAO {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * NUEVO: Actualizar datos de un colaborador
     */
    public function actualizar($id, $cedula, $nombre, $origen) {
        try {
            $sql = "UPDATE colaboradores SET cedula = :cedula, nombre_completo = :nombre, origen = :origen WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':cedula' => $cedula,
                ':nombre' => $nombre,
                ':origen' => $origen,
                ':id' => $id
            ]);
        } catch (PDOException $e) {
            // Error por cédula duplicada
            if ($e->getCode() == 23000) {
                return false; 
            }
            throw $e;
        }
    }

    /**
     * Listar colaboradores con Paginación y Búsqueda
     * Usado en el panel principal con la barra de búsqueda AJAX
     */
    public function listarPaginado($limit, $offset, $busqueda = '') {
        $sql = "SELECT * FROM colaboradores";
        $params = [];

        // Filtro de búsqueda (Nombre o Cédula)
        if (!empty($busqueda)) {
            $sql .= " WHERE (nombre_completo LIKE :b1 OR cedula LIKE :b2)";
            $params[':b1'] = "%$busqueda%";
            $params[':b2'] = "%$busqueda%";
        }

        $sql .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        
        // Bind de parámetros de búsqueda
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        
        // Bind manual para enteros (limit/offset)
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Contar total de registros (Soporta filtro de búsqueda)
     * Necesario para calcular el número de páginas
     */
    public function contarTotal($busqueda = '') {
        $sql = "SELECT COUNT(*) as total FROM colaboradores";
        $params = [];

        if (!empty($busqueda)) {
            $sql .= " WHERE (nombre_completo LIKE :b1 OR cedula LIKE :b2)";
            $params[':b1'] = "%$busqueda%";
            $params[':b2'] = "%$busqueda%";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch()['total'];
    }

    /**
     * Busca un colaborador por su Cédula (Usado al Escanear QR)
     * IMPORTANTE: Solo devuelve colaboradores ACTIVOS (activo = 1)
     */
    public function obtenerPorCedula($cedula) {
        $sql = "SELECT * FROM colaboradores WHERE cedula = :cedula AND activo = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cedula' => $cedula]);
        return $stmt->fetch(); 
    }

    /**
     * Registra un nuevo colaborador manualmente
     */
    public function crear($cedula, $nombre, $origen) {
        try {
            // Se asume que 'activo' tiene valor por defecto 1 en la BD, 
            // pero lo incluimos explícitamente por seguridad si deseas.
            $sql = "INSERT INTO colaboradores (cedula, nombre_completo, origen, estado_actual, activo) 
                    VALUES (:cedula, :nombre, :origen, 'FUERA', 1)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':cedula' => $cedula,
                ':nombre' => $nombre,
                ':origen' => $origen
            ]);
        } catch (PDOException $e) {
            // Código de error para llave duplicada (Cédula ya existe)
            if ($e->getCode() == 23000) {
                return false; 
            }
            throw $e;
        }
    }

    /**
     * Actualizar estado de asistencia (DENTRO/FUERA)
     */
    public function actualizarEstado($id, $estado) {
        $sql = "UPDATE colaboradores SET estado_actual = :estado, ultima_accion = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':estado' => $estado, ':id' => $id]);
    }

    /**
     * NUEVO: Cambiar estado Activo/Inactivo (Deshabilitar usuario)
     */
    public function cambiarEstadoActivo($id, $nuevoEstado) {
        $sql = "UPDATE colaboradores SET activo = :estado WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':estado' => $nuevoEstado, ':id' => $id]);
    }

    /**
     * Listar TODOS los colaboradores activos 
     * (Usado para exportación Excel masiva y PDF masivo)
     */
    public function listarTodos() {
        $sql = "SELECT * FROM colaboradores WHERE activo = 1 ORDER BY nombre_completo ASC";
        return $this->db->query($sql)->fetchAll();
    }
}
?>