// Funcionalidades JavaScript para el sistema de pedidos

document.addEventListener('DOMContentLoaded', function() {
    // Manejo del carrito
    const botonesAgregar = document.querySelectorAll('.agregar-carrito');
    
    botonesAgregar.forEach(boton => {
        boton.addEventListener('click', function() {
            const producto = {
                id: this.dataset.id,
                nombre: this.dataset.nombre,
                precio: parseFloat(this.dataset.precio)
            };
            
            agregarAlCarrito(producto);
        });
    });
    
    // Actualizar contador del carrito
    actualizarContadorCarrito();
});

function agregarAlCarrito(producto) {
    // Enviar datos al servidor via AJAX
    const formData = new FormData();
    formData.append('agregar', 'true');
    formData.append('producto_id', producto.id);
    formData.append('cantidad', 1);
    
    fetch('carrito.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(() => {
        // Actualizar contador
        actualizarContadorCarrito();
        
        // Mostrar mensaje de confirmación
        mostrarMensaje('Producto agregado al carrito', 'success');
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarMensaje('Error al agregar producto', 'error');
    });
}

function actualizarContadorCarrito() {
    // Obtener el contador actual del carrito (simulado)
    // En una implementación real, esto vendría del servidor
    const contador = document.querySelector('.carrito-count');
    if (contador) {
        // Simular actualización - en producción esto vendría del servidor
        const current = parseInt(contador.textContent) || 0;
        contador.textContent = current + 1;
    }
}

function mostrarMensaje(mensaje, tipo) {
    // Crear elemento de mensaje
    const mensajeDiv = document.createElement('div');
    mensajeDiv.className = `mensaje ${tipo}`;
    mensajeDiv.textContent = mensaje;
    
    // Agregar al inicio del main
    const main = document.querySelector('main');
    main.insertBefore(mensajeDiv, main.firstChild);
    
    // Remover después de 3 segundos
    setTimeout(() => {
        mensajeDiv.remove();
    }, 3000);
}

// Validación de formularios
function validarFormularioPedido(form) {
    const nombre = form.querySelector('#nombre').value.trim();
    const email = form.querySelector('#email').value.trim();
    const telefono = form.querySelector('#telefono').value.trim();
    const direccion = form.querySelector('#direccion').value.trim();
    
    if (!nombre || !email || !telefono || !direccion) {
        mostrarMensaje('Por favor complete todos los campos obligatorios', 'error');
        return false;
    }
    
    // Validar email básico
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        mostrarMensaje('Por favor ingrese un email válido', 'error');
        return false;
    }
    
    return true;
}

// Funciones para el administrador
function cambiarEstadoPedido(pedidoId, nuevoEstado) {
    const formData = new FormData();
    formData.append('pedido_id', pedidoId);
    formData.append('estado', nuevoEstado);
    formData.append('cambiar_estado', 'true');
    
    fetch('admin/pedidos.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(() => {
        location.reload(); // Recargar para ver cambios
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al cambiar estado del pedido');
    });
}