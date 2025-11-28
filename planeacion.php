<?php
// Incluir archivo de conexión a la base de datos
include 'conexion2.php';

// Inicializar variables
$mensaje = '';
$editando = false;
$idPlan = '';
$idCursoBase = '';
$fechaIni = '';
$fechaFin = '';
$lugar = '';
$costo = '';
$numParticipantes = '';
$horario = '';
$fechaCurso = '';
$proveedor = '';
$instructor = '';
$factura = '';
$estado = '';
$nombreCurso = ''; // Variable para almacenar el nombre del curso

// Procesar formulario cuando se envía
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verificar qué acción se está realizando
    if (isset($_POST['agregar']) || isset($_POST['actualizar'])) {
        // Obtener datos del formulario
        $idCursoBase = $_POST['idCursoBase'];
        $fechaIni = $_POST['fechaIni'];
        $fechaFin = $_POST['fechaFin'];
        $lugar = isset($_POST['lugar']) ? $_POST['lugar'] : '';
        $costo = isset($_POST['costo']) && !empty($_POST['costo']) ? $_POST['costo'] : null;
        $numParticipantes = isset($_POST['numParticipantes']) && !empty($_POST['numParticipantes']) ? $_POST['numParticipantes'] : null;
        $horario = isset($_POST['horario']) ? $_POST['horario'] : '';
        $fechaCurso = isset($_POST['fechaCurso']) && !empty($_POST['fechaCurso']) ? $_POST['fechaCurso'] : null;
        $proveedor = isset($_POST['proveedor']) ? $_POST['proveedor'] : '';
        $instructor = isset($_POST['instructor']) ? $_POST['instructor'] : '';
        $factura = isset($_POST['factura']) ? $_POST['factura'] : '';
        $estado = isset($_POST['estado']) ? $_POST['estado'] : 'Programado';
        
        // Convertir fechas al formato adecuado para SQL Server (yyyy-mm-dd)
        // Preparar las fechas como objetos DateTime para SQL Server
        $fechaIniObj = null;
        $fechaFinObj = null;
        $fechaCursoObj = null;
        
        if (!empty($fechaIni)) {
            $fechaIniObj = date_create_from_format('Y-m-d', $fechaIni);
        }
        
        if (!empty($fechaFin)) {
            $fechaFinObj = date_create_from_format('Y-m-d', $fechaFin);
        }
        
        if (!empty($fechaCurso)) {
            $fechaCursoObj = $fechaCurso; // Guardamos como string según la nueva estructura
        }
        
        // Validar datos (puedes agregar más validaciones según necesites)
        if (empty($idCursoBase) || empty($fechaIni) || empty($fechaFin)) {
            $mensaje = "Por favor, complete los campos obligatorios.";
        } else {
            // Preparar consulta SQL
            if (isset($_POST['agregar'])) {
                // Insertar nuevo registro
                $sql = "INSERT INTO plancursos (IdCursoBase, FechaIni, FechaFin, Lugar, Costo, NumParticipantes, 
                        Horario, FechaCurso, Proveedor, Instructor, Factura, Estado) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $params = array(
                    $idCursoBase, 
                    $fechaIniObj, 
                    $fechaFinObj, 
                    $lugar, 
                    $costo, 
                    $numParticipantes, 
                    $horario, 
                    $fechaCursoObj, 
                    $proveedor, 
                    $instructor, 
                    $factura, 
                    $estado
                );
                
                $stmt = sqlsrv_prepare($conn, $sql, $params);
                
                if (sqlsrv_execute($stmt)) {
                    $mensaje = "Planeación de capacitación agregada correctamente.";
                    // Limpiar campos después de agregar
                    $idCursoBase = $fechaIni = $fechaFin = $lugar = $costo = $numParticipantes = '';
                    $horario = $fechaCurso = $proveedor = $instructor = $factura = $estado = '';
                } else {
                    $mensaje = "Error al agregar la planeación: " . print_r(sqlsrv_errors(), true);
                }
            } else {
                // Actualizar registro existente
                $idPlan = $_POST['idPlan'];
                $sql = "UPDATE plancursos SET IdCursoBase=?, FechaIni=?, FechaFin=?, Lugar=?, Costo=?, 
                        NumParticipantes=?, Horario=?, FechaCurso=?, Proveedor=?, Instructor=?, 
                        Factura=?, Estado=? WHERE IdPlan=?";
                
                $params = array(
                    $idCursoBase, 
                    $fechaIniObj, 
                    $fechaFinObj, 
                    $lugar, 
                    $costo, 
                    $numParticipantes, 
                    $horario, 
                    $fechaCursoObj, 
                    $proveedor, 
                    $instructor, 
                    $factura, 
                    $estado,
                    $idPlan
                );
                
                $stmt = sqlsrv_prepare($conn, $sql, $params);
                
                if (sqlsrv_execute($stmt)) {
                    $mensaje = "Planeación actualizada correctamente.";
                    $editando = false;
                    // Limpiar campos después de actualizar
                    $idCursoBase = $fechaIni = $fechaFin = $lugar = $costo = $numParticipantes = '';
                    $horario = $fechaCurso = $proveedor = $instructor = $factura = $estado = '';
                } else {
                    $mensaje = "Error al actualizar la planeación: " . print_r(sqlsrv_errors(), true);
                }
            }
        }
    } elseif (isset($_POST['eliminar'])) {
        // Eliminar registro
        $idPlan = $_POST['idPlan'];
        $sql = "DELETE FROM plancursos WHERE IdPlan=?";
        $params = array($idPlan);
        $stmt = sqlsrv_prepare($conn, $sql, $params);
        
        if (sqlsrv_execute($stmt)) {
            $mensaje = "Planeación eliminada correctamente.";
        } else {
            $mensaje = "Error al eliminar la planeación: " . print_r(sqlsrv_errors(), true);
        }
    } elseif (isset($_POST['editar'])) {
        // Cargar datos para edición
        $idPlan = $_POST['idPlan'];
        $sql = "SELECT p.*, c.NombreCurso, c.Area 
                FROM plancursos p 
                JOIN cursos c ON p.IdCursoBase = c.Id 
                WHERE p.IdPlan=?";
        $params = array($idPlan);
        $stmt = sqlsrv_prepare($conn, $sql, $params);
        sqlsrv_execute($stmt);
        
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        
        if ($row) {
            $editando = true;
            $idPlan = $row['IdPlan'];
            $idCursoBase = $row['IdCursoBase'];
            $nombreCurso = $row['NombreCurso'];
            
            // Formatear fechas para los campos de formulario
            $fechaIni = $row['FechaIni'] instanceof DateTime ? $row['FechaIni']->format('Y-m-d') : '';
            $fechaFin = $row['FechaFin'] instanceof DateTime ? $row['FechaFin']->format('Y-m-d') : '';
            
            // Manejar específicamente FechaCurso
            $fechaCurso = $row['Fecha Curso'] ?? '';
            
            $lugar = $row['Lugar'];
            $costo = $row['Costo'];
            $numParticipantes = $row['NumParticipantes'];
            $horario = $row['Horario'];
            $proveedor = $row['Proveedor'];
            $instructor = $row['Instructor'];
            $factura = $row['Factura'];
            $estado = $row['Estado'];
        }
    } elseif (isset($_POST['cancelar'])) {
        // Cancelar edición
        $editando = false;
        $idCursoBase = $fechaIni = $fechaFin = $lugar = $costo = $numParticipantes = '';
        $horario = $fechaCurso = $proveedor = $instructor = $factura = $estado = '';
    }
}

