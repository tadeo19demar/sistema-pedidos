<?php
session_start();
include 'includes/config.php';
include 'includes/funciones.php';

$categoria_id = isset($_GET['categoria']) ? intval($_GET['categoria']) : null;
$productos = obtenerProductos($categoria_id);
$categorias = obtenerCategorias();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú - Sistema de Pedidos</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <h1>Nuestro Menú</h1>
        
        <!-- Filtros por categoría -->
        <div class="filtros">
            <a href="menu.php" class="btn <?php echo !$categoria_id ? 'active' : ''; ?>">Todos</a>
            <?php foreach ($categorias as $cat): ?>
                <a href="menu.php?categoria=<?php echo $cat['id']; ?>" 
                   class="btn <?php echo $categoria_id == $cat['id'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat['nombre']); ?>
                </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Lista de productos -->
        <div class="productos-grid">
            <?php foreach ($productos as $producto): ?>
                <div class="producto-card">
                    <div class="producto-imagen">
                        <?php if ($producto['imagen']): ?>
                            <img src="<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                        <?php else: ?>
                            <div class="imagen-placeholder">Sin imagen</div>
                        <?php endif; ?>
                    </div>
                    <div class="producto-info">
                        <h3><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                        <p class="categoria"><?php echo htmlspecialchars($producto['categoria_nombre']); ?></p>
                        <p class="descripcion"><?php echo htmlspecialchars($producto['descripcion']); ?></p>
                        <p class="precio">$<?php echo number_format($producto['precio'], 2); ?></p>
                        <button class="btn agregar-carrito" 
                                data-id="<?php echo $producto['id']; ?>"
                                data-nombre="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                data-precio="<?php echo $producto['precio']; ?>">
                            Agregar al Carrito
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
    
    <script src="js/script.js"></script>
</body>
</html>