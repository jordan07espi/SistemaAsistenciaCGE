<?php
// controllers/ExportarAsistencias.php

// Cargar librerías de Composer
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

session_start();

// 1. SEGURIDAD
if (!isset($_SESSION['admin_id'])) {
    die("Acceso denegado");
}

require_once __DIR__ . '/../models/AsistenciaDAO.php';

// 2. OBTENER DATOS
$inicio = $_GET['inicio'] ?? date('Y-m-d');
$fin    = $_GET['fin']    ?? date('Y-m-d');
$sede   = $_GET['sede']   ?? 'TODAS';

try {
    $dao = new AsistenciaDAO();
    $datos = $dao->filtrarReporte($inicio, $fin, $sede, null, null);

    // 3. CREAR DOCUMENTO EXCEL
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Reporte Asistencias');

    // --- ENCABEZADOS ---
    $headers = ['FECHA', 'HORA', 'CÉDULA', 'NOMBRE COMPLETO', 'ORIGEN', 'TIPO', 'SEDE REGISTRO', 'MODO'];
    $sheet->fromArray($headers, NULL, 'A1');

    // Estilo para Encabezados (Azul Institucional + Negrita + Texto Blanco)
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['argb' => Color::COLOR_WHITE]],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0056b3']], // Azul
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];
    $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

    // --- LLENAR DATOS ---
    $row = 2;
    if (empty($datos)) {
        $sheet->setCellValue('A2', 'No hay registros en este rango de fechas.');
        $sheet->mergeCells('A2:H2');
    } else {
        foreach ($datos as $d) {
            $fecha = date('d/m/Y', strtotime($d['fecha_hora']));
            $hora = date('H:i:s', strtotime($d['fecha_hora']));

            $sheet->setCellValue('A' . $row, $fecha);
            $sheet->setCellValue('B' . $row, $hora);
            
            // Cédula como TEXTO explícito para no perder ceros a la izquierda
            $sheet->setCellValueExplicit('C' . $row, $d['cedula'], DataType::TYPE_STRING);
            
            $sheet->setCellValue('D' . $row, $d['nombre_completo']);
            $sheet->setCellValue('E' . $row, $d['colaborador_origen']);
            $sheet->setCellValue('F' . $row, $d['tipo']);
            $sheet->setCellValue('G' . $row, $d['sede_registro']);
            $sheet->setCellValue('H' . $row, $d['modo_registro']);

            // --- ESTILOS CONDICIONALES (Colores para Entrada/Salida) ---
            if ($d['tipo'] === 'ENTRADA') {
                // Verde Claro fondo, Verde Oscuro texto
                $colorStyle = [
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD4EDDA']],
                    'font' => ['color' => ['argb' => 'FF155724'], 'bold' => true],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
                ];
            } else {
                // Rojo Claro fondo, Rojo Oscuro texto
                $colorStyle = [
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF8D7DA']],
                    'font' => ['color' => ['argb' => 'FF721C24'], 'bold' => true],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
                ];
            }
            $sheet->getStyle('F' . $row)->applyFromArray($colorStyle);

            $row++;
        }
    }

    // --- AJUSTE FINAL ---
    // Auto-ajustar ancho de columnas
    foreach (range('A', 'H') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Nombre del archivo
    $filename = "Reporte_Asistencia_" . date('Ymd_His') . ".xlsx";

    // 4. LIMPIAR BUFFER (Vital para evitar archivos corruptos)
    if (ob_get_length()) ob_end_clean();

    // 5. CABECERAS DE DESCARGA
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    // 6. GENERAR Y ENVIAR
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    if (ob_get_length()) ob_end_clean();
    echo "Error: " . $e->getMessage();
}