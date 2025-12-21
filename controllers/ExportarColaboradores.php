<?php
// controllers/ExportarColaboradores.php

// 1. INICIAR BUFFER: Captura cualquier error o espacio en blanco previo
ob_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../models/ColaboradorDAO.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

// (Eliminamos el ob_clean() suelto que estaba aquí y causaba el error)

$dao = new ColaboradorDAO();
$colaboradores = $dao->listarTodos();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Colaboradores');

// 1. Encabezados
$headers = ['CÉDULA', 'NOMBRE COMPLETO', 'SEDE/ORIGEN', 'ESTADO ACTUAL', 'ÚLTIMA ACCIÓN'];
$sheet->fromArray([$headers], NULL, 'A1');

// Estilo para encabezado (Negrita, Fondo Azul, Texto Blanco)
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']]
];
$sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

// 2. Llenar datos
$fila = 2;
foreach ($colaboradores as $col) {
    $sheet->setCellValueExplicit('A' . $fila, $col['cedula'], DataType::TYPE_STRING);
    $sheet->setCellValue('B' . $fila, $col['nombre_completo']);
    $sheet->setCellValue('C' . $fila, $col['origen']);
    $sheet->setCellValue('D' . $fila, $col['estado_actual']);
    $sheet->setCellValue('E' . $fila, $col['ultima_accion']);
    $fila++;
}

// 3. Auto-ajustar columnas
foreach (range('A', 'E') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// 4. LIMPIEZA FINAL Y DESCARGA
// Borramos cualquier "basura" (warnings, espacios) acumulada en el buffer
ob_end_clean();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Reporte_Colaboradores_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;