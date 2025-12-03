<?php
// Conexión a la base de datos
include("conexion2.php");

// Función para sanitizar entradas
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Configuración de paginación
$registros_por_pagina = isset($_GET['registros_por_pagina']) ? (int)$_GET['registros_por_pagina'] : 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$pagina_actual = max(1, $pagina_actual);
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Parámetros de búsqueda
$busqueda = isset($_GET['busqueda']) ? sanitize($_GET['busqueda']) : '';
$filtro_sucursal = isset($_GET['filtro_sucursal']) ? sanitize($_GET['filtro_sucursal']) : '';
$filtro_rol = isset($_GET['filtro_rol']) ? sanitize($_GET['filtro_rol']) : '';

// Manejo de acciones CRUD
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

// Procesar formulario para crear o actualizar usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'create' || $action === 'update')) {
    $clave = isset($_POST['Clave']) ? sanitize($_POST['Clave']) : '';
    $nombreCompleto = isset($_POST['NombreCompleto']) ? sanitize($_POST['NombreCompleto']) : '';
    $idPuesto = isset($_POST['idPuesto']) ? (int)$_POST['idPuesto'] : null;
    $sucursal = isset($_POST['Sucursal']) ? sanitize($_POST['Sucursal']) : '';
    
    $fechaIngreso = isset($_POST['FechaIngreso']) ? $_POST['FechaIngreso'] : '';
    if (!empty($fechaIngreso)) {
        $fechaObj = new DateTime($fechaIngreso);
    } else {
        $fechaObj = null;
    }
    
    $usuario = isset($_POST['usuario']) ? sanitize($_POST['usuario']) : '';
    $pass = isset($_POST['pass']) ? sanitize($_POST['pass']) : '';
    $correo = isset($_POST['correo']) ? sanitize($_POST['correo']) : '';
    $rol = isset($_POST['rol']) ? sanitize($_POST['rol']) : '';

    if ($action === 'create') {
        $sql = "INSERT INTO usuarios (Clave, NombreCompleto, idPuesto, Sucursal, FechaIngreso, usuario, pass, correo, rol) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = array($clave, $nombreCompleto, $idPuesto, $sucursal, $fechaObj, $usuario, $pass, $correo, $rol);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $error = "Error al crear usuario: " . print_r(sqlsrv_errors(), true);
        } else {
            $success = "Usuario creado exitosamente";
        }
    } elseif ($action === 'update') {
        $sql = "UPDATE usuarios SET NombreCompleto = ?, idPuesto = ?, Sucursal = ?, 
                FechaIngreso = ?, usuario = ?, pass = ?, correo = ?, rol = ? WHERE Clave = ?";
        $params = array($nombreCompleto, $idPuesto, $sucursal, $fechaObj, 
                        $usuario, $pass, $correo, $rol, $clave);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $error = "Error al actualizar usuario: " . print_r(sqlsrv_errors(), true);
        } else {
            $success = "Usuario actualizado exitosamente";
        }
    }
}

elseif ($action === 'delete' && isset($_GET['id'])) {
    $clave = sanitize($_GET['id']);
    $sql = "DELETE FROM usuarios WHERE Clave = ?";
    $params = array($clave);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        $error = "Error al eliminar usuario: " . print_r(sqlsrv_errors(), true);
    } else {
        $success = "Usuario eliminado exitosamente";
    }
}

// Obtener usuario específico para editar
$userToEdit = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $clave = sanitize($_GET['id']);
    $sql = "SELECT * FROM usuarios WHERE Clave = ?";
    $params = array($clave);
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt !== false) {
        $userToEdit = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }
}

// Construir consulta con filtros de búsqueda
$where_conditions = array();
$search_params = array();

if (!empty($busqueda)) {
    $where_conditions[] = "(u.Clave LIKE ? OR u.NombreCompleto LIKE ? OR u.usuario LIKE ? OR u.correo LIKE ? OR p.puesto LIKE ?)";
    $busqueda_wildcard = '%' . $busqueda . '%';
    $search_params = array_merge($search_params, [$busqueda_wildcard, $busqueda_wildcard, $busqueda_wildcard, $busqueda_wildcard, $busqueda_wildcard]);
}

if (!empty($filtro_sucursal)) {
    $where_conditions[] = "u.Sucursal = ?";
    $search_params[] = $filtro_sucursal;
}

