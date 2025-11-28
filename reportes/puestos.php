<?php
// Incluir el archivo de conexión
include '../conexion2.php';

// Obtener departamentos únicos para el filtro
$sqlDeptos = "SELECT DISTINCT depto FROM puestos WHERE depto IS NOT NULL ORDER BY depto";
$stmtDeptos = sqlsrv_query($conn, $sqlDeptos);

// Procesar filtros
$filtroDepto = isset($_GET['depto']) && $_GET['depto'] != '' ? $_GET['depto'] : null;

// Construir consulta con filtros
$sql = "SELECT
    p.Id,
    p.puesto,
    p.depto,
    COUNT(DISTINCT u.Clave) as TotalEmpleados,
    COUNT(DISTINCT cp.IdCurso) as CursosRequeridos,
    COUNT(DISTINCT ap.IdActividad) as ActividadesRequeridas
FROM puestos p
LEFT JOIN usuarios u ON p.puesto = u.Puesto
LEFT JOIN cursoxpuesto cp ON p.puesto = cp.puesto
LEFT JOIN ActividadesXPuesto ap ON p.puesto = ap.puesto";

$params = array();
$whereConditions = array();

if($filtroDepto) {
    $whereConditions[] = "p.depto = ?";
    $params[] = $filtroDepto;
}

if(count($whereConditions) > 0) {
    $sql .= " WHERE " . implode(" AND ", $whereConditions);
}

$sql .= " GROUP BY p.Id, p.puesto, p.depto
ORDER BY p.depto, p.puesto";

$stmt = sqlsrv_query($conn, $sql, $params);

if($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Calcular estadísticas generales
$sqlStats = "SELECT
    COUNT(DISTINCT p.Id) as TotalPuestos,
    COUNT(DISTINCT u.Clave) as TotalEmpleados,
    COUNT(DISTINCT cp.IdCurso) as TotalCursosAsignados
FROM puestos p
LEFT JOIN usuarios u ON p.puesto = u.Puesto
LEFT JOIN cursoxpuesto cp ON p.puesto = cp.puesto";

$stmtStats = sqlsrv_query($conn, $sqlStats);
$stats = sqlsrv_fetch_array($stmtStats, SQLSRV_FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Puestos</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .stats-card-2 {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .stats-card-3 {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
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
            <i class="fas fa-briefcase"></i> Reporte de Puestos
        </h2>

        <!-- Estadísticas Generales -->
        <div class="row">
            <div class="col-md-4">
                <div class="stats-card stats-card-1">
                    <h3><?php echo $stats['TotalPuestos']; ?></h3>
                    <p><i class="fas fa-briefcase"></i> Total de Puestos</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card stats-card-2">
                    <h3><?php echo $stats['TotalEmpleados']; ?></h3>
                    <p><i class="fas fa-users"></i> Total de Empleados</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card stats-card-3">
                    <h3><?php echo $stats['TotalCursosAsignados']; ?></h3>
                    <p><i class="fas fa-book"></i> Cursos Asignados a Puestos</p>
                </div>
            </div>
        </div>

        <!-- Sección de Filtros -->
        <div class="filter-section">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <label for="depto" class="form-label">Filtrar por Departamento</label>
                    <select class="form-select" id="depto" name="depto">
                        <option value="">Todos los departamentos</option>
                        <?php
                        while($deptoRow = sqlsrv_fetch_array($stmtDeptos, SQLSRV_FETCH_ASSOC)):
                        ?>
                            <option value="<?php echo htmlspecialchars($deptoRow['depto']); ?>"
                                    <?php echo ($filtroDepto == $deptoRow['depto']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($deptoRow['depto']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                    <a href="puestos.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>

        <!-- Tabla de Puestos -->
        <div class="table-responsive">
            <table id="tablaPuestos" class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Puesto</th>
                        <th>Departamento</th>
                        <th>Total Empleados</th>
                        <th>Cursos Requeridos</th>
                        <th>Actividades Requeridas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['Id']); ?></td>
                        <td><?php echo htmlspecialchars($row['puesto']); ?></td>
                        <td><?php echo htmlspecialchars($row['depto']); ?></td>
                        <td>
                            <span class="badge bg-primary">
                                <?php echo htmlspecialchars($row['TotalEmpleados']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-success">
                                <?php echo htmlspecialchars($row['CursosRequeridos']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-info">
                                <?php echo htmlspecialchars($row['ActividadesRequeridas']); ?>
                            </span>
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
            $('#tablaPuestos').DataTable({
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
                        title: 'Reporte de Puestos'
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fas fa-file-pdf"></i> Exportar a PDF',
                        className: 'btn btn-danger btn-sm',
                        title: 'Reporte de Puestos',
                        orientation: 'landscape'
                    },
                    {
                        extend: 'print',
                        text: '<i class="fas fa-print"></i> Imprimir',
                        className: 'btn btn-info btn-sm',
                        title: 'Reporte de Puestos'
                    }
                ],
                order: [[2, 'asc'], [1, 'asc']]
            });
        });
    </script>
</body>
</html>

<?php
// Cerrar la conexión
sqlsrv_close($conn);
?>
