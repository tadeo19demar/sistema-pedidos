<header>
    <div class="header-content">
        <div class="logo">
            <h1>Sistema de Pedidos</h1>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Inicio</a></li>
                <li><a href="menu.php">Menú</a></li>
                <li>
                    <a href="carrito.php" class="carrito-link">
                        Carrito 
                        <span class="carrito-count">
                            <?php echo isset($_SESSION['carrito']) ? count($_SESSION['carrito']) : 0; ?>
                        </span>
                    </a>
                </li>
                <!-- Agregar este botón -->
                <li>
                    <a href="carrito.php" class="btn" style="margin-left: 10px;">
                        Ver Carrito
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</header>