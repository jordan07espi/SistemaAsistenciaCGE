<?php
// controllers/ObtenerEstadisticas.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

// Zona horaria
date_default_timezone_set('America/Guayaquil');

$sede = $_GET['sede'] ?? '';
$db = Database::getConnection();

try {
    $response = [
        'resumen' => [],
        'asistencia_semanal' => [],
        'distribucion_estado' => []
    ];

    // 1. RESUMEN HOY (Tarjetas superiores)
    // Filtro base para hoy
    $sqlBase = "SELECT COUNT(*) FROM asistencias a 
                INNER JOIN colaboradores c ON a.colaborador_id = c.id 
                WHERE DATE(a.fecha_hora) = CURDATE()";
    
    $params = [];
    if (!empty($sede) && $sede !== 'TODAS') {
        $sqlBase .= " AND a.sede_registro = :sede";
        $params[':sede'] = $sede;
    }

    // Total Asistencias Hoy
    $stmt = $db->prepare($sqlBase);
    $stmt->execute($params);
    $totalHoy = $stmt->fetchColumn();

    // Colaboradores Puntuales (Ejemplo: llegadas antes de las 8:15 AM)
    $sqlPuntuales = $sqlBase . " AND TIME(a.fecha_hora) <= '08:15:00' AND a.tipo = 'ENTRADA'";
    $stmt = $db->prepare($sqlPuntuales);
    $stmt->execute($params);
    $puntuales = $stmt->fetchColumn();

    // Atrasos (Después de las 8:15 AM)
    $sqlAtrasos = $sqlBase . " AND TIME(a.fecha_hora) > '08:15:00' AND a.tipo = 'ENTRADA'";
    $stmt = $db->prepare($sqlAtrasos);
    $stmt->execute($params);
    $atrasos = $stmt->fetchColumn();

    $response['resumen'] = [
        'total' => $totalHoy,
        'puntuales' => $puntuales,
        'atrasos' => $atrasos
    ];

    // 2. GRÁFICO 1: ASISTENCIA ÚLTIMOS 7 DÍAS (Barras)
    // Muestra cuántas ENTRADAS hubo por día
    $sqlSemana = "SELECT DATE(a.fecha_hora) as fecha, COUNT(*) as total 
                  FROM asistencias a 
                  WHERE a.tipo = 'ENTRADA' 
                  AND a.fecha_hora >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) ";
    
    if (!empty($sede) && $sede !== 'TODAS') {
        $sqlSemana .= " AND a.sede_registro = :sede ";
    }
    
    $sqlSemana .= " GROUP BY DATE(a.fecha_hora) ORDER BY fecha ASC";

    $stmt = $db->prepare($sqlSemana);
    $stmt->execute($params); // Reusamos params si tiene sede
    $response['asistencia_semanal'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. GRÁFICO 2: DISTRIBUCIÓN ACTUAL (Pastel)
    // Cuántos están DENTRO vs FUERA (Estado actual de colaboradores)
    // NOTA: El estado 'DENTRO/FUERA' es global del colaborador, pero podemos intentar
    // filtrar por la sede de su ÚLTIMO registro si quisiéramos ser muy estrictos.
    // Para simplificar y velocidad, si filtramos por sede, contaremos registros de HOY en esa sede.
    
    if (!empty($sede) && $sede !== 'TODAS') {
        // Si hay sede, contamos asistencias de hoy agrupadas por tipo (Entrada/Salida) en esa sede
        $sqlPie = "SELECT tipo as estado, COUNT(*) as total 
                   FROM asistencias a 
                   WHERE DATE(a.fecha_hora) = CURDATE() 
                   AND a.sede_registro = :sede 
                   GROUP BY tipo";
        $stmt = $db->prepare($sqlPie);
        $stmt->execute([':sede' => $sede]);
    } else {
        // Global: Usamos el estado actual de la tabla colaboradores
        $sqlPie = "SELECT estado_actual as estado, COUNT(*) as total 
                   FROM colaboradores 
                   WHERE activo = 1 
                   GROUP BY estado_actual";
        $stmt = $db->query($sqlPie);
    }
    
    $response['distribucion_estado'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>