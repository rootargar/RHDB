<?php
/**
 * Configuración de Roles y Permisos del Sistema
 * Define los roles disponibles y los permisos de acceso a cada módulo
 */

// Definición de roles del sistema
define('ROL_ADMINISTRADOR', 'Administrador');
define('ROL_SUPERVISOR', 'Supervisor');
define('ROL_INSTRUCTOR', 'Instructor');
define('ROL_GERENTE', 'Gerente');
define('ROL_EMPLEADO', 'Empleado');

// Array de todos los roles válidos
$roles_validos = [
    ROL_ADMINISTRADOR,
    ROL_SUPERVISOR,
    ROL_INSTRUCTOR,
    ROL_GERENTE,
    ROL_EMPLEADO
];

/**
 * Configuración de permisos por módulo
 * true = tiene acceso, false = no tiene acceso
 */
$permisos = [
    // Módulo de empleados/usuarios
    'empleados' => [
        ROL_ADMINISTRADOR => true,  // Acceso total
        ROL_SUPERVISOR => true,     // Acceso limitado
        ROL_INSTRUCTOR => false,
        ROL_GERENTE => false,
        ROL_EMPLEADO => false
    ],

    // Módulo de cursos
    'cursos' => [
        ROL_ADMINISTRADOR => true,
        ROL_SUPERVISOR => true,
        ROL_INSTRUCTOR => true,     // Solo catálogo
        ROL_GERENTE => false,
        ROL_EMPLEADO => false
    ],

    // Módulo de puestos
    'puestos' => [
        ROL_ADMINISTRADOR => true,
        ROL_SUPERVISOR => true,
        ROL_INSTRUCTOR => false,
        ROL_GERENTE => false,
        ROL_EMPLEADO => false
    ],

    // Módulo de cursos por puesto
    'cursoxpuesto' => [
        ROL_ADMINISTRADOR => true,
        ROL_SUPERVISOR => true,
        ROL_INSTRUCTOR => false,
        ROL_GERENTE => false,
        ROL_EMPLEADO => false
    ],

    // Módulo de planeación
    'planeacion' => [
        ROL_ADMINISTRADOR => true,
        ROL_SUPERVISOR => true,
        ROL_INSTRUCTOR => true,     // Puede programar
        ROL_GERENTE => false,
        ROL_EMPLEADO => false
    ],

    // Módulo de asignar participantes
    'capacitacion' => [
        ROL_ADMINISTRADOR => true,
        ROL_SUPERVISOR => true,
        ROL_INSTRUCTOR => true,     // Capturar capacitaciones
        ROL_GERENTE => false,
        ROL_EMPLEADO => false
    ],

    // Módulo de certificaciones
    'certificados' => [
        ROL_ADMINISTRADOR => true,
        ROL_SUPERVISOR => true,
        ROL_INSTRUCTOR => true,
        ROL_GERENTE => true,
        ROL_EMPLEADO => true        // Solo ver las propias
    ],

    // Módulo de reportes
    'reportes' => [
        ROL_ADMINISTRADOR => true,  // Todos los reportes
        ROL_SUPERVISOR => true,     // Todos los reportes
        ROL_INSTRUCTOR => false,
        ROL_GERENTE => true,        // Reportes generales
        ROL_EMPLEADO => false
    ],

    // Módulo de actividades
    'actividades' => [
        ROL_ADMINISTRADOR => true,
        ROL_SUPERVISOR => true,
        ROL_INSTRUCTOR => true,
        ROL_GERENTE => false,
        ROL_EMPLEADO => false
    ]
];

/**
 * Permisos específicos para acciones dentro de módulos
 */
$permisos_acciones = [
    'usuarios' => [
        'crear' => [ROL_ADMINISTRADOR, ROL_SUPERVISOR],
        'editar' => [ROL_ADMINISTRADOR, ROL_SUPERVISOR],
        'eliminar' => [ROL_ADMINISTRADOR],
        'ver' => [ROL_ADMINISTRADOR, ROL_SUPERVISOR]
    ],
    'cursos' => [
        'crear' => [ROL_ADMINISTRADOR, ROL_SUPERVISOR],
        'editar' => [ROL_ADMINISTRADOR, ROL_SUPERVISOR],
        'eliminar' => [ROL_ADMINISTRADOR],
        'ver' => [ROL_ADMINISTRADOR, ROL_SUPERVISOR, ROL_INSTRUCTOR]
    ],
    'certificados' => [
        'crear' => [ROL_ADMINISTRADOR, ROL_SUPERVISOR, ROL_INSTRUCTOR],
        'editar' => [ROL_ADMINISTRADOR, ROL_SUPERVISOR, ROL_INSTRUCTOR],
        'eliminar' => [ROL_ADMINISTRADOR, ROL_SUPERVISOR],
        'ver_todos' => [ROL_ADMINISTRADOR, ROL_SUPERVISOR, ROL_GERENTE, ROL_INSTRUCTOR],
        'ver_propios' => [ROL_EMPLEADO]
    ],
    'reportes' => [
        'generales' => [ROL_ADMINISTRADOR, ROL_SUPERVISOR, ROL_GERENTE],
        'detallados' => [ROL_ADMINISTRADOR, ROL_SUPERVISOR]
    ]
];

/**
 * Verifica si un rol tiene permiso para acceder a un módulo
 *
 * @param string $rol El rol del usuario
 * @param string $modulo El módulo a verificar
 * @return bool true si tiene permiso, false si no
 */
function tiene_permiso($rol, $modulo) {
    global $permisos;

    if (!isset($permisos[$modulo])) {
        return false;
    }

    return isset($permisos[$modulo][$rol]) && $permisos[$modulo][$rol] === true;
}

/**
 * Verifica si un rol tiene permiso para realizar una acción específica
 *
 * @param string $rol El rol del usuario
 * @param string $modulo El módulo
 * @param string $accion La acción a verificar
 * @return bool true si tiene permiso, false si no
 */
function tiene_permiso_accion($rol, $modulo, $accion) {
    global $permisos_acciones;

    if (!isset($permisos_acciones[$modulo][$accion])) {
        return false;
    }

    return in_array($rol, $permisos_acciones[$modulo][$accion]);
}

/**
 * Obtiene el nombre descriptivo de un rol
 *
 * @param string $rol El rol
 * @return string Descripción del rol
 */
function get_descripcion_rol($rol) {
    $descripciones = [
        ROL_ADMINISTRADOR => 'Acceso total al sistema',
        ROL_SUPERVISOR => 'Acceso a reportes y gestión de usuarios limitada',
        ROL_INSTRUCTOR => 'Acceso a cursos y captura de capacitaciones',
        ROL_GERENTE => 'Acceso a reportes generales',
        ROL_EMPLEADO => 'Acceso solo a sus propias capacitaciones'
    ];

    return isset($descripciones[$rol]) ? $descripciones[$rol] : 'Sin descripción';
}

/**
 * Valida si un rol es válido
 *
 * @param string $rol El rol a validar
 * @return bool true si es válido, false si no
 */
function es_rol_valido($rol) {
    global $roles_validos;
    return in_array($rol, $roles_validos);
}
?>
