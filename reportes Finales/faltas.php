<?php
// Incluir el archivo de conexión
include '../conexion2.php';

// Obtener datos para filtros
$sqlDeptos = "SELECT DISTINCT Departamento FROM capacitaciones WHERE Departamento IS NOT NULL ORDER BY Departamento";
$stmtDeptos = sqlsrv_query($conn, $sqlDeptos);

$sqlCursos = "SELECT DISTINCT NomCurso FROM capacitaciones WHERE NomCurso IS NOT NULL ORDER BY NomCurso";
$stmtCursos = sqlsrv_query($conn, $sqlCursos);

// Procesar filtros
$filtroDepto = isset($_GET['departamento']) && $_GET['departamento'] != '' ? $_GET['departamento'] : null;
$filtroCurso = isset($_GET['curso']) && $_GET['curso'] != '' ? $_GET['curso'] : null;
$filtroFechaInicio = isset($_GET['fecha_inicio']) && $_GET['fecha_inicio'] != '' ? $_GET['fecha_inicio'] : null;
$filtroFechaFin = isset($_GET['fecha_fin']) && $_GET['fecha_fin'] != '' ? $_GET['fecha_fin'] : null;

// Construir consulta con filtros - solo faltas
$sql = "SELECT
    cap.Id,
    cap.IdEmp,
    cap.Empleado,
    cap.Puesto,
    cap.Departamento,
    cap.NomCurso,
    cap.Area,
    cap.FechaIni,
    cap.FechaFin,
    CASE
        WHEN cap.Asistio = 'No' THEN 'Inasistencia Registrada'
        WHEN cap.Asistio IS NULL OR cap.Asistio = '' THEN 'Sin Confirmar'
        ELSE 'Otro'
    END as TipoFalta
FROM capacitaciones cap
WHERE cap.Asistio = 'No' OR cap.Asistio IS NULL OR cap.Asistio = ''";

$params = array();

if($filtroDepto) {
    $sql .= " AND cap.Departamento = ?";
    $params[] = $filtroDepto;
}

if($filtroCurso) {
    $sql .= " AND cap.NomCurso = ?";
    $params[] = $filtroCurso;
}

if($filtroFechaInicio) {
    $sql .= " AND cap.FechaIni >= ?";
    $params[] = $filtroFechaInicio;
}

if($filtroFechaFin) {
    $sql .= " AND cap.FechaFin <= ?";
    $params[] = $filtroFechaFin;
}

$sql .= " ORDER BY cap.FechaIni DESC, cap.Empleado";

$stmt = sqlsrv_query($conn, $sql, $params);

if($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Calcular estadísticas
$sqlStats = "SELECT
    COUNT(CASE WHEN Asistio = 'No' THEN 1 END) as TotalFaltas,
    COUNT(CASE WHEN Asistio IS NULL OR Asistio = '' THEN 1 END) as SinConfirmar,
    COUNT(DISTINCT CASE WHEN Asistio = 'No' THEN IdEmp END) as EmpleadosConFaltas,
    COUNT(DISTINCT CASE WHEN Asistio = 'No' THEN NomCurso END) as CursosConFaltas
FROM capacitaciones
WHERE Asistio = 'No' OR Asistio IS NULL OR Asistio = ''";

$stmtStats = sqlsrv_query($conn, $sqlStats);
$stats = sqlsrv_fetch_array($stmtStats, SQLSRV_FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Faltas</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- DataTables Buttons CSS -->
    <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .container-fluid {
            padding: 20px;
        }
        .filter-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .stats-card {
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            color: white;
        }
        .stats-card-1 {
            background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%);
        }
        .stats-card-2 {
            background: linear-gradient(135deg, #fbc2eb 0%, #a6c1ee 100%);
        }
        .stats-card-3 {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        .stats-card-4 {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .stats-card h3 {
            margin: 0;
            font-size: 2rem;
        }
        .stats-card p {
            margin: 0;
            font-size: 0.9rem;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h2 class="mb-4">
            <i class="fas fa-times-circle"></i> Reporte de Faltas e Inasistencias
        </h2>

        <!-- Estadísticas Generales -->
        <div class="row">
            <div class="col-md-3">
                <div class="stats-card stats-card-1">
                    <h3><?php echo $stats['TotalFaltas']; ?></h3>
                    <p><i class="fas fa-times-circle"></i> Faltas Registradas</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-card-2">
                    <h3><?php echo $stats['SinConfirmar']; ?></h3>
                    <p><i class="fas fa-question-circle"></i> Sin Confirmar</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-card-3">
                    <h3><?php echo $stats['EmpleadosConFaltas']; ?></h3>
                    <p><i class="fas fa-users"></i> Empleados con Faltas</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-card-4">
                    <h3><?php echo $stats['CursosConFaltas']; ?></h3>
                    <p><i class="fas fa-book"></i> Cursos con Faltas</p>
                </div>
            </div>
        </div>

        <!-- Sección de Filtros -->
        <div class="filter-section">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label for="departamento" class="form-label">Departamento</label>
                    <select class="form-select" id="departamento" name="departamento">
                        <option value="">Todos</option>
                        <?php
                        while($deptoRow = sqlsrv_fetch_array($stmtDeptos, SQLSRV_FETCH_ASSOC)):
                        ?>
                            <option value="<?php echo htmlspecialchars($deptoRow['Departamento']); ?>"
                                    <?php echo ($filtroDepto == $deptoRow['Departamento']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($deptoRow['Departamento']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="curso" class="form-label">Curso</label>
                    <select class="form-select" id="curso" name="curso">
                        <option value="">Todos</option>
                        <?php
                        while($cursoRow = sqlsrv_fetch_array($stmtCursos, SQLSRV_FETCH_ASSOC)):
                        ?>
                            <option value="<?php echo htmlspecialchars($cursoRow['NomCurso']); ?>"
                                    <?php echo ($filtroCurso == $cursoRow['NomCurso']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cursoRow['NomCurso']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio"
                           value="<?php echo $filtroFechaInicio ? $filtroFechaInicio : ''; ?>">
                </div>
                <div class="col-md-2">
                    <label for="fecha_fin" class="form-label">Fecha Fin</label>
                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin"
                           value="<?php echo $filtroFechaFin ? $filtroFechaFin : ''; ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                    <a href="faltas.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>

        <!-- Tabla de Faltas -->
        <div class="table-responsive">
            <table id="tablaFaltas" class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>ID Empleado</th>
                        <th>Empleado</th>
                        <th>Puesto</th>
                        <th>Departamento</th>
                        <th>Curso</th>
                        <th>Área</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Fin</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['Id']); ?></td>
                        <td><?php echo htmlspecialchars($row['IdEmp']); ?></td>
                        <td><?php echo htmlspecialchars($row['Empleado']); ?></td>
                        <td><?php echo htmlspecialchars($row['Puesto']); ?></td>
                        <td><?php echo htmlspecialchars($row['Departamento']); ?></td>
                        <td><?php echo htmlspecialchars($row['NomCurso']); ?></td>
                        <td><?php echo htmlspecialchars($row['Area']); ?></td>
                        <td>
                            <?php
                            if($row['FechaIni']) {
                                echo $row['FechaIni']->format('d/m/Y');
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if($row['FechaFin']) {
                                echo $row['FechaFin']->format('d/m/Y');
                            }
                            ?>
                        </td>
                        <td class="text-center">
                            <?php if($row['TipoFalta'] == 'Inasistencia Registrada'): ?>
                                <span class="badge bg-danger">
                                    <i class="fas fa-times"></i> Falta
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-question"></i> Sin Confirmar
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bootstrap JS y Popper -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- DataTables Buttons -->
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

    <script>
        $(document).ready(function() {
            $('#tablaFaltas').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                responsive: true,
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excel',
                        text: '<i class="fas fa-file-excel"></i> Exportar a Excel',
                        className: 'btn btn-success btn-sm',
                        title: 'Reporte de Faltas',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9]
                        }
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fas fa-file-pdf"></i> Exportar a PDF',
                        className: 'btn btn-danger btn-sm',
                        title: 'Reporte de Faltas',
                        orientation: 'landscape',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9]
                        }
                    },
                    {
                        extend: 'print',
                        text: '<i class="fas fa-print"></i> Imprimir',
                        className: 'btn btn-info btn-sm',
                        title: 'Reporte de Faltas'
                    }
                ],
                order: [[7, 'desc']]
            });
        });
    </script>
</body>
</html>

<?php
// Cerrar la conexión
sqlsrv_close($conn);
?>
