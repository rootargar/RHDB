<!DOCTYPE html>
<html>
<head>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <title>Login</title>
    <style>
         body {
            background-image: url('images/fondo.jpg');
            background-color:rgb(110, 184, 236); 
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-repeat: no-repeat; 
            background-position: center;
            background-size: cover; 
            height: 50vh;
   
        }
        
        .login-form {
            width: 300px;
            margin: 200px auto;
            padding: 20px;
            background-color: white; /* Mantener el formulario blanco para contraste */
            border: 1px solid #ccc;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        input[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #019DF4; /* Azul Movistar para el botón */
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        
        input[type="submit"]:hover {
            background-color: #0082CF; /* Un poco más oscuro al pasar el mouse */
        }
        
        .header-logo {
            text-align: center;
            margin-bottom: 20px;
            color: #019DF4;
            font-size: 24px;
            font-weight: bold;
        }
    </style>

</head>
<body>
    <div class="login-form">
        <h2>Iniciar Sesión</h2>
        <form action="login.php" method="POST">
            <div class="form-group">
                <label>Usuario:</label>
                <input type="text" name="usuario" required>
            </div>
            <div class="form-group">
                <label>Contraseña:</label>
                <input type="password" name="password" required>
            </div>
            <input type="submit" value="Ingresar">
        </form>
    </div>
</body>
</html>
<?php if (isset($_GET['error'])): ?>
    <div style="color: red;">Usuario o contraseña incorrectos</div>
<?php endif; ?>
