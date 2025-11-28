<?php

// Configuración de la conexión a la base de datos SQL Server
include("conexion2.php");

// Variable para almacenar el ID del empleado seleccionado (si se ha filtrado)
$empleado_seleccionado = isset($_POST['id_empleado']) ? $_POST['id_empleado'] : '';

// Función para exportar a Excel
if (isset($_POST['exportar_excel'])) {
    // Establecer encabezados para descarga de Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="Reporte_Cursos_Faltantes_' . date('Y-m-d') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Crear la tabla HTML para Excel (sin los botones de acción)
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Reporte de Cursos Faltantes</title>
        <style>
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #000; padding: 5px; text-align: left; }
            th { background-color: #f2f2f2; }
        </style>
    </head>
    <body>
        <h2>Reporte de Cursos Faltantes por Empleado - ' . date('d/m/Y') . '</h2>';
        
    // Si hay un empleado seleccionado, mostrar sus datos
    if (!empty($empleado_seleccionado)) {
        // Obtener información del empleado
        $sql_empleado = "SELECT u.Clave AS IdEmp, u.NombreCompleto AS Empleado, p.puesto AS Puesto, p.depto AS Departamento 
                         FROM usuarios u 
                         LEFT JOIN puestos p ON u.idPuesto = p.Id 
                         WHERE u.Clave = ?";
        $params_empleado = array($empleado_seleccionado);
        $stmt_empleado = sqlsrv_query($conn, $sql_empleado, $params_empleado);
        
        if ($stmt_empleado !== false && $row_empleado = sqlsrv_fetch_array($stmt_empleado, SQLSRV_FETCH_ASSOC)) {
            echo '<div style="margin-bottom: 15px; padding: 10px; background-color: #f8f9fa; border: 1px solid #ddd;">
                <p><strong>ID Empleado:</strong> ' . $row_empleado['IdEmp'] . '</p>
                <p><strong>Nombre:</strong> ' . $row_empleado['Empleado'] . '</p>
                <p><strong>Puesto:</strong> ' . $row_empleado['Puesto'] . '</p>
                <p><strong>Departamento:</strong> ' . $row_empleado['Departamento'] . '</p>
            </div>';
        }
    }
    
    echo '<table>
            <thead>
                <tr>';
    
    // Si no hay empleado seleccionado, mostrar columnas de empleado
    if (empty($empleado_seleccionado)) {
        echo '<th>ID Empleado</th>
              <th>Empleado</th>
              <th>Puesto</th>
              <th>Departamento</th>';
    }
    
    echo '      <th>ID Curso</th>
                <th>Nombre del Curso</th>
                <th>Área</th>
                <th>Departamento del Curso</th>
                <th>Estado</th>
            </tr>
            </thead>
            <tbody>';
    
    if (empty($empleado_seleccionado)) {
        // Si no hay empleado seleccionado, obtener todos los empleados
        $sql_empleados = "SELECT u.Clave AS IdEmp, u.NombreCompleto AS Empleado, p.puesto AS Puesto, p.depto AS Departamento 
                          FROM usuarios u 
                          LEFT JOIN puestos p ON u.idPuesto = p.Id 
                          ORDER BY u.NombreCompleto";
        $stmt_empleados = sqlsrv_query($conn, $sql_empleados);
        
        if ($stmt_empleados !== false) {
            while ($empleado = sqlsrv_fetch_array($stmt_empleados, SQLSRV_FETCH_ASSOC)) {
                // Para cada empleado, obtener sus cursos faltantes
                mostrarCursosFaltantes($conn, $empleado['IdEmp'], $empleado, true, true);
            }
            sqlsrv_free_stmt($stmt_empleados);
        }
    } else {
        // Si hay un empleado seleccionado, obtener solo sus cursos faltantes
        $empleado_info = null;
        $sql_empleado = "SELECT u.Clave AS IdEmp, u.NombreCompleto AS Empleado, p.puesto AS Puesto, p.depto AS Departamento 
                          FROM usuarios u 
                          LEFT JOIN puestos p ON u.idPuesto = p.Id 
                          WHERE u.Clave = ?";
        $params_empleado = array($empleado_seleccionado);
        $stmt_empleado = sqlsrv_query($conn, $sql_empleado, $params_empleado);
        
        if ($stmt_empleado !== false && $row_empleado = sqlsrv_fetch_array($stmt_empleado, SQLSRV_FETCH_ASSOC)) {
            $empleado_info = $row_empleado;
        }
        
        if ($stmt_empleado !== false) {
            sqlsrv_free_stmt($stmt_empleado);
        }
        
        if ($empleado_info) {
            mostrarCursosFaltantes($conn, $empleado_seleccionado, $empleado_info, false, true);
        }
    }
    
    echo '</tbody></table></body></html>';
    exit();
}

// Función para mostrar los cursos faltantes de un empleado
function mostrarCursosFaltantes($conn, $idEmpleado, $empleado_info, $mostrarEmpleado = true, $esExcel = false) {
    // 1. Obtener todos los cursos que debe tomar según su puesto
    $sql_cursos_requeridos = "SELECT c.Id AS IdCurso, c.NombreCurso, c.Area, p.depto AS Departamento 
                             FROM cursoxpuesto cp
                             LEFT JOIN cursos c ON cp.IdCurso = c.Id
                             LEFT JOIN puestos p ON cp.idPuesto = p.Id
                             LEFT JOIN usuarios u ON u.idPuesto = p.Id
                             WHERE u.Clave = ? 
                             ORDER BY c.NombreCurso";
    $params_req = array($idEmpleado);
    $stmt_req = sqlsrv_query($conn, $sql_cursos_requeridos, $params_req);
    
    if ($stmt_req === false) {
        return;
    }
    
    // 2. Obtener los cursos que ya ha completado
    $sql_completados = "SELECT c.Id AS IdCurso
                        FROM capacitaciones cap
                        INNER JOIN plancursos pc ON cap.IdPlan = pc.IdPlan
                        INNER JOIN cursos c ON pc.IdCursoBase = c.Id
                        WHERE cap.IdEmp = ? 
                        AND pc.Estado = 'Completado' 
                        AND cap.Asistio = 'Si'";
    $params_comp = array($idEmpleado);
    $stmt_comp = sqlsrv_query($conn, $sql_completados, $params_comp);
    
    if ($stmt_comp === false) {
        sqlsrv_free_stmt($stmt_req);
        return;
    }
    
    // Crear un array con los IDs de los cursos completados para facilitar la búsqueda
    $cursos_completados = array();
    while ($comp = sqlsrv_fetch_array($stmt_comp, SQLSRV_FETCH_ASSOC)) {
        $cursos_completados[] = $comp['IdCurso'];
    }
    sqlsrv_free_stmt($stmt_comp);
    
    // 3. Recorrer los cursos requeridos y verificar cuáles faltan
    $hayFaltantes = false;
    
    while ($req = sqlsrv_fetch_array($stmt_req, SQLSRV_FETCH_ASSOC)) {
        // Si el curso no está en la lista de completados, es un curso faltante
        if (!in_array($req['IdCurso'], $cursos_completados)) {
            $hayFaltantes = true;
            
            echo '<tr>';
            
            // Mostrar datos del empleado si es necesario
            if ($mostrarEmpleado) {
                echo '<td>' . $empleado_info['IdEmp'] . '</td>';
                echo '<td>' . $empleado_info['Empleado'] . '</td>';
                echo '<td>' . $empleado_info['Puesto'] . '</td>';
                echo '<td>' . $empleado_info['Departamento'] . '</td>';
            }
            
            echo '<td>' . $req['IdCurso'] . '</td>';
            echo '<td>' . $req['NombreCurso'] . '</td>';
            echo '<td>' . ($req['Area'] ?? 'N/A') . '</td>';
            echo '<td>' . $req['Departamento'] . '</td>';
            
            // Verificar si el curso está programado pero no completado
            $sql_programado = "SELECT pc.Estado 
                              FROM capacitaciones cap
                              INNER JOIN plancursos pc ON cap.IdPlan = pc.IdPlan
                              INNER JOIN cursos c ON pc.IdCursoBase = c.Id
                              WHERE cap.IdEmp = ? AND c.Id = ?";
            $params_prog = array($idEmpleado, $req['IdCurso']);
            $stmt_prog = sqlsrv_query($conn, $sql_programado, $params_prog);
            
            $estado = 'Pendiente';
            if ($stmt_prog !== false && $prog = sqlsrv_fetch_array($stmt_prog, SQLSRV_FETCH_ASSOC)) {
                $estado = $prog['Estado'];
            }
            
            if ($stmt_prog !== false) {
                sqlsrv_free_stmt($stmt_prog);
            }
            
            if ($esExcel) {
                echo '<td>' . $estado . '</td>';
            } else {
                $clase = '';
                switch ($estado) {
                    case 'Programado':
                        $clase = 'badge-warning';
                        break;
                    case 'En Proceso':
                        $clase = 'badge-info';
                        break;
                    case 'Cancelado':
                        $clase = 'badge-danger';
                        break;
                    default:
                        $clase = 'badge-secondary';
                }
                echo '<td><span class="' . $clase . '">' . $estado . '</span></td>';
            }
            
            echo '</tr>';
        }
    }
    
    // Si no hay cursos faltantes y es un reporte individual, mostrar mensaje
    if (!$hayFaltantes && !$mostrarEmpleado) {
        $cols = $mostrarEmpleado ? 9 : 5;
        echo '<tr><td colspan="' . $cols . '" class="text-center">Este empleado ha completado todos los cursos requeridos para su puesto.</td></tr>';
    }
    
    sqlsrv_free_stmt($stmt_req);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cursos Faltantes por Empleado</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            padding: 0;
            margin: 0;
        }
        .card {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #f8f9fa;
            padding: 15px;
            font-weight: bold;
        }
        .btn-export {
            margin-right: 5px;
        }
        .table th {
            background-color: #f2f2f2;
        }
        /* Estilos para iframe */
        html, body {
            height: 100%;
            width: 100%;
            overflow-x: hidden;
        }
        .container-fluid {
            padding: 15px;
        }
        .empleado-info {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border-left: 5px solid #dc3545;
        }
        .filtro-empleado {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        /* Badges para estados */
        .badge-secondary {
            background-color: #6c757d;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: normal;
        }
        .badge-warning {
            background-color: #ffc107;
            color: #212529;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: normal;
        }
        .badge-info {
            background-color: #17a2b8;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: normal;
        }
        .badge-danger {
            background-color: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: normal;
        }
        .select2-container {
            width: 100% !important;
        }
        .sin-faltantes {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Cursos Faltantes por Empleado</h4>
                </div>
                <div>
                    <form method="post" action="" class="d-inline">
                        <input type="hidden" name="id_empleado" value="<?php echo $empleado_seleccionado; ?>">
                        <button type="submit" name="exportar_excel" class="btn btn-success btn-export">
                            <i class="fas fa-file-excel me-1"></i> Exportar a Excel
                        </button>
                    </form>
                    <button type="button" class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> Imprimir
                    </button>
                </div>
            </div>
            
            <div class="card-body">
                <!-- Filtro de empleado -->
                <div class="filtro-empleado">
                    <form method="post" action="" class="row g-3 align-items-center">
                        <div class="col-md-6">
                            <label for="id_empleado" class="form-label">Seleccionar Empleado:</label>
                            <select class="form-select select2" id="id_empleado" name="id_empleado">
                                <option value="">Todos los empleados</option>
                                <?php
                                // Obtener lista de empleados
                                $sql_empleados = "SELECT Clave as IdEmp, NombreCompleto as Empleado FROM usuarios ORDER BY NombreCompleto";
                                $stmt_empleados = sqlsrv_query($conn, $sql_empleados);
                                
                                if ($stmt_empleados !== false) {
                                    while ($row = sqlsrv_fetch_array($stmt_empleados, SQLSRV_FETCH_ASSOC)) {
                                        $selected = ($empleado_seleccionado == $row['IdEmp']) ? 'selected' : '';
                                        echo '<option value="' . $row['IdEmp'] . '" ' . $selected . '>' . $row['Empleado'] . ' (ID: ' . $row['IdEmp'] . ')</option>';
                                    }
                                    sqlsrv_free_stmt($stmt_empleados);
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary d-block">
                                <i class="fas fa-filter me-1"></i> Filtrar
                            </button>
                        </div>
                    </form>
                </div>
                
                <?php if (!empty($empleado_seleccionado)): ?>
                    <?php
                    // Obtener información del empleado
                    $sql_empleado = "SELECT u.Clave AS IdEmp, u.NombreCompleto AS Empleado, p.puesto AS Puesto, p.depto AS Departamento 
                                     FROM usuarios u 
                                     LEFT JOIN puestos p ON u.idPuesto = p.Id 
                                     WHERE u.Clave = ?";
                    $params_empleado = array($empleado_seleccionado);
                    $stmt_empleado = sqlsrv_query($conn, $sql_empleado, $params_empleado);
                    
                    if ($stmt_empleado !== false && $row_empleado = sqlsrv_fetch_array($stmt_empleado, SQLSRV_FETCH_ASSOC)):
                    ?>
                    <div class="empleado-info">
                        <div class="row">
                            <div class="col-md-3">
                                <p class="mb-1"><strong>ID Empleado:</strong></p>
                                <h5><?php echo $row_empleado['IdEmp']; ?></h5>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-1"><strong>Nombre:</strong></p>
                                <h5><?php echo $row_empleado['Empleado']; ?></h5>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-1"><strong>Puesto:</strong></p>
                                <h5><?php echo $row_empleado['Puesto']; ?></h5>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-1"><strong>Departamento:</strong></p>
                                <h5><?php echo $row_empleado['Departamento']; ?></h5>
                            </div>
                        </div>
                    </div>
                    <?php
                    endif;
                    if ($stmt_empleado !== false) {
                        sqlsrv_free_stmt($stmt_empleado);
                    }
                    ?>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table id="tabla-cursos-faltantes" class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <?php if (empty($empleado_seleccionado)): ?>
                                <th>ID Empleado</th>
                                <th>Empleado</th>
                                <th>Puesto</th>
                                <th>Departamento</th>
                                <?php endif; ?>
                                <th>ID Curso</th>
                                <th>Nombre del Curso</th>
                                <th>Área</th>
                                <th>Departamento del Curso</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (empty($empleado_seleccionado)) {
                                // Si no hay empleado seleccionado, obtener todos los empleados
                                $sql_empleados = "SELECT u.Clave AS IdEmp, u.NombreCompleto AS Empleado, p.puesto AS Puesto, p.depto AS Departamento 
                                                 FROM usuarios u 
                                                 LEFT JOIN puestos p ON u.idPuesto = p.Id 
                                                 ORDER BY u.NombreCompleto";
                                $stmt_empleados = sqlsrv_query($conn, $sql_empleados);
                                
                                if ($stmt_empleados !== false) {
                                    while ($empleado = sqlsrv_fetch_array($stmt_empleados, SQLSRV_FETCH_ASSOC)) {
                                        // Para cada empleado, obtener sus cursos faltantes
                                        mostrarCursosFaltantes($conn, $empleado['IdEmp'], $empleado, true);
                                    }
                                    sqlsrv_free_stmt($stmt_empleados);
                                }
                            } else {
                                // Si hay un empleado seleccionado, obtener solo sus cursos faltantes
                                $empleado_info = null;
                                $sql_empleado = "SELECT u.Clave AS IdEmp, u.NombreCompleto AS Empleado, p.puesto AS Puesto, p.depto AS Departamento 
                                                FROM usuarios u 
                                                LEFT JOIN puestos p ON u.idPuesto = p.Id 
                                                WHERE u.Clave = ?";
                                $params_empleado = array($empleado_seleccionado);
                                $stmt_empleado = sqlsrv_query($conn, $sql_empleado, $params_empleado);
                                
                                if ($stmt_empleado !== false && $row_empleado = sqlsrv_fetch_array($stmt_empleado, SQLSRV_FETCH_ASSOC)) {
                                    $empleado_info = $row_empleado;
                                }
                                
                                if ($stmt_empleado !== false) {
                                    sqlsrv_free_stmt($stmt_empleado);
                                }
                                
                                if ($empleado_info) {
                                    mostrarCursosFaltantes($conn, $empleado_seleccionado, $empleado_info, false);
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle me-2"></i>Leyenda de Estados:</h5>
                            <div class="row mt-2">
                                <div class="col-md-3">
                                    <span class="badge-secondary me-2">Pendiente</span> - Curso no programado
                                </div>
                                <div class="col-md-3">
                                    <span class="badge-warning me-2">Programado</span> - Curso agendado
                                </div>
                                <div class="col-md-3">
                                    <span class="badge-info me-2">En Proceso</span> - Curso en ejecución
                                </div>
                                <div class="col-md-3">
                                    <span class="badge-danger me-2">Cancelado</span> - Curso cancelado
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Inicializar Select2 para el selector de empleados
            $('.select2').select2({
                theme: 'bootstrap-5',
                placeholder: "Seleccione un empleado",
                allowClear: true
            });
            
            // Inicializar DataTable
            $('#tabla-cursos-faltantes').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                responsive: true,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
                dom: 'Bfrtip',
                buttons: [
                    'pageLength'
                ]
            });
        });
    </script>
</body>
</html>