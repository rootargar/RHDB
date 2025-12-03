<?php
// Incluir archivo de conexión a la base de datos
include 'conexion2.php';

// Inicializar variables
$mensaje = '';
$cursoSeleccionado = false;
$idPlan = isset($_GET['id']) ? $_GET['id'] : '';
$nombreCurso = '';
$fechaIni = '';
$fechaFin = '';
$area = '';
$idCursoBase = '';
$estadoCurso = '';
$fechaIniObj = null;
$fechaFinObj = null;

// Si hay un ID de plan en la URL, cargar los detalles del curso
if (!empty($idPlan)) {
    $sql = "SELECT p.*, c.NombreCurso, c.Id as IdCursoBase, c.Area
             FROM plancursos p
             LEFT JOIN cursos c ON p.IdCursoBase = c.Id
             WHERE p.IdPlan = ?";
    $params = array($idPlan);
    $stmt = sqlsrv_prepare($conn, $sql, $params);

    if (sqlsrv_execute($stmt)) {
        $curso = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if ($curso) {
            $cursoSeleccionado = true;
            $nombreCurso = $curso['NombreCurso'];
            $fechaIni = $curso['FechaIni'] instanceof DateTime ? $curso['FechaIni']->format('Y-m-d') : '';
            $fechaFin = $curso['FechaFin'] instanceof DateTime ? $curso['FechaFin']->format('Y-m-d') : '';
            $area = $curso['Area'];
            $idCursoBase = $curso['IdCursoBase'];
            $estadoCurso = $curso['Estado'];

            // Crear objetos DateTime para las fechas (necesarios para consultas)
            if (!empty($fechaIni)) {
                $fechaIniObj = date_create_from_format('Y-m-d', $fechaIni);
            }
            if (!empty($fechaFin)) {
                $fechaFinObj = date_create_from_format('Y-m-d', $fechaFin);
            }
        }
    } else {
        echo "<!-- Error al cargar detalles del curso: " . print_r(sqlsrv_errors(), true) . " -->";
    }
}


