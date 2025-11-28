<?php
// Conexión a la base de datos
include("conexion2.php");

session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit();
}

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Capacitaciones</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        :root {
            --primary-color: #3498db;
            --primary-hover: #2980b9;
            --secondary-color: #2980b9;
            --sidebar-bg: #2c3e50;
            --sidebar-hover: #34495e;
            --text-light: #ecf0f1;
            --card-bg: #ffffff;
            --card-border: #e9ecef;
            --card-hover: #f8f9fa;
            --shadow-light: 0 2px 10px rgba(0, 0, 0, 0.08);
            --shadow-medium: 0 4px 20px rgba(0, 0, 0, 0.12);
            --shadow-heavy: 0 8px 30px rgba(0, 0, 0, 0.16);
            --transition-base: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-fast: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        body {
            display: flex;
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            transition: var(--transition-base);
        }
        
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, var(--sidebar-bg) 0%, #1a252f 100%);
            color: var(--text-light);
            transition: var(--transition-base);
            box-shadow: var(--shadow-medium);
            position: relative;
            overflow: hidden;
        }
        
        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s;
        }
        
        .sidebar:hover::before {
            left: 100%;
        }
        
        .sidebar-header {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
        }
        
        .sidebar-header h3 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 8px;
            background: linear-gradient(45deg, #fff, #bdc3c7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .sidebar-menu {
            padding: 15px 0;
        }
        
        .menu-item, .nav-link {
            padding: 15px 20px;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: var(--transition-fast);
            color: var(--text-light);
            text-decoration: none;
            position: relative;
            overflow: hidden;
        }
        
        .menu-item::before, .nav-link::before {
            content: '';
            position: absolute;
            left: -100%;
            top: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(52, 152, 219, 0.3), transparent);
            transition: left 0.4s ease;
        }
        
        .menu-item:hover, .nav-link:hover {
            background: linear-gradient(90deg, var(--sidebar-hover), rgba(52, 152, 219, 0.1));
            border-left: 4px solid var(--primary-color);
            transform: translateX(5px);
            box-shadow: inset 0 0 10px rgba(52, 152, 219, 0.2);
        }
        
        .menu-item:hover::before, .nav-link:hover::before {
            left: 100%;
        }
        
        .menu-item i, .nav-link i {
            margin-right: 12px;
            font-size: 1.2rem;
            width: 22px;
            text-align: center;
            transition: var(--transition-fast);
        }
        
        .menu-item:hover i, .nav-link:hover i {
            transform: scale(1.1) rotate(5deg);
            color: var(--primary-color);
        }
        
        .active {
            background: linear-gradient(90deg, var(--sidebar-hover), rgba(52, 152, 219, 0.2));
            border-left: 4px solid var(--primary-color);
            box-shadow: inset 0 0 15px rgba(52, 152, 219, 0.3);
        }
        
        .content {
            flex: 1;
            padding: 25px;
            overflow-y: auto;
        }
        
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding: 20px 0;
            border-bottom: 2px solid transparent;
            background: linear-gradient(90deg, transparent, rgba(52, 152, 219, 0.1), transparent);
            border-radius: 8px;
            transition: var(--transition-base);
        }
        
        .content-header:hover {
            background: linear-gradient(90deg, rgba(52, 152, 219, 0.05), rgba(52, 152, 219, 0.15), rgba(52, 152, 219, 0.05));
            border-bottom-color: var(--primary-color);
        }
        
        .content-header h2 {
            color: #2c3e50;
            font-weight: 700;
            font-size: 1.8rem;
            transition: var(--transition-fast);
        }
        
        .content-header:hover h2 {
            transform: translateX(5px);
            color: var(--primary-color);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 25px;
            transition: var(--transition-fast);
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }
        
        .user-info:hover {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: var(--shadow-light);
            transform: translateY(-2px);
        }
        
        .user-info img {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            margin-right: 12px;
            transition: var(--transition-fast);
            border: 2px solid transparent;
        }
        
        .user-info:hover img {
            transform: scale(1.05);
            border-color: var(--primary-color);
        }
        
        .card-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
            margin: 35px 0;
            justify-content: center;
        }
        
        .card {
            display: flex;
            flex-direction: column;
            height: 100%;
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow-light);
            padding: 25px;
            text-decoration: none;
            color: inherit;
            transition: var(--transition-base);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(52, 152, 219, 0.1);
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, var(--primary-color), #2ecc71, var(--primary-color));
            transition: left 0.6s ease;
        }
        
        .card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--shadow-heavy);
            border-color: var(--primary-color);
        }
        
        .card:hover::before {
            left: 0;
        }
        
        .card-icon {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 3rem;
            color: var(--primary-color);
            transition: var(--transition-fast);
        }
        
        .card:hover .card-icon {
            transform: scale(1.1) rotate(10deg);
            filter: drop-shadow(0 4px 8px rgba(52, 152, 219, 0.3));
        }
        
        .card h3 {
            text-align: center;
            margin-bottom: 18px;
            font-weight: 600;
            color: #2c3e50;
            transition: var(--transition-fast);
        }
        
        .card:hover h3 {
            color: var(--primary-color);
            transform: scale(1.05);
        }
        
        .card p {
            flex-grow: 1;
            margin-bottom: 25px;
            text-align: center;
            line-height: 1.6;
            color: #555;
            transition: var(--transition-fast);
        }
        
        .card .btn {
            align-self: center;
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(45deg, var(--primary-color), var(--primary-hover));
            color: white;
            border-radius: 25px;
            font-weight: 600;
            text-align: center;
            margin-top: auto;
            transition: var(--transition-fast);
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .card .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transition: width 0.4s, height 0.4s, top 0.4s, left 0.4s;
            transform: translate(-50%, -50%);
        }
        
        .card .btn:hover {
            background: linear-gradient(45deg, var(--primary-hover), #1abc9c);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(52, 152, 219, 0.4);
        }
        
        .card .btn:hover::before {
            width: 100%;
            height: 100%;
        }
        
        .dashboard {
            display: flex;
            justify-content: space-between;
            margin-bottom: 35px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .stat-card {
            flex: 1;
            min-width: 220px;
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border-radius: 15px;
            box-shadow: var(--shadow-light);
            padding: 25px;
            text-align: center;
            transition: var(--transition-base);
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }
        
        .stat-card::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
            transform: scale(0);
            transition: transform 0.6s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px) rotate(1deg);
            box-shadow: var(--shadow-heavy);
        }
        
        .stat-card:hover::after {
            transform: scale(1);
        }
        
        .stat-card-empleados {
            border-top: 4px solid #3498db;
        }
        
        .stat-card-programados {
            border-top: 4px solid #2ecc71;
        }
        
        .stat-card-completados {
            border-top: 4px solid #f39c12;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            margin: 15px 0;
            color: #2c3e50;
            transition: var(--transition-fast);
        }
        
        .stat-card:hover .stat-number {
            transform: scale(1.1);
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            font-weight: 600;
        }
        
        .stat-icon {
            font-size: 2rem;
            margin-bottom: 18px;
            transition: var(--transition-fast);
        }
        
        .stat-card:hover .stat-icon {
            transform: scale(1.2) rotate(15deg);
        }
        
        .stat-card-empleados .stat-icon {
            color: #3498db;
        }
        
        .stat-card-programados .stat-icon {
            color: #2ecc71;
        }
        
        .stat-card-completados .stat-icon {
            color: #f39c12;
        }
        
        .submenu {
            list-style: none;
            padding-left: 20px;
            display: none;
            background: rgba(0, 0, 0, 0.1);
            border-radius: 0 0 8px 8px;
            margin: 5px 15px;
            overflow: hidden;
        }
        
        .submenu.show {
            display: block;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                max-height: 0;
            }
            to {
                opacity: 1;
                max-height: 200px;
            }
        }
        
        .submenu li a {
            padding: 10px 15px;
            font-size: 0.9em;
            display: block;
            color: #bdc3c7;
            transition: var(--transition-fast);
            text-decoration: none;
            border-radius: 4px;
            margin: 2px 0;
        }
        
        .submenu li a:hover {
            color: #fff;
            background: linear-gradient(90deg, rgba(52, 152, 219, 0.3), rgba(52, 152, 219, 0.1));
            transform: translateX(5px);
        }
        
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
            }
            
            .card-container {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 20px;
            }
            
            .dashboard {
                flex-direction: column;
            }
            
            .stat-card {
                width: 100%;
            }
        }

        /* Animación de carga suave */
        .page-content {
            display: none;
            animation: fadeIn 0.5s ease-in-out;
        }
        
        .page-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .welcome-message {
            text-align: center;
            margin: 40px 0;
            padding: 30px;
            background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(248,249,250,0.9) 100%);
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }
        
        .welcome-message h1 {
            font-size: 2.8rem;
            background: linear-gradient(45deg, var(--primary-color), #2ecc71);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 15px;
            font-weight: 800;
        }
        
        .welcome-message p {
            font-size: 1.15rem;
            color: #555;
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>Sistema de Capacitaciones</h3>
            <p>Panel de Control</p>
        </div>
        <div class="sidebar-menu">
            <a href="#inicio" class="menu-item active" data-page="inicio">
                <i class="fas fa-home"></i>
                <span>Inicio</span>
            </a>
            <a href="#empleados" class="menu-item" data-page="empleados">
                <i class="fas fa-users"></i>
                <span>Empleados</span>
            </a>
            <a href="#cursos" class="menu-item" data-page="cursos">
                <i class="fas fa-user-graduate"></i>
                <span>Cursos</span>
            </a>
            <a href="#puestos" class="menu-item" data-page="puestos">
                <i class="fas fa-briefcase"></i>
                <span>Puestos</span>
            </a>
            <a href="#cursoxpuesto" class="menu-item" data-page="cursoxpuesto">
                <i class="fas fa-clipboard-check"></i>
                <span>Cursos Por Puesto</span>
            </a>
            <a href="#planeacion" class="menu-item" data-page="planeacion">
                <i class="fas fa-calendar-alt"></i>
                <span>Programar Capacitación</span>
            </a>
            <a href="#capacitacion" class="menu-item" data-page="capacitacion">
                <i class="fas fa-user-plus"></i>
                <span>Asignar Participantes</span>
            </a>
            <a href="#certificados" class="menu-item" data-page="certificados">
                <i class="fas fa-graduation-cap"></i>
                <span>Capacitaciones y Certificados</span>
            </a>
           
            <!-- Opción de Reportes en el menú lateral con submenú -->
            <li class="nav-item">
                <a href="#reportes" class="nav-link" data-page="reportes">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reportes</span>
                    <i class="fas fa-angle-down ml-auto"></i>
                </a>
                <ul class="submenu" id="submenu-reportes">
                    <li><a href="#reportes-empleados" class="nav-link">Empleados</a></li>
                    <li><a href="#reportes-cursos" class="nav-link">Cursos</a></li>
                    <li><a href="#reportes-puestos" class="nav-link">Puestos</a></li>
                    <li><a href="#reportes-cursos-puesto" class="nav-link">Cursos Por Puesto</a></li>
                    <li><a href="#reportes-cursos-programados" class="nav-link">Cursos Programados</a></li>
                    <li><a href="#reportes-capacitaciones" class="nav-link">Capacitaciones</a></li>
                    <li><a href="#reportes-proximas-capacitaciones" class="nav-link">Próximas Capacitaciones</a></li>
                    <li><a href="#reportes-cursos-concluidos" class="nav-link">Cursos Concluidos</a></li>
                    <li><a href="#reportes-cursos-faltantes" class="nav-link">Cursos Faltantes</a></li>
                    <li><a href="#reportes-asistencias" class="nav-link">Asistencias</a></li>
                    <li><a href="#reportes-faltas" class="nav-link">Faltas</a></li>
                </ul>
            </li>

            <a href="logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content">
        <div class="content-header">
            <h2>Panel de Control</h2>
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span>Bienvenido, <span class="user-name"><?php echo htmlspecialchars($_SESSION['usuario']); ?></span></span>
            </div>
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
                <a href="#empleados" class="card" data-target="empleados">
                    <div class="card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Empleados</h3>
                    <p>Gestione la información de los empleados, agregue nuevos, actualice</p>
                    <span class="btn">Acceder</span>
                </a>
                
                <a href="#cursos" class="card" data-target="cursos">
                    <div class="card-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h3>Cursos</h3>
                    <p>Administre los cursos disponibles, cree nuevos cursos y actualice la información.</p>
                    <span class="btn">Acceder</span>
                </a>
                
                <a href="#puestos" class="card" data-target="puestos">
                    <div class="card-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <h3>Puestos de Empleados</h3>
                    <p>Gestione los puestos de trabajo, sus descripciones y requisitos.</p>
                    <span class="btn">Acceder</span>
                </a>

                <a href="#cursoxpuesto" class="card" data-target="cursoxpuesto">
                    <div class="card-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <h3>Asigna Cursos a Cada puesto</h3>
                    <p>Gestione los puestos de trabajo, sus descripciones y requisitos.</p>
                    <span class="btn">Acceder</span>
                </a>
                
                <a href="#planeacion" class="card" data-target="planeacion">
                    <div class="card-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3>Programar Capacitación</h3>
                    <p>Programe nuevas capacitaciones, asigne instructores y participantes.</p>
                    <span class="btn">Acceder</span>
                </a>
                <a href="#capacitacion" class="card" data-target="capacitacion">
                    <div class="card-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h3>Asignar Participantes</h3>
                    <p>Programe nuevas capacitaciones, asigne instructores y participantes.</p>
                    <span class="btn">Acceder</span>
                </a>
                <a href="#certificados" class="card" data-target="certificados">
                    <div class="card-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3>Capacitaciones Y Certificados</h3>
                    <p>Consulte capacitaciones  y Certificados obtenidos.</p>
                    <span class="btn">Acceder</span>
                </a>
              
              
                <a href="#reportes" class="card" data-target="reportes">
                    <div class="card-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3>Reportes</h3>
                    <p>Genere informes detallados sobre las capacitaciones, asistencia y rendimiento.</p>
                    <span class="btn">Acceder</span>
                </a>
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
            $('.menu-item, .nav-link').click(function(e) {
                if($(this).attr('href') !== 'logout.php') {
                    e.preventDefault();
                    const targetPage = $(this).data('page');
                    
                    // Si no es el enlace de reportes (que tiene submenú)
                    if (targetPage && targetPage !== 'reportes') {
                        // Actualizar menú activo
                        $('.menu-item, .nav-link').removeClass('active');
                        $(this).addClass('active');
                        
                        // Mostrar página correspondiente
                        $('.page-content').removeClass('active');
                        $('#' + targetPage).addClass('active');
                    }
                }
            });
            
            // Manejo de tarjetas en la página de inicio
            $('.card').click(function(e) {
    const targetPage = $(this).data('target');
    
    // Si no tiene un data-target, significa que es un enlace externo
    if (!targetPage) {
        return; // Permite que el enlace externo funcione normalmente
    }
    
    // Solo previene el comportamiento predeterminado si es una navegación interna
    e.preventDefault();
    
    // El resto del código para la navegación interna...
    $('.menu-item, .nav-link').removeClass('active');
    $('.menu-item[data-page="' + targetPage + '"], .nav-link[data-page="' + targetPage + '"]').addClass('active');
    
    $('.page-content').removeClass('active');
    $('#' + targetPage).addClass('active');
    
    if (targetPage === 'reportes') {
        $('#submenu-reportes').addClass('show');
    }
});
            
            // Toggle para el submenú de reportes
            $('a[href="#reportes"]').click(function(e) {
                e.preventDefault();
                $('#submenu-reportes').toggleClass('show');
                
                // Si se hace clic en el enlace principal de reportes, también mostramos
                // la página de reportes (podría ser una página de índice de reportes)
                $('.page-content').removeClass('active');
                $('#reportes').addClass('active');
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
                    
                    // También mantener el submenú visible
                    $('#submenu-reportes').addClass('show');
                }
            }
            
            // Agregar listeners para cada enlace del submenú
            $.each(reportesFiles, function(reporteHash, phpFile) {
                $(`a[href="${reporteHash}"]`).click(function(e) {
                    e.preventDefault();
                    cargarReporte(reporteHash);
                    
                    // Actualizar el hash de la URL sin recargar la página
                    window.location.hash = reporteHash;
                });
            });
            
            // Función para cargar el contenido de usuarios en el iframe
            function cargarUsuarios() {
                $('#usuarios-iframe').attr('src', 'usuarios.php');
            }
            
            // Función para cargar el contenido de cursos en el iframe
            function cargarCursos() {
                $('#cursos-iframe').attr('src', 'cursos.php');
            }
            
            // Función para cargar el contenido de puestos en el iframe
            function cargarPuestos() {
                $('#puestos-iframe').attr('src', 'puestos.php');
            }
            
            // Función para cargar el contenido de cursoxpuesto en el iframe
            function cargarCursoxPuesto() {
                $('#cursoxpuesto-iframe').attr('src', 'cursoxpuesto.php');
            }
            
            // Función para cargar el contenido de planeación en el iframe
            function cargarPlaneacion() {
                $('#planeacion-iframe').attr('src', 'planeacion.php');
            }
            
            // Función para cargar el contenido de capacitación en el iframe
            function cargarCapacitacion() {
                $('#capacitacion-iframe').attr('src', 'asignar_participantes.php');
            }
             // Función para cargar el contenido de certificados en el iframe
            function cargarcertificados() {
                $('#certificados-iframe').attr('src', 'certificaciones.php');
            }
            
            
            // Verificar el hash actual al cargar la página
            const currentHash = window.location.hash;
            
            // Iniciar las secciones según el hash actual
            if (currentHash === '#empleados') {
                $('.menu-item[data-page="empleados"]').click();
                cargarUsuarios();
            } else if (currentHash === '#cursos') {
                $('.menu-item[data-page="cursos"]').click();
                cargarCursos();
            } else if (currentHash === '#puestos') {
                $('.menu-item[data-page="puestos"]').click();
                cargarPuestos();
            } else if (currentHash === '#cursoxpuesto') {
                $('.menu-item[data-page="cursoxpuesto"]').click();
                cargarCursoxPuesto();
            } else if (currentHash === '#planeacion') {
                $('.menu-item[data-page="planeacion"]').click();
                cargarPlaneacion();
            } else if (currentHash === '#capacitacion') {
                $('.menu-item[data-page="capacitacion"]').click();
                cargarCapacitacion();
            } else if (currentHash === '#certificados') {
                $('.menu-item[data-page="certificados"]').click();
                cargarcertificados();    
            }              else if (reportesFiles[currentHash]) {
                cargarReporte(currentHash);
            }
            
            // Escuchar cambios en el hash de la URL
            $(window).on('hashchange', function() {
                const newHash = window.location.hash;
                
                if (newHash === '#empleados') {
                    $('.menu-item[data-page="empleados"]').click();
                    cargarUsuarios();
                } else if (newHash === '#cursos') {
                    $('.menu-item[data-page="cursos"]').click();
                    cargarCursos();
                } else if (newHash === '#puestos') {
                    $('.menu-item[data-page="puestos"]').click();
                    cargarPuestos();
                } else if (newHash === '#cursoxpuesto') {
                    $('.menu-item[data-page="cursoxpuesto"]').click();
                    cargarCursoxPuesto();
                } else if (newHash === '#planeacion') {
                    $('.menu-item[data-page="planeacion"]').click();
                    cargarPlaneacion();
                } else if (newHash === '#capacitacion') {
                    $('.menu-item[data-page="capacitacion"]').click();
                    cargarCapacitacion();
                } else if (newHash === '#certificados') {
                    $('.menu-item[data-page="certificados"]').click();
                    cargarcertificados();    
                }  else if (reportesFiles[newHash]) {
                    cargarReporte(newHash);
                }
            });
            
            // Agregar event listeners adicionales para los enlaces directos
            $('a[href="#empleados"]').click(function() {
                cargarUsuarios();
            });
            
            $('a[href="#cursos"]').click(function() {
                cargarCursos();
            });
            
            $('a[href="#puestos"]').click(function() {
                cargarPuestos();
            });
            
            $('a[href="#cursoxpuesto"]').click(function() {
                cargarCursoxPuesto();
            });
            
            $('a[href="#planeacion"]').click(function() {
                cargarPlaneacion();
            });
            
            $('a[href="#capacitacion"]').click(function() {
                cargarCapacitacion();
            });
            $('a[href="#certificados"]').click(function() {
                cargarcertificados();
            });
          
        });
    </script>
</body>
</html>