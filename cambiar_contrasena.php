<?php
require_once 'db.php';

session_start();

$errorMsg = '';

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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $correo_electronico = $conn->real_escape_string($_POST['correo_electronico']);
    $password = $conn->real_escape_string($_POST['password']);
    $confirmPassword = $conn->real_escape_string($_POST['confirmPassword']);

    $validacionPassword = validarPassword($password);
    if ($validacionPassword !== "") {
        $errorMsg = $validacionPassword;
    } elseif ($password === $confirmPassword) {
        $passwordEncriptada = password_hash($password, PASSWORD_DEFAULT);

        // Actualiza la contraseña en la base de datos
        $stmt = $conn->prepare("UPDATE USERS SET PASSWORD = ? WHERE EMAIL = ?");
        $stmt->bind_param("ss", $passwordEncriptada, $correo_electronico);

        if ($stmt->execute()) {
            $successMsg = "Contraseña cambiada exitosamente.";
        } else {
            $errorMsg = "Error al cambiar la contraseña: " . $conn->error;
        }
        $stmt->close();
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
                    <h2 class="text-center">Cambiar Contraseña</h2>
                    <form method="post" class="mt-4">
                    <div class="form-group">
                        <label for="email">Correo Electrónico:</label>
                        <input type="email" class="form-control" name="correo_electronico" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Nueva Contraseña:</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Confirmar Nueva Contraseña:</label>
                        <input type="password" class="form-control" name="confirmPassword" required>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary btn-custom">Cambiar Contraseña</button>
                    </div>
                </form>
                <?php if (!empty($errorMsg)): ?>
                    <div class="alert alert-danger mt-3"><?php echo $errorMsg; ?></div>
                <?php endif; ?>
                <?php if (!empty($successMsg)): ?>
                    <div class="alert alert-success mt-3"><?php echo $successMsg; ?></div>
                <?php endif; ?>
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

</html>