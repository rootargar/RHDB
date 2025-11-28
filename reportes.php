<?php

// Obtener el tipo de reporte solicitado
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'default';

// Definir la ruta del reporte según el tipo
$ruta_reporte = '';
switch($tipo) {
    case 'empleados':
        $titulo = 'Reporte de Empleados';
        $ruta_reporte = 'reportes/empleados.php';
        break;
    case 'cursos':
        $titulo = 'Reporte de Cursos';
        $ruta_reporte = 'reportes/cursos.php';
        break;
    case 'puestos':
        $titulo = 'Reporte de Puestos';
        $ruta_reporte = 'reportes/puestos.php';
        break;
    case 'cursos_por_puesto':
        $titulo = 'Reporte de Cursos Por Puesto';
        $ruta_reporte = 'reportes/cursos_por_puesto.php';
        break;
    case 'cursos_programados':
        $titulo = 'Reporte de Cursos Programados';
        $ruta_reporte = 'reportes/cursos_programados.php';
        break;
    case 'capacitaciones':
        $titulo = 'Reporte de Capacitaciones';
        $ruta_reporte = 'reportes/capacitaciones.php';
        break;
    case 'proximas_capacitaciones':
        $titulo = 'Reporte de Próximas Capacitaciones';
        $ruta_reporte = 'reportes/proximas_capacitaciones.php';
        break;
    case 'cursos_empleado_concluidos':
        $titulo = 'Reporte de Cursos Por Empleado Concluidos';
        $ruta_reporte = 'reportes/cursos_empleado_concluidos.php';
        break;
    case 'cursos_empleado_faltantes':
        $titulo = 'Reporte de Cursos Por Empleado Faltantes';
        $ruta_reporte = 'reportes/cursos_empleado_faltantes.php';
        break;
    case 'asistencias':
        $titulo = 'Reporte de Asistencias';
        $ruta_reporte = 'reportes/asistencias.php';
        break;
    case 'faltas':
        $titulo = 'Reporte de Faltas';
        $ruta_reporte = 'reportes/faltas.php';
        break;
    default:
        $titulo = 'Reportes del Sistema';
        $ruta_reporte = 'reportes/default.php';
        break;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo; ?> - Sistema de Capacitación</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        #content-container {
            padding: 15px;
            background-color: #f9f9f9;
            min-height: calc(100vh - 80px);
        }
        .iframe-container {
            width: 100%;
            height: calc(100vh - 100px);
            border: none;
        }
        iframe {
            width: 100%;
            height: 100%;
            border: none;
            background-color: #fff;
        }
    </style>
</head>
<body>
    <!-- Aquí iría tu encabezado y menú principal -->
    
    <div id="content-container">
        <div class="iframe-container">
            <?php if (!empty($ruta_reporte)): ?>
                <iframe src="<?php echo $ruta_reporte; ?>" frameborder="0" scrolling="auto"></iframe>
            <?php else: ?>
                <div class="alert alert-info">
                    <h4><i class="fas fa-info-circle me-2"></i>Bienvenido a la sección de Reportes</h4>
                    <p>Seleccione un tipo de reporte del menú lateral para visualizar la información.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