if (!empty($filtro_rol)) {
    $where_conditions[] = "u.rol = ?";
    $search_params[] = $filtro_rol;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Contar total de usuarios para la paginación (con filtros)
$sql_count = "SELECT COUNT(*) as total FROM usuarios u 
              LEFT JOIN puestos p ON u.idPuesto = p.Id 
              $where_clause";
$stmt_count = sqlsrv_query($conn, $sql_count, $search_params);
$total_registros = 0;

if ($stmt_count !== false) {
    $row_count = sqlsrv_fetch_array($stmt_count, SQLSRV_FETCH_ASSOC);
    $total_registros = $row_count['total'];
}

$total_paginas = ceil($total_registros / $registros_por_pagina);

// Obtener usuarios con paginación y filtros
$sql = "SELECT u.Clave, u.NombreCompleto, p.puesto, p.depto, u.Sucursal, 
        u.FechaIngreso, u.usuario, u.correo, u.rol, u.idPuesto 
        FROM usuarios u 
        LEFT JOIN puestos p ON u.idPuesto = p.Id 
        $where_clause
        ORDER BY u.Clave
        OFFSET ? ROWS
        FETCH NEXT ? ROWS ONLY";

$final_params = array_merge($search_params, [$offset, $registros_por_pagina]);
$stmt = sqlsrv_query($conn, $sql, $final_params);
$usuarios = array();

if ($stmt !== false) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $usuarios[] = $row;
    }
}

// Obtener todos los puestos para el select
$sql_puestos = "SELECT Id, puesto, depto FROM puestos ORDER BY puesto";
$stmt_puestos = sqlsrv_query($conn, $sql_puestos);
$puestos = array();

if ($stmt_puestos !== false) {
    while ($row = sqlsrv_fetch_array($stmt_puestos, SQLSRV_FETCH_ASSOC)) {
        $puestos[] = $row;
    }
}





