<?php
// Incluir el archivo de conexión
include 'conexion2.php';

// Inicializar variables
$idPuestoSeleccionado = isset($_GET['id_puesto']) ? $_GET['id_puesto'] : NULL;
$puestoSeleccionado = '';
$departamentoSeleccionado = '';
$mensajeExito = '';
$mensajeError = '';

// Si hay un puesto seleccionado, obtener su nombre y departamento
if (!empty($idPuestoSeleccionado)) {
    $sqlPuestoInfo = "SELECT puesto, depto FROM puestos WHERE Id = ?";
    $paramsPuestoInfo = array($idPuestoSeleccionado);
    $stmtPuestoInfo = sqlsrv_query($conn, $sqlPuestoInfo, $paramsPuestoInfo);
    
    if ($stmtPuestoInfo !== false && $row = sqlsrv_fetch_array($stmtPuestoInfo, SQLSRV_FETCH_ASSOC)) {
        $puestoSeleccionado = $row['puesto'];
        $departamentoSeleccionado = $row['depto'];
    }
}

// Procesar asignación de curso a puesto
if (isset($_POST['asignar_curso']) && isset($_POST['id_curso']) && !empty($_POST['id_curso']) && !empty($idPuestoSeleccionado)) {
    $idCurso = $_POST['id_curso'];
    
    // Verificar si el curso ya está asignado a este puesto
    $sqlVerificar = "SELECT COUNT(*) AS existe FROM cursoxpuesto WHERE idPuesto = ? AND IdCurso = ?";
    $paramsVerificar = array($idPuestoSeleccionado, $idCurso);
    $stmtVerificar = sqlsrv_query($conn, $sqlVerificar, $paramsVerificar);
    
    if ($stmtVerificar !== false) {
        $row = sqlsrv_fetch_array($stmtVerificar, SQLSRV_FETCH_ASSOC);
        if ($row['existe'] == 0) {
            // Insertar la asignación de curso a puesto
            $sqlInsertar = "INSERT INTO cursoxpuesto (idPuesto, IdCurso) VALUES (?, ?)";
            $paramsInsertar = array($idPuestoSeleccionado, $idCurso);
            $stmtInsertar = sqlsrv_query($conn, $sqlInsertar, $paramsInsertar);
            
            if ($stmtInsertar !== false) {
                $mensajeExito = "Curso asignado correctamente al puesto.";
            } else {
                $mensajeError = "Error al asignar el curso al puesto: " . print_r(sqlsrv_errors(), true);
            }
        } else {
            $mensajeError = "Este curso ya está asignado a este puesto.";
        }
    } else {
        $mensajeError = "Error al verificar la existencia del curso en el puesto: " . print_r(sqlsrv_errors(), true);
    }
}

// Procesar desasignación de curso a puesto
if (isset($_GET['desasignar']) && !empty($_GET['desasignar']) && !empty($idPuestoSeleccionado)) {
    $idCursoDesasignar = $_GET['desasignar'];
    
    // Eliminar la asignación del curso al puesto
    $sqlEliminar = "DELETE FROM cursoxpuesto WHERE idPuesto = ? AND IdCurso = ?";
    $paramsEliminar = array($idPuestoSeleccionado, $idCursoDesasignar);
    $stmtEliminar = sqlsrv_query($conn, $sqlEliminar, $paramsEliminar);
    
    if ($stmtEliminar !== false) {
        $mensajeExito = "Curso desasignado correctamente del puesto.";
    } else {
        $mensajeError = "Error al desasignar el curso del puesto: " . print_r(sqlsrv_errors(), true);
    }
}

// Consultar todos los puestos para el selector
$sqlPuestos = "SELECT Id, puesto, depto FROM puestos ORDER BY depto, puesto";
$stmtPuestos = sqlsrv_query($conn, $sqlPuestos);

