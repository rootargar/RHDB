<?php
// Incluir el archivo de conexión
include '../conexion2.php';

// Obtener áreas únicas para el filtro
$sqlAreas = "SELECT DISTINCT Area FROM cursos WHERE Area IS NOT NULL ORDER BY Area";
$stmtAreas = sqlsrv_query($conn, $sqlAreas);

// Procesar filtros
$filtroArea = isset($_GET['area']) && $_GET['area'] != '' ? $_GET['area'] : null;

// Construir consulta con filtros
$sql = "SELECT
    c.IdCurso,
    c.Area,
    c.NombreCurso,
    COUNT(DISTINCT cap.Id) as TotalCapacitaciones,
    COUNT(DISTINCT CASE WHEN cap.Asistio = 'Si' THEN cap.Id END) as TotalAsistencias,
    COUNT(DISTINCT pc.IdPlan) as VecesImpartido
FROM cursos c
LEFT JOIN capacitaciones cap ON c.IdCurso = cap.IdCurso
LEFT JOIN plancursos pc ON c.IdCurso = pc.IdCurso";

$params = array();
$whereConditions = array();

if($filtroArea) {
    $whereConditions[] = "c.Area = ?";
    $params[] = $filtroArea;
}

if(count($whereConditions) > 0) {
    $sql .= " WHERE " . implode(" AND ", $whereConditions);
}

$sql .= " GROUP BY c.IdCurso, c.Area, c.NombreCurso
ORDER BY c.Area, c.NombreCurso";

$stmt = sqlsrv_query($conn, $sql, $params);

if($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Cursos</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- DataTables Buttons CSS -->
    <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h2 class="mb-4">
            <i class="fas fa-book"></i> Reporte de Cursos
        </h2>

        <!-- Sección de Filtros -->
        <div class="filter-section">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <label for="area" class="form-label">Filtrar por Área</label>
                    <select class="form-select" id="area" name="area">
                        <option value="">Todas las áreas</option>
                        <?php
                        sqlsrv_fetch($stmtAreas); // Reset pointer
                        $stmtAreas = sqlsrv_query($conn, $sqlAreas);
                        while($areaRow = sqlsrv_fetch_array($stmtAreas, SQLSRV_FETCH_ASSOC)):
                        ?>
                            <option value="<?php echo htmlspecialchars($areaRow['Area']); ?>"
                                    <?php echo ($filtroArea == $areaRow['Area']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($areaRow['Area']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                    <a href="cursos.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>

        <!-- Tabla de Cursos -->
        <div class="table-responsive">
            <table id="tablaCursos" class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Área</th>
                        <th>Nombre del Curso</th>
                        <th>Total Capacitaciones</th>
                        <th>Total Asistencias</th>
                        <th>Veces Impartido</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['IdCurso']); ?></td>
                        <td><?php echo htmlspecialchars($row['Area']); ?></td>
                        <td><?php echo htmlspecialchars($row['NombreCurso']); ?></td>
                        <td><?php echo htmlspecialchars($row['TotalCapacitaciones']); ?></td>
                        <td><?php echo htmlspecialchars($row['TotalAsistencias']); ?></td>
                        <td><?php echo htmlspecialchars($row['VecesImpartido']); ?></td>
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
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        $(document).ready(function() {
            $('#tablaCursos').DataTable({
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
                        title: 'Reporte de Cursos'
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fas fa-file-pdf"></i> Exportar a PDF',
                        className: 'btn btn-danger btn-sm',
                        title: 'Reporte de Cursos',
                        orientation: 'landscape'
                    },
                    {
                        extend: 'print',
                        text: '<i class="fas fa-print"></i> Imprimir',
                        className: 'btn btn-info btn-sm',
                        title: 'Reporte de Cursos'
                    }
                ],
                order: [[1, 'asc'], [2, 'asc']]
            });
        });
    </script>
</body>
</html>

<?php
// Cerrar la conexión
sqlsrv_close($conn);
?>
