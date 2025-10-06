<?php
session_start();
include 'includes/config.php';
include 'includes/funciones.php';

// Verificar que el carrito no esté vacío
if (empty($_SESSION['carrito'])) {
    header('Location: menu.php');
    exit;
}

// Procesar pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_data = [
        'nombre' => trim($_POST['nombre']),
        'email' => trim($_POST['email']),
        'telefono' => trim($_POST['telefono']),
        'direccion' => trim($_POST['direccion']),
        'notas' => trim($_POST['notas'])
    ];
    
    // Validaciones básicas
    $errores = [];
    if (empty($cliente_data['nombre'])) $errores[] = 'El nombre es obligatorio';
    if (empty($cliente_data['email'])) $errores[] = 'El email es obligatorio';
    if (empty($cliente_data['telefono'])) $errores[] = 'El teléfono es obligatorio';
    if (empty($cliente_data['direccion'])) $errores[] = 'La dirección es obligatoria';
    
    if (empty($errores)) {
        $pedido_id = procesarPedido($cliente_data, $_SESSION['carrito']);
        
        if ($pedido_id) {
            // Limpiar carrito
            $_SESSION['carrito'] = [];
            $_SESSION['pedido_exitoso'] = true;
            $_SESSION['pedido_id'] = $pedido_id;
            header('Location: confirmacion.php');
            exit;
        } else {
            $errores[] = 'Error al procesar el pedido. Intente nuevamente.';
        }
    }
}

// Calcular total
$total = 0;
foreach ($_SESSION['carrito'] as $item) {
    $total += $item['precio'] * $item['cantidad'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Realizar Pedido - Sistema de Pedidos</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <h1>Completar Pedido</h1>
        
        <?php if (!empty($errores)): ?>
            <div class="errores">
                <?php foreach ($errores as $error): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="pedido-contenido">
            <div class="resumen-pedido">
                <h2>Resumen de tu Pedido</h2>
                <?php foreach ($_SESSION['carrito'] as $item): ?>
                    <div class="resumen-item">
                        <span class="nombre"><?php echo htmlspecialchars($item['nombre']); ?></span>
                        <span class="cantidad">x<?php echo $item['cantidad']; ?></span>
                        <span class="subtotal">$<?php echo number_format($item['precio'] * $item['cantidad'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
                <div class="resumen-total">
                    <strong>Total: $<?php echo number_format($total, 2); ?></strong>
                </div>
            </div>
            
            <div class="formulario-pedido">
                <h2>Información de Entrega</h2>
                <form method="POST" action="pedido.php">
                    <div class="form-group">
                        <label for="nombre">Nombre Completo *</label>
                        <input type="text" id="nombre" name="nombre" required 
                               value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="telefono">Teléfono *</label>
                        <input type="tel" id="telefono" name="telefono" required
                               value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="direccion">Dirección de Entrega *</label>
                        <textarea id="direccion" name="direccion" required><?php echo isset($_POST['direccion']) ? htmlspecialchars($_POST['direccion']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="notas">Notas Adicionales (opcional)</label>
                        <textarea id="notas" name="notas"><?php echo isset($_POST['notas']) ? htmlspecialchars($_POST['notas']) : ''; ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Confirmar Pedido</button>
                </form>
            </div>
        </div>
    </main>
    
    <script src="js/script.js"></script>
</body>
</html>