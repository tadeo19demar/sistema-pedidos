<?php
session_start();
include 'includes/config.php';
include 'includes/funciones.php';

// Inicializar carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Agregar producto al carrito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar'])) {
    $producto_id = intval($_POST['producto_id']);
    $cantidad = intval($_POST['cantidad']);
    
    $producto = obtenerProducto($producto_id);
    
    if ($producto) {
        // Verificar si el producto ya está en el carrito
        $encontrado = false;
        foreach ($_SESSION['carrito'] as &$item) {
            if ($item['id'] == $producto_id) {
                $item['cantidad'] += $cantidad;
                $encontrado = true;
                break;
            }
        }
        
        // Si no está, agregarlo
        if (!$encontrado) {
            $_SESSION['carrito'][] = [
                'id' => $producto_id,
                'nombre' => $producto['nombre'],
                'precio' => $producto['precio'],
                'cantidad' => $cantidad
            ];
        }
        
        $_SESSION['mensaje'] = 'Producto agregado al carrito';
    }
}

// Actualizar cantidades
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar'])) {
    foreach ($_POST['cantidades'] as $id => $cantidad) {
        $cantidad = intval($cantidad);
        if ($cantidad <= 0) {
            // Eliminar producto si la cantidad es 0
            $_SESSION['carrito'] = array_filter($_SESSION['carrito'], function($item) use ($id) {
                return $item['id'] != $id;
            });
        } else {
            // Actualizar cantidad
            foreach ($_SESSION['carrito'] as &$item) {
                if ($item['id'] == $id) {
                    $item['cantidad'] = $cantidad;
                    break;
                }
            }
        }
    }
    $_SESSION['mensaje'] = 'Carrito actualizado';
}

// Eliminar producto
if (isset($_GET['eliminar'])) {
    $id_eliminar = intval($_GET['eliminar']);
    $_SESSION['carrito'] = array_filter($_SESSION['carrito'], function($item) use ($id_eliminar) {
        return $item['id'] != $id_eliminar;
    });
    $_SESSION['mensaje'] = 'Producto eliminado del carrito';
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
    <title>Carrito - Sistema de Pedidos</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <h1>Tu Carrito de Compras</h1>
        
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="mensaje"><?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?></div>
        <?php endif; ?>
        
        <?php if (empty($_SESSION['carrito'])): ?>
            <div class="carrito-vacio">
                <p>Tu carrito está vacío</p>
                <a href="menu.php" class="btn">Ver Menú</a>
            </div>
        <?php else: ?>
            <form method="POST" action="carrito.php">
                <div class="carrito-items">
                    <?php foreach ($_SESSION['carrito'] as $item): ?>
                        <div class="carrito-item">
                            <div class="item-info">
                                <h3><?php echo htmlspecialchars($item['nombre']); ?></h3>
                                <p class="precio-unitario">$<?php echo number_format($item['precio'], 2); ?> c/u</p>
                            </div>
                            <div class="item-cantidad">
                                <input type="number" name="cantidades[<?php echo $item['id']; ?>]" 
                                       value="<?php echo $item['cantidad']; ?>" min="0" class="cantidad-input">
                            </div>
                            <div class="item-subtotal">
                                $<?php echo number_format($item['precio'] * $item['cantidad'], 2); ?>
                            </div>
                            <div class="item-acciones">
                                <a href="carrito.php?eliminar=<?php echo $item['id']; ?>" class="btn-eliminar">Eliminar</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="carrito-total">
                    <h2>Total: $<?php echo number_format($total, 2); ?></h2>
                </div>
                
                <div class="carrito-acciones">
                    <button type="submit" name="actualizar" class="btn">Actualizar Carrito</button>
                    <a href="pedido.php" class="btn btn-primary">Continuar con el Pedido</a>
                </div>
            </form>
        <?php endif; ?>
    </main>
    
    <script src="js/script.js"></script>
</body>
</html>