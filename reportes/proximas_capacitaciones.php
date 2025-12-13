<?php

// Configuración de la conexión a la base de datos SQL Server
include("conexion2.php");

// Incluir sistema de autenticación para verificar el rol
require_once(__DIR__ . '/../auth_check.php');

// Variables para filtrado
$filtro_empleado = '';
$es_empleado_rol = es_empleado();

// Si el usuario es Empleado, filtrar por su usuario logueado
if ($es_empleado_rol) {
    $clave_usuario = get_clave_usuario();
    if ($clave_usuario) {
        $filtro_empleado = " AND cap.IdEmp = '$clave_usuario'";
    }
}

// Establecer fecha predeterminada (hoy) - CORREGIDO: usar formato ISO
$fecha_seleccionada = isset($_POST['fecha_seleccion']) ? $_POST['fecha_seleccion'] : date('Y-m-d');

// AGREGAR: Validar y convertir formato de fecha para SQL Server
function convertirFechaParaSQL($fecha) {
    // Convertir fecha a formato ISO (YYYY-MM-DD) que SQL Server siempre acepta
    $fechaObj = DateTime::createFromFormat('Y-m-d', $fecha);
    if ($fechaObj === false) {
        // Si falla, usar fecha actual
        return date('Y-m-d');
    }
    return $fechaObj->format('Y-m-d');
}

// Convertir la fecha seleccionada al formato correcto
$fecha_sql = convertirFechaParaSQL($fecha_seleccionada);