// Procesar formulario cuando se envía
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verificar qué acción se está realizando
    if (isset($_POST['agregar_participantes'])) {
        // Obtener datos del formulario
        $idPlan = $_POST['idPlan'];
        $fechaIni = $_POST['fechaIni'];
        $fechaFin = $_POST['fechaFin'];
        $estadoCurso = $_POST['estadoCurso'];

        // Validar que el curso no esté Completado o Cancelado
        if ($estadoCurso === 'Completado' || $estadoCurso === 'Cancelado') {
            $mensaje = "No se pueden asignar participantes a capacitaciones con estado '$estadoCurso'.";
        } else {
            // Convertir fechas a objetos DateTime
            $fechaIniObj = null;
            $fechaFinObj = null;

            if (!empty($fechaIni)) {
                $fechaIniObj = date_create_from_format('Y-m-d', $fechaIni);
            }

            if (!empty($fechaFin)) {
                $fechaFinObj = date_create_from_format('Y-m-d', $fechaFin);
            }

            // Verificar si se han seleccionado empleados
            if (isset($_POST['empleados']) && !empty($_POST['empleados'])) {
            $empleadosSeleccionados = $_POST['empleados'];
            $errores = [];
            $exitos = 0;
            
            // Insertar cada empleado seleccionado en la tabla capacitaciones
            foreach ($empleadosSeleccionados as $idEmp) {
                // Obtener nombre del empleado para mensajes
                $sqlEmpleado = "SELECT u.NombreCompleto FROM usuarios u WHERE u.Clave = ?";
                $paramsEmpleado = array($idEmp);
                $stmtEmpleado = sqlsrv_prepare($conn, $sqlEmpleado, $paramsEmpleado);
                sqlsrv_execute($stmtEmpleado);
                $empleado = sqlsrv_fetch_array($stmtEmpleado, SQLSRV_FETCH_ASSOC);
                $nombreEmpleado = $empleado ? $empleado['NombreCompleto'] : "ID: $idEmp";
                
                // Verificar si el empleado ya está asignado a este curso
                $sqlVerificar = "SELECT Id FROM capacitaciones WHERE IdEmp = ? AND IdPlan = ?";
                $paramsVerificar = array($idEmp, $idPlan);
                $stmtVerificar = sqlsrv_prepare($conn, $sqlVerificar, $paramsVerificar);
                sqlsrv_execute($stmtVerificar);
                $existente = sqlsrv_fetch_array($stmtVerificar, SQLSRV_FETCH_ASSOC);
                
                if (!$existente) {
                    // Insertar en la tabla capacitaciones con la nueva estructura
                    $sqlInsertar = "INSERT INTO capacitaciones (IdEmp, IdPlan, FechaIni, FechaFin, Asistio) 
                                   VALUES (?, ?, ?, ?, ?)";
                    
                    $asistio = ''; // Valor inicial, puede ser actualizado después
                    
                    $paramsInsertar = array(
                        $idEmp,
                        $idPlan,
                        $fechaIniObj,
                        $fechaFinObj,
                        $asistio
                    );
                    
                    $stmtInsertar = sqlsrv_prepare($conn, $sqlInsertar, $paramsInsertar);
                    
                    if (sqlsrv_execute($stmtInsertar)) {
                        $exitos++;
                    } else {
                        $errores[] = "Error al asignar al empleado {$nombreEmpleado}: " . print_r(sqlsrv_errors(), true);
                    }
                } else {
                    $errores[] = "El empleado {$nombreEmpleado} ya está asignado a este curso.";
                }
            }
            
            // Construir mensaje de resultado
            if ($exitos > 0) {
                $mensaje = "Se han asignado $exitos participantes al curso correctamente.";
            }
            
            if (!empty($errores)) {
                $mensaje .= " Hubieron algunos errores: " . implode(". ", $errores);
            }
            } else {
                $mensaje = "Por favor, seleccione al menos un empleado para asignar al curso.";
            }
        }
    } elseif (isset($_POST['eliminar_participante'])) {
        // Eliminar participante
        $idCapacitacion = $_POST['idCapacitacion'];
        $sql = "DELETE FROM capacitaciones WHERE Id = ?";
        $params = array($idCapacitacion);
        $stmt = sqlsrv_prepare($conn, $sql, $params);
        
        if (sqlsrv_execute($stmt)) {
            $mensaje = "Participante eliminado correctamente.";
        } else {
            $mensaje = "Error al eliminar el participante: " . print_r(sqlsrv_errors(), true);
        }
    } elseif (isset($_POST['actualizar_asistencia'])) {
        // Actualizar estado de asistencia
        $idCapacitacion = $_POST['idCapacitacion'];
        $asistio = $_POST['asistio'];
        $estadoCurso = $_POST['estadoCurso'];

        // Validar que el curso esté Completado para marcar asistencia
        if ($estadoCurso !== 'Completado') {
            $mensaje = "Solo se puede marcar asistencia en capacitaciones con estado 'Completado'.";
        } else {
            $sql = "UPDATE capacitaciones SET Asistio = ? WHERE Id = ?";
            $params = array($asistio, $idCapacitacion);
            $stmt = sqlsrv_prepare($conn, $sql, $params);

            if (sqlsrv_execute($stmt)) {
                $mensaje = "Asistencia actualizada correctamente.";
            } else {
                $mensaje = "Error al actualizar la asistencia: " . print_r(sqlsrv_errors(), true);
            }
        }
    }
}

// Consultar cursos disponibles
$sqlCursos = "SELECT p.IdPlan, c.NombreCurso, p.FechaIni, c.Area, p.Estado 
              FROM plancursos p 
              LEFT JOIN cursos c ON p.IdCursoBase = c.Id
              ORDER BY p.FechaIni DESC";
$resultCursos = sqlsrv_query($conn, $sqlCursos);

// Verificar errores en la consulta
if (!$resultCursos) {
    echo "<!-- Error en consulta cursos: " . print_r(sqlsrv_errors(), true) . " -->";
}

// Si hay un curso seleccionado, consultar empleados disponibles
if ($cursoSeleccionado) {
    // Obtener lista de empleados ya asignados a este curso
    $sqlAsignados = "SELECT IdEmp FROM capacitaciones WHERE IdPlan = ?";
    $paramsAsignados = array($idPlan);
    $stmtAsignados = sqlsrv_prepare($conn, $sqlAsignados, $paramsAsignados);
    sqlsrv_execute($stmtAsignados);
    
    $empleadosAsignados = array();
    while ($row = sqlsrv_fetch_array($stmtAsignados, SQLSRV_FETCH_ASSOC)) {
        $empleadosAsignados[] = $row['IdEmp'];
    }
    
    // Consultar todos los empleados con sus puestos y departamentos
    $sqlEmpleados = "SELECT u.Clave, u.NombreCompleto, p.puesto as Puesto, p.depto as Departamento 
                     FROM usuarios u 
                     LEFT JOIN puestos p ON u.idPuesto = p.Id 
                     ORDER BY u.NombreCompleto";
    $resultEmpleados = sqlsrv_query($conn, $sqlEmpleados);
    
    // Depuración para consulta de empleados
    if (!$resultEmpleados) {
        echo "<!-- Error al consultar empleados: " . print_r(sqlsrv_errors(), true) . " -->";
    }
    
    // Consultar participantes ya asignados al curso 
    // (Ahora debemos hacer JOIN con usuarios para obtener información)
    $sqlParticipantes = "SELECT c.Id, c.IdEmp, c.FechaIni, c.FechaFin, c.Asistio, 
                         u.NombreCompleto as Empleado, p.puesto as Puesto, p.depto as Departamento 
                         FROM capacitaciones c 
                         JOIN usuarios u ON c.IdEmp = u.Clave
                         LEFT JOIN puestos p ON u.idPuesto = p.Id
                         WHERE c.IdPlan = ?
                         ORDER BY u.NombreCompleto";
    $paramsParticipantes = array($idPlan);
    $resultParticipantes = sqlsrv_query($conn, $sqlParticipantes, $paramsParticipantes);
    
    // Código de depuración
    if (!$resultParticipantes) {
        $errorInfo = print_r(sqlsrv_errors(), true);
        echo "<!-- Debug error participantes: " . $errorInfo . " -->";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Participantes</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        body {
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .mensaje {
            margin: 15px 0;
        }
        .curso-selector {
            margin-bottom: 30px;
        }
        .card {
            margin-bottom: 20px;
        }
        .empleados-container {
            max-height: 400px;
            overflow-y: auto;
        }
        .empleado-item {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .empleado-item:hover {
            background-color: #f8f9fa;
        }
        .form-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .btn-asistencia {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        .asistio-si {
            color: #28a745;
        }
        .asistio-no {
            color: #dc3545;
        }
        .asistio-na {
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h2 class="mb-4">Asignar Participantes a Capacitaciones</h2>
        
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-info mensaje"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        
        <?php if (!$cursoSeleccionado): ?>
            <!-- Lista de cursos disponibles -->
            <div class="curso-selector">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Seleccione un curso para asignar participantes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Curso</th>
                                        <th>Área</th>
                                        <th>Fecha Inicio</th>
                                        <th>Estado</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($resultCursos) {
                                        $hasRows = false;
                                        
                                        while($row = sqlsrv_fetch_array($resultCursos, SQLSRV_FETCH_ASSOC)) {
                                            $hasRows = true;
                                            
                                            // Formatear fecha
                                            $fechaIniCurso = $row['FechaIni'] instanceof DateTime ? $row['FechaIni']->format('Y-m-d') : '';
                                            
                                            echo "<tr>";
                                            echo "<td>" . $row['IdPlan'] . "</td>";
                                            echo "<td>" . $row['NombreCurso'] . "</td>";
                                            echo "<td>" . $row['Area'] . "</td>";
                                            echo "<td>" . $fechaIniCurso . "</td>";
                                            
                                            // Mostrar estado con color
                                            $estadoClass = '';
                                            switch($row['Estado']) {
                                                case 'Programado':
                                                    $estadoClass = 'text-primary';
                                                    break;
                                                case 'En proceso':
                                                    $estadoClass = 'text-warning';
                                                    break;
                                                case 'Completado':
                                                    $estadoClass = 'text-success';
                                                    break;
                                                case 'Cancelado':
                                                    $estadoClass = 'text-danger';
                                                    break;
                                            }
                                            echo "<td><span class='" . $estadoClass . "'>" . $row['Estado'] . "</span></td>";
                                            
                                            echo "<td>";
                                            echo "<a href='asignar_participantes.php?id=" . $row['IdPlan'] . "' class='btn btn-sm btn-primary'>";
                                            echo "<i class='fas fa-users'></i> Asignar Participantes";
                                            echo "</a>";
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                        
                                        if (!$hasRows) {
                                            echo "<tr><td colspan='6' class='text-center'>No hay cursos planificados disponibles</td></tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='6' class='text-center'>Error al cargar los cursos</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Detalles del curso seleccionado y asignación de participantes -->
            <div class="row">
                <div class="col-md-5">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Detalles del Curso</h5>
                        </div>
                        <div class="card-body">
                            <h5><?php echo $nombreCurso; ?></h5>
                            <p><strong>Área:</strong> <?php echo $area; ?></p>
                            <p><strong>Fecha Inicio:</strong> <?php echo $fechaIni; ?></p>
                            <p><strong>Fecha Fin:</strong> <?php echo $fechaFin; ?></p>
                            <p><strong>Estado:</strong>
                                <span class="badge <?php
                                    echo $estadoCurso === 'Programado' ? 'badge-primary' :
                                        ($estadoCurso === 'En proceso' ? 'badge-warning' :
                                        ($estadoCurso === 'Completado' ? 'badge-success' : 'badge-danger'));
                                ?>">
                                    <?php echo $estadoCurso; ?>
                                </span>
                            </p>
                            <?php if ($estadoCurso === 'Completado' || $estadoCurso === 'Cancelado'): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    No se pueden asignar participantes a capacitaciones con estado "<?php echo $estadoCurso; ?>".
                                </div>
                            <?php endif; ?>
                            <a href="asignar_participantes.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver a la lista de cursos
                            </a>
                        </div>
                    </div>
                    
                    <?php if ($estadoCurso !== 'Completado' && $estadoCurso !== 'Cancelado'): ?>
                    <div class="card mt-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Seleccionar Participantes</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $idPlan; ?>">
                                <input type="hidden" name="idPlan" value="<?php echo $idPlan; ?>">
                                <input type="hidden" name="fechaIni" value="<?php echo $fechaIni; ?>">
                                <input type="hidden" name="fechaFin" value="<?php echo $fechaFin; ?>">
                                <input type="hidden" name="estadoCurso" value="<?php echo $estadoCurso; ?>">
                                
                                <div class="form-group">
                                    <label for="empleadoFiltro">Buscar empleados:</label>
                                    <input type="text" class="form-control" id="empleadoFiltro" placeholder="Escriba para filtrar por nombre, puesto o departamento">
                                </div>
                                
                                <div class="empleados-container">
                                    <?php
                                    if (isset($resultEmpleados) && $resultEmpleados) {
                                        while ($empleado = sqlsrv_fetch_array($resultEmpleados, SQLSRV_FETCH_ASSOC)) {
                                            $yaAsignado = in_array($empleado['Clave'], $empleadosAsignados);
                                            $disabled = $yaAsignado ? 'disabled' : '';
                                            $labelClass = $yaAsignado ? 'text-muted' : '';
                                            
                                            echo "<div class='empleado-item' data-nombre='" . strtolower($empleado['NombreCompleto']) . "' " .
                                                 "data-puesto='" . strtolower($empleado['Puesto']) . "' " .
                                                 "data-departamento='" . strtolower($empleado['Departamento']) . "'>";
                                            
                                            echo "<div class='custom-control custom-checkbox'>";
                                            echo "<input type='checkbox' class='custom-control-input' id='emp" . $empleado['Clave'] . "' " .
                                                 "name='empleados[]' value='" . $empleado['Clave'] . "' $disabled>";
                                            
                                            echo "<label class='custom-control-label $labelClass' for='emp" . $empleado['Clave'] . "'>";
                                            echo "<strong>" . $empleado['NombreCompleto'] . "</strong><br>";
                                            echo "<small>Puesto: " . $empleado['Puesto'] . "</small><br>";
                                            echo "<small>Departamento: " . $empleado['Departamento'] . "</small>";
                                            
                                            if ($yaAsignado) {
                                                echo "<span class='badge badge-info ml-2'>Ya asignado</span>";
                                            }
                                            
                                            echo "</label>";
                                            echo "</div>";
                                            echo "</div>";
                                        }
                                    } else {
                                        echo "<p>No hay empleados disponibles.</p>";
                                    }
                                    ?>
                                </div>
                                
                                <button type="submit" name="agregar_participantes" class="btn btn-success mt-3">
                                    <i class="fas fa-user-plus"></i> Agregar Participantes Seleccionados
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-7">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Participantes Asignados</h5>
                        </div>
                        <div class="card-body">
                            <?php if (isset($resultParticipantes) && $resultParticipantes): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>Empleado</th>
                                                <th>Puesto</th>
                                                <th>Departamento</th>
                                                <th>Asistencia</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $hasParticipantes = false;
                                            while ($participante = sqlsrv_fetch_array($resultParticipantes, SQLSRV_FETCH_ASSOC)) {
                                                $hasParticipantes = true;
                                                echo "<tr>";
                                                echo "<td>" . $participante['Empleado'] . "</td>";
                                                echo "<td>" . $participante['Puesto'] . "</td>";
                                                echo "<td>" . $participante['Departamento'] . "</td>";
                                                
                                                // Mostrar estado de asistencia
                                                echo "<td>";
                                                
                                                $asistioClass = '';
                                                $asistioText = 'N/A';
                                                
                                                if ($participante['Asistio'] == 'Si') {
                                                    $asistioClass = 'asistio-si';
                                                    $asistioText = 'Sí asistió';
                                                } elseif ($participante['Asistio'] == 'No') {
                                                    $asistioClass = 'asistio-no';
                                                    $asistioText = 'No asistió';
                                                } else {
                                                    $asistioClass = 'asistio-na';
                                                }
                                                
                                                echo "<span class='" . $asistioClass . "'>" . $asistioText . "</span>";
                                                echo "</td>";
                                                
                                                echo "<td class='text-center'>";
                                                echo "<div class='btn-group'>";

                                                // Botones para marcar asistencia (solo si está Completado)
                                                $disabledAsistencia = ($estadoCurso !== 'Completado') ? 'disabled' : '';
                                                $titleAsistencia = ($estadoCurso !== 'Completado') ? 'Solo disponible para capacitaciones completadas' : '';

                                                echo "<form method='post' action='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $idPlan . "' style='display:inline;'>";
                                                echo "<input type='hidden' name='idCapacitacion' value='" . $participante['Id'] . "'>";
                                                echo "<input type='hidden' name='asistio' value='Si'>";
                                                echo "<input type='hidden' name='estadoCurso' value='" . $estadoCurso . "'>";
                                                echo "<button type='submit' name='actualizar_asistencia' class='btn btn-sm btn-success btn-asistencia' " .
                                                     "title='" . ($estadoCurso === 'Completado' ? 'Marcar como asistió' : $titleAsistencia) . "' $disabledAsistencia>";
                                                echo "<i class='fas fa-check'></i>";
                                                echo "</button>";
                                                echo "</form> ";

                                                echo "<form method='post' action='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $idPlan . "' style='display:inline;'>";
                                                echo "<input type='hidden' name='idCapacitacion' value='" . $participante['Id'] . "'>";
                                                echo "<input type='hidden' name='asistio' value='No'>";
                                                echo "<input type='hidden' name='estadoCurso' value='" . $estadoCurso . "'>";
                                                echo "<button type='submit' name='actualizar_asistencia' class='btn btn-sm btn-danger btn-asistencia' " .
                                                     "title='" . ($estadoCurso === 'Completado' ? 'Marcar como no asistió' : $titleAsistencia) . "' $disabledAsistencia>";
                                                echo "<i class='fas fa-times'></i>";
                                                echo "</button>";
                                                echo "</form> ";

                                                // Botón para eliminar
                                                echo "<form method='post' action='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $idPlan . "' style='display:inline;'>";
                                                echo "<input type='hidden' name='idCapacitacion' value='" . $participante['Id'] . "'>";
                                                echo "<button type='submit' name='eliminar_participante' class='btn btn-sm btn-secondary btn-asistencia' title='Eliminar participante' onclick='return confirm(\"¿Está seguro de eliminar este participante?\")'>";
                                                echo "<i class='fas fa-trash'></i>";
                                                echo "</button>";
                                                echo "</form>";

                                                echo "</div>";
                                                echo "</td>";
                                                echo "</tr>";
                                            }
                                            
                                            if (!$hasParticipantes) {
                                                echo "<tr><td colspan='5' class='text-center'>No hay participantes asignados a este curso</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-center">No hay participantes asignados a este curso.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        // Filtro para buscar empleados
        $(document).ready(function() {
            $("#empleadoFiltro").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $(".empleado-item").filter(function() {
                    var nombre = $(this).data('nombre');
                    var puesto = $(this).data('puesto');
                    var departamento = $(this).data('departamento');
                    
                    var match = nombre.indexOf(value) > -1 || 
                               puesto.indexOf(value) > -1 || 
                               departamento.indexOf(value) > -1;
                    
                    $(this).toggle(match);
                });
            });
        });
    </script>
</body>
</html>