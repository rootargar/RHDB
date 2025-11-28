<?php

// Configuración de la conexión a la base de datos SQL Server
include("conexion2.php");

// Directorio para almacenar los archivos PDF
$directorio_pdf = "archivos_cursos/";

// Crear el directorio si no existe
if (!file_exists($directorio_pdf)) {
    mkdir($directorio_pdf, 0777, true);
}

// Procesar la subida de archivos PDF
if (isset($_POST['subir_pdf']) && isset($_FILES['archivo_pdf'])) {
    $id_capacitacion = $_POST['id_capacitacion'];
    
    // Validar que se ha seleccionado un archivo
    if ($_FILES['archivo_pdf']['error'] == 0) {
        $nombre_temporal = $_FILES['archivo_pdf']['tmp_name'];
        $nombre_archivo = $_FILES['archivo_pdf']['name'];
        $extension = pathinfo($nombre_archivo, PATHINFO_EXTENSION);
        
        // Validar que sea un archivo PDF
        if (strtolower($extension) == 'pdf') {
            // Crear un nombre único para el archivo
            $nombre_archivo_nuevo = "curso_" . $id_capacitacion . "_" . time() . ".pdf";
            $ruta_completa = $directorio_pdf . $nombre_archivo_nuevo;
            
            // Mover el archivo al directorio
            if (move_uploaded_file($nombre_temporal, $ruta_completa)) {
                // Actualizar la ruta en la base de datos
                $sql_update = "UPDATE capacitaciones SET ruta_archivo = ? WHERE Id = ?";
                $params = array($ruta_completa, $id_capacitacion);
                $stmt_update = sqlsrv_query($conn, $sql_update, $params);
                
                if ($stmt_update) {
                    echo "<script>alert('Archivo PDF subido correctamente');</script>";
                } else {
                    echo "<script>alert('Error al actualizar la base de datos');</script>";
                }
            } else {
                echo "<script>alert('Error al subir el archivo');</script>";
            }
        } else {
            echo "<script>alert('Solo se permiten archivos PDF');</script>";
        }
    } else {
        echo "<script>alert('Error al subir el archivo: " . $_FILES['archivo_pdf']['error'] . "');</script>";
    }
}

// Eliminar PDF y limpiar ruta
if (isset($_POST['eliminar_pdf'])) {
    $id_capacitacion = $_POST['id_capacitacion'];
    
    // Obtener la ruta actual del archivo
    $sql_ruta = "SELECT ruta_archivo FROM capacitaciones WHERE Id = ?";
    $params = array($id_capacitacion);
    $stmt_ruta = sqlsrv_query($conn, $sql_ruta, $params);
    
    if ($stmt_ruta && $row = sqlsrv_fetch_array($stmt_ruta, SQLSRV_FETCH_ASSOC)) {
        $ruta_archivo = $row['ruta_archivo'];
        
        // Eliminar el archivo físico si existe
        if (!empty($ruta_archivo) && file_exists($ruta_archivo)) {
            unlink($ruta_archivo);
        }
        
        // Limpiar la ruta en la base de datos
        $sql_update = "UPDATE capacitaciones SET ruta_archivo = NULL WHERE Id = ?";
        $params = array($id_capacitacion);
        $stmt_update = sqlsrv_query($conn, $sql_update, $params);
        
        if ($stmt_update) {
            echo "<script>alert('Archivo PDF eliminado correctamente');</script>";
        } else {
            echo "<script>alert('Error al actualizar la base de datos');</script>";
        }
    }
}

