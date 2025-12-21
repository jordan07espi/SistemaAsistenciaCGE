<?php
// controllers/ImportarColaboradores.php
require_once __DIR__ . '/../vendor/autoload.php'; // Cargar librería Excel
require_once __DIR__ . '/../models/ColaboradorDAO.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => '', 'insertados' => 0, 'errores' => 0];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['message' => 'Método no permitido']);
    exit;
}

if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['message' => 'Error al subir el archivo.']);
    exit;
}

$tmpName = $_FILES['excel_file']['tmp_name'];
$dao = new ColaboradorDAO();

try {
    // 1. Cargar el archivo Excel automáticamente (detecta xlsx o xls)
    $spreadsheet = IOFactory::load($tmpName);
    $sheet = $spreadsheet->getActiveSheet();
    $data = $sheet->toArray(); // Convertir todo a array simple

    // 2. Recorrer filas (Empezamos en índice 1 si la 0 es cabecera)
    foreach ($data as $index => $row) {
        // Saltar cabecera si detectamos palabras clave
        if ($index === 0 && (strtoupper($row[0] ?? '') === 'CEDULA' || strtoupper($row[0] ?? '') === 'NOMBRE')) {
            continue;
        }

        // Validar columnas mínimas (A=0, B=1, C=2)
        if (empty($row[0]) || empty($row[1])) {
            continue; // Fila vacía o incompleta
        }

        $cedula = trim((string)$row[0]);
        $nombre = trim((string)$row[1]);
        $origen = isset($row[2]) ? strtoupper(trim((string)$row[2])) : 'INSTITUTO';

        // Validar origen
        if ($origen !== 'CAPACITADORA') $origen = 'INSTITUTO';

        // Insertar
        if ($dao->crear($cedula, $nombre, $origen)) {
            $response['insertados']++;
        } else {
            $response['errores']++; // Posible duplicado
        }
    }

    $response['status'] = 'success';
    $response['message'] = 'Proceso completado.';

} catch (Exception $e) {
    $response['message'] = 'Error al leer Excel: ' . $e->getMessage();
}

echo json_encode($response);