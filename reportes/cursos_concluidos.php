<?php

// Configuración de la conexión a la base de datos SQL Server
include("conexion2.php");

// Incluir sistema de autenticación para verificar el rol
require_once(__DIR__ . '/../auth_check.php');

// Variable para almacenar el ID del empleado seleccionado (si se ha filtrado)
$empleado_seleccionado = isset($_POST['id_empleado']) ? $_POST['id_empleado'] : '';

// Si el usuario es Empleado, automáticamente filtrar por su usuario logueado
if (es_empleado()) {
    $clave_usuario = get_clave_usuario();
    if ($clave_usuario) {
        $empleado_seleccionado = $clave_usuario;
    }
}

// Función para exportar a Excel
if (isset($_POST['exportar_excel'])) {
    // Establecer encabezados para descarga de Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="Reporte_Cursos_Concluidos_' . date('Y-m-d') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Crear la tabla HTML para Excel (sin los botones de acción)
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Reporte de Cursos Concluidos</title>
        <style>
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #000; padding: 5px; text-align: left; }
            th { background-color: #f2f2f2; }
        </style>
    </head>
    <body>
        <h2>Reporte de Cursos Concluidos por Empleado - ' . date('d/m/Y') . '</h2>';
        
    // Si hay un empleado seleccionado, mostrar sus datos
    if (!empty($empleado_seleccionado)) {
        // Obtener información del empleado con la nueva estructura
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
                <tr>
                    <th>ID Curso</th>
                    <th>Nombre del Curso</th>
                    <th>Área</th>
                    <th>Fecha Inicio</th>
                    <th>Fecha Fin</th>
                    <th>Instructor</th>
                    <th>Lugar</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>';
    
    // Construir la consulta SQL actualizada para obtener los cursos concluidos
    $sql = "SELECT 
                c.Id AS IdCurso, 
                c.NombreCurso, 
                c.Area, 
                pc.FechaIni, 
                pc.FechaFin, 
                pc.Instructor, 
                pc.Lugar, 
                pc.Estado
            FROM 
                capacitaciones cap
            INNER JOIN 
                plancursos pc ON cap.IdPlan = pc.IdPlan
            INNER JOIN 
                cursos c ON pc.IdCursoBase = c.Id
            INNER JOIN 
                usuarios u ON cap.IdEmp = u.Clave
            INNER JOIN 
                puestos p ON u.idPuesto = p.Id
            INNER JOIN 
                cursoxpuesto cp ON c.Id = cp.IdCurso AND p.Id = cp.idPuesto
            WHERE 
                pc.Estado = 'Completado' 
                AND cap.Asistio = 'Si'";
    
    // Si se ha seleccionado un empleado específico, agregar el filtro
    if (!empty($empleado_seleccionado)) {
        $sql .= " AND cap.IdEmp = ?";
        $params = array($empleado_seleccionado);
    } else {
        $params = array();
    }
    
    $sql .= " ORDER BY u.NombreCompleto, c.NombreCurso";
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt !== false) {
        while ($fila = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Formatear las fechas correctamente para SQL Server
            $fechaIni = $fila['FechaIni'] instanceof DateTime ? $fila['FechaIni']->format('d/m/Y') : 'N/A';
            $fechaFin = $fila['FechaFin'] instanceof DateTime ? $fila['FechaFin']->format('d/m/Y') : 'N/A';
            
            echo '<tr>';
            echo '<td>' . $fila['IdCurso'] . '</td>';
            echo '<td>' . $fila['NombreCurso'] . '</td>';
            echo '<td>' . $fila['Area'] . '</td>';
            echo '<td>' . $fechaIni . '</td>';
            echo '<td>' . $fechaFin . '</td>';
            echo '<td>' . $fila['Instructor'] . '</td>';
            echo '<td>' . $fila['Lugar'] . '</td>';
            echo '<td>' . $fila['Estado'] . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="8">No se encontraron registros</td></tr>';
    }
    
    echo '</tbody></table></body></html>';
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cursos Concluidos por Empleado</title>
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
            border-left: 5px solid #0d6efd;
        }
        .filtro-empleado {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .badge-completado {
            background-color: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: normal;
        }
        .select2-container {
            width: 100% !important;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0"><i class="fas fa-check-circle me-2"></i>Cursos Concluidos por Empleado</h4>
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
                                // Obtener lista de empleados con la nueva estructura
                                $sql_empleados = "SELECT u.Clave AS IdEmp, u.NombreCompleto AS Empleado 
                                                 FROM usuarios u 
                                                 ORDER BY u.NombreCompleto";
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
                    // Obtener información del empleado con la nueva estructura
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
                    <table id="tabla-cursos-concluidos" class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <?php if (empty($empleado_seleccionado)): ?>
                                <th>Empleado</th>
                                <th>Puesto</th>
                                <th>Departamento</th>
                                <?php endif; ?>
                                <th>ID Curso</th>
                                <th>Nombre del Curso</th>
                                <th>Área</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Fin</th>
                                <th>Instructor</th>
                                <th>Lugar</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Construir la consulta SQL actualizada para obtener los cursos concluidos
                            $sql = "SELECT ";
                            
                            if (empty($empleado_seleccionado)) {
                                $sql .= "u.Clave AS IdEmp, u.NombreCompleto AS Empleado, p.puesto AS Puesto, p.depto AS Departamento, ";
                            }
                            
                            $sql .= "c.Id AS IdCurso, 
                                    c.NombreCurso, 
                                    c.Area, 
                                    pc.FechaIni, 
                                    pc.FechaFin, 
                                    pc.Instructor, 
                                    pc.Lugar, 
                                    pc.Estado
                                FROM 
                                    capacitaciones cap
                                INNER JOIN 
                                    plancursos pc ON cap.IdPlan = pc.IdPlan
                                INNER JOIN 
                                    cursos c ON pc.IdCursoBase = c.Id
                                INNER JOIN 
                                    usuarios u ON cap.IdEmp = u.Clave
                                INNER JOIN 
                                    puestos p ON u.idPuesto = p.Id
                                INNER JOIN 
                                    cursoxpuesto cp ON c.Id = cp.IdCurso AND p.Id = cp.idPuesto
                                WHERE 
                                    pc.Estado = 'Completado' 
                                    AND cap.Asistio = 'Si'";
                            
                            // Si se ha seleccionado un empleado específico, agregar el filtro
                            if (!empty($empleado_seleccionado)) {
                                $sql .= " AND cap.IdEmp = ?";
                                $params = array($empleado_seleccionado);
                                $sql .= " ORDER BY c.NombreCurso";
                            } else {
                                $params = array();
                                $sql .= " ORDER BY u.NombreCompleto, c.NombreCurso";
                            }
                            
                            $stmt = sqlsrv_query($conn, $sql, $params);
                            
                            if ($stmt !== false) {
                                while ($fila = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                                    // Formatear las fechas correctamente para SQL Server
                                    $fechaIni = $fila['FechaIni'] instanceof DateTime ? $fila['FechaIni']->format('d/m/Y') : 'N/A';
                                    $fechaFin = $fila['FechaFin'] instanceof DateTime ? $fila['FechaFin']->format('d/m/Y') : 'N/A';
                                    
                                    echo '<tr>';
                                    
                                    // Si no hay empleado seleccionado, mostrar columnas de empleado
                                    if (empty($empleado_seleccionado)) {
                                        echo '<td>' . $fila['Empleado'] . '</td>';
                                        echo '<td>' . $fila['Puesto'] . '</td>';
                                        echo '<td>' . $fila['Departamento'] . '</td>';
                                    }
                                    
                                    echo '<td>' . $fila['IdCurso'] . '</td>';
                                    echo '<td>' . $fila['NombreCurso'] . '</td>';
                                    echo '<td>' . $fila['Area'] . '</td>';
                                    echo '<td>' . $fechaIni . '</td>';
                                    echo '<td>' . $fechaFin . '</td>';
                                    echo '<td>' . $fila['Instructor'] . '</td>';
                                    echo '<td>' . $fila['Lugar'] . '</td>';
                                    echo '<td><span class="badge-completado">' . $fila['Estado'] . '</span></td>';
                                    echo '</tr>';
                                }
                            } else {
                                $columnas = empty($empleado_seleccionado) ? 11 : 8;
                                echo '<tr><td colspan="' . $columnas . '" class="text-center">No se encontraron cursos concluidos</td></tr>';
                                
                                // Mostrar error si ocurrió alguno
                                if (($errors = sqlsrv_errors()) != null) {
                                    echo '<tr><td colspan="' . $columnas . '" class="text-center text-danger">';
                                    foreach ($errors as $error) {
                                        echo "Error SQL: " . $error['message'] . "<br>";
                                    }
                                    echo '</td></tr>';
                                }
                            }
                            
                            // Cerrar recursos SQL Server
                            if ($stmt !== false) {
                                sqlsrv_free_stmt($stmt);
                            }
                            ?>
                        </tbody>
                    </table>
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
            $('#tabla-cursos-concluidos').DataTable({
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