// Función para exportar a Excel
if (isset($_POST['exportar_excel'])) {
    // Establecer encabezados para descarga de Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="Reporte_Capacitaciones_' . date('Y-m-d') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Crear la tabla HTML para Excel (sin los botones de acción)
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Reporte de Capacitaciones</title>
        <style>
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #000; padding: 5px; text-align: left; }
            th { background-color: #f2f2f2; }
        </style>
    </head>
    <body>
        <h2>Reporte de Capacitaciones - ' . date('d/m/Y') . '</h2>
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
                    <th>Certificado</th>
                </tr>
            </thead>
            <tbody>';
    
    // Obtener los datos de la tabla capacitaciones para SQL Server con la nueva estructura
    $sql = "SELECT c.Id, c.IdEmp, u.NombreCompleto AS Empleado, p.puesto AS Puesto, 
                  c.FechaIni, c.FechaFin, pc.IdCursoBase AS IdCurso, cu.NombreCurso, c.ruta_archivo 
            FROM capacitaciones c
            INNER JOIN usuarios u ON c.IdEmp = u.Clave
            INNER JOIN puestos p ON u.idPuesto = p.Id
            INNER JOIN plancursos pc ON c.IdPlan = pc.IdPlan
            INNER JOIN cursos cu ON pc.IdCursoBase = cu.Id
            WHERE c.Asistio = 'Si' 
            ORDER BY u.NombreCompleto";
    $stmt = sqlsrv_query($conn, $sql);
    
    if ($stmt !== false) {
        while ($fila = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Formatear las fechas correctamente para SQL Server
            $fechaIni = $fila['FechaIni'] instanceof DateTime ? $fila['FechaIni']->format('d/m/Y') : 'N/A';
            $fechaFin = $fila['FechaFin'] instanceof DateTime ? $fila['FechaFin']->format('d/m/Y') : 'N/A';
            $certificado = !empty($fila['ruta_archivo']) ? 'Disponible' : 'No disponible';
            
            echo '<tr>';
            echo '<td>' . $fila['Id'] . '</td>';
            echo '<td>' . $fila['IdEmp'] . '</td>';
            echo '<td>' . $fila['Empleado'] . '</td>';
            echo '<td>' . $fila['Puesto'] . '</td>';
            echo '<td>' . $fechaIni . '</td>';
            echo '<td>' . $fechaFin . '</td>';
            echo '<td>' . $fila['IdCurso'] . '</td>';
            echo '<td>' . $fila['NombreCurso'] . '</td>';
            echo '<td>' . $certificado . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="9">No se encontraron registros</td></tr>';
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
    <title>Reporte de Capacitaciones</title>
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
        .btn-pdf {
            margin-right: 3px;
        }
        .modal-xl {
            max-width: 90%;
        }
        .pdf-container {
            height: 80vh;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Reporte de Capacitaciones</h4>
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
                    <table id="tabla-capacitaciones" class="table table-striped table-bordered table-hover">
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
                                <th>PDF</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Obtener los datos de la tabla capacitaciones con la nueva estructura
                            $sql = "SELECT c.Id, c.IdEmp, u.NombreCompleto AS Empleado, p.puesto AS Puesto, 
                                    c.FechaIni, c.FechaFin, pc.IdCursoBase AS IdCurso, cu.NombreCurso, c.ruta_archivo 
                                FROM capacitaciones c
                                INNER JOIN usuarios u ON c.IdEmp = u.Clave
                                INNER JOIN puestos p ON u.idPuesto = p.Id
                                INNER JOIN plancursos pc ON c.IdPlan = pc.IdPlan
                                INNER JOIN cursos cu ON pc.IdCursoBase = cu.Id
                                WHERE c.Asistio = 'Si' 
                                ORDER BY u.NombreCompleto";
                            $stmt = sqlsrv_query($conn, $sql);
                            
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
                                    
                                    // Estado del PDF
                                    if (!empty($fila['ruta_archivo']) && file_exists($fila['ruta_archivo'])) {
                                        echo '<td><span class="badge bg-success">Disponible</span></td>';
                                    } else {
                                        echo '<td><span class="badge bg-secondary">No disponible</span></td>';
                                    }
                                    
                                    // Botones de acción para el PDF
                                    echo '<td>';
                                    
                                    // Botón para ver PDF (si existe)
                                    if (!empty($fila['ruta_archivo']) && file_exists($fila['ruta_archivo'])) {
                                        echo '<button type="button" class="btn btn-info btn-sm btn-pdf" onclick="verPDF(\'' . $fila['ruta_archivo'] . '\')">
                                                <i class="fas fa-eye"></i>
                                            </button>';
                                            
                                        // Botón para eliminar PDF
                                        echo '<form method="post" action="" class="d-inline" onsubmit="return confirm(\'¿Está seguro de eliminar este PDF?\')">
                                                <input type="hidden" name="id_capacitacion" value="' . $fila['Id'] . '">
                                                <button type="submit" name="eliminar_pdf" class="btn btn-danger btn-sm btn-pdf">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>';
                                    }
                                    
                                    // Botón para subir PDF (siempre visible)
                                    echo '<button type="button" class="btn btn-primary btn-sm btn-pdf" data-bs-toggle="modal" data-bs-target="#modalSubirPDF' . $fila['Id'] . '">
                                            <i class="fas fa-upload"></i>
                                        </button>';
                                    
                                    echo '</td>';
                                    echo '</tr>';
                                    
                                    // Modal para subir PDF
                                    echo '<div class="modal fade" id="modalSubirPDF' . $fila['Id'] . '" tabindex="-1" aria-labelledby="tituloModalSubirPDF' . $fila['Id'] . '" aria-hidden="true">
                                          <div class="modal-dialog">
                                            <div class="modal-content">
                                              <div class="modal-header">
                                                <h5 class="modal-title" id="tituloModalSubirPDF' . $fila['Id'] . '">Subir PDF del Curso</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                              </div>
                                              <div class="modal-body">
                                                <form method="post" action="" enctype="multipart/form-data">
                                                  <input type="hidden" name="id_capacitacion" value="' . $fila['Id'] . '">
                                                  <div class="mb-3">
                                                    <label for="archivo_pdf" class="form-label">Seleccione archivo PDF del curso: <strong>' . $fila['NombreCurso'] . '</strong></label>
                                                    <input type="file" class="form-control" id="archivo_pdf" name="archivo_pdf" accept=".pdf" required>
                                                  </div>
                                                  <div class="text-end">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" name="subir_pdf" class="btn btn-primary">Subir PDF</button>
                                                  </div>
                                                </form>
                                              </div>
                                            </div>
                                          </div>
                                        </div>';
                                }
                            } else {
                                echo '<tr><td colspan="10" class="text-center">No se encontraron registros</td></tr>';
                                echo "Error en la consulta: " . print_r(sqlsrv_errors(), true);
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

    <!-- Modal para visualizar PDF -->
    <div class="modal fade" id="modalVerPDF" tabindex="-1" aria-labelledby="tituloModalVerPDF" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloModalVerPDF">Certificado de Capacitación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="pdf-container">
                        <iframe id="pdfFrame" src="" width="100%" height="100%" frameborder="0"></iframe>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
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
            $('#tabla-capacitaciones').DataTable({
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

        // Función para visualizar PDF
        function verPDF(rutaPDF) {
            document.getElementById('pdfFrame').src = rutaPDF;
            var modal = new bootstrap.Modal(document.getElementById('modalVerPDF'));
            modal.show();
        }
    </script>
</body>
</html>