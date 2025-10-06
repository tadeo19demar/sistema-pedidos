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

// Reporte de ventas diarias
$sql_ventas_diarias = "SELECT DATE(fecha_pedido) as fecha, COUNT(*) as total_pedidos, SUM(total) as total_ventas 
                       FROM pedidos 
                       WHERE fecha_pedido BETWEEN ? AND ? + INTERVAL 1 DAY
                       GROUP BY DATE(fecha_pedido) 
                       ORDER BY fecha DESC";
$stmt_ventas = $conn->prepare($sql_ventas_diarias);
$stmt_ventas->bind_param("ss", $fecha_inicio, $fecha_fin);
$stmt_ventas->execute();
$ventas_diarias = $stmt_ventas->get_result();

// Productos más vendidos
$sql_productos_populares = "SELECT p.nombre, SUM(pd.cantidad) as total_vendido, SUM(pd.cantidad * pd.precio) as total_ingresos
                           FROM pedido_detalles pd
                           JOIN productos p ON pd.producto_id = p.id
                           JOIN pedidos ped ON pd.pedido_id = ped.id
                           WHERE ped.fecha_pedido BETWEEN ? AND ? + INTERVAL 1 DAY
                           GROUP BY p.id, p.nombre
                           ORDER BY total_vendido DESC
                           LIMIT 10";
$stmt_productos = $conn->prepare($sql_productos_populares);
$stmt_productos->bind_param("ss", $fecha_inicio, $fecha_fin);
$stmt_productos->execute();
$productos_populares = $stmt_productos->get_result();
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
        
        .admin-sections {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .section-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .section-card h2 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .section-card p {
            color: #7f8c8d;
            margin-bottom: 20px;
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
                <div class="form-group">
                    <label for="fecha_inicio">Fecha Inicio:</label>
                    <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>">
                </div>
                
                <div class="form-group">
                    <label for="fecha_fin">Fecha Fin:</label>
                    <input type="date" id="fecha_fin" name="fecha_fin" value="<?php echo $fecha_fin; ?>">
                </div>
                
                <button type="submit" class="btn">Generar Reporte</button>
            </form>
        </div>
        
        <!-- Ventas diarias -->
        <div class="reporte-section">
            <h2>Ventas Diarias</h2>
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Total Pedidos</th>
                        <th>Total Ventas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($venta = $ventas_diarias->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($venta['fecha'])); ?></td>
                            <td><?php echo $venta['total_pedidos']; ?></td>
                            <td>$<?php echo number_format($venta['total_ventas'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Productos más populares -->
        <div class="reporte-section">
            <h2>Productos Más Vendidos</h2>
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad Vendida</th>
                        <th>Total Ingresos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($producto = $productos_populares->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                            <td><?php echo $producto['total_vendido']; ?></td>
                            <td>$<?php echo number_format($producto['total_ingresos'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>