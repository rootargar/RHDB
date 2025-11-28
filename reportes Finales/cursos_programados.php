<?php

// Configuración de la conexión a la base de datos SQL Server
include("conexion2.php");

// Función para exportar a Excel
if (isset($_POST['exportar_excel'])) {
    // Establecer encabezados para descarga de Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="Reporte_Plan_Cursos_' . date('Y-m-d') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Crear la tabla HTML para Excel (sin los botones de acción)
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Reporte de Plan de Cursos</title>
        <style>
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #000; padding: 5px; text-align: left; }
            th { background-color: #f2f2f2; }
        </style>
    </head>
    <body>
        <h2>Reporte de Plan de Cursos - ' . date('d/m/Y') . '</h2>
        <table>
            <thead>
                <tr>
                    <th>ID Plan</th>
                    <th>Nombre del Curso</th>
                    <th>ID Curso</th>
                    <th>Área</th>
                    <th>Fecha Inicio</th>
                    <th>Fecha Fin</th>
                    <th>Fecha Curso</th>
                    <th>Lugar</th>
                    <th>Horario</th>
                    <th>Instructor</th>
                    <th>Proveedor</th>
                    <th>Costo</th>
                    <th>Núm. Participantes</th>
                    <th>Factura</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>';
    
    // Obtener los datos de la tabla plancursos para SQL Server
    $sql = "SELECT IdPlan, Area, FechaIni, FechaFin, Lugar, Costo, NumParticipantes, Horario, 
            FechaCurso, Proveedor, Instructor, Factura, IdCurso, Estado, NombreCurso 
            FROM plancursos ORDER BY FechaIni DESC";
    $stmt = sqlsrv_query($conn, $sql);
    
    if ($stmt !== false) {
        while ($fila = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Formatear las fechas correctamente para SQL Server
            $fechaInicio = $fila['FechaIni'] instanceof DateTime ? $fila['FechaIni']->format('d/m/Y') : 'N/A';
            $fechaFin = $fila['FechaFin'] instanceof DateTime ? $fila['FechaFin']->format('d/m/Y') : 'N/A';
            $fechaCurso = $fila['FechaCurso'] instanceof DateTime ? $fila['FechaCurso']->format('d/m/Y') : 'N/A';
            
            echo '<tr>';
            echo '<td>' . $fila['IdPlan'] . '</td>';
            echo '<td>' . $fila['NombreCurso'] . '</td>';
            echo '<td>' . $fila['IdCurso'] . '</td>';
            echo '<td>' . $fila['Area'] . '</td>';
            echo '<td>' . $fechaInicio . '</td>';
            echo '<td>' . $fechaFin . '</td>';
            echo '<td>' . $fechaCurso . '</td>';
            echo '<td>' . $fila['Lugar'] . '</td>';
            echo '<td>' . $fila['Horario'] . '</td>';
            echo '<td>' . $fila['Instructor'] . '</td>';
            echo '<td>' . $fila['Proveedor'] . '</td>';
            echo '<td>' . number_format($fila['Costo'], 2) . '</td>';
            echo '<td>' . $fila['NumParticipantes'] . '</td>';
            echo '<td>' . $fila['Factura'] . '</td>';
            echo '<td>' . $fila['Estado'] . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="15">No se encontraron registros</td></tr>';
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
    <title>Reporte de Plan de Cursos</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
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
        /* Estilos para la tabla con scroll horizontal */
        .table-responsive {
            overflow-x: auto;
        }
        /* Formato para valores monetarios */
        .currency {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Reporte de Plan de Cursos</h4>
                </div>
                <div>
                    <form method="post" action="" class="d-inline">
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
                <div class="table-responsive">
                    <table id="tabla-plancursos" class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ID Plan</th>
                                <th>Nombre del Curso</th>
                                <th>ID Curso</th>
                                <th>Área</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Fin</th>
                                <th>Fecha Curso</th>
                                <th>Lugar</th>
                                <th>Horario</th>
                                <th>Instructor</th>
                                <th>Proveedor</th>
                                <th>Costo</th>
                                <th>Núm. Participantes</th>
                                <th>Factura</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Obtener los datos de la tabla plancursos para SQL Server
                            $sql = "SELECT IdPlan, Area, FechaIni, FechaFin, Lugar, Costo, NumParticipantes, Horario, 
                                    FechaCurso, Proveedor, Instructor, Factura, IdCurso, Estado, NombreCurso 
                                    FROM plancursos ORDER BY FechaIni DESC";
                            $stmt = sqlsrv_query($conn, $sql);
                            
                            if ($stmt !== false) {
                                while ($fila = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                                    // Formatear las fechas correctamente para SQL Server
                                    $fechaInicio = $fila['FechaIni'] instanceof DateTime ? $fila['FechaIni']->format('d/m/Y') : 'N/A';
                                    $fechaFin = $fila['FechaFin'] instanceof DateTime ? $fila['FechaFin']->format('d/m/Y') : 'N/A';
                                    $fechaCurso = $fila['FechaCurso'] instanceof DateTime ? $fila['FechaCurso']->format('d/m/Y') : 'N/A';
                                    
                                    echo '<tr>';
                                    echo '<td>' . $fila['IdPlan'] . '</td>';
                                    echo '<td>' . $fila['NombreCurso'] . '</td>';
                                    echo '<td>' . $fila['IdCurso'] . '</td>';
                                    echo '<td>' . $fila['Area'] . '</td>';
                                    echo '<td>' . $fechaInicio . '</td>';
                                    echo '<td>' . $fechaFin . '</td>';
                                    echo '<td>' . $fechaCurso . '</td>';
                                    echo '<td>' . $fila['Lugar'] . '</td>';
                                    echo '<td>' . $fila['Horario'] . '</td>';
                                    echo '<td>' . $fila['Instructor'] . '</td>';
                                    echo '<td>' . $fila['Proveedor'] . '</td>';
                                    echo '<td class="currency">' . number_format($fila['Costo'], 2) . '</td>';
                                    echo '<td>' . $fila['NumParticipantes'] . '</td>';
                                    echo '<td>' . $fila['Factura'] . '</td>';
                                    echo '<td>' . $fila['Estado'] . '</td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="15" class="text-center">No se encontraron registros</td></tr>';
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
    
    <script>
        $(document).ready(function() {
            $('#tabla-plancursos').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                responsive: true,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
                dom: 'Bfrtip',
                buttons: [
                    'pageLength'
                ],
                // Ordenar por fecha de inicio (columna 4) de más reciente a más antigua
                order: [[4, 'desc']]
            });
        });
    </script>
</body>
</html>