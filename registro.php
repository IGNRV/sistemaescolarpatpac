<?php
require_once 'db.php';

session_start();

// Función para validar la contraseña
function validarPassword($password) {
    if (strlen($password) < 8) {
        return "La contraseña debe tener al menos 8 caracteres.";
    }
    if (!preg_match("/\d/", $password)) {
        return "La contraseña debe incluir al menos un número.";
    }
    if (!preg_match("/[#\$%\^&]/", $password)) {
        return "La contraseña debe incluir al menos un símbolo (#, $, %, etc.).";
    }
    return "";
}


// Verifica si el usuario está logueado y obtiene su id
if (!isset($_SESSION['EMAIL'])) {
    header('Location: login.php');
    exit;
} else {
    $EMAIL = $_SESSION['EMAIL'];
    $queryUsuario = "SELECT ID FROM USERS WHERE EMAIL = '$EMAIL'";
    $resultadoUsuario = $conn->query($queryUsuario);
    if ($resultadoUsuario->num_rows > 0) {
        $usuario = $resultadoUsuario->fetch_assoc();
        $id_usuario = $usuario['ID'];
    } else {
        $mensaje = "Usuario no encontrado.";
        exit;
    }
}

$tiposUsuario = [];
$queryTiposUsuario = "SELECT ID, NOMBRE_TIPO_USUARIO FROM TIPO_USUARIO";
$resultadoTiposUsuario = $conn->query($queryTiposUsuario);

if ($resultadoTiposUsuario->num_rows > 0) {
    while ($fila = $resultadoTiposUsuario->fetch_assoc()) {
        $tiposUsuario[] = $fila; // Ahora incluye tanto el ID como el nombre
    }
}


$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = strtoupper($conn->real_escape_string($_POST['nombre']));
    $usuario = strtoupper($conn->real_escape_string($_POST['usuario']));
    $correo_electronico = $conn->real_escape_string($_POST['correo_electronico']);
    $password = $conn->real_escape_string($_POST['password']);
    $confirmPassword = $conn->real_escape_string($_POST['confirmPassword']);

    // Validación de la contraseña
    $validacionPassword = validarPassword($password);
    if ($validacionPassword !== "") {
        $errorMsg = $validacionPassword;
    } elseif ($password === $confirmPassword) {
        $passwordEncriptada = password_hash($password, PASSWORD_DEFAULT); // Encriptación de la contraseña

        // Verifica si el correo electrónico ya está registrado
        $checkUser = "SELECT ID FROM USERS WHERE EMAIL = '{$correo_electronico}'";
        $result = $conn->query($checkUser);

        if ($result->num_rows > 0) {
            $errorMsg = "El usuario ya existe con ese correo electrónico.";
        } else {
            // Inserta el nuevo usuario en la base de datos
            $insertUser = "INSERT INTO USERS (NAME, EMAIL, USERNAME, PASSWORD, PHOTO, STATUS) VALUES ('{$nombre}', '{$correo_electronico}', '{$usuario}', '{$passwordEncriptada}', '22_Profile.jpg', 'ACTIVO')";
            if ($conn->query($insertUser) === TRUE) {
                header('Location: login.php');
                exit;
            } else {
                $errorMsg = "Error al registrar el usuario: " . $conn->error;
            }
        }
    } else {
        $errorMsg = "Las contraseñas no coinciden.";
    }
}
?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro</title>
    <!-- Incluye Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .form-container {
            padding: 20px; /* Espaciado interno para el formulario */
            margin-top: 20px; /* Espaciado superior para separar del borde */
            box-shadow: 0 4px 8px rgba(0,0,0,0.1); /* Sombra para resaltar el formulario */
            border-radius: 8px; /* Bordes redondeados */
        }
        .btn-custom {
            width: 100%; /* Ancho total para dispositivos móviles */
        }
        @media (min-width: 768px) {
            .btn-custom {
                width: auto; /* Ancho automático para tabletas y escritorio */
            }
        }
    </style>
</head>

<body>
<div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-6">
                <div class="form-container">
                    <h2 class="text-center">Registro de Usuario</h2>
                    <form method="post" action="registro.php" class="mt-4">
                        <div class="form-group">
                            <label for="usuario">Usuario:</label>
                            <input type="text" class="form-control to-uppercase" name="usuario" value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="nombre">Nombre:</label>
                            <input type="text" class="form-control to-uppercase" name="nombre" value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Correo Electrónico:</label>
                            <input type="email" class="form-control to-uppercase" name="correo_electronico" value="<?php echo isset($_POST['correo_electronico']) ? htmlspecialchars($_POST['correo_electronico']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
    <label for="tipoUsuario">Tipo de Usuario:</label>
    <select class="form-control" name="tipoUsuario" id="tipoUsuario">
        <?php foreach ($tiposUsuario as $tipo): ?>
            <option value="<?php echo htmlspecialchars($tipo['ID']); ?>"><?php echo htmlspecialchars($tipo['NOMBRE_TIPO_USUARIO']); ?></option>
        <?php endforeach; ?>
    </select>
</div>

                        <div class="form-group">
                            <label for="password">Contraseña:</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Confirmar Contraseña:</label>
                            <input type="password" class="form-control" name="confirmPassword" required>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-custom">Registrarse</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para mostrar el mensaje de error -->
    <?php if (!empty($errorMsg)): ?>
        <div class="modal fade" id="errorModal" tabindex="-1" role="dialog" aria-labelledby="errorModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="errorModalLabel">Error</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <?php echo $errorMsg; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Incluye jQuery y Bootstrap JS -->
        <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
        <script>
            // Muestra automáticamente el modal al cargar la página
            $(document).ready(function() {
                $('#errorModal').modal('show');
            });
        </script>
       
    <?php endif; ?>
</body>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var inputs = document.querySelectorAll('.to-uppercase');
        inputs.forEach(function(input) {
            input.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        });
    });
</script>
</html>