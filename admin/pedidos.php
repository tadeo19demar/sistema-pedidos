<?php
session_start();
include '../includes/config.php';

// Verificar autenticación
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Cambiar estado de pedido
if (isset($_POST['cambiar_estado'])) {
    $pedido_id = intval($_POST['pedido_id']);
    $nuevo_estado = $_POST['estado'];
    
    $sql = "UPDATE pedidos SET estado = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $nuevo_estado, $pedido_id);
    $stmt->execute();
    
    $_SESSION['mensaje'] = 'Estado del pedido actualizado';
}

// Obtener pedidos
$sql = "SELECT p.*, c.nombre as cliente_nombre, c.telefono as cliente_telefono 
        FROM pedidos p 
        JOIN clientes c ON p.cliente_id = c.id 
        ORDER BY p.fecha_pedido DESC";
$result = $conn->query($sql);
$pedidos = [];
while ($row = $result->fetch_assoc()) {
    $pedidos[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos - Admin</title>
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
        .estados {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .estado-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            background: #ecf0f1;
        }
        
        .estado-btn.active {
            background: #3498db;
            color: white;
        }
        
        .pedido-card {
            background: white;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .pedido-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .estado-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .estado-pendiente { background: #f39c12; color: white; }
        .estado-confirmado { background: #3498db; color: white; }
        .estado-preparando { background: #9b59b6; color: white; }
        .estado-en_camino { background: #e67e22; color: white; }
        .estado-entregado { background: #27ae60; color: white; }
        .estado-cancelado { background: #e74c3c; color: white; }
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
        <h1>Gestión de Pedidos</h1>
        
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="mensaje"><?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?></div>
        <?php endif; ?>
        
        <div class="pedidos-lista">
            <?php foreach ($pedidos as $pedido): ?>
                <div class="pedido-card">
                    <div class="pedido-header">
                        <div>
                            <h3>Pedido #<?php echo $pedido['id']; ?></h3>
                            <p><strong>Cliente:</strong> <?php echo htmlspecialchars($pedido['cliente_nombre']); ?></p>
                            <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($pedido['cliente_telefono']); ?></p>
                            <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></p>
                            <p><strong>Total:</strong> $<?php echo number_format($pedido['total'], 2); ?></p>
                        </div>
                        <div>
                            <span class="estado-badge estado-<?php echo $pedido['estado']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $pedido['estado'])); ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if ($pedido['notas']): ?>
                        <p><strong>Notas:</strong> <?php echo htmlspecialchars($pedido['notas']); ?></p>
                    <?php endif; ?>
                    
                    <!-- Detalles del pedido -->
                    <?php
                    $sql_detalles = "SELECT pd.*, pr.nombre as producto_nombre 
                                    FROM pedido_detalles pd 
                                    JOIN productos pr ON pd.producto_id = pr.id 
                                    WHERE pd.pedido_id = ?";
                    $stmt = $conn->prepare($sql_detalles);
                    $stmt->bind_param("i", $pedido['id']);
                    $stmt->execute();
                    $detalles = $stmt->get_result();
                    ?>
                    
                    <h4>Productos:</h4>
                    <ul>
                        <?php while ($detalle = $detalles->fetch_assoc()): ?>
                            <li><?php echo htmlspecialchars($detalle['producto_nombre']); ?> 
                                - <?php echo $detalle['cantidad']; ?> x $<?php echo number_format($detalle['precio'], 2); ?></li>
                        <?php endwhile; ?>
                    </ul>
                    
                    <!-- Formulario para cambiar estado -->
                    <form method="POST" class="form-cambiar-estado">
                        <input type="hidden" name="pedido_id" value="<?php echo $pedido['id']; ?>">
                        <select name="estado">
                            <option value="pendiente" <?php echo $pedido['estado'] == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="confirmado" <?php echo $pedido['estado'] == 'confirmado' ? 'selected' : ''; ?>>Confirmado</option>
                            <option value="preparando" <?php echo $pedido['estado'] == 'preparando' ? 'selected' : ''; ?>>Preparando</option>
                            <option value="en_camino" <?php echo $pedido['estado'] == 'en_camino' ? 'selected' : ''; ?>>En Camino</option>
                            <option value="entregado" <?php echo $pedido['estado'] == 'entregado' ? 'selected' : ''; ?>>Entregado</option>
                            <option value="cancelado" <?php echo $pedido['estado'] == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                        </select>
                        <button type="submit" name="cambiar_estado" class="btn">Actualizar Estado</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</body>
</html>