<?php
// controllers/ExportarAsistencias.php

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

session_start();

if (!isset($_SESSION['admin_id'])) {
    die("Acceso denegado");
}

require_once __DIR__ . '/../models/AsistenciaDAO.php';

// Configurar Zona Horaria
date_default_timezone_set('America/Guayaquil');

$inicio = $_GET['inicio'] ?? date('Y-m-d');
$fin    = $_GET['fin']    ?? date('Y-m-d');
$sede   = $_GET['sede']   ?? 'TODAS';

try {
    $dao = new AsistenciaDAO();
    // 1. Obtener datos ordenados ALFABÉTICAMENTE para cálculo correcto
    $raw_data = $dao->obtenerReporteParaExcel($inicio, $fin, $sede);

    // 2. Procesamiento (Consolidar Entradas y Salidas)
    $filas_consolidadas = [];
    $pendientes = []; 

    foreach ($raw_data as $row) {
        $id = $row['colaborador_id'];
        
        if ($row['tipo'] === 'ENTRADA') {
            if (isset($pendientes[$id])) {
                $filas_consolidadas[] = armarFila($pendientes[$id], null);
            }
            $pendientes[$id] = $row;
        } 
        elseif ($row['tipo'] === 'SALIDA') {
            if (isset($pendientes[$id])) {
                $filas_consolidadas[] = armarFila($pendientes[$id], $row);
                unset($pendientes[$id]); 
            } else {
                $filas_consolidadas[] = armarFila(null, $row);
            }
        }
    }
    
    foreach ($pendientes as $p) {
        $filas_consolidadas[] = armarFila($p, null);
    }

    // =================================================================
    // 3. NUEVO: REORDENAMIENTO CRONOLÓGICO (Llegada Primero -> Último)
    // =================================================================
    usort($filas_consolidadas, function($a, $b) {
        // Convertimos fechas a timestamp para comparar
        $t1 = strtotime(str_replace('/', '-', $a['fecha']) . ' ' . ($a['hora_in'] !== '--:--' ? $a['hora_in'] : $a['hora_out']));
        $t2 = strtotime(str_replace('/', '-', $b['fecha']) . ' ' . ($b['hora_in'] !== '--:--' ? $b['hora_in'] : $b['hora_out']));
        return $t1 - $t2;
    });

    // --- CREAR DOCUMENTO EXCEL ---
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Reporte Cronológico');

    // Encabezados
    $headers = [
        'FECHA', 'CÉDULA', 'NOMBRE COMPLETO', 'ORIGEN', 'SEDE', 
        'HORA ENTRADA', 'HORA SALIDA', 'TIEMPO TRABAJADO', 'MODO'
    ];
    $sheet->fromArray($headers, NULL, 'A1');

    // Estilos
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['argb' => Color::COLOR_WHITE]],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0056b3']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];
    $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

    // Llenar Datos
    $rowIdx = 2;
    if (empty($filas_consolidadas)) {
        $sheet->setCellValue('A2', 'No hay registros para generar reporte.');
        $sheet->mergeCells('A2:I2');
    } else {
        foreach ($filas_consolidadas as $dato) {
            $sheet->setCellValue('A' . $rowIdx, $dato['fecha']);
            $sheet->setCellValueExplicit('B' . $rowIdx, $dato['cedula'], DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $rowIdx, $dato['nombre']);
            $sheet->setCellValue('D' . $rowIdx, $dato['origen']);
            $sheet->setCellValue('E' . $rowIdx, $dato['sede']);
            $sheet->setCellValue('F' . $rowIdx, $dato['hora_in']);
            $sheet->setCellValue('G' . $rowIdx, $dato['hora_out']);
            $sheet->setCellValue('H' . $rowIdx, $dato['tiempo']);
            $sheet->setCellValue('I' . $rowIdx, $dato['modo']);

            if ($dato['tiempo'] === '--:--' || strpos($dato['tiempo'], 'Sin') !== false) {
                $sheet->getStyle('H' . $rowIdx)->getFont()->getColor()->setARGB(Color::COLOR_RED);
            }

            $rowIdx++;
        }
    }

    // Auto-ajuste
    foreach (range('A', 'I') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Descarga
    $filename = "Reporte_Cronologico_" . date('Ymd_His') . ".xlsx";
    if (ob_get_length()) ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    if (ob_get_length()) ob_end_clean();
    echo "Error: " . $e->getMessage();
}

// Función auxiliar (se mantiene igual)
function armarFila($entrada, $salida) {
    $base = $entrada ? $entrada : $salida;
    
    // Detectamos la fecha real de la operación
    $fechaHora = $base['fecha_hora'];
    
    $fila = [
        'fecha'    => date('d/m/Y', strtotime($fechaHora)),
        'cedula'   => $base['cedula'],
        'nombre'   => $base['nombre_completo'],
        'origen'   => $base['colaborador_origen'],
        'sede'     => $base['sede_registro'],
        'modo'     => $base['modo_registro'],
        'hora_in'  => '--:--',
        'hora_out' => '--:--',
        'tiempo'   => '--:--'
    ];

    if ($entrada && $salida) {
        $dt1 = new DateTime($entrada['fecha_hora']);
        $dt2 = new DateTime($salida['fecha_hora']);
        $interval = $dt1->diff($dt2);
        
        $fila['hora_in']  = $dt1->format('H:i:s');
        $fila['hora_out'] = $dt2->format('H:i:s');
        $fila['tiempo']   = $interval->format('%H:%I:%S'); 

    } elseif ($entrada && !$salida) {
        $fila['hora_in']  = date('H:i:s', strtotime($entrada['fecha_hora']));
        $fila['tiempo']   = 'Sin Salida';

    } elseif (!$entrada && $salida) {
        $fila['hora_out'] = date('H:i:s', strtotime($salida['fecha_hora']));
        $fila['tiempo']   = 'Sin Entrada';
    }

    return $fila;
}
?>