// Función para generar URL con parámetros de paginación y búsqueda
function buildUrl($pagina = null, $action = '', $id = '') {
    global $busqueda, $filtro_sucursal, $filtro_rol, $registros_por_pagina, $pagina_actual;
    
    $params = array();
    
    if ($pagina !== null) {
        $params['pagina'] = $pagina;
    } else {
        $params['pagina'] = $pagina_actual;
    }
    
    if (!empty($busqueda)) {
        $params['busqueda'] = $busqueda;
    }
    
    if (!empty($filtro_sucursal)) {
        $params['filtro_sucursal'] = $filtro_sucursal;
    }
    
    if (!empty($filtro_rol)) {
        $params['filtro_rol'] = $filtro_rol;
    }
    
    if ($registros_por_pagina != 10) {
        $params['registros_por_pagina'] = $registros_por_pagina;
    }
    
    if (!empty($action)) {
        $params['action'] = $action;
    }
    
    if (!empty($id)) {
        $params['id'] = $id;
    }
    
    return '?' . http_build_query($params);
}
// Función específica para construir URLs de paginación
function buildPaginationUrl($pagina) {
    global $busqueda, $filtro_sucursal, $filtro_rol, $registros_por_pagina;
    
    $params = array();
    
    // Siempre incluir la página
    $params['pagina'] = $pagina;
    
    // Mantener los filtros de búsqueda actuales
    if (!empty($busqueda)) {
        $params['busqueda'] = $busqueda;
    }
    
    if (!empty($filtro_sucursal)) {
        $params['filtro_sucursal'] = $filtro_sucursal;
    }
    
    if (!empty($filtro_rol)) {
        $params['filtro_rol'] = $filtro_rol;
    }
    
    // Mantener la configuración de registros por página si no es el default
    if ($registros_por_pagina != 10) {
        $params['registros_por_pagina'] = $registros_por_pagina;
    }
    
    return '?' . http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <style>
        .pagination-info {
            font-size: 0.9em;
            color: #6c757d;
        }
        .pagination .page-link {
            color: #0d6efd;
        }
        .pagination .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .search-card {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        .search-results-info {
            font-style: italic;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1 class="mb-4">Gestión de Empleados</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Panel de Búsqueda -->
        <div class="card mb-4 search-card">
            <div class="card-header">
                <i class="bi bi-search"></i> Búsqueda y Filtros
            </div>
            <div class="card-body">
                <form method="get" action="" class="row g-3">
                    <div class="col-md-4">
                        <label for="busqueda" class="form-label">Búsqueda general</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="busqueda" name="busqueda" 
                                value="<?php echo htmlspecialchars($busqueda); ?>" 
                                placeholder="Buscar por nombre, clave, usuario, correo o puesto...">
                        </div>
                        <small class="form-text text-muted">Busca en: Clave, Nombre, Usuario, Correo y Puesto</small>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="filtro_sucursal" class="form-label">Filtrar por Sucursal</label>
                        <select class="form-select" id="filtro_sucursal" name="filtro_sucursal">
                            <option value="">Todas las sucursales</option>
                            <option value="Matriz" <?php echo $filtro_sucursal === 'Matriz' ? 'selected' : ''; ?>>Matriz</option>
                            <option value="Mazatlan" <?php echo $filtro_sucursal === 'Mazatlan' ? 'selected' : ''; ?>>Mazatlan</option>
                            <option value="Mochis" <?php echo $filtro_sucursal === 'Mochis' ? 'selected' : ''; ?>>Mochis</option>
                            <option value="Guasave" <?php echo $filtro_sucursal === 'Guasave' ? 'selected' : ''; ?>>Guasave</option>
                            <option value="SE" <?php echo $filtro_sucursal === 'SE' ? 'selected' : ''; ?>>SE</option>
                            <option value="Guamuchil" <?php echo $filtro_sucursal === 'Guamuchil' ? 'selected' : ''; ?>>Guamuchil</option>
                            <option value="LaCruz" <?php echo $filtro_sucursal === 'LaCruz' ? 'selected' : ''; ?>>LaCruz</option>
                            <option value="TRPMazatlan" <?php echo $filtro_sucursal === 'TRPMazatlan' ? 'selected' : ''; ?>>TRPMazatlan</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="filtro_rol" class="form-label">Filtrar por Rol</label>
                        <select class="form-select" id="filtro_rol" name="filtro_rol">
                            <option value="">Todos los roles</option>
                            <option value="Administrador" <?php echo $filtro_rol === 'Administrador' ? 'selected' : ''; ?>>Administrador</option>
                            <option value="Supervisor" <?php echo $filtro_rol === 'Supervisor' ? 'selected' : ''; ?>>Supervisor</option>
                            <option value="Instructor" <?php echo $filtro_rol === 'Instructor' ? 'selected' : ''; ?>>Instructor</option>
                            <option value="Gerente" <?php echo $filtro_rol === 'Gerente' ? 'selected' : ''; ?>>Gerente</option>
                            <option value="Empleado" <?php echo $filtro_rol === 'Empleado' ? 'selected' : ''; ?>>Empleado</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="btn-group w-100" role="group">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                            <a href="?" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle"></i> Limpiar
                            </a>
                        </div>
                    </div>
                </form>
                
                <!-- Información de resultados de búsqueda -->
                <?php if (!empty($busqueda) || !empty($filtro_sucursal) || !empty($filtro_rol)): ?>
                    <div class="mt-3 search-results-info">
                        <strong>Resultados de búsqueda:</strong> Se encontraron <?php echo $total_registros; ?> empleado(s)
                        <?php if (!empty($busqueda)): ?>
                            que coinciden con "<?php echo htmlspecialchars($busqueda); ?>"
                        <?php endif; ?>
                        <?php if (!empty($filtro_sucursal)): ?>
                            en la sucursal "<?php echo htmlspecialchars($filtro_sucursal); ?>"
                        <?php endif; ?>
                        <?php if (!empty($filtro_rol)): ?>
                            con rol "<?php echo htmlspecialchars($filtro_rol); ?>"
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Formulario para crear/editar usuario -->
        <div class="card mb-4">
            <div class="card-header">
                <?php echo $action === 'edit' ? 'Editar Usuario' : 'Crear Nuevo Usuario'; ?>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo buildUrl($pagina_actual, $action === 'edit' ? 'update' : 'create'); ?>">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="Clave" class="form-label">Clave</label>
                            <input type="text" class="form-control" id="Clave" name="Clave" 
                                value="<?php echo $userToEdit ? $userToEdit['Clave'] : ''; ?>" 
                                <?php echo $action === 'edit' ? 'readonly' : ''; ?> required>
                        </div>
                        <div class="col-md-6">
                            <label for="NombreCompleto" class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" id="NombreCompleto" name="NombreCompleto" 
                                value="<?php echo $userToEdit ? $userToEdit['NombreCompleto'] : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="idPuesto" class="form-label">Puesto</label>
                            <select class="form-select" id="idPuesto" name="idPuesto" required>
                                <option value="" disabled <?php echo !$userToEdit ? 'selected' : ''; ?>>Seleccione un puesto</option>
                                <?php foreach ($puestos as $puesto): ?>
                                    <option value="<?php echo $puesto['Id']; ?>" 
                                        <?php echo $userToEdit && $userToEdit['idPuesto'] == $puesto['Id'] ? 'selected' : ''; ?>>
                                        <?php echo $puesto['puesto'] . ' - ' . $puesto['depto']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="Sucursal" class="form-label">Sucursal</label>
                            <select class="form-select" id="Sucursal" name="Sucursal" required>
                                <option value="" disabled <?php echo !$userToEdit ? 'selected' : ''; ?>>Seleccione una sucursal</option>
                                <option value="Matriz" <?php echo $userToEdit && $userToEdit['Sucursal'] === 'Matriz' ? 'selected' : ''; ?>>Matriz</option>
                                <option value="Mazatlan" <?php echo $userToEdit && $userToEdit['Sucursal'] === 'Mazatlan' ? 'selected' : ''; ?>>Mazatlan</option>
                                <option value="Mochis" <?php echo $userToEdit && $userToEdit['Sucursal'] === 'Mochis' ? 'selected' : ''; ?>>Mochis</option>
                                <option value="Guasave" <?php echo $userToEdit && $userToEdit['Sucursal'] === 'Guasave' ? 'selected' : ''; ?>>Guasave</option>
                                <option value="SE" <?php echo $userToEdit && $userToEdit['Sucursal'] === 'SE' ? 'selected' : ''; ?>>SE</option>
                                <option value="Guamuchil" <?php echo $userToEdit && $userToEdit['Sucursal'] === 'Guamuchil' ? 'selected' : ''; ?>>Guamuchil</option>
                                <option value="LaCruz" <?php echo $userToEdit && $userToEdit['Sucursal'] === 'LaCruz' ? 'selected' : ''; ?>>LaCruz</option>
                                <option value="TRPMazatlan" <?php echo $userToEdit && $userToEdit['Sucursal'] === 'TRPMazatlan' ? 'selected' : ''; ?>>TRPMazatlan</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="FechaIngreso" class="form-label">Fecha de Ingreso</label>
                            <input type="date" class="form-control" id="FechaIngreso" name="FechaIngreso" 
                                value="<?php echo $userToEdit && $userToEdit['FechaIngreso'] ? date('Y-m-d', strtotime($userToEdit['FechaIngreso']->format('Y-m-d'))) : ''; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="rol" class="form-label">Rol</label>
                            <select class="form-select" id="rol" name="rol" required>
                                <option value="" disabled <?php echo !$userToEdit ? 'selected' : ''; ?>>Seleccione un rol</option>
                                <option value="Administrador" <?php echo $userToEdit && $userToEdit['rol'] === 'Administrador' ? 'selected' : ''; ?>>Administrador</option>
                                <option value="Supervisor" <?php echo $userToEdit && $userToEdit['rol'] === 'Supervisor' ? 'selected' : ''; ?>>Supervisor</option>
                                <option value="Instructor" <?php echo $userToEdit && $userToEdit['rol'] === 'Instructor' ? 'selected' : ''; ?>>Instructor</option>
                                <option value="Gerente" <?php echo $userToEdit && $userToEdit['rol'] === 'Gerente' ? 'selected' : ''; ?>>Gerente</option>
                                <option value="Empleado" <?php echo $userToEdit && $userToEdit['rol'] === 'Empleado' ? 'selected' : ''; ?>>Empleado</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="usuario" class="form-label">Usuario</label>
                            <input type="text" class="form-control" id="usuario" name="usuario" 
                                value="<?php echo $userToEdit ? $userToEdit['usuario'] : ''; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="pass" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="pass" name="pass" 
                                value="<?php echo $userToEdit ? $userToEdit['pass'] : ''; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="correo" class="form-label">Correo</label>
                            <input type="email" class="form-control" id="correo" name="correo" 
                                value="<?php echo $userToEdit ? $userToEdit['correo'] : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3 text-end">
                        <a href="<?php echo buildUrl($pagina_actual); ?>" class="btn btn-secondary me-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <?php echo $action === 'edit' ? 'Actualizar' : 'Crear'; ?> Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla de usuarios -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Lista de Usuarios</span>
                <div class="pagination-info">
                    <?php 
                    $inicio = ($pagina_actual - 1) * $registros_por_pagina + 1;
                    $fin = min($pagina_actual * $registros_por_pagina, $total_registros);
                    echo "Mostrando $inicio-$fin de $total_registros registros";
                    ?>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Clave</th>
                                <th>Nombre</th>
                                <th>Puesto</th>
                                <th>Departamento</th>
                                <th>Sucursal</th>
                                <th>Usuario</th>
                                <th>Correo</th>
                                <th>Rol</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($usuarios)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">
                                        <?php if (!empty($busqueda) || !empty($filtro_sucursal) || !empty($filtro_rol)): ?>
                                            No se encontraron empleados que coincidan con los criterios de búsqueda.
                                            <br><a href="?" class="btn btn-sm btn-outline-primary mt-2">Ver todos los empleados</a>
                                        <?php else: ?>
                                            No hay usuarios registrados
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <tr>
                                        <td><?php echo $usuario['Clave']; ?></td>
                                        <td><?php echo $usuario['NombreCompleto']; ?></td>
                                        <td><?php echo $usuario['puesto']; ?></td>
                                        <td><?php echo $usuario['depto']; ?></td>
                                        <td><?php echo $usuario['Sucursal']; ?></td>
                                        <td><?php echo $usuario['usuario']; ?></td>
                                        <td><?php echo $usuario['correo']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php
                                                echo $usuario['rol'] === 'Administrador' ? 'danger' :
                                                    ($usuario['rol'] === 'Supervisor' ? 'warning' :
                                                    ($usuario['rol'] === 'Instructor' ? 'primary' :
                                                    ($usuario['rol'] === 'Gerente' ? 'success' : 'info')));
                                            ?>">
                                                <?php echo $usuario['rol']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?php echo buildUrl($pagina_actual, 'edit', $usuario['Clave']); ?>" class="btn btn-sm btn-warning me-1">
                                                <i class="bi bi-pencil"></i> Editar
                                            </a>
                                            <a href="<?php echo buildUrl($pagina_actual, 'delete', $usuario['Clave']); ?>" class="btn btn-sm btn-danger" 
                                               onclick="return confirm('¿Está seguro de eliminar este usuario?')">
                                                <i class="bi bi-trash"></i> Eliminar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                 <!-- Paginación -->
                <?php if ($total_paginas > 1): ?>
                    <nav aria-label="Paginación de usuarios" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <!-- Botón Primera página -->
                            <?php if ($pagina_actual > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo buildPaginationUrl(1); ?>" title="Primera página">
                                        <i class="bi bi-chevron-double-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Botón Anterior -->
                            <?php if ($pagina_actual > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo buildPaginationUrl($pagina_actual - 1); ?>" title="Página anterior">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Números de página -->
                            <?php
                            $rango = 2; // Número de páginas a mostrar a cada lado de la página actual
                            $inicio_rango = max(1, $pagina_actual - $rango);
                            $fin_rango = min($total_paginas, $pagina_actual + $rango);

                            // Mostrar "..." si hay páginas antes del rango
                            if ($inicio_rango > 1) {
                                echo '<li class="page-item"><a class="page-link" href="' . buildPaginationUrl(1) . '">1</a></li>';
                                if ($inicio_rango > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }

                            // Mostrar páginas en el rango
                            for ($i = $inicio_rango; $i <= $fin_rango; $i++) {
                                $active_class = ($i == $pagina_actual) ? 'active' : '';
                                echo '<li class="page-item ' . $active_class . '">';
                                echo '<a class="page-link" href="' . buildPaginationUrl($i) . '">' . $i . '</a>';
                                echo '</li>';
                            }

                            // Mostrar "..." si hay páginas después del rango
                            if ($fin_rango < $total_paginas) {
                                if ($fin_rango < $total_paginas - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="' . buildPaginationUrl($total_paginas) . '">' . $total_paginas . '</a></li>';
                            }
                            ?>

                            <!-- Botón Siguiente -->
                            <?php if ($pagina_actual < $total_paginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo buildPaginationUrl($pagina_actual + 1); ?>" title="Página siguiente">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Botón Última página -->
                            <?php if ($pagina_actual < $total_paginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo buildPaginationUrl($total_paginas); ?>" title="Última página">
                                        <i class="bi bi-chevron-double-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>

                    <!-- Selector de registros por página -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="pagination-info">
                            Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?>
                        </div>
                        <div>
                            <select class="form-select form-select-sm" style="width: auto;" onchange="cambiarRegistrosPorPagina(this.value)">
                                <option value="10" <?php echo $registros_por_pagina == 10 ? 'selected' : ''; ?>>10 por página</option>
                                <option value="25" <?php echo $registros_por_pagina == 25 ? 'selected' : ''; ?>>25 por página</option>
                                <option value="50" <?php echo $registros_por_pagina == 50 ? 'selected' : ''; ?>>50 por página</option>
                                <option value="100" <?php echo $registros_por_pagina == 100 ? 'selected' : ''; ?>>100 por página</option>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="validacion-usuarios.js"></script>
    <script>
        function cambiarRegistrosPorPagina(registros) {
            const url = new URL(window.location);
            url.searchParams.set('registros_por_pagina', registros);
            url.searchParams.set('pagina', '1'); // Volver a la primera página
            window.location.href = url.toString();
        }
    </script>
</body>
</html>