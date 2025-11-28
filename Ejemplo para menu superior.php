<?php
// Incluir validación de autenticación y permisos
require_once 'auth_check.php';

// Verificar autenticación
requerir_autenticacion();

// Conexión a la base de datos
include("conexion2.php");

// Obtener información del usuario
$usuario_actual = get_usuario_actual();
$rol_actual = get_rol_usuario();
$nombre_completo = get_nombre_completo_usuario();

// Consulta para contar empleados activos
$sql_empleados = "SELECT COUNT(*) as total_empleados FROM usuarios";
$result_empleados = sqlsrv_query($conn, $sql_empleados);
$row_empleados = sqlsrv_fetch_array($result_empleados, SQLSRV_FETCH_ASSOC);
$total_empleados = $row_empleados['total_empleados'];

// Consulta para contar cursos programados
$sql_programados = "SELECT COUNT(*) as total_programados FROM plancursos WHERE Estado = 'programado'";
$result_programados = sqlsrv_query($conn, $sql_programados);
$row_programados = sqlsrv_fetch_array($result_programados, SQLSRV_FETCH_ASSOC);
$total_programados = $row_programados['total_programados'];

// Consulta para contar cursos completados
$sql_completados = "SELECT COUNT(*) as total_completados FROM plancursos WHERE Estado = 'completado'";
$result_completados = sqlsrv_query($conn, $sql_completados);
$row_completados = sqlsrv_fetch_array($result_completados, SQLSRV_FETCH_ASSOC);
$total_completados = $row_completados['total_completados'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <link rel="stylesheet" href="styles.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Sistema de Capacitaciones</title>
   
</head>
<body>
     <nav class="top-navbar">
        <div class="navbar-brand">
            <!-- Solo el logo, sin textos -->
            <img src="kwdaf.png" alt="Logo de la empresa">
        </div>
    <!-- Top Navigation -->
    
        <div class="navbar-menu">
            <a href="#inicio" class="nav-item active" data-page="inicio">
                <i class="fas fa-home"></i>
                <span>Inicio</span>
            </a>

            <?php if (tiene_permiso($rol_actual, 'empleados')): ?>
            <a href="#empleados" class="nav-item" data-page="empleados">
                <i class="fas fa-users"></i>
                <span>Empleados</span>
            </a>
            <?php endif; ?>

            <?php if (tiene_permiso($rol_actual, 'cursos')): ?>
            <a href="#cursos" class="nav-item" data-page="cursos">
                <i class="fas fa-user-graduate"></i>
                <span>Cursos</span>
            </a>
            <?php endif; ?>

            <?php if (tiene_permiso($rol_actual, 'puestos')): ?>
            <a href="#puestos" class="nav-item" data-page="puestos">
                <i class="fas fa-briefcase"></i>
                <span>Puestos</span>
            </a>
            <?php endif; ?>

            <?php if (tiene_permiso($rol_actual, 'cursoxpuesto')): ?>
            <a href="#cursoxpuesto" class="nav-item" data-page="cursoxpuesto">
                <i class="fas fa-clipboard-check"></i>
                <span>Cursos Por Puesto</span>
            </a>
            <?php endif; ?>

            <?php if (tiene_permiso($rol_actual, 'planeacion')): ?>
            <a href="#planeacion" class="nav-item" data-page="planeacion">
                <i class="fas fa-calendar-alt"></i>
                <span>Programar Capacitación</span>
            </a>
            <?php endif; ?>

            <?php if (tiene_permiso($rol_actual, 'capacitacion')): ?>
            <a href="#capacitacion" class="nav-item" data-page="capacitacion">
                <i class="fas fa-user-plus"></i>
                <span>Asignar Participantes</span>
            </a>
            <?php endif; ?>

            <?php if (tiene_permiso($rol_actual, 'certificados')): ?>
            <a href="#certificados" class="nav-item" data-page="certificados">
                <i class="fas fa-graduation-cap"></i>
                <span>Capacitaciones y Certificados</span>
            </a>
            <?php endif; ?>

            <?php if (tiene_permiso($rol_actual, 'reportes')): ?>
            <!-- Opción de Reportes en el menú superior con dropdown -->
            <div class="nav-dropdown">
                <a href="#reportes" class="nav-item dropdown-toggle" data-page="reportes">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reportes</span>
                    <i class="fas fa-angle-down"></i>
                </a>
                <div class="dropdown-menu" id="dropdown-reportes">
                    <?php if (es_administrador() || es_supervisor()): ?>
                    <a href="#reportes-empleados" class="dropdown-item">Empleados</a>
                    <a href="#reportes-cursos" class="dropdown-item">Cursos</a>
                    <a href="#reportes-puestos" class="dropdown-item">Puestos</a>
                    <a href="#reportes-cursos-puesto" class="dropdown-item">Cursos Por Puesto</a>
                    <a href="#reportes-cursos-programados" class="dropdown-item">Cursos Programados</a>
                    <a href="#reportes-capacitaciones" class="dropdown-item">Capacitaciones</a>
                    <a href="#reportes-proximas-capacitaciones" class="dropdown-item">Próximas Capacitaciones</a>
                    <?php endif; ?>

                    <?php if (tiene_permiso_accion($rol_actual, 'reportes', 'cursos_concluidos')): ?>
                    <a href="#reportes-cursos-concluidos" class="dropdown-item">Cursos Concluidos</a>
                    <?php endif; ?>

                    <?php if (tiene_permiso_accion($rol_actual, 'reportes', 'cursos_faltantes')): ?>
                    <a href="#reportes-cursos-faltantes" class="dropdown-item">Cursos Faltantes</a>
                    <?php endif; ?>

                    <?php if (es_administrador() || es_supervisor()): ?>
                    <a href="#reportes-asistencias" class="dropdown-item">Asistencias</a>
                    <a href="#reportes-faltas" class="dropdown-item">Faltas</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="navbar-user">
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span>
                    <?php echo $nombre_completo ? htmlspecialchars($nombre_completo) : htmlspecialchars($usuario_actual); ?>
                    <br>
                    <small style="font-size: 0.8em; opacity: 0.8;">
                        <?php
                        $badge_color = '#6c757d';
                        if ($rol_actual === ROL_ADMINISTRADOR) $badge_color = '#dc3545';
                        elseif ($rol_actual === ROL_SUPERVISOR) $badge_color = '#0d6efd';
                        elseif ($rol_actual === ROL_INSTRUCTOR) $badge_color = '#0dcaf0';
                        elseif ($rol_actual === ROL_GERENTE) $badge_color = '#198754';
                        ?>
                        <span style="background-color: <?php echo $badge_color; ?>; padding: 2px 8px; border-radius: 10px; color: white;">
                            <?php echo htmlspecialchars($rol_actual); ?>
                        </span>
                    </small>
                </span>
            </div>
            <a href="logout.php" class="nav-item logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-header">
            <h2>  </h2>
        </div>

        <!-- Página de Inicio -->
        <div id="inicio" class="page-content active">
            <div class="welcome-message">
                <h1>Bienvenido al Sistema de Capacitaciones</h1>
                <p>Gestione de manera eficiente las capacitaciones, empleados, cursos y más.</p>
            </div>
               <!-- Dashboard de estadísticas -->
            <div class="dashboard">
                <div class="stat-card stat-card-empleados">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_empleados; ?></div>
                    <div class="stat-label">Empleados Activos</div>
                </div>
                
                <div class="stat-card stat-card-programados">
                    <div class="stat-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_programados; ?></div>
                    <div class="stat-label">Cursos Programados</div>
                </div>
                
                <div class="stat-card stat-card-completados">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_completados; ?></div>
                    <div class="stat-label">Cursos Completados</div>
                </div>
            </div>

            <div class="card-container">
                <?php if (tiene_permiso($rol_actual, 'empleados')): ?>
                <a href="#empleados" class="card" data-target="empleados">
                    <div class="card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Empleados</h3>
                    <p>Gestione la información de los empleados, agregue nuevos, actualice</p>
                    <span class="btn">Acceder</span>
                </a>
                <?php endif; ?>

                <?php if (tiene_permiso($rol_actual, 'cursos')): ?>
                <a href="#cursos" class="card" data-target="cursos">
                    <div class="card-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h3>Cursos</h3>
                    <p>Administre los cursos disponibles, cree nuevos cursos y actualice.</p>
                    <span class="btn">Acceder</span>
                </a>
                <?php endif; ?>

                <?php if (tiene_permiso($rol_actual, 'puestos')): ?>
                <a href="#puestos" class="card" data-target="puestos">
                    <div class="card-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <h3>Puestos de Empleados</h3>
                    <p>Gestione los puestos de trabajo, sus descripciones y requisitos.</p>
                    <span class="btn">Acceder</span>
                </a>
                <?php endif; ?>

                <?php if (tiene_permiso($rol_actual, 'cursoxpuesto')): ?>
                <a href="#cursoxpuesto" class="card" data-target="cursoxpuesto">
                    <div class="card-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <h3>Asigna Cursos</h3>
                    <p>Defina los Cursos a cada puesto de trabajo.</p>
                    <span class="btn">Acceder</span>
                </a>
                <?php endif; ?>

                <?php if (tiene_permiso($rol_actual, 'planeacion')): ?>
                <a href="#planeacion" class="card" data-target="planeacion">
                    <div class="card-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3>Programar Capacitación</h3>
                    <p>Programe nuevas capacitaciones, asigne instructores y participantes.</p>
                    <span class="btn">Acceder</span>
                </a>
                <?php endif; ?>

                <?php if (tiene_permiso($rol_actual, 'capacitacion')): ?>
                <a href="#capacitacion" class="card" data-target="capacitacion">
                    <div class="card-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h3>Asignar Participantes</h3>
                    <p>Programe nuevas capacitaciones, asigne instructores y participantes.</p>
                    <span class="btn">Acceder</span>
                </a>
                <?php endif; ?>

                <?php if (tiene_permiso($rol_actual, 'certificados')): ?>
                <a href="#certificados" class="card" data-target="certificados">
                    <div class="card-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3>Capacitaciones Y Certificados</h3>
                    <p>Consulte capacitaciones  y Certificados obtenidos.</p>
                    <span class="btn">Acceder</span>
                </a>
                <?php endif; ?>

                <?php if (tiene_permiso($rol_actual, 'reportes')): ?>
                <a href="#reportes" class="card" data-target="reportes">
                    <div class="card-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3>Reportes</h3>
                    <p>Genere informes detallados sobre las capacitaciones, asistencia y rendimiento.</p>
                    <span class="btn">Acceder</span>
                </a>
                <?php endif; ?>

                <!-- Enlace externo disponible para todos los usuarios -->
                <a href="https://kwdaf.freshdesk.com/support/solutions" class="card" target="_blank">
                    <div class="card-icon">
                        <i class="fas fa-sitemap"></i>
                    </div>
                    <h3>Enlace a Politicas Y Procesos</h3>
                    <p>Accede a Politicas Y Procesos de la Empresa.</p>
                    <span class="btn">Acceder</span>
                </a>
            </div>
        </div>

        <!-- Página de Empleados -->
        <div id="empleados" class="page-content">
            <!-- Contenedor para el iframe -->
            <div id="contenido-usuarios" class="content-loader">
                <iframe id="usuarios-iframe" style="width:100%; height:800px; border:none;" src="about:blank"></iframe>
            </div>
        </div>

        <!-- Página de Cursos -->
        <div id="cursos" class="page-content">
            <!-- Contenedor para el iframe -->
            <div id="contenido-cursos" class="content-loader">
                <iframe id="cursos-iframe" style="width:100%; height:800px; border:none;" src="about:blank"></iframe>
            </div>
        </div>

        <!-- Página de Puestos -->
        <div id="puestos" class="page-content">
            <!-- Contenedor para el iframe -->
            <div id="contenido-puestos" class="content-loader">
                <iframe id="puestos-iframe" style="width:100%; height:800px; border:none;" src="about:blank"></iframe>
            </div>
        </div>

        <!-- Página de cursoxpuesto -->
        <div id="cursoxpuesto" class="page-content">
            <!-- Contenedor para el iframe -->
            <div id="contenido-cursoxpuesto" class="content-loader">
                <iframe id="cursoxpuesto-iframe" style="width:100%; height:800px; border:none;" src="about:blank"></iframe>
            </div>
        </div>

        <!-- Página de Planeación de Capacitaciones -->
        <div id="planeacion" class="page-content">
            <!-- Contenedor para el iframe -->
            <div id="contenido-planeacion" class="content-loader">
                <iframe id="planeacion-iframe" style="width:100%; height:800px; border:none;" src="about:blank"></iframe>
            </div>
        </div>

        <!-- Página de Asignar participantes -->
        <div id="capacitacion" class="page-content">
            <!-- Contenedor para el iframe -->
            <div id="contenido-capacitacion" class="content-loader">
                <iframe id="capacitacion-iframe" style="width:100%; height:800px; border:none;" src="about:blank"></iframe>
            </div>
        </div>
             <!-- Página de Asignar certificados -->
        <div id="certificados" class="page-content">
            <!-- Contenedor para el iframe -->
            <div id="contenido-certificados" class="content-loader">
                <iframe id="certificados-iframe" style="width:100%; height:800px; border:none;" src="about:blank"></iframe>
            </div>
        </div>
       
        <!-- Página de Reportes -->
        <div id="reportes" class="page-content">
            <!-- Contenedor para el iframe de reportes -->
            <div id="contenido-reportes" class="content-loader">
                <iframe id="reportes-iframe" style="width:100%; height:800px; border:none;" src="about:blank"></iframe>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
    $(document).ready(function() {
    // Manejo de navegación general
    $('.nav-item:not(.dropdown-toggle), .dropdown-item').click(function(e) {
        if($(this).attr('href') !== 'logout.php' && !$(this).hasClass('dropdown-toggle')) {
            e.preventDefault();
            const targetPage = $(this).data('page') || $(this).attr('href').substring(1);
            
            if (targetPage && targetPage !== 'reportes') {
                // Actualizar menú activo
                $('.nav-item, .dropdown-item').removeClass('active');
                $(this).addClass('active');
                
                // Mostrar página correspondiente
                $('.page-content').removeClass('active');
                $('#' + targetPage).addClass('active');
                
                // Cerrar dropdowns
                $('.dropdown-menu').removeClass('show');
            }
        }
    });
    
    // Manejo de tarjetas en la página de inicio
    $('.card').click(function(e) {
        const targetPage = $(this).data('target');
        
        if (!targetPage) {
            return;
        }
        
        e.preventDefault();
        
        $('.nav-item, .dropdown-item').removeClass('active');
        $('.nav-item[data-page="' + targetPage + '"], .dropdown-item[data-page="' + targetPage + '"]').addClass('active');
        
        $('.page-content').removeClass('active');
        $('#' + targetPage).addClass('active');
        
        if (targetPage === 'reportes') {
            $('#dropdown-reportes').addClass('show');
        }
    });
    
    // Toggle para el dropdown de reportes
    $('.dropdown-toggle').click(function(e) {
        e.preventDefault();
        const dropdown = $(this).next('.dropdown-menu');
        $('.dropdown-menu').not(dropdown).removeClass('show');
        dropdown.toggleClass('show');
        
        // Mostrar la página de reportes
        $('.page-content').removeClass('active');
        $('#reportes').addClass('active');
        
        // Actualizar menú activo
        $('.nav-item, .dropdown-item').removeClass('active');
        $(this).addClass('active');
    });
    
    // Cerrar dropdowns cuando se hace clic fuera
    $(document).click(function(e) {
        if (!$(e.target).closest('.nav-dropdown').length) {
            $('.dropdown-menu').removeClass('show');
        }
    });
    
    // Mapeo de rutas hash a archivos PHP
    const reportesFiles = {
        '#reportes-empleados': 'reportes/empleados.php',
        '#reportes-cursos': 'reportes/cursos.php',
        '#reportes-puestos': 'reportes/puestos.php',
        '#reportes-cursos-puesto': 'reportes/reportecursosxpuesto.php',
        '#reportes-cursos-programados': 'reportes/cursos_programados.php',
        '#reportes-capacitaciones': 'reportes/capacitaciones.php',
        '#reportes-proximas-capacitaciones': 'reportes/proximas_capacitaciones.php',
        '#reportes-cursos-concluidos': 'reportes/cursos_concluidos.php',
        '#reportes-cursos-faltantes': 'reportes/cursos_faltantes.php',
        '#reportes-asistencias': 'reportes/asistencias.php',
        '#reportes-faltas': 'reportes/faltas.php'
    };
    
    // Función para cargar el contenido de reportes en el iframe
    function cargarReporte(reporteHash) {
        const iframe = $('#reportes-iframe');
        if (reportesFiles[reporteHash]) {
            iframe.attr('src', reportesFiles[reporteHash]);
            
            // Mostrar el contenedor de reportes
            $('.page-content').removeClass('active');
            $('#reportes').addClass('active');
            
            // Mantener el dropdown visible
            $('#dropdown-reportes').addClass('show');
            
            // Actualizar menú activo
            $('.nav-item, .dropdown-item').removeClass('active');
            $('.dropdown-toggle').addClass('active');
            $(`a[href="${reporteHash}"]`).addClass('active');
        }
    }
    
    // Agregar listeners para cada enlace del dropdown
    $.each(reportesFiles, function(reporteHash, phpFile) {
        $(`a[href="${reporteHash}"]`).click(function(e) {
            e.preventDefault();
            cargarReporte(reporteHash);
            window.location.hash = reporteHash;
        });
    });
    
    // Funciones para cargar contenido en iframes
    function cargarUsuarios() {
        $('#usuarios-iframe').attr('src', 'usuarios.php');
    }
    
    function cargarCursos() {
        $('#cursos-iframe').attr('src', 'cursos.php');
    }
    
    function cargarPuestos() {
        $('#puestos-iframe').attr('src', 'puestos.php');
    }
    
    function cargarCursoxPuesto() {
        $('#cursoxpuesto-iframe').attr('src', 'cursoxpuesto.php');
    }
    
    function cargarPlaneacion() {
        $('#planeacion-iframe').attr('src', 'planeacion.php');
    }
    
    function cargarCapacitacion() {
        $('#capacitacion-iframe').attr('src', 'asignar_participantes.php');
    }
    
    function cargarcertificados() {
        $('#certificados-iframe').attr('src', 'certificaciones.php');
    }
    
    // Verificar el hash actual al cargar la página
    const currentHash = window.location.hash;
    
    if (currentHash === '#empleados') {
        $('.nav-item[data-page="empleados"]').addClass('active');
        $('.page-content').removeClass('active');
        $('#empleados').addClass('active');
        cargarUsuarios();
    } else if (currentHash === '#cursos') {
        $('.nav-item[data-page="cursos"]').addClass('active');
        $('.page-content').removeClass('active');
        $('#cursos').addClass('active');
        cargarCursos();
    } else if (currentHash === '#puestos') {
        $('.nav-item[data-page="puestos"]').addClass('active');
        $('.page-content').removeClass('active');
        $('#puestos').addClass('active');
        cargarPuestos();
    } else if (currentHash === '#cursoxpuesto') {
        $('.nav-item[data-page="cursoxpuesto"]').addClass('active');
        $('.page-content').removeClass('active');
        $('#cursoxpuesto').addClass('active');
        cargarCursoxPuesto();
    } else if (currentHash === '#planeacion') {
        $('.nav-item[data-page="planeacion"]').addClass('active');
        $('.page-content').removeClass('active');
        $('#planeacion').addClass('active');
        cargarPlaneacion();
    } else if (currentHash === '#capacitacion') {
        $('.nav-item[data-page="capacitacion"]').addClass('active');
        $('.page-content').removeClass('active');
        $('#capacitacion').addClass('active');
        cargarCapacitacion();
    } else if (currentHash === '#certificados') {
        $('.nav-item[data-page="certificados"]').addClass('active');
        $('.page-content').removeClass('active');
        $('#certificados').addClass('active');
        cargarcertificados();
    } else if (reportesFiles[currentHash]) {
        cargarReporte(currentHash);
    } else {
        // Por defecto mostrar inicio
        $('.nav-item[data-page="inicio"]').addClass('active');
        $('.page-content').removeClass('active');
        $('#inicio').addClass('active');
    }
    
    // Escuchar cambios en el hash de la URL
    $(window).on('hashchange', function() {
        const newHash = window.location.hash;
        
        if (newHash === '#empleados') {
            $('.nav-item[data-page="empleados"]').click();
            cargarUsuarios();
        } else if (newHash === '#cursos') {
            $('.nav-item[data-page="cursos"]').click();
            cargarCursos();
        } else if (newHash === '#puestos') {
            $('.nav-item[data-page="puestos"]').click();
            cargarPuestos();
        } else if (newHash === '#cursoxpuesto') {
            $('.nav-item[data-page="cursoxpuesto"]').click();
            cargarCursoxPuesto();
        } else if (newHash === '#planeacion') {
            $('.nav-item[data-page="planeacion"]').click();
            cargarPlaneacion();
        } else if (newHash === '#capacitacion') {
            $('.nav-item[data-page="capacitacion"]').click();
            cargarCapacitacion();
        } else if (newHash === '#certificados') {
            $('.nav-item[data-page="certificados"]').click();
            cargarcertificados();
        } else if (reportesFiles[newHash]) {
            cargarReporte(newHash);
        }
    });
    
    // Event listeners para enlaces directos
    $('a[href="#empleados"]').click(function() { cargarUsuarios(); });
    $('a[href="#cursos"]').click(function() { cargarCursos(); });
    $('a[href="#puestos"]').click(function() { cargarPuestos(); });
    $('a[href="#cursoxpuesto"]').click(function() { cargarCursoxPuesto(); });
    $('a[href="#planeacion"]').click(function() { cargarPlaneacion(); });
    $('a[href="#capacitacion"]').click(function() { cargarCapacitacion(); });
    $('a[href="#certificados"]').click(function() { cargarcertificados(); });
});
    </script>
</body>
</html>