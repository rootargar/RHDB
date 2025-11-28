// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
    // Obtener el formulario
    const form = document.querySelector('form');
    
    if (form) {
        // Agregar evento para validar el formulario antes de enviar
        form.addEventListener('submit', function(event) {
            // Variable para controlar si hay errores en la validación
            let hasErrors = false;
            
            // Obtener los valores de los campos
            const clave = document.getElementById('Clave').value.trim();
            const nombreCompleto = document.getElementById('NombreCompleto').value.trim();
            const departamento = document.getElementById('Departamento').value.trim();
            const sucursal = document.getElementById('Sucursal').value.trim();
            const fechaIngreso = document.getElementById('FechaIngreso').value.trim();
            const puesto = document.getElementById('Puesto').value.trim();
            const usuario = document.getElementById('usuario').value.trim();
            const pass = document.getElementById('pass').value.trim();
            const correo = document.getElementById('correo').value.trim();
            
            // Limpiar mensajes de error previos
            clearErrors();
            
            // Validar que los campos no estén vacíos
            if (clave === '') {
                showError('Clave', 'La clave es obligatoria');
                hasErrors = true;
            }
            
            if (nombreCompleto === '') {
                showError('NombreCompleto', 'El nombre completo es obligatorio');
                hasErrors = true;
            }
            
            if (departamento === '' || departamento === null) {
                showError('Departamento', 'Debe seleccionar un departamento');
                hasErrors = true;
            }
            
            if (sucursal === '' || sucursal === null) {
                showError('Sucursal', 'Debe seleccionar una sucursal');
                hasErrors = true;
            }
            
            if (fechaIngreso === '') {
                showError('FechaIngreso', 'La fecha de ingreso es obligatoria');
                hasErrors = true;
            }
            
            if (puesto === '') {
                showError('Puesto', 'El puesto es obligatorio');
                hasErrors = true;
            }
            
            if (usuario === '') {
                showError('usuario', 'El usuario es obligatorio');
                hasErrors = true;
            }
            
            if (pass === '') {
                showError('pass', 'La contraseña es obligatoria');
                hasErrors = true;
            } else if (pass.length < 6) {
                showError('pass', 'La contraseña debe tener al menos 6 caracteres');
                hasErrors = true;
            }
            
            if (correo === '') {
                showError('correo', 'El correo electrónico es obligatorio');
                hasErrors = true;
            } else if (!isValidEmail(correo)) {
                showError('correo', 'El formato del correo electrónico no es válido');
                hasErrors = true;
            }
            
            // Si hay errores, evitar que se envíe el formulario
            if (hasErrors) {
                event.preventDefault();
            }
        });
    }
    
    // Función para mostrar errores
    function showError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
        
        // Agregar clase de error al campo
        field.classList.add('is-invalid');
        
        // Insertar mensaje de error después del campo
        field.parentNode.appendChild(errorDiv);
    }
    
    // Función para limpiar errores
    function clearErrors() {
        // Eliminar todas las clases de error
        document.querySelectorAll('.is-invalid').forEach(function(field) {
            field.classList.remove('is-invalid');
        });
        
        // Eliminar todos los mensajes de error
        document.querySelectorAll('.invalid-feedback').forEach(function(errorDiv) {
            errorDiv.remove();
        });
    }
    
    // Función para validar formato de correo electrónico
    function isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }
});
