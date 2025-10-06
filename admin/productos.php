<?php
session_start();
include '../includes/config.php';

// Verificar autenticación
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Obtener categorías para el formulario
$categorias = [];
$sql_categorias = "SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre";
$result_categorias = $conn->query($sql_categorias);
while ($row = $result_categorias->fetch_assoc()) {
    $categorias[] = $row;
}

// Procesar formulario de agregar/editar producto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['agregar_producto'])) {
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);
        $precio = floatval($_POST['precio']);
        $categoria_id = intval($_POST['categoria_id']);
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        // Manejar upload de imagen
        $imagen = null;
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = $_FILES['imagen']['type'];
            
            if (in_array($file_type, $allowed_types)) {
                $upload_dir = '../uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
                $filename = 'producto_' . time() . '_' . uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $upload_path)) {
                    $imagen = 'uploads/' . $filename;
                }
            }
        }
        
        $sql = "INSERT INTO productos (nombre, descripcion, precio, imagen, categoria_id, activo) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdsii", $nombre, $descripcion, $precio, $imagen, $categoria_id, $activo);
        
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = 'Producto agregado correctamente';
        } else {
            $_SESSION['error'] = 'Error al agregar el producto';
        }
    }
    
    // Editar producto
    if (isset($_POST['editar_producto'])) {
        $id = intval($_POST['id']);
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);
        $precio = floatval($_POST['precio']);
        $categoria_id = intval($_POST['categoria_id']);
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        // Si se sube nueva imagen
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = $_FILES['imagen']['type'];
            
            if (in_array($file_type, $allowed_types)) {
                $upload_dir = '../uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Eliminar imagen anterior si existe
                $sql_old = "SELECT imagen FROM productos WHERE id = ?";
                $stmt_old = $conn->prepare($sql_old);
                $stmt_old->bind_param("i", $id);
                $stmt_old->execute();
                $result_old = $stmt_old->get_result();
                $old_product = $result_old->fetch_assoc();
                
                if ($old_product['imagen'] && file_exists('../' . $old_product['imagen'])) {
                    unlink('../' . $old_product['imagen']);
                }
                
                $file_extension = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
                $filename = 'producto_' . time() . '_' . uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $upload_path)) {
                    $imagen = 'uploads/' . $filename;
                    
                    $sql = "UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, 
                            imagen = ?, categoria_id = ?, activo = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssdsiii", $nombre, $descripcion, $precio, $imagen, $categoria_id, $activo, $id);
                }
            }
        } else {
            // Mantener imagen actual
            $sql = "UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, 
                    categoria_id = ?, activo = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssdiii", $nombre, $descripcion, $precio, $categoria_id, $activo, $id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = 'Producto actualizado correctamente';
        } else {
            $_SESSION['error'] = 'Error al actualizar el producto';
        }
    }
    
    // Eliminar producto
    if (isset($_POST['eliminar_producto'])) {
        $id = intval($_POST['id']);
        
        // Obtener información de la imagen para eliminarla
        $sql_img = "SELECT imagen FROM productos WHERE id = ?";
        $stmt_img = $conn->prepare($sql_img);
        $stmt_img->bind_param("i", $id);
        $stmt_img->execute();
        $result_img = $stmt_img->get_result();
        $producto = $result_img->fetch_assoc();
        
        // Eliminar imagen del servidor si existe
        if ($producto['imagen'] && file_exists('../' . $producto['imagen'])) {
            unlink('../' . $producto['imagen']);
        }
        
        // Eliminar producto de la base de datos
        $sql = "DELETE FROM productos WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = 'Producto eliminado correctamente';
        } else {
            $_SESSION['error'] = 'Error al eliminar el producto';
        }
    }
    
    header('Location: productos.php');
    exit;
}

// Obtener productos para mostrar
$sql_productos = "SELECT p.*, c.nombre as categoria_nombre 
                 FROM productos p 
                 LEFT JOIN categorias c ON p.categoria_id = c.id 
                 ORDER BY p.nombre";
$result_productos = $conn->query($sql_productos);
$productos = [];
while ($row = $result_productos->fetch_assoc()) {
    $productos[] = $row;
}

// Obtener producto específico para editar (si se solicita)
$producto_editar = null;
if (isset($_GET['editar'])) {
    $id_editar = intval($_GET['editar']);
    $sql_editar = "SELECT * FROM productos WHERE id = ?";
    $stmt_editar = $conn->prepare($sql_editar);
    $stmt_editar->bind_param("i", $id_editar);
    $stmt_editar->execute();
    $result_editar = $stmt_editar->get_result();
    $producto_editar = $result_editar->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos - Admin</title>
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
        .admin-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        
        .form-section, .list-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .producto-item {
            display: grid;
            grid-template-columns: 80px 1fr auto;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
            align-items: center;
        }
        
        .producto-item:last-child {
            border-bottom: none;
        }
        
        .producto-imagen {
            width: 80px;
            height: 80px;
            background: #ecf0f1;
            border-radius: 4px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .producto-imagen img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .producto-info h3 {
            margin: 0 0 5px 0;
            color: #2c3e50;
        }
        
        .producto-info p {
            margin: 2px 0;
            color: #7f8c8d;
            font-size: 0.9em;
        }
        
        .producto-precio {
            font-weight: bold;
            color: #27ae60;
        }
        
        .producto-acciones {
            display: flex;
            gap: 10px;
        }
        
        .btn-editar {
            background: #3498db;
            color: white;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.8em;
        }
        
        .btn-eliminar {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8em;
        }
        
        .estado-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.7em;
            font-weight: bold;
        }
        
        .estado-activo {
            background: #27ae60;
            color: white;
        }
        
        .estado-inactivo {
            background: #e74c3c;
            color: white;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1em;
        }
        
        .form-group textarea {
            height: 80px;
            resize: vertical;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input {
            width: auto;
        }
        
        .imagen-preview {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            border-radius: 4px;
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
        <h1>Gestión de Productos</h1>
        
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="mensaje"><?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="errores"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <div class="admin-content">
            <!-- Formulario para agregar/editar producto -->
            <div class="form-section">
                <h2><?php echo $producto_editar ? 'Editar Producto' : 'Agregar Nuevo Producto'; ?></h2>
                
                <form method="POST" enctype="multipart/form-data">
                    <?php if ($producto_editar): ?>
                        <input type="hidden" name="id" value="<?php echo $producto_editar['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="nombre">Nombre del Producto *</label>
                        <input type="text" id="nombre" name="nombre" required
                               value="<?php echo $producto_editar ? htmlspecialchars($producto_editar['nombre']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion"><?php echo $producto_editar ? htmlspecialchars($producto_editar['descripcion']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="precio">Precio *</label>
                        <input type="number" id="precio" name="precio" step="0.01" min="0" required
                               value="<?php echo $producto_editar ? $producto_editar['precio'] : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="categoria_id">Categoría *</label>
                        <select id="categoria_id" name="categoria_id" required>
                            <option value="">Seleccionar categoría</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo $categoria['id']; ?>"
                                    <?php echo ($producto_editar && $producto_editar['categoria_id'] == $categoria['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($categoria['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="imagen">Imagen del Producto</label>
                        <input type="file" id="imagen" name="imagen" accept="image/*">
                        
                        <?php if ($producto_editar && $producto_editar['imagen']): ?>
                            <div>
                                <p>Imagen actual:</p>
                                <img src="../<?php echo htmlspecialchars($producto_editar['imagen']); ?>" 
                                     alt="Imagen actual" class="imagen-preview">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="activo" name="activo" value="1"
                                <?php echo ($producto_editar && $producto_editar['activo']) || !$producto_editar ? 'checked' : ''; ?>>
                            <label for="activo">Producto activo (disponible para venta)</label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <?php if ($producto_editar): ?>
                            <button type="submit" name="editar_producto" class="btn">Actualizar Producto</button>
                            <a href="productos.php" class="btn" style="background: #7f8c8d;">Cancelar</a>
                        <?php else: ?>
                            <button type="submit" name="agregar_producto" class="btn">Agregar Producto</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Lista de productos existentes -->
            <div class="list-section">
                <h2>Productos Existentes</h2>
                
                <?php if (empty($productos)): ?>
                    <p>No hay productos registrados.</p>
                <?php else: ?>
                    <div class="productos-lista">
                        <?php foreach ($productos as $producto): ?>
                            <div class="producto-item">
                                <div class="producto-imagen">
                                    <?php if ($producto['imagen']): ?>
                                        <img src="../<?php echo htmlspecialchars($producto['imagen']); ?>" 
                                             alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                                    <?php else: ?>
                                        <div style="color: #7f8c8d; font-size: 0.8em;">Sin imagen</div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="producto-info">
                                    <h3><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                                    <p><?php echo htmlspecialchars($producto['descripcion']); ?></p>
                                    <p class="producto-precio">$<?php echo number_format($producto['precio'], 2); ?></p>
                                    <p><small>Categoría: <?php echo htmlspecialchars($producto['categoria_nombre']); ?></small></p>
                                    <span class="estado-badge <?php echo $producto['activo'] ? 'estado-activo' : 'estado-inactivo'; ?>">
                                        <?php echo $producto['activo'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </div>
                                
                                <div class="producto-acciones">
                                    <a href="productos.php?editar=<?php echo $producto['id']; ?>" class="btn-editar">Editar</a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('¿Está seguro de eliminar este producto?');">
                                        <input type="hidden" name="id" value="<?php echo $producto['id']; ?>">
                                        <button type="submit" name="eliminar_producto" class="btn-eliminar">Eliminar</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <script>
        // Preview de imagen antes de subir
        document.getElementById('imagen')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Eliminar preview anterior si existe
                    const oldPreview = document.querySelector('.imagen-preview-nueva');
                    if (oldPreview) oldPreview.remove();
                    
                    // Crear nuevo preview
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'imagen-preview imagen-preview-nueva';
                    img.style.maxWidth = '200px';
                    img.style.maxHeight = '200px';
                    img.style.marginTop = '10px';
                    img.style.borderRadius = '4px';
                    
                    e.target.parentNode.appendChild(img);
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>