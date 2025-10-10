<?php
session_start();
include 'includes/config.php';

// Verificar que el pedido fue exitoso
if (!isset($_SESSION['pedido_exitoso']) || !$_SESSION['pedido_exitoso']) {
    header('Location: index.php');
    exit;
}

// Obtener información del pedido
$pedido_id = $_SESSION['pedido_id'];
$sql_pedido = "SELECT p.*, c.nombre as cliente_nombre, c.email as cliente_email, 
                      c.telefono as cliente_telefono, c.direccion as cliente_direccion
               FROM pedidos p 
               JOIN clientes c ON p.cliente_id = c.id 
               WHERE p.id = ?";
$stmt = $conn->prepare($sql_pedido);
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$result = $stmt->get_result();
$pedido = $result->fetch_assoc();

// Obtener detalles del pedido
$sql_detalles = "SELECT pd.*, pr.nombre as producto_nombre 
                 FROM pedido_detalles pd 
                 JOIN productos pr ON pd.producto_id = pr.id 
                 WHERE pd.pedido_id = ?";
$stmt_detalles = $conn->prepare($sql_detalles);
$stmt_detalles->bind_param("i", $pedido_id);
$stmt_detalles->execute();
$detalles = $stmt_detalles->get_result();

// Limpiar la sesión de pedido exitoso
unset($_SESSION['pedido_exitoso']);
unset($_SESSION['pedido_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Pedido - Sistema de Pedidos</title>
    <link rel="stylesheet" href="css/estilo.css">
    <style>
        .confirmacion-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .confirmacion-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .confirmacion-icon {
            font-size: 4em;
            color: #27ae60;
            margin-bottom: 20px;
        }
        
        .numero-pedido {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 1.5em;
            font-weight: bold;
            display: inline-block;
            margin: 10px 0;
        }
        
        .info-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        
        .info-section h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
        }
        
        .detalles-pedido {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .detalles-pedido th {
            background: #34495e;
            color: white;
            padding: 12px;
            text-align: left;
        }
        
        .detalles-pedido td {
            padding: 12px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .detalles-pedido tr:hover {
            background: #f8f9fa;
        }
        
        .total-pedido {
            text-align: right;
            font-size: 1.3em;
            font-weight: bold;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #34495e;
        }
        
        .acciones {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ecf0f1;
        }
        
        .estado-pedido {
            display: inline-block;
            padding: 8px 16px;
            background: #f39c12;
            color: white;
            border-radius: 15px;
            font-weight: bold;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <div class="confirmacion-container">
            <div class="confirmacion-header">
                <div class="confirmacion-icon">✅</div>
                <h1>¡Pedido Confirmado!</h1>
                <p>Gracias por tu compra. Tu pedido ha sido recibido exitosamente.</p>
                <div class="numero-pedido">Pedido #<?php echo $pedido_id; ?></div>
                <div class="estado-pedido">Estado: <?php echo ucfirst(str_replace('_', ' ', $pedido['estado'])); ?></div>
            </div>
            
            <!-- Información del cliente -->
            <div class="info-section">
                <h3>Información de Entrega</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <strong>Nombre:</strong><br>
                        <?php echo htmlspecialchars($pedido['cliente_nombre']); ?>
                    </div>
                    <div>
                        <strong>Email:</strong><br>
                        <?php echo htmlspecialchars($pedido['cliente_email']); ?>
                    </div>
                    <div>
                        <strong>Teléfono:</strong><br>
                        <?php echo htmlspecialchars($pedido['cliente_telefono']); ?>
                    </div>
                    <div>
                        <strong>Dirección:</strong><br>
                        <?php echo htmlspecialchars($pedido['cliente_direccion']); ?>
                    </div>
                </div>
                
                <?php if (!empty($pedido['notas'])): ?>
                    <div style="margin-top: 15px;">
                        <strong>Notas adicionales:</strong><br>
                        <?php echo htmlspecialchars($pedido['notas']); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Detalles del pedido -->
            <div class="info-section">
                <h3>Detalles del Pedido</h3>
                <table class="detalles-pedido">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio Unitario</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($detalle = $detalles->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($detalle['producto_nombre']); ?></td>
                                <td><?php echo $detalle['cantidad']; ?></td>
                                <td>$<?php echo number_format($detalle['precio'], 2); ?></td>
                                <td>$<?php echo number_format($detalle['precio'] * $detalle['cantidad'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <div class="total-pedido">
                    Total del Pedido: $<?php echo number_format($pedido['total'], 2); ?>
                </div>
            </div>
            
            <!-- Información adicional -->
            <div class="info-section">
                <h3>¿Qué sigue?</h3>
                <ul style="line-height: 1.6;">
                    <li>Hemos recibido tu pedido y está siendo procesado</li>
                    <li>Recibirás una confirmación por email (si el sistema está configurado)</li>
                    <li>El tiempo de entrega estimado es de 30-45 minutos</li>
                    <li>Para cualquier consulta, contacta al restaurante directamente</li>
                </ul>
            </div>
            
            <div class="acciones">
                <a href="index.php" class="btn">Volver al Inicio</a>
                <a href="menu.php" class="btn btn-primary">Hacer Otro Pedido</a>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>