<?php
session_start();

// Incluir archivo de conexión
include 'conexion2.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];

    // Consulta SQL para verificar las credenciales y obtener todos los datos del usuario
    $sql = "SELECT u.Clave, u.NombreCompleto, u.usuario, u.pass, u.rol, u.correo, u.idPuesto, u.Sucursal
            FROM usuarios u
            WHERE u.usuario = ? AND u.pass = ?";
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

        // Verificar que el usuario tenga un rol asignado
        if (!empty($row['rol'])) {
            // Login exitoso - Guardar datos en sesión
            $_SESSION['usuario'] = $usuario;
            $_SESSION['clave_usuario'] = $row['Clave'];
            $_SESSION['nombre_completo'] = $row['NombreCompleto'];
            $_SESSION['rol'] = $row['rol'];
            $_SESSION['correo'] = $row['correo'];
            $_SESSION['id_puesto'] = $row['idPuesto'];
            $_SESSION['sucursal'] = $row['Sucursal'];
            $_SESSION['loggedin'] = true;

            $estado_login = "exitoso";

            // Preparar redirección (se ejecutará después de registrar la auditoría)
            $redireccion = "principal.php";
        } else {
            // Usuario válido pero sin rol asignado
            $estado_login = "sin_rol";
            $redireccion = "index.php?error=2"; // Error 2 para sin rol asignado
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