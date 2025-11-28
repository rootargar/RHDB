<?php
// Incluir el archivo de conexión
include 'conexion2.php';

// Procesar eliminación de un curso si se recibe un ID
if(isset($_GET['eliminar']) && !empty($_GET['eliminar'])) {
    $idEliminar = $_GET['eliminar'];
    $sqlEliminar = "DELETE FROM cursos WHERE Id = ?";
    $paramsEliminar = array($idEliminar);
    $stmtEliminar = sqlsrv_query($conn, $sqlEliminar, $paramsEliminar);
    
    if($stmtEliminar === false) {
        echo "<script>alert('Error al eliminar el curso.');</script>";
    } else {
        echo "<script>alert('Curso eliminado correctamente.');</script>";
    }
}

// Procesar formulario de nuevo curso o actualización
if(isset($_POST['accion'])) {
    $area = $_POST['area'];
    $nombreCurso = $_POST['nombre_curso'];
    
    if($_POST['accion'] == 'agregar') {
        // Insertar nuevo curso
        $sqlInsertar = "INSERT INTO cursos (Area, NombreCurso) VALUES (?, ?)";
        $paramsInsertar = array($area, $nombreCurso);
        $stmtInsertar = sqlsrv_query($conn, $sqlInsertar, $paramsInsertar);
        
        if($stmtInsertar === false) {
            echo "<script>alert('Error al agregar el curso.');</script>";
        } else {
            echo "<script>alert('Curso agregado correctamente.');</script>";
        }
    } elseif($_POST['accion'] == 'actualizar') {
        // Actualizar curso existente
        $idCurso = $_POST['id_curso'];
        $sqlActualizar = "UPDATE cursos SET Area = ?, NombreCurso = ? WHERE Id = ?";
        $paramsActualizar = array($area, $nombreCurso, $idCurso);
        $stmtActualizar = sqlsrv_query($conn, $sqlActualizar, $paramsActualizar);
        
        if($stmtActualizar === false) {
            echo "<script>alert('Error al actualizar el curso.');</script>";
        } else {
            echo "<script>alert('Curso actualizado correctamente.');</script>";
        }
    }
}

// Variable para almacenar los datos del curso en caso de edición
$cursoEditar = null;

// Verificar si se solicita editar un curso
if(isset($_GET['editar']) && !empty($_GET['editar'])) {
    $idEditar = $_GET['editar'];
    $sqlEditar = "SELECT Id, Area, NombreCurso FROM cursos WHERE Id = ?";
    $paramsEditar = array($idEditar);
    $stmtEditar = sqlsrv_query($conn, $sqlEditar, $paramsEditar);
    
    if($stmtEditar !== false && sqlsrv_has_rows($stmtEditar)) {
        $cursoEditar = sqlsrv_fetch_array($stmtEditar, SQLSRV_FETCH_ASSOC);
    }
}

// Consultar todos los cursos
$sql = "SELECT Id, Area, NombreCurso FROM cursos ORDER BY Area, NombreCurso";
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
    <title>Gestión de Cursos</title>
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
        <h2 class="mb-4"><?php echo $cursoEditar ? 'Editar Curso' : 'Nuevo Curso'; ?></h2>
        
        <form method="post" class="mb-5">
            <?php if($cursoEditar): ?>
                <input type="hidden" name="id_curso" value="<?php echo $cursoEditar['Id']; ?>">
                <input type="hidden" name="accion" value="actualizar">
            <?php else: ?>
                <input type="hidden" name="accion" value="agregar">
            <?php endif; ?>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="area" class="form-label">Área</label>
                    <input type="text" class="form-control" id="area" name="area" required 
                           value="<?php echo $cursoEditar ? $cursoEditar['Area'] : ''; ?>">
                </div>
                <div class="col-md-6">
                    <label for="nombre_curso" class="form-label">Nombre del Curso</label>
                    <input type="text" class="form-control" id="nombre_curso" name="nombre_curso" required
                           value="<?php echo $cursoEditar ? $cursoEditar['NombreCurso'] : ''; ?>">
                </div>
            </div>
            
            <div class="d-flex justify-content-start">
                <button type="submit" class="btn btn-primary">
                    <?php echo $cursoEditar ? 'Actualizar Curso' : 'Agregar Curso'; ?>
                </button>
                <?php if($cursoEditar): ?>
                    <a href="cursos.php" class="btn btn-secondary ms-2">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
        
        <h2 class="mb-3">Lista de Cursos</h2>
        <table id="tablaCursos" class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Área</th>
                    <th>Nombre del Curso</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                <tr>
                    <td><?php echo $row['Id']; ?></td>
                    <td><?php echo $row['Area']; ?></td>
                    <td><?php echo $row['NombreCurso']; ?></td>
                    <td class="action-buttons">
                        <a href="cursos.php?editar=<?php echo $row['Id']; ?>" class="btn btn-warning btn-sm">Editar</a>
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
            $('#tablaCursos').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                responsive: true
            });
        });
        
        // Función para confirmar eliminación
        function confirmarEliminar(id) {
            if(confirm('¿Está seguro de que desea eliminar este curso?')) {
                window.location.href = 'cursos.php?eliminar=' + id;
            }
        }
    </script>
</body>
</html>