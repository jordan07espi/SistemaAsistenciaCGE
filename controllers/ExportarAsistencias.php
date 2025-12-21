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

// Configurar Zona Horaria para cálculos correctos
date_default_timezone_set('America/Guayaquil');

$inicio = $_GET['inicio'] ?? date('Y-m-d');
$fin    = $_GET['fin']    ?? date('Y-m-d');
$sede   = $_GET['sede']   ?? 'TODAS';

try {
    $dao = new AsistenciaDAO();
    // Usamos el NUEVO método ordenado
    $raw_data = $dao->obtenerReporteParaExcel($inicio, $fin, $sede);

    // --- PROCESAMIENTO DE DATOS (Consolidar Entradas y Salidas) ---
    $filas_consolidadas = [];
    $pendientes = []; // Array temporal para guardar entradas abiertas: [colab_id => data_entrada]

    foreach ($raw_data as $row) {
        $id = $row['colaborador_id'];
        
        if ($row['tipo'] === 'ENTRADA') {
            // Si ya tenía una entrada previa sin cerrar (olvidó marcar salida), la guardamos incompleta
            if (isset($pendientes[$id])) {
                $filas_consolidadas[] = armarFila($pendientes[$id], null);
            }
            // Registrar nueva entrada pendiente
            $pendientes[$id] = $row;
        } 
        elseif ($row['tipo'] === 'SALIDA') {
            if (isset($pendientes[$id])) {
                // ¡MATCH! Tenemos entrada y salida. Cerramos el ciclo.
                $filas_consolidadas[] = armarFila($pendientes[$id], $row);
                unset($pendientes[$id]); // Limpiar pendiente
            } else {
                // Salida huérfana (sin entrada registrada en este rango)
                $filas_consolidadas[] = armarFila(null, $row);
            }
        }
    }
    
    // Procesar los que quedaron pendientes al final (Entradas sin Salida)
    foreach ($pendientes as $p) {
        $filas_consolidadas[] = armarFila($p, null);
    }

    // --- CREAR DOCUMENTO EXCEL ---
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Reporte Detallado');

    // Encabezados Actualizados
    $headers = [
        'FECHA', 
        'CÉDULA', 
        'NOMBRE COMPLETO', 
        'ORIGEN', 
        'SEDE', 
        'HORA ENTRADA', 
        'HORA SALIDA', 
        'TIEMPO TRABAJADO', 
        'MODO'
    ];
    $sheet->fromArray($headers, NULL, 'A1');

    // Estilo Encabezados
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

            // Alerta visual si falta marca (Incompleto)
            if ($dato['tiempo'] === '--:--' || $dato['tiempo'] === 'Sin Salida' || $dato['tiempo'] === 'Sin Entrada') {
                $sheet->getStyle('H' . $rowIdx)->getFont()->getColor()->setARGB(Color::COLOR_RED);
            }

            $rowIdx++;
        }
    }

    // Auto-ajustar columnas
    foreach (range('A', 'I') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Descarga
    $filename = "Reporte_Horas_" . date('Ymd_His') . ".xlsx";
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

// --- FUNCIÓN AUXILIAR PARA ARMAR LA FILA ---
function armarFila($entrada, $salida) {
    // Datos base (preferimos datos de Entrada, si no existe, de Salida)
    $base = $entrada ? $entrada : $salida;
    
    $fila = [
        'fecha'    => date('d/m/Y', strtotime($base['fecha_hora'])),
        'cedula'   => $base['cedula'],
        'nombre'   => $base['nombre_completo'],
        'origen'   => $base['colaborador_origen'],
        'sede'     => $base['sede_registro'],
        'modo'     => $base['modo_registro'],
        'hora_in'  => '',
        'hora_out' => '',
        'tiempo'   => ''
    ];

    if ($entrada && $salida) {
        // CASO IDEAL: Entrada y Salida completas
        $dt1 = new DateTime($entrada['fecha_hora']);
        $dt2 = new DateTime($salida['fecha_hora']);
        $interval = $dt1->diff($dt2);
        
        $fila['hora_in']  = $dt1->format('H:i:s');
        $fila['hora_out'] = $dt2->format('H:i:s');
        // Formato H horas, I minutos (ej: 08:30)
        $fila['tiempo']   = $interval->format('%H:%I:%S'); 

    } elseif ($entrada && !$salida) {
        // CASO: Solo Entrada (Trabajando o olvidó salir)
        $fila['hora_in']  = date('H:i:s', strtotime($entrada['fecha_hora']));
        $fila['hora_out'] = '--:--';
        $fila['tiempo']   = 'Sin Salida';

    } elseif (!$entrada && $salida) {
        // CASO: Solo Salida (Olvidó entrar o turno cruzado fuera de rango)
        $fila['hora_in']  = '--:--';
        $fila['hora_out'] = date('H:i:s', strtotime($salida['fecha_hora']));
        $fila['tiempo']   = 'Sin Entrada';
    }

    return $fila;
}