// Función para exportar a Excel
if (isset($_POST['exportar_excel'])) {
    // Establecer encabezados para descarga de Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="Reporte_Proximas_Capacitaciones_' . date('Y-m-d') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Crear la tabla HTML para Excel (sin los botones de acción)
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Reporte de Próximas Capacitaciones</title>
        <style>
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #000; padding: 5px; text-align: left; }
            th { background-color: #f2f2f2; }
        </style>
    </head>
    <body>
        <h2>Reporte de Próximas Capacitaciones (a partir del ' . date('d/m/Y', strtotime($fecha_sql)) . ')</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ID Empleado</th>
                    <th>Empleado</th>
                    <th>Puesto</th>
                    <th>Fecha Inicio</th>
                    <th>Fecha Fin</th>
                    <th>ID Curso</th>
                    <th>Nombre Curso</th>
                    <th>Área</th>
                    <th>Departamento</th>
                    <th>Días Restantes</th>
                </tr>
            </thead>
            <tbody>';
    
    // CORREGIDO: Usar CONVERT para formato de fecha en SQL Server
    $sql = "SELECT 
                cap.Id, 
                cap.IdEmp, 
                u.NombreCompleto AS Empleado, 
                p.puesto AS Puesto, 
                cap.FechaIni, 
                cap.FechaFin, 
                curso.Id AS IdCurso, 
                curso.NombreCurso, 
                curso.Area, 
                p.depto AS Departamento,
                DATEDIFF(day, GETDATE(), cap.FechaIni) as DiasRestantes
            FROM capacitaciones cap
            LEFT JOIN usuarios u ON cap.IdEmp = u.Clave
            LEFT JOIN puestos p ON u.idPuesto = p.Id
            LEFT JOIN plancursos pc ON cap.IdPlan = pc.IdPlan
            LEFT JOIN cursos curso ON pc.IdCursoBase = curso.Id
            WHERE cap.FechaIni >= CONVERT(datetime, ?, 120)" .
            $filtro_empleado . "
            ORDER BY cap.FechaIni ASC";
            
    $params = array($fecha_sql);
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt !== false) {
        while ($fila = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Formatear las fechas correctamente para SQL Server
            $fechaIni = $fila['FechaIni'] instanceof DateTime ? $fila['FechaIni']->format('d/m/Y') : 'N/A';
            $fechaFin = $fila['FechaFin'] instanceof DateTime ? $fila['FechaFin']->format('d/m/Y') : 'N/A';
            
            echo '<tr>';
            echo '<td>' . $fila['Id'] . '</td>';
            echo '<td>' . $fila['IdEmp'] . '</td>';
            echo '<td>' . $fila['Empleado'] . '</td>';
            echo '<td>' . $fila['Puesto'] . '</td>';
            echo '<td>' . $fechaIni . '</td>';
            echo '<td>' . $fechaFin . '</td>';
            echo '<td>' . $fila['IdCurso'] . '</td>';
            echo '<td>' . $fila['NombreCurso'] . '</td>';
            echo '<td>' . $fila['Area'] . '</td>';
            echo '<td>' . $fila['Departamento'] . '</td>';
            echo '<td>' . $fila['DiasRestantes'] . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="11">No se encontraron registros</td></tr>';
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
    <title>Próximas Capacitaciones</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Datepicker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
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
        .fecha-filtro {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .dias-proximos {
            font-weight: bold;
        }
        .dias-proximos-0 {
            color: #dc3545; /* Rojo - Hoy */
        }
        .dias-proximos-1, .dias-proximos-2, .dias-proximos-3 {
            color: #fd7e14; /* Naranja - Próximos 3 días */
        }
        .dias-proximos-7 {
            color: #28a745; /* Verde - Próxima semana */
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Próximas Capacitaciones</h4>
                </div>
                <div>
                    <form method="post" action="" class="d-inline">
                        <button type="submit" name="exportar_excel" class="btn btn-success btn-export">
                            <i class="fas fa-file-excel me-1"></i> Exportar a Excel
                        </button>
                        <input type="hidden" name="fecha_seleccion" value="<?php echo $fecha_seleccionada; ?>">
                    </form>
                    <button type="button" class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> Imprimir
                    </button>
                </div>
            </div>
            
            <div class="card-body">
                <!-- Filtro de fecha -->
                <div class="fecha-filtro">
                    <form method="post" action="" class="row g-3 align-items-center">
                        <div class="col-md-4">
                            <label for="fecha_seleccion" class="form-label">Mostrar capacitaciones a partir de:</label>
                            <div class="input-group">
                                <input type="date" class="form-control" id="fecha_seleccion" name="fecha_seleccion" 
                                       value="<?php echo $fecha_seleccionada; ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-1"></i> Filtrar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="table-responsive">
                    <table id="tabla-proximas" class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>ID Empleado</th>
                                <th>Empleado</th>
                                <th>Puesto</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Fin</th>
                                <th>ID Curso</th>
                                <th>Nombre Curso</th>
                                <th>Área</th>
                                <th>Departamento</th>
                                <th>Días Restantes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // CORREGIDO: Usar CONVERT para formato de fecha en SQL Server
                            $sql = "SELECT 
                                        cap.Id, 
                                        cap.IdEmp, 
                                        u.NombreCompleto AS Empleado, 
                                        p.puesto AS Puesto, 
                                        cap.FechaIni, 
                                        cap.FechaFin, 
                                        curso.Id AS IdCurso, 
                                        curso.NombreCurso, 
                                        curso.Area, 
                                        p.depto AS Departamento,
                                        DATEDIFF(day, GETDATE(), cap.FechaIni) as DiasRestantes
                                    FROM capacitaciones cap
                                    LEFT JOIN usuarios u ON cap.IdEmp = u.Clave
                                    LEFT JOIN puestos p ON u.idPuesto = p.Id
                                    LEFT JOIN plancursos pc ON cap.IdPlan = pc.IdPlan
                                    LEFT JOIN cursos curso ON pc.IdCursoBase = curso.Id
                                    WHERE cap.FechaIni >= CONVERT(datetime, ?, 120)" .
                                    $filtro_empleado . "
                                    ORDER BY cap.FechaIni ASC";
                                    
                            $params = array($fecha_sql);
                            $stmt = sqlsrv_query($conn, $sql, $params);
                            
                            if ($stmt !== false) {
                                while ($fila = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                                    // Formatear las fechas correctamente para SQL Server
                                    $fechaIni = $fila['FechaIni'] instanceof DateTime ? $fila['FechaIni']->format('d/m/Y') : 'N/A';
                                    $fechaFin = $fila['FechaFin'] instanceof DateTime ? $fila['FechaFin']->format('d/m/Y') : 'N/A';
                                    
                                    // Definir clase CSS para los días restantes
                                    $diasRestantesClass = '';
                                    if ($fila['DiasRestantes'] == 0) {
                                        $diasRestantesClass = 'dias-proximos-0';
                                    } elseif ($fila['DiasRestantes'] > 0 && $fila['DiasRestantes'] <= 3) {
                                        $diasRestantesClass = 'dias-proximos-1';
                                    } elseif ($fila['DiasRestantes'] > 3 && $fila['DiasRestantes'] <= 7) {
                                        $diasRestantesClass = 'dias-proximos-7';
                                    }
                                    
                                    echo '<tr>';
                                    echo '<td>' . $fila['Id'] . '</td>';
                                    echo '<td>' . $fila['IdEmp'] . '</td>';
                                    echo '<td>' . $fila['Empleado'] . '</td>';
                                    echo '<td>' . $fila['Puesto'] . '</td>';
                                    echo '<td>' . $fechaIni . '</td>';
                                    echo '<td>' . $fechaFin . '</td>';
                                    echo '<td>' . $fila['IdCurso'] . '</td>';
                                    echo '<td>' . $fila['NombreCurso'] . '</td>';
                                    echo '<td>' . $fila['Area'] . '</td>';
                                    echo '<td>' . $fila['Departamento'] . '</td>';
                                    echo '<td class="dias-proximos ' . $diasRestantesClass . '">' . $fila['DiasRestantes'] . '</td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="11" class="text-center">No se encontraron capacitaciones programadas a partir de la fecha seleccionada</td></tr>';
                                
                                // Mostrar error de SQL si existe
                                if (($errors = sqlsrv_errors()) != null) {
                                    echo '<tr><td colspan="11" class="text-center text-danger">';
                                    foreach ($errors as $error) {
                                        echo "SQLSTATE: ".$error['SQLSTATE']." - Código: ".$error['code']." - Mensaje: ".$error['message']."<br />";
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

    <!-- Scripts permanecen igual -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#tabla-proximas').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                responsive: true,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
                dom: 'Bfrtip',
                buttons: [
                    'pageLength'
                ],
                order: [[10, 'asc']]
            });
            
            flatpickr("#fecha_seleccion", {
                dateFormat: "Y-m-d",
                locale: "es",
                allowInput: true
            });
        });
    </script>
</body>
</html>