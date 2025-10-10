<?php
session_start();
include '../includes/config.php';

// Verificar autenticación
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Obtener parámetros de fecha
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');

// Reporte de ventas diarias (CON FILTRO PARA EXCLUIR CANCELADOS)
$sql_ventas_diarias = "SELECT DATE(fecha_pedido) as fecha, COUNT(*) as total_pedidos, SUM(total) as total_ventas 
                       FROM pedidos 
                       WHERE fecha_pedido BETWEEN ? AND ? + INTERVAL 1 DAY
                       AND estado != 'cancelado'
                       AND estado IS NOT NULL
                       GROUP BY DATE(fecha_pedido) 
                       ORDER BY fecha DESC";
$stmt_ventas = $conn->prepare($sql_ventas_diarias);
$stmt_ventas->bind_param("ss", $fecha_inicio, $fecha_fin);
$stmt_ventas->execute();
$ventas_diarias = $stmt_ventas->get_result();

// Calcular totales generales (CON FILTRO PARA EXCLUIR CANCELADOS)
$sql_totales = "SELECT 
                COUNT(*) as total_pedidos_periodo,
                SUM(total) as total_ventas_periodo,
                AVG(total) as promedio_venta
                FROM pedidos 
                WHERE fecha_pedido BETWEEN ? AND ? + INTERVAL 1 DAY
                AND estado != 'cancelado'
                AND estado IS NOT NULL";
$stmt_totales = $conn->prepare($sql_totales);
$stmt_totales->bind_param("ss", $fecha_inicio, $fecha_fin);
$stmt_totales->execute();
$totales = $stmt_totales->get_result()->fetch_assoc();

// Calcular estadísticas de pedidos cancelados
$sql_cancelados = "SELECT 
                   COUNT(*) as total_cancelados,
                   SUM(total) as total_perdido
                   FROM pedidos 
                   WHERE fecha_pedido BETWEEN ? AND ? + INTERVAL 1 DAY
                   AND estado = 'cancelado'";
$stmt_cancelados = $conn->prepare($sql_cancelados);
$stmt_cancelados->bind_param("ss", $fecha_inicio, $fecha_fin);
$stmt_cancelados->execute();
$cancelados = $stmt_cancelados->get_result()->fetch_assoc();

// Calcular total general de pedidos (incluyendo cancelados)
$sql_total_general = "SELECT COUNT(*) as total_general_pedidos
                      FROM pedidos 
                      WHERE fecha_pedido BETWEEN ? AND ? + INTERVAL 1 DAY
                      AND estado IS NOT NULL";
$stmt_total_general = $conn->prepare($sql_total_general);
$stmt_total_general->bind_param("ss", $fecha_inicio, $fecha_fin);
$stmt_total_general->execute();
$total_general = $stmt_total_general->get_result()->fetch_assoc();

// Calcular porcentaje de cancelación
$porcentaje_cancelacion = 0;
if ($total_general['total_general_pedidos'] > 0) {
    $porcentaje_cancelacion = ($cancelados['total_cancelados'] / $total_general['total_general_pedidos']) * 100;
}

// Productos más vendidos (CON FILTRO PARA EXCLUIR CANCELADOS)
$sql_productos_populares = "SELECT p.nombre, SUM(pd.cantidad) as total_vendido, SUM(pd.cantidad * pd.precio) as total_ingresos
                           FROM pedido_detalles pd
                           JOIN productos p ON pd.producto_id = p.id
                           JOIN pedidos ped ON pd.pedido_id = ped.id
                           WHERE ped.fecha_pedido BETWEEN ? AND ? + INTERVAL 1 DAY
                           AND ped.estado != 'cancelado'
                           AND ped.estado IS NOT NULL
                           GROUP BY p.id, p.nombre
                           ORDER BY total_vendido DESC
                           LIMIT 10";