if ($stmtPuestos === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Consultar todos los cursos disponibles (si hay un puesto seleccionado)
$cursos = array();
if (!empty($idPuestoSeleccionado)) {
    $sqlCursos = "SELECT c.Id AS IdCurso, c.Area, c.NombreCurso 
                  FROM cursos c
                  WHERE c.Id NOT IN (
                    SELECT cp.IdCurso FROM cursoxpuesto cp WHERE cp.idPuesto = ?
                  )
                  ORDER BY c.Area, c.NombreCurso";
    $paramsCursos = array($idPuestoSeleccionado);
    $stmtCursos = sqlsrv_query($conn, $sqlCursos, $paramsCursos);
    
    if ($stmtCursos !== false) {
        while ($row = sqlsrv_fetch_array($stmtCursos, SQLSRV_FETCH_ASSOC)) {
            $cursos[] = $row;
        }
    } else {
        $mensajeError = "Error al consultar cursos disponibles: " . print_r(sqlsrv_errors(), true);
    }
}

// Consultar los cursos ya asignados al puesto seleccionado
$cursosAsignados = array();
if (!empty($idPuestoSeleccionado)) {
    $sqlAsignados = "SELECT cp.IdCurso, c.Area, c.NombreCurso
                     FROM cursoxpuesto cp
                     INNER JOIN cursos c ON cp.IdCurso = c.Id
                     WHERE cp.idPuesto = ?
                     ORDER BY c.Area, c.NombreCurso";
    $paramsAsignados = array($idPuestoSeleccionado);
    $stmtAsignados = sqlsrv_query($conn, $sqlAsignados, $paramsAsignados);
    
    if ($stmtAsignados !== false) {
        while ($row = sqlsrv_fetch_array($stmtAsignados, SQLSRV_FETCH_ASSOC)) {
            $cursosAsignados[] = $row;
        }
    } else {
        $mensajeError = "Error al consultar cursos asignados: " . print_r(sqlsrv_errors(), true);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignación de Cursos por Puesto</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Font Awesome para íconos -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .container {
            margin-top: 20px;
        }
        .action-buttons {
            white-space: nowrap;
        }
        .card {
            margin-bottom: 20px;
        }
        .alert {
            margin-top: 20px;
        }
        .table-card {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mb-4">Asignación de Cursos por Puesto</h2>
        
        <?php if(!empty($mensajeExito)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $mensajeExito; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if(!empty($mensajeError)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $mensajeError; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <!-- Selector de Puesto -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Seleccione un Puesto</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <select id="selector-puesto" class="form-select form-select-lg mb-3" aria-label="Seleccionar Puesto">
                            <option value="">-- Seleccione un Puesto --</option>
                            <?php while($puesto = sqlsrv_fetch_array($stmtPuestos, SQLSRV_FETCH_ASSOC)): ?>
                                <option value="<?php echo $puesto['Id']; ?>" 
                                        <?php echo ($idPuestoSeleccionado == $puesto['Id']) ? 'selected' : ''; ?>>
                                    <?php echo $puesto['puesto'] . ' - ' . $puesto['depto']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if(!empty($idPuestoSeleccionado)): ?>
            <div class="row">
                <!-- Cursos Disponibles -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Cursos Disponibles</h5>
                        </div>
                        <div class="card-body table-card">
                            <div class="table-responsive">
                                <table id="tablaCursosDisponibles" class="table table-striped table-bordered">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Área</th>
                                            <th>Nombre del Curso</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(!empty($cursos)): ?>
                                            <?php foreach($cursos as $curso): ?>
                                            <tr>
                                                <td><?php echo $curso['Area']; ?></td>
                                                <td><?php echo $curso['NombreCurso']; ?></td>
                                                <td class="action-buttons">
                                                    <form method="post" style="display: inline;">
                                                        <input type="hidden" name="id_curso" value="<?php echo $curso['IdCurso']; ?>">
                                                        <button type="submit" name="asignar_curso" class="btn btn-success btn-sm">
                                                            <i class="fas fa-plus"></i> Asignar
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center">No hay cursos disponibles para asignar.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Cursos Asignados -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Cursos Asignados a: <?php echo $puestoSeleccionado; ?></h5>
                        </div>
                        <div class="card-body table-card">
                            <div class="table-responsive">
                                <table id="tablaCursosAsignados" class="table table-striped table-bordered">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Área</th>
                                            <th>Nombre del Curso</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(!empty($cursosAsignados)): ?>
                                            <?php foreach($cursosAsignados as $asignado): ?>
                                            <tr>
                                                <td><?php echo $asignado['Area']; ?></td>
                                                <td><?php echo $asignado['NombreCurso']; ?></td>
                                                <td class="action-buttons">
                                                    <a href="javascript:void(0);" onclick="confirmarDesasignar(<?php echo $asignado['IdCurso']; ?>)" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-minus"></i> Desasignar
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center">No hay cursos asignados a este puesto.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info mt-4">
                Por favor, seleccione un puesto para ver y gestionar los cursos asignados.
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS y Popper -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <!-- jQuery y DataTables -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        // Esperar a que el documento esté completamente cargado
        $(document).ready(function() {
            try {
                // Configuración común para ambas tablas
                var options = {
    language: {
        url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
    },
    responsive: true,
    searching: true,
    paging: true,
    info: true,
    lengthMenu: [5, 10, 25, 50],
    pageLength: 5,
    ordering: false,
    columns: [ // Define explícitamente las tres columnas
        null, // Columna 1: Área
        null, // Columna 2: Nombre del Curso
        null  // Columna 3: Acción
    ]
};
               
                // Inicializar tablas solo si existen
                if ($('#tablaCursosDisponibles').length) {
                    // Destruir tabla si ya está inicializada
                    if ($.fn.DataTable.isDataTable('#tablaCursosDisponibles')) {
                        $('#tablaCursosDisponibles').DataTable().destroy();
                    }
                    // Inicializar tabla sin errores
                    setTimeout(function() {
                        $('#tablaCursosDisponibles').DataTable(options);
                    }, 100);
                }
                
                if ($('#tablaCursosAsignados').length) {
                    // Destruir tabla si ya está inicializada
                    if ($.fn.DataTable.isDataTable('#tablaCursosAsignados')) {
                        $('#tablaCursosAsignados').DataTable().destroy();
                    }
                    // Inicializar tabla sin errores
                    setTimeout(function() {
                        $('#tablaCursosAsignados').DataTable(options);
                    }, 100);
                }
            } catch (e) {
                console.error("Error al inicializar DataTables:", e);
            }
            
            // Evento change del selector de puesto
            $('#selector-puesto').change(function() {
                var idPuesto = $(this).val();
                
                if(idPuesto) {
                    window.location.href = 'cursoxpuesto.php?id_puesto=' + idPuesto;
                } else {
                    window.location.href = 'cursoxpuesto.php';
                }
            });
        });
        
        // Función para confirmar desasignación
        function confirmarDesasignar(idCurso) {
            if(confirm('¿Está seguro de que desea desasignar este curso del puesto?')) {
                window.location.href = 'cursoxpuesto.php?id_puesto=<?php echo $idPuestoSeleccionado; ?>&desasignar=' + idCurso;
            }
        }
    </script>
</body>
</html>