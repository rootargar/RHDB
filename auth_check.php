<?php
/**
 * Middleware de Autenticación y Validación de Roles
 * Este archivo debe incluirse al inicio de cada página que requiera autenticación
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir configuración de roles
require_once __DIR__ . '/roles_config.php';

/**
 * Verifica si el usuario está autenticado
 *
 * @return bool true si está autenticado, false si no
 */
function esta_autenticado() {
    return isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && isset($_SESSION['usuario']);
}

/**
 * Obtiene el rol del usuario actual
 *
 * @return string|null El rol del usuario o null si no está definido
 */
function get_rol_usuario() {
    return isset($_SESSION['rol']) ? $_SESSION['rol'] : null;
}

/**
 * Obtiene el nombre de usuario actual
 *
 * @return string|null El nombre de usuario o null si no está autenticado
 */
function get_usuario_actual() {
    return isset($_SESSION['usuario']) ? $_SESSION['usuario'] : null;
}

/**
 * Obtiene el nombre completo del usuario actual
 *
 * @return string|null El nombre completo o null si no está definido
 */
function get_nombre_completo_usuario() {
    return isset($_SESSION['nombre_completo']) ? $_SESSION['nombre_completo'] : null;
}

/**
 * Redirige al usuario a la página de login si no está autenticado
 *
 * @param string $mensaje_error Mensaje de error opcional
 */
function requerir_autenticacion($mensaje_error = '') {
    if (!esta_autenticado()) {
        // Guardar la página que intentaba acceder
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];

        // Destruir sesión
        session_destroy();

        // Redirigir a login
        $redirect = 'index.php';
        if ($mensaje_error) {
            $redirect .= '?error=' . urlencode($mensaje_error);
        }
        header("Location: $redirect");
        exit();
    }
}

/**
 * Verifica que el usuario tenga el rol adecuado para acceder a un módulo
 *
 * @param string $modulo El módulo que se quiere acceder
 * @param bool $redirigir Si debe redirigir en caso de no tener permiso
 * @return bool true si tiene permiso, false si no
 */
function verificar_permiso($modulo, $redirigir = true) {
    // Primero verificar autenticación
    requerir_autenticacion();

    $rol = get_rol_usuario();

    // Si no tiene rol asignado, denegar acceso
    if (!$rol) {
        if ($redirigir) {
            redirigir_sin_permiso('No tiene un rol asignado');
        }
        return false;
    }

    // Verificar si tiene permiso para el módulo
    if (!tiene_permiso($rol, $modulo)) {
        if ($redirigir) {
            redirigir_sin_permiso('No tiene permisos para acceder a este módulo');
        }
        return false;
    }

    return true;
}

/**
 * Verifica que el usuario tenga permiso para realizar una acción específica
 *
 * @param string $modulo El módulo
 * @param string $accion La acción a realizar
 * @param bool $redirigir Si debe redirigir en caso de no tener permiso
 * @return bool true si tiene permiso, false si no
 */
function verificar_permiso_accion($modulo, $accion, $redirigir = true) {
    // Primero verificar autenticación
    requerir_autenticacion();

    $rol = get_rol_usuario();

    if (!$rol) {
        if ($redirigir) {
            redirigir_sin_permiso('No tiene un rol asignado');
        }
        return false;
    }

    if (!tiene_permiso_accion($rol, $modulo, $accion)) {
        if ($redirigir) {
            redirigir_sin_permiso('No tiene permisos para realizar esta acción');
        }
        return false;
    }

    return true;
}

/**
 * Redirige al usuario a una página de error cuando no tiene permisos
 *
 * @param string $mensaje Mensaje de error
 */
function redirigir_sin_permiso($mensaje = 'No tiene permisos para acceder a esta página') {
    $_SESSION['error_permiso'] = $mensaje;
    header("Location: principal.php?error=sin_permiso");
    exit();
}

/**
 * Verifica si el usuario es administrador
 *
 * @return bool true si es administrador, false si no
 */
function es_administrador() {
    $rol = get_rol_usuario();
    return $rol === ROL_ADMINISTRADOR;
}

/**
 * Verifica si el usuario es supervisor
 *
 * @return bool true si es supervisor, false si no
 */
function es_supervisor() {
    $rol = get_rol_usuario();
    return $rol === ROL_SUPERVISOR;
}

/**
 * Verifica si el usuario es instructor
 *
 * @return bool true si es instructor, false si no
 */
function es_instructor() {
    $rol = get_rol_usuario();
    return $rol === ROL_INSTRUCTOR;
}

/**
 * Verifica si el usuario es gerente
 *
 * @return bool true si es gerente, false si no
 */
function es_gerente() {
    $rol = get_rol_usuario();
    return $rol === ROL_GERENTE;
}

/**
 * Verifica si el usuario es empleado
 *
 * @return bool true si es empleado, false si no
 */
function es_empleado() {
    $rol = get_rol_usuario();
    return $rol === ROL_EMPLEADO;
}

/**
 * Registra un intento de acceso no autorizado en log
 *
 * @param string $modulo El módulo al que intentó acceder
 */
function registrar_acceso_denegado($modulo) {
    $usuario = get_usuario_actual();
    $rol = get_rol_usuario();
    $ip = $_SERVER['REMOTE_ADDR'];
    $fecha = date('Y-m-d H:i:s');

    // Podrías guardar esto en una tabla de auditoría
    $log_message = "[$fecha] Acceso denegado - Usuario: $usuario, Rol: $rol, Módulo: $modulo, IP: $ip\n";

    // Guardar en archivo de log (opcional)
    error_log($log_message, 3, __DIR__ . '/logs/accesos_denegados.log');
}

/**
 * Función auxiliar para mostrar mensajes de error de permisos
 */
function mostrar_error_permiso() {
    if (isset($_SESSION['error_permiso'])) {
        $mensaje = htmlspecialchars($_SESSION['error_permiso']);
        unset($_SESSION['error_permiso']);
        return $mensaje;
    }
    return null;
}

/**
 * Verifica si el usuario actual puede ver todos los registros o solo los suyos
 * Útil para módulo de certificaciones donde empleados solo ven lo suyo
 *
 * @param string $clave_usuario La clave del registro a verificar
 * @return bool true si puede ver, false si no
 */
function puede_ver_registro($clave_usuario) {
    $rol = get_rol_usuario();

    // Administradores, supervisores, gerentes e instructores ven todo
    if (in_array($rol, [ROL_ADMINISTRADOR, ROL_SUPERVISOR, ROL_GERENTE, ROL_INSTRUCTOR])) {
        return true;
    }

    // Empleados solo ven sus propios registros
    if ($rol === ROL_EMPLEADO) {
        // Comparar con la clave de usuario en sesión
        $clave_actual = isset($_SESSION['clave_usuario']) ? $_SESSION['clave_usuario'] : null;
        return $clave_usuario === $clave_actual;
    }

    return false;
}
?>
