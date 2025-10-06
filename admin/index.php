<?php
session_start();
include '../includes/config.php';

// Verificar autenticación
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Obtener estadísticas
$sql_pedidos_hoy = "SELECT COUNT(*) as total FROM pedidos WHERE DATE(fecha_pedido) = CURDATE()";
$pedidos_hoy = $conn->query($sql_pedidos_hoy)->fetch_assoc()['total'];

$sql_ventas_hoy = "SELECT COALESCE(SUM(total), 0) as total FROM pedidos WHERE DATE(fecha_pedido) = CURDATE()";
$ventas_hoy = $conn->query($sql_ventas_hoy)->fetch_assoc()['total'];

$sql_pedidos_pendientes = "SELECT COUNT(*) as total FROM pedidos WHERE estado = 'pendiente'";
$pedidos_pendientes = $conn->query($sql_pedidos_pendientes)->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
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
        <h1>Panel de Administración</h1>
        
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $pedidos_hoy; ?></div>
                <div class="stat-label">Pedidos Hoy</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number">$<?php echo number_format($ventas_hoy, 2); ?></div>
                <div class="stat-label">Ventas Hoy</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $pedidos_pendientes; ?></div>
                <div class="stat-label">Pedidos Pendientes</div>
            </div>
        </div>
        
        <div class="admin-sections">
            <div class="section-card">
                <h2>Gestión de Productos</h2>
                <p>Administra el menú del restaurante</p>
                <a href="productos.php" class="btn">Gestionar Productos</a>
            </div>
            
            <div class="section-card">
                <h2>Gestión de Pedidos</h2>
                <p>Revisa y actualiza el estado de los pedidos</p>
                <a href="pedidos.php" class="btn">Gestionar Pedidos</a>
            </div>
            
            <div class="section-card">
                <h2>Reportes</h2>
                <p>Consulta reportes de ventas y productos</p>
                <a href="reportes.php" class="btn">Ver Reportes</a>
            </div>
        </div>
    </main>
</body>
</html>