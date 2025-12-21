<?php
// controllers/GenerarCredencial.php

// 1. LIMPIAR BUFFER (Vital para no corromper el PDF)
ob_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../models/ColaboradorDAO.php';
// Aseguramos que cargue la conexión DB para consultas manuales
require_once __DIR__ . '/../config/db.php'; 

use setasign\Fpdi\Fpdi;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

// ==========================================
// CONFIGURACIÓN DE LA TARJETA
// ==========================================
$anchoCard = 85; // mm
$altoCard = 55;  // mm
// Ruta de la NUEVA imagen JPG
$imgPlantilla = __DIR__ . '/../public/assets/templates/plantilla_credencial.jpeg';

// Validar que exista la imagen
if (!file_exists($imgPlantilla)) {
    ob_end_clean();
    die("Error: No se encuentra la imagen 'plantilla_credencial.jpeg' en public/assets/templates/");
}

// ==========================================
// OBTENER DATOS
// ==========================================
$id = $_GET['id'] ?? null;
$modo = $_GET['modo'] ?? 'individual'; // 'individual' o 'masivo'

$dao = new ColaboradorDAO();
$colaboradores = [];

if ($modo === 'masivo') {
    $colaboradores = $dao->listarTodos();
} elseif ($id) {
    // Consulta manual para obtener un solo colaborador por ID
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT * FROM colaboradores WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $col = $stmt->fetch();
    if ($col) $colaboradores[] = $col;
}

if (empty($colaboradores)) {
    ob_end_clean();
    die("No hay colaboradores para generar credenciales.");
}

// ==========================================
// FUNCIONES AUXILIARES
// ==========================================

// Generar QR temporal en disco
function generarQR($contenido) {
    $result = Builder::create()
        ->writer(new PngWriter())
        ->data($contenido)
        ->encoding(new Encoding('UTF-8'))
        ->errorCorrectionLevel(ErrorCorrectionLevel::High)
        ->size(400)
        ->margin(0)
        ->build();
    
    $tempFile = sys_get_temp_dir() . '/qr_' . md5($contenido . uniqid()) . '.png';
    $result->saveToFile($tempFile);
    return $tempFile;
}

/**
 * Dibuja una credencial completa en las coordenadas ($x, $y)
 */
function dibujarCredencial($pdf, $x, $y, $w, $h, $col, $bgImage) {
    // 1. Imagen de Fondo
    $pdf->Image($bgImage, $x, $y, $w, $h);

    // 2. Definir coordenadas relativas (dentro de la tarjeta de 85x55)
    // QR alineado a la derecha
    $qrSize = 24; // Tamaño del QR en mm
    $marginRight = 4;
    $qrX = $x + ($w - $qrSize - $marginRight); // X absoluto
    $qrY = $y + (($h - $qrSize) / 2) + 2;      // Y absoluto (Centrado verticalmente + ajuste visual)

    // 3. Generar y colocar QR
    $qrPath = generarQR($col['cedula']);
    $pdf->Image($qrPath, $qrX, $qrY, $qrSize, $qrSize);
    @unlink($qrPath); // Borrar temp

    // 4. Textos (Alineados a la izquierda)
    $pdf->SetTextColor(0, 0, 0); // Negro
    $marginLeft = 5;
    $textX = $x + $marginLeft;
    $textAreaWidth = ($qrX - $textX) - 2; // Espacio disponible hasta el QR
    
    // -- Nombre --
    // Ajustar fuente si el nombre es muy largo
    $len = strlen($col['nombre_completo']);
    $fontSize = ($len > 25) ? 10 : 12; // Si es largo baja a 10pt, si no 12pt
    
    $pdf->SetFont('Arial', 'B', $fontSize);
    
    // Calcular Y inicial del texto (aprox al 35% de la altura)
    $cursorY = $y + ($h * 0.35); 
    
    $pdf->SetXY($textX, $cursorY);
    $nombre = mb_convert_encoding($col['nombre_completo'], 'ISO-8859-1', 'UTF-8');
    $pdf->MultiCell($textAreaWidth, 5, $nombre, 0, 'L');

    // -- Cédula --
    $pdf->SetFont('Arial', '', 10);
    // Un pequeño salto después del nombre
    $pdf->SetXY($textX, $pdf->GetY() + 1); 
    $pdf->Cell($textAreaWidth, 5, 'CI: ' . $col['cedula'], 0, 1, 'L');

    // -- Origen / Cargo --
    $pdf->SetTextColor(80, 80, 80); // Gris oscuro
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->SetX($textX); 
    $origen = mb_convert_encoding($col['origen'], 'ISO-8859-1', 'UTF-8');
    $pdf->Cell($textAreaWidth, 4, $origen, 0, 1, 'L');
}

// ==========================================
// GENERACIÓN DEL PDF
// ==========================================

if ($modo === 'masivo') {
    // --- MODO A4 (8 por página) ---
    $pdf = new Fpdi('P', 'mm', 'A4'); // Vertical
    $pdf->SetAutoPageBreak(false);
    
    // ==========================================
    // AJUSTE DE MÁRGENES Y SEPARACIÓN (GAP)
    // ==========================================
    $gap = 4; // 4 milímetros de separación entre tarjetas
    
    // CÁLCULOS AUTOMÁTICOS PARA CENTRAR EN A4 (210x297mm)
    // Ancho útil: (85 * 2) + 4 = 174mm
    // Margen X: (210 - 174) / 2 = 18mm
    $startX = 18;
    
    // Alto útil (4 filas): (55 * 4) + (4 * 3 espacios) = 220 + 12 = 232mm
    // Margen Y: (297 - 232) / 2 = 32.5mm
    $startY = 32.5;

    $i = 0;
    foreach ($colaboradores as $col) {
        // Nueva página cada 8 credenciales
        if ($i % 8 === 0) {
            $pdf->AddPage();
        }

        // Calcular posición en la grilla (0 a 7)
        $posEnPagina = $i % 8;
        $fila = floor($posEnPagina / 2); // 0, 1, 2, 3
        $columna = $posEnPagina % 2;     // 0, 1

        $posX = $startX + ($columna * ($anchoCard + $gap));
        $posY = $startY + ($fila * ($altoCard + $gap));

        dibujarCredencial($pdf, $posX, $posY, $anchoCard, $altoCard, $col, $imgPlantilla);

        $i++;
    }

} else {
    // --- MODO INDIVIDUAL (Tamaño Tarjeta) ---
    // Crea un PDF con el tamaño exacto de la credencial
    $pdf = new Fpdi('L', 'mm', [$anchoCard, $altoCard]);
    $pdf->SetAutoPageBreak(false);

    foreach ($colaboradores as $col) {
        $pdf->AddPage();
        dibujarCredencial($pdf, 0, 0, $anchoCard, $altoCard, $col, $imgPlantilla);
    }
}

// ==========================================
// SALIDA
// ==========================================
ob_end_clean(); // Limpiar cualquier salida previa

$nombreArchivo = ($modo === 'masivo') ? 'Credenciales_Masivas.pdf' : 'Credencial_' . ($colaboradores[0]['cedula'] ?? 'temp') . '.pdf';

$pdf->Output('I', $nombreArchivo);
?>