$stmt_productos = $conn->prepare($sql_productos_populares);
$stmt_productos->bind_param("ss", $fecha_inicio, $fecha_fin);
$stmt_productos->execute();
$productos_populares = $stmt_productos->get_result();

// Calcular total de productos vendidos
$total_productos_vendidos = 0;
$productos_array = [];
while ($producto = $productos_populares->fetch_assoc()) {
    $productos_array[] = $producto;
    $total_productos_vendidos += $producto['total_vendido'];
}

// Reset pointer para reutilizar el resultado
$productos_populares->data_seek(0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Admin</title>
    <link rel="stylesheet" href="../css/estilo.css">
    <style>
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 0.9em;
        }
        
        .stat-card.cancelados {
            background: #fff5f5;
            border-left: 4px solid #e74c3c;
        }
        
        .stat-card.ventas {
            background: #f0fff4;
            border-left: 4px solid #27ae60;
        }
        
        .stat-card.pedidos {
            background: #f0f8ff;
            border-left: 4px solid #3498db;
        }
        
        .admin-nav {
            background: #34495e;
            padding: 15px;
            margin-bottom: 30px;
        }
        
        .admin-nav a {
            color: white;
            text-decoration: none;
            margin-right: 20px;
            padding: 10px 15px;
            border-radius: 4px;
            transition: background 0.3s;
        }
        
        .admin-nav a:hover {
            background: #2c3e50;
        }
        
        .filtros-reportes {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .reporte-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            position: relative;
        }
        
        .reporte-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .acciones-reporte {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .btn-imprimir {
            background: #27ae60;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-imprimir:hover {
            background: #219a52;
        }
        
        .resumen-totales {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
        
        .totales-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .total-item {
            text-align: center;
            padding: 10px;
        }
        
        .total-valor {
            font-size: 1.5em;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .total-label {
            color: #7f8c8d;
            font-size: 0.9em;
        }
        
        .cancelados-info {
            background: #fff5f5;
            padding: 10px;
            border-radius: 4px;
            margin-top: 15px;
            border-left: 2px solid #e74c3c;
        }
        
        .cancelados-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 5px;
        }
        
        .cancelado-item {
            text-align: center;
            padding: 5px;
        }
        
        .cancelado-valor {
            font-size: 1.3em;
            font-weight: bold;
            color: #e74c3c;
        }
        
        .cancelado-label {
            color: #7f8c8d;
            font-size: 0.9em;
        }
        
        .porcentaje-alto {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .porcentaje-bajo {
            color: #27ae60;
            font-weight: bold;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        
        th {
            background: #34495e;
            color: white;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .tfoot-total {
            background: #2c3e50;
            color: white;
            font-weight: bold;
        }
        
        .tfoot-total td {
            border-bottom: none;
        }
        
        .filtro-estado {
            margin-top: 10px;
            padding: 10px;
            background: #e8f4fd;
            border-radius: 4px;
            font-size: 0.9em;
            color: #2c3e50;
        }
        
        @media print {
            .admin-nav,
            .filtros-reportes,
            .acciones-reporte,
            .btn-imprimir,
            .filtro-estado {
                display: none !important;
            }
            
            .reporte-section {
                box-shadow: none;
                border: 1px solid #ddd;
                page-break-inside: avoid;
            }
            
            body {
                font-size: 12px;
            }
            
            .resumen-totales {
                background: #f0f0f0;
            }
        }
    </style>
</head>
<body>
    <div class="admin-nav">
        <a href="index.php">Dashboard</a>
        <a href="productos.php">Productos</a>
        <a href="pedidos.php">Pedidos</a>
        <a href="reportes.php">Reportes</a>
        <a href="logout.php" style="float: right;">Cerrar Sesión</a>
    </div>
    
    <main class="container">
        <h1>Reportes de Ventas</h1>
        
        <!-- Filtros -->
        <div class="filtros-reportes">
            <form method="GET">
                <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 15px; align-items: end;">
                    <div class="form-group">
                        <label for="fecha_inicio">Fecha Inicio:</label>
                        <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_fin">Fecha Fin:</label>
                        <input type="date" id="fecha_fin" name="fecha_fin" value="<?php echo $fecha_fin; ?>">
                    </div>
                    
                    <div>
                        <button type="submit" class="btn">Generar Reporte</button>
                    </div>
                </div>
            </form>
       <!--     <div class="filtro-estado">
              <strong>⚠️ Nota:</strong> Las ventas y productos más vendidos excluyen pedidos cancelados.
            </div>-->
        </div>

        <!-- Resumen de Totales -->
        <div class="reporte-section">
            <div class="reporte-header">
                <h2>Resumen General del Periodo</h2>
           <!-- <button class="btn-imprimir" onclick="window.print()">
                    📄 Imprimir Reporte
                </button> -->
            </div>
            
            <div class="resumen-totales">
                <h3>Periodo: <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> - <?php echo date('d/m/Y', strtotime($fecha_fin)); ?></h3>
                
                <!-- Estadísticas de Ventas -->
                <h4>📊 Estadísticas de Ventas</h4>
                <div class="totales-grid">
                    <div class="total-item">
                        <div class="total-valor"><?php echo $total_general['total_general_pedidos'] ?? 0; ?></div>
                        <div class="total-label">Total Pedidos Recibidos</div>
                    </div>
                    <div class="total-item">
                        <div class="total-valor"><?php echo $totales['total_pedidos_periodo'] ?? 0; ?></div>
                        <div class="total-label">Pedidos Completados</div>
                    </div>
                    <div class="total-item">
                        <div class="total-valor">$<?php echo number_format($totales['total_ventas_periodo'] ?? 0, 2); ?></div>
                        <div class="total-label">Ventas Totales</div>
                    </div>
                    <div class="total-item">
                        <div class="total-valor">$<?php echo number_format($totales['promedio_venta'] ?? 0, 2); ?></div>
                        <div class="total-label">Ticket Promedio</div>
                    </div>
                </div>
                
                <!-- Estadísticas de Cancelaciones -->
                <div class="cancelados-info">
                    <h4>❌ Estadísticas de Cancelaciones</h4>
                    <div class="cancelados-grid">
                        <div class="cancelado-item">
                            <div class="cancelado-valor"><?php echo $cancelados['total_cancelados'] ?? 0; ?></div>
                            <div class="cancelado-label">Pedidos Cancelados</div>
                        </div>
                        <div class="cancelado-item">
                            <div class="cancelado-valor">$<?php echo number_format($cancelados['total_perdido'] ?? 0, 2); ?></div>
                            <div class="cancelado-label">Valor Perdido</div>
                        </div>
                        <div class="cancelado-item">
                            <div class="cancelado-valor <?php echo $porcentaje_cancelacion > 10 ? 'porcentaje-alto' : 'porcentaje-bajo'; ?>">
                                <?php echo number_format($porcentaje_cancelacion, 1); ?>%
                            </div>
                            <div class="cancelado-label">Tasa de Cancelación</div>
                        </div>
                        <div class="cancelado-item">
                            <div class="cancelado-valor">
                                <?php 
                                $tasa_exito = 100 - $porcentaje_cancelacion;
                                echo number_format($tasa_exito, 1); 
                                ?>%
                            </div>
                            <div class="cancelado-label">Tasa de Éxito</div>
                        </div>
                    </div>
                    
                    <?php if ($porcentaje_cancelacion > 10): ?>
                        <div style="margin-top: 10px; padding: 10px; background: #ffeaa7; border-radius: 4px; font-size: 0.9em;">
                            <strong>⚠️ Alerta:</strong> La tasa de cancelación es mayor al 10%. Considera revisar los procesos.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Ventas diarias -->
        <div class="reporte-section">
            <div class="reporte-header">
                <h2>Ventas Diarias</h2>
                <button class="btn-imprimir" onclick="imprimirSeccion('ventas-diarias')">
                    📄 Imprimir Ventas Diarias
                </button>
            </div>
            
            <table id="ventas-diarias">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Total Pedidos</th>
                        <th>Total Ventas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_pedidos = 0;
                    $total_ventas = 0;
                    ?>
                    <?php while ($venta = $ventas_diarias->fetch_assoc()): ?>
                        <?php 
                        $total_pedidos += $venta['total_pedidos'];
                        $total_ventas += $venta['total_ventas'];
                        ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($venta['fecha'])); ?></td>
                            <td><?php echo $venta['total_pedidos']; ?></td>
                            <td>$<?php echo number_format($venta['total_ventas'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr class="tfoot-total">
                        <td><strong>TOTAL</strong></td>
                        <td><strong><?php echo $total_pedidos; ?></strong></td>
                        <td><strong>$<?php echo number_format($total_ventas, 2); ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <!-- Productos más populares -->
        <div class="reporte-section">
            <div class="reporte-header">
                <h2>Productos Más Vendidos</h2>
                <button class="btn-imprimir" onclick="imprimirSeccion('productos-populares')">
                    📄 Imprimir Productos Más Vendidos
                </button>
            </div>
            
            <table id="productos-populares">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad Vendida</th>
                        <th>Total Ingresos</th>
                        <th>% del Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_ingresos_productos = 0;
                    // Calcular total de ingresos primero
                    foreach ($productos_array as $producto) {
                        $total_ingresos_productos += $producto['total_ingresos'];
                    }
                    ?>
                    <?php foreach ($productos_array as $producto): ?>
                        <?php 
                        $porcentaje = $total_ingresos_productos > 0 ? ($producto['total_ingresos'] / $total_ingresos_productos) * 100 : 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                            <td><?php echo $producto['total_vendido']; ?></td>
                            <td>$<?php echo number_format($producto['total_ingresos'], 2); ?></td>
                            <td><?php echo number_format($porcentaje, 1); ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="tfoot-total">
                        <td><strong>TOTAL</strong></td>
                        <td><strong><?php echo $total_productos_vendidos; ?></strong></td>
                        <td><strong>$<?php echo number_format($total_ingresos_productos, 2); ?></strong></td>
                        <td><strong>100%</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Botones de acción generales -->
        <div class="acciones-reporte">
            <button class="btn-imprimir" onclick="window.print()">
                🖨️ Imprimir Reporte Completo
            </button>
            <button class="btn" onclick="exportarPDF()">
                📊 Exportar a PDF
            </button>
            <button class="btn" onclick="exportarExcel()">
                📈 Exportar a Excel
            </button>
        </div>
    </main>

    <script>
        function imprimirSeccion(seccionId) {
            const elemento = document.getElementById(seccionId);
            const ventana = window.open('', '_blank');
            ventana.document.write(`
                <html>
                    <head>
                        <title>Reporte - ${seccionId}</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 20px; }
                            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                            th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
                            th { background: #34495e; color: white; }
                            .tfoot-total { background: #2c3e50; color: white; font-weight: bold; }
                            h2 { color: #2c3e50; border-bottom: 2px solid #34495e; padding-bottom: 10px; }
                        </style>
                    </head>
                    <body>
                        <h2>${document.querySelector('h1').textContent}</h2>
                        <p><strong>Periodo:</strong> <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> - <?php echo date('d/m/Y', strtotime($fecha_fin)); ?></p>
                        <p><strong>Nota:</strong> Ventas y productos excluyen pedidos cancelados</p>
                        ${elemento.outerHTML}
                    </body>
                </html>
            `);
            ventana.document.close();
            ventana.print();
        }

        function exportarPDF() {
            alert('Función de exportación a PDF - Para implementar con una librería como jsPDF');
        }

        function exportarExcel() {
            alert('Función de exportación a Excel - Para implementar con una librería como SheetJS');
        }
    </script>
</body>
</html>