// Consultar todos los registros para mostrar en la tabla
$sqlPlaneacion = "SELECT 
                   p.IdPlan, 
                   p.IdCursoBase,
                   c.Area, 
                   c.NombreCurso,
                   p.FechaIni, 
                   p.FechaFin, 
                   p.FechaCurso,
                   p.Lugar, 
                   p.Costo, 
                   p.NumParticipantes, 
                   p.Horario, 
                   p.Proveedor, 
                   p.Instructor, 
                   p.Factura, 
                   p.Estado
                  FROM plancursos p
                  JOIN cursos c ON p.IdCursoBase = c.Id
                  ORDER BY p.FechaIni DESC";
$resultPlaneacion = sqlsrv_query($conn, $sqlPlaneacion);

if ($resultPlaneacion === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Consultar cursos disponibles para el dropdown
$sqlCursos = "SELECT Id, Area, NombreCurso FROM cursos ORDER BY Area, NombreCurso";
$resultCursos = sqlsrv_query($conn, $sqlCursos);

if ($resultCursos === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planeación de Capacitaciones</title>
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
        .btn-group-sm .btn {
            margin-right: 5px;
        }
        .table-responsive {
            margin-top: 20px;
        }
        .form-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .required::after {
            content: " *";
            color: red;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h2 class="mb-4">Planeación de Capacitaciones</h2>
        
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-info mensaje"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        
        <div class="form-section">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <?php if ($editando): ?>
                    <input type="hidden" name="idPlan" value="<?php echo $idPlan; ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="idCursoBase" class="required">Curso:</label>
                            <select class="form-control" id="idCursoBase" name="idCursoBase" required>
                                <option value="">-- Seleccione un curso --</option>
                                <?php
                                if ($resultCursos) {
                                    while($rowCurso = sqlsrv_fetch_array($resultCursos, SQLSRV_FETCH_ASSOC)) {
                                        $selected = ($idCursoBase == $rowCurso['Id']) ? 'selected' : '';
                                        echo "<option value='" . $rowCurso['Id'] . "' " . $selected . ">" . 
                                             $rowCurso['Area'] . " - " . $rowCurso['NombreCurso'] . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="estado">Estado:</label>
                            <select class="form-control" id="estado" name="estado">
                                <option value="Programado" <?php echo ($estado == 'Programado') ? 'selected' : ''; ?>>Programado</option>
                                <option value="En proceso" <?php echo ($estado == 'En proceso') ? 'selected' : ''; ?>>En proceso</option>
                                <option value="Completado" <?php echo ($estado == 'Completado') ? 'selected' : ''; ?>>Completado</option>
                                <option value="Cancelado" <?php echo ($estado == 'Cancelado') ? 'selected' : ''; ?>>Cancelado</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="fechaIni" class="required">Fecha Inicio:</label>
                            <input type="date" class="form-control" id="fechaIni" name="fechaIni" required value="<?php echo $fechaIni; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="fechaFin" class="required">Fecha Fin:</label>
                            <input type="date" class="form-control" id="fechaFin" name="fechaFin" required value="<?php echo $fechaFin; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="fechaCurso">Fecha Curso:</label>
                            <input type="text" class="form-control" id="fechaCurso" name="fechaCurso" placeholder="Ej: 20-05-2025" value="<?php echo $fechaCurso; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="horario">Horario:</label>
                            <input type="text" class="form-control" id="horario" name="horario" placeholder="Ej: 9:00 - 13:00" value="<?php echo $horario; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="lugar">Lugar:</label>
                            <input type="text" class="form-control" id="lugar" name="lugar" value="<?php echo $lugar; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="numParticipantes">Número de Participantes:</label>
                            <input type="number" class="form-control" id="numParticipantes" name="numParticipantes" value="<?php echo $numParticipantes; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="costo">Costo:</label>
                            <input type="text" class="form-control" id="costo" name="costo" value="<?php echo $costo; ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="proveedor">Proveedor:</label>
                            <input type="text" class="form-control" id="proveedor" name="proveedor" value="<?php echo $proveedor; ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="instructor">Instructor:</label>
                            <input type="text" class="form-control" id="instructor" name="instructor" value="<?php echo $instructor; ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="factura">Factura:</label>
                            <input type="text" class="form-control" id="factura" name="factura" value="<?php echo $factura; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="form-group text-right">
                    <?php if ($editando): ?>
                        <button type="submit" name="actualizar" class="btn btn-primary">
                            <i class="fas fa-save"></i> Actualizar
                        </button>
                        <button type="submit" name="cancelar" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    <?php else: ?>
                        <button type="submit" name="agregar" class="btn btn-success">
                            <i class="fas fa-plus"></i> Agregar
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="thead-dark">
                    <tr>
                        <th>ID</th>
                        <th>Área</th>
                        <th>Curso</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Fin</th>
                        <th>Lugar</th>
                        <th>Instructor</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($resultPlaneacion) {
                        $hasRows = false;
                        
                        while($row = sqlsrv_fetch_array($resultPlaneacion, SQLSRV_FETCH_ASSOC)) {
                            $hasRows = true;
                            echo "<tr>";
                            echo "<td>" . $row['IdPlan'] . "</td>";
                            echo "<td>" . $row['Area'] . "</td>";
                            echo "<td>" . $row['NombreCurso'] . "</td>";
                            
                            // Formatear fechas correctamente
                            $fechaIni = $row['FechaIni'] instanceof DateTime ? $row['FechaIni']->format('Y-m-d') : 
                                       (is_string($row['FechaIni']) ? date('Y-m-d', strtotime($row['FechaIni'])) : '');
                            
                            $fechaFin = $row['FechaFin'] instanceof DateTime ? $row['FechaFin']->format('Y-m-d') : 
                                       (is_string($row['FechaFin']) ? date('Y-m-d', strtotime($row['FechaFin'])) : '');
                            
                            echo "<td>" . $fechaIni . "</td>";
                            echo "<td>" . $fechaFin . "</td>";
                            echo "<td>" . $row['Lugar'] . "</td>";
                            echo "<td>" . $row['Instructor'] . "</td>";
                            echo "<td>";
                            
                            // Mostrar estado con diferente color según el valor
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
                            echo "<span class='" . $estadoClass . "'>" . $row['Estado'] . "</span>";
                            echo "</td>";
                            
                            echo "<td class='text-center'>";
                            echo "<div class='btn-group btn-group-sm'>";
                            echo "<form method='post' action='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "' style='display:inline;'>";
                            echo "<input type='hidden' name='idPlan' value='" . $row['IdPlan'] . "'>";
                            echo "<button type='submit' name='editar' class='btn btn-primary' title='Editar'><i class='fas fa-edit'></i></button> ";
                            echo "<button type='submit' name='eliminar' class='btn btn-danger' title='Eliminar' onclick='return confirm(\"¿Está seguro de eliminar esta planeación?\")'><i class='fas fa-trash'></i></button>";
                            echo "</form>";
                            // Agregar botón para asignar participantes
                            echo "<a href='asignar_participantes.php?id=" . $row['IdPlan'] . "' class='btn btn-info' title='Asignar Participantes'><i class='fas fa-users'></i></a>";
                            echo "</div>";
                            echo "</td>";
                        }
                        
                        if (!$hasRows) {
                            echo "<tr><td colspan='9' class='text-center'>No hay planeaciones registradas</td></tr>";
                        }
                    } else {
                        echo "<tr><td colspan='9' class='text-center'>Error al cargar las planeaciones</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>