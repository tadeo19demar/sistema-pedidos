<?php
// Funciones comunes del sistema

function obtenerProductos($categoria_id = null) {
    global $conn;
    
    $sql = "SELECT p.*, c.nombre as categoria_nombre 
            FROM productos p 
            JOIN categorias c ON p.categoria_id = c.id 
            WHERE p.activo = 1";
    
    if ($categoria_id) {
        $sql .= " AND p.categoria_id = ?";
    }
    
    $sql .= " ORDER BY c.nombre, p.nombre";
    
    $stmt = $conn->prepare($sql);
    
    if ($categoria_id) {
        $stmt->bind_param("i", $categoria_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $productos = [];
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
    
    return $productos;
}

function obtenerCategorias() {
    global $conn;
    
    $sql = "SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre";
    $result = $conn->query($sql);
    
    $categorias = [];
    while ($row = $result->fetch_assoc()) {
        $categorias[] = $row;
    }
    
    return $categorias;
}

function obtenerProducto($id) {
    global $conn;
    
    $sql = "SELECT p.*, c.nombre as categoria_nombre 
            FROM productos p 
            JOIN categorias c ON p.categoria_id = c.id 
            WHERE p.id = ? AND p.activo = 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

function procesarPedido($cliente_data, $carrito) {
    global $conn;
    
    // Iniciar transacción
    $conn->begin_transaction();
    
    try {
        // Insertar cliente
        $sql_cliente = "INSERT INTO clientes (nombre, email, telefono, direccion) VALUES (?, ?, ?, ?)";
        $stmt_cliente = $conn->prepare($sql_cliente);
        $stmt_cliente->bind_param("ssss", $cliente_data['nombre'], $cliente_data['email'], $cliente_data['telefono'], $cliente_data['direccion']);
        $stmt_cliente->execute();
        $cliente_id = $conn->insert_id;
        
        // Calcular total
        $total = 0;
        foreach ($carrito as $item) {
            $total += $item['precio'] * $item['cantidad'];
        }
        
        // Insertar pedido
        $sql_pedido = "INSERT INTO pedidos (cliente_id, total, notas) VALUES (?, ?, ?)";
        $stmt_pedido = $conn->prepare($sql_pedido);
        $stmt_pedido->bind_param("ids", $cliente_id, $total, $cliente_data['notas']);
        $stmt_pedido->execute();
        $pedido_id = $conn->insert_id;
        
        // Insertar detalles del pedido
        $sql_detalle = "INSERT INTO pedido_detalles (pedido_id, producto_id, cantidad, precio) VALUES (?, ?, ?, ?)";
        $stmt_detalle = $conn->prepare($sql_detalle);
        
        foreach ($carrito as $item) {
            $stmt_detalle->bind_param("iiid", $pedido_id, $item['id'], $item['cantidad'], $item['precio']);
            $stmt_detalle->execute();
        }
        
        // Confirmar transacción
        $conn->commit();
        return $pedido_id;
        
    } catch (Exception $e) {
        // Revertir en caso de error
        $conn->rollback();
        return false;
    }
}
?>