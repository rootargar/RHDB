<?php
// Incluir el archivo de conexión
include 'conexion2.php';

// Procesar eliminación de un puesto si se recibe un ID
if(isset($_GET['eliminar']) && !empty($_GET['eliminar'])) {
    $idEliminar = $_GET['eliminar'];
    $sqlEliminar = "DELETE FROM puestos WHERE Id = ?";
    $paramsEliminar = array($idEliminar);
    $stmtEliminar = sqlsrv_query($conn, $sqlEliminar, $paramsEliminar);
    
    if($stmtEliminar === false) {
        echo "<script>alert('Error al eliminar el puesto.');</script>";
    } else {
        echo "<script>alert('Puesto eliminado correctamente.');</script>";
    }
}

// Procesar formulario de nuevo puesto o actualización
if(isset($_POST['accion'])) {
    $puesto = $_POST['puesto'];
    $depto = $_POST['depto'];
    
    if($_POST['accion'] == 'agregar') {
        // Insertar nuevo puesto
        $sqlInsertar = "INSERT INTO puestos (puesto, depto) VALUES (?, ?)";
        $paramsInsertar = array($puesto, $depto);
        $stmtInsertar = sqlsrv_query($conn, $sqlInsertar, $paramsInsertar);
        
        if($stmtInsertar === false) {
            echo "<script>alert('Error al agregar el puesto.');</script>";
        } else {
            echo "<script>alert('Puesto agregado correctamente.');</script>";
        }
    } elseif($_POST['accion'] == 'actualizar') {
        // Actualizar puesto existente
        $idPuesto = $_POST['id_puesto'];
        $sqlActualizar = "UPDATE puestos SET puesto = ?, depto = ? WHERE Id = ?";
        $paramsActualizar = array($puesto, $depto, $idPuesto);
        $stmtActualizar = sqlsrv_query($conn, $sqlActualizar, $paramsActualizar);
        
        if($stmtActualizar === false) {
            echo "<script>alert('Error al actualizar el puesto.');</script>";
        } else {
            echo "<script>alert('Puesto actualizado correctamente.');</script>";
        }
    }
}

// Variable para almacenar los datos del puesto en caso de edición
$puestoEditar = null;

// Verificar si se solicita editar un puesto
if(isset($_GET['editar']) && !empty($_GET['editar'])) {
    $idEditar = $_GET['editar'];
    $sqlEditar = "SELECT Id, puesto, depto FROM puestos WHERE Id = ?";
    $paramsEditar = array($idEditar);
    $stmtEditar = sqlsrv_query($conn, $sqlEditar, $paramsEditar);
    
    if($stmtEditar !== false && sqlsrv_has_rows($stmtEditar)) {
        $puestoEditar = sqlsrv_fetch_array($stmtEditar, SQLSRV_FETCH_ASSOC);
    }
}

// Consultar todos los puestos
$sql = "SELECT Id, puesto, depto FROM puestos ORDER BY depto, puesto";
$stmt = sqlsrv_query($conn, $sql);

if($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Puestos</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        .container {
            margin-top: 20px;
        }
        .action-buttons {
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mb-4"><?php echo $puestoEditar ? 'Editar Puesto' : 'Nuevo Puesto'; ?></h2>
        
        <form method="post" class="mb-5">
            <?php if($puestoEditar): ?>
                <input type="hidden" name="id_puesto" value="<?php echo $puestoEditar['Id']; ?>">
                <input type="hidden" name="accion" value="actualizar">
            <?php else: ?>
                <input type="hidden" name="accion" value="agregar">
            <?php endif; ?>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="puesto" class="form-label">Puesto</label>
                    <input type="text" class="form-control" id="puesto" name="puesto" required 
                           value="<?php echo $puestoEditar ? $puestoEditar['puesto'] : ''; ?>">
                </div>
                <div class="col-md-6">
                    <label for="depto" class="form-label">Departamento</label>
                    <input type="text" class="form-control" id="depto" name="depto" required
                           value="<?php echo $puestoEditar ? $puestoEditar['depto'] : ''; ?>">
                </div>
            </div>
            
            <div class="d-flex justify-content-start">
                <button type="submit" class="btn btn-primary">
                    <?php echo $puestoEditar ? 'Actualizar Puesto' : 'Agregar Puesto'; ?>
                </button>
                <?php if($puestoEditar): ?>
                    <a href="puestos.php" class="btn btn-secondary ms-2">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
        
        <h2 class="mb-3">Lista de Puestos</h2>
        <table id="tablaPuestos" class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Puesto</th>
                    <th>Departamento</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                <tr>
                    <td><?php echo $row['Id']; ?></td>
                    <td><?php echo $row['puesto']; ?></td>
                    <td><?php echo $row['depto']; ?></td>
                    <td class="action-buttons">
                        <a href="puestos.php?editar=<?php echo $row['Id']; ?>" class="btn btn-warning btn-sm">Editar</a>
                        <a href="javascript:void(0);" onclick="confirmarEliminar(<?php echo $row['Id']; ?>)" class="btn btn-danger btn-sm">Eliminar</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Bootstrap JS y Popper -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <!-- jQuery y DataTables -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Inicializar DataTable
            $('#tablaPuestos').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                responsive: true
            });
        });
        
        // Función para confirmar eliminación
        function confirmarEliminar(id) {
            if(confirm('¿Está seguro de que desea eliminar este puesto?')) {
                window.location.href = 'puestos.php?eliminar=' + id;
            }
        }
    </script>
</body>
</html>