<?php
session_start();
include 'includes/config.php';
include 'includes/funciones.php';

$categorias = obtenerCategorias();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Pedidos Online</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <section class="hero">
            <h1>Bienvenido a Nuestro Restaurante</h1>
            <p>Disfruta de nuestra deliciosa comida desde la comodidad de tu hogar</p>
            <a href="menu.php" class="btn">Ver Menú</a>
        </section>
        
        <section class="categorias">
            <h2>Nuestras Categorías</h2>
            <div class="categorias-grid">
                <?php foreach ($categorias as $categoria): ?>
                    <div class="categoria-card">
                        <h3><?php echo htmlspecialchars($categoria['nombre']); ?></h3>
                        <p><?php echo htmlspecialchars($categoria['descripcion']); ?></p>
                        <a href="menu.php?categoria=<?php echo $categoria['id']; ?>" class="btn">Ver Productos</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
    
    <script src="js/script.js"></script>
</body>
</html>