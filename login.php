<?php
session_start();

// Incluir archivo de conexión
include 'conexion2.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];

    // Consulta SQL para verificar las credenciales Y el rol
    $sql = "SELECT usuario, pass, rol FROM usuarios WHERE usuario = ? AND pass = ?";
    $params = array($usuario, $password);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    // Variable para guardar el estado del login
    $estado_login = "fallido";

    if (sqlsrv_has_rows($stmt)) {
        // Obtener los datos del usuario
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        
        // Verificar si el usuario tiene rol de administrador
        if ($row['rol'] == 'administrador') {
            // Login exitoso para administrador
            $_SESSION['usuario'] = $usuario;
            $_SESSION['rol'] = $row['rol'];
            $_SESSION['loggedin'] = true;
            
            $estado_login = "exitoso";
            
            // Preparar redirección (se ejecutará después de registrar la auditoría)
            $redireccion = "principal.php";
        } else {
            // Usuario válido pero sin permisos de administrador
            $estado_login = "acceso_denegado";
            $redireccion = "index.php?error=2"; // Error 2 para acceso denegado
        }
    } else {
        // Login fallido - credenciales incorrectas
        $estado_login = "fallido";
        $redireccion = "index.php?error=1"; // Error 1 para credenciales incorrectas
    }
    
    // Usamos CONVERT para dar formato a la fecha (103 es el código para dd/mm/yyyy)
    $sql_auditoria = "INSERT INTO auditoria (usuario, fecha_hora, estado) 
                      VALUES (?, CONVERT(VARCHAR, GETDATE(), 103) + ' ' + 
                             CONVERT(VARCHAR, GETDATE(), 108), ?)";
    $params_auditoria = array($usuario, $estado_login);
    $stmt_auditoria = sqlsrv_query($conn, $sql_auditoria, $params_auditoria);
    
    if ($stmt_auditoria === false) {
        // Imprimir los errores para diagnosticar en desarrollo
        echo "Error al registrar auditoría:<br>";
        echo print_r(sqlsrv_errors(), true);
        exit();
    }
    
    // Redirigir después de registrar la auditoría
    header("Location: " . $redireccion);
    exit();
}
?>