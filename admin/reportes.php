<?php
require_once('../fpdf/fpdf.php');
require_once('../includes/config.php'); // Asegúrate de que config.php define $conn

// Verifica si hay parámetros de fecha
$fechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
$fechaFin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');

// Crear clase personalizada de PDF
class PDF extends FPDF
{
    function Header()
    {
        // Logo (ajusta la ruta si es necesario. Si 'img/logo.png' está en la raíz de tu sistema-pedidos)
        $this->Image('../img/logo.png', 10, 8, 25);
        // Título
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(80);
        $this->Cell(100, 10, utf8_decode('Reporte de Ventas y Productos Más Vendidos'), 0, 1, 'C');
        $this->Ln(5);
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 10, 'Fecha de generación: ' . date('d/m/Y'), 0, 1, 'R');
        $this->Ln(3);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function FancyTable($header, $data, $w)
    {
        // Colores, ancho y fuente
        $this->SetFillColor(41, 128, 185);
        $this->SetTextColor(255);
        $this->SetDrawColor(41, 128, 185);
        $this->SetLineWidth(.3);
        $this->SetFont('Arial', 'B', 11);

        // Cabecera
        for ($i = 0; $i < count($header); $i++)
            $this->Cell($w[$i], 8, utf8_decode($header[$i]), 1, 0, 'C', true);
        $this->Ln();

        // Restaurar colores
        $this->SetFillColor(245, 245, 245);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 10);

        // Datos
        $fill = false;
        foreach ($data as $row) {
            for ($i = 0; $i < count($w); $i++)
                $this->Cell($w[$i], 8, utf8_decode($row[$i]), 1, 0, 'C', $fill);
            $this->Ln();
            $fill = !$fill;
        }
        $this->Ln(5);
    }
}

// Crear PDF
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 12);

// -----------------------------
// REPORTE DE VENTAS DIARIAS
// -----------------------------
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, utf8_decode('Ventas Diarias Detalladas'), 0, 1, 'L');
$pdf->Ln(2);


$query = $conn->prepare("SELECT p.id, p.fecha_pedido, c.nombre AS cliente, p.total
                             FROM pedidos p
                             INNER JOIN clientes c ON p.cliente_id = c.id
                             WHERE p.fecha_pedido BETWEEN ? AND ?
                             ORDER BY p.fecha_pedido ASC");

$query->bind_param("ss", $fechaInicio, $fechaFin);
$query->execute();
$result = $query->get_result();

$data = [];
$totalGeneral = 0;
while ($row = $result->fetch_assoc()) {
    $data[] = [
        $row['id'],
        date('d/m/Y', strtotime($row['fecha_pedido'])),
        $row['cliente'],
        '$' . number_format($row['total'], 2)
    ];
    $totalGeneral += $row['total'];
}

if (count($data) > 0) {
    $header = ['ID Pedido', 'Fecha', 'Cliente', 'Total'];
    $w = [30, 40, 80, 40];
    $pdf->FancyTable($header, $data, $w);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(150, 10, 'TOTAL GENERAL:', 1, 0, 'R');
    $pdf->Cell(40, 10, '$' . number_format($totalGeneral, 2), 1, 1, 'C');
} else {
    $pdf->Cell(0, 10, utf8_decode('No se encontraron ventas en el rango seleccionado.'), 0, 1, 'C');
}

$pdf->Ln(10);

// -----------------------------
// REPORTE DE PRODUCTOS MÁS VENDIDOS
// -----------------------------
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, utf8_decode('Productos Más Vendidos'), 0, 1, 'L');
$pdf->Ln(2);

// === INICIO DE CAMBIO PARA LA SEGUNDA CONSULTA ===
// Aquí se usa 'pedido_detalles' en lugar de 'detalle_pedido'
// Y se calcula SUM(dp.cantidad * dp.precio) para el total recaudado
$query = $conn->prepare("SELECT pr.nombre, SUM(dp.cantidad) AS cantidad_vendida,
                                    SUM(dp.cantidad * dp.precio) AS total_recaudado
                             FROM pedido_detalles dp
                             INNER JOIN productos pr ON dp.producto_id = pr.id
                             INNER JOIN pedidos p ON dp.pedido_id = p.id
                             WHERE p.fecha_pedido BETWEEN ? AND ?
                             GROUP BY pr.nombre
                             ORDER BY cantidad_vendida DESC");
// === FIN DE CAMBIO PARA LA SEGUNDA CONSULTA ===

$query->bind_param("ss", $fechaInicio, $fechaFin);
$query->execute();
$result = $query->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        $row['nombre'],
        $row['cantidad_vendida'],
        '$' . number_format($row['total_recaudado'], 2)
    ];
}

if (count($data) > 0) {
    $header = ['Producto', 'Cantidad Vendida', 'Total Recaudado'];
    $w = [90, 50, 50];
    $pdf->FancyTable($header, $data, $w);
} else {
    $pdf->Cell(0, 10, utf8_decode('No se registraron productos vendidos en este periodo.'), 0, 1, 'C');
}

$pdf->Ln(15);
$pdf->SetFont('Arial', 'I', 10);
$pdf->Cell(0, 10, utf8_decode('Reporte generado automáticamente por el Sistema de Gestión de Pedidos'), 0, 1, 'C');

// Salida
$pdf->Output('I', 'Reporte_Ventas.pdf');
?>