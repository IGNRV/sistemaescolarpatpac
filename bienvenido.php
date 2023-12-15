<?php
// Incluye la conexión a la base de datos
require_once 'db.php';
/* error_reporting(E_ALL);
ini_set('display_errors', 1); */
// Inicia sesión
session_start();

// Verifica si el usuario está logueado
if (!isset($_SESSION['EMAIL'])) {
    header('Location: login.php');
    exit;
}

// Obtener el ID del usuario que ha iniciado sesión
$EMAIL = $_SESSION['EMAIL'];
$queryUsuario = "SELECT ID FROM USERS WHERE EMAIL = '$EMAIL'";
$resultadoUsuario = $conn->query($queryUsuario);

/* $id_usuario = '';
if ($resultadoUsuario->num_rows > 0) {
    $usuario = $resultadoUsuario->fetch_assoc();
    $id_usuario = $usuario['id'];

    // Consulta para obtener la foto del alumno
    $queryFoto = "SELECT foto_de_alumno FROM alumno WHERE id_usuario = $id_usuario";
    $resultadoFoto = $conn->query($queryFoto);

    if ($resultadoFoto->num_rows > 0) {
        $fila = $resultadoFoto->fetch_assoc();
        $nombre_foto = $fila['foto_de_alumno'] ? $fila['foto_de_alumno'] : '22_Profile.jpg';

        // Construir la URL completa de la foto
        $url_foto = "https://sistemaescolar.oralisisdataservice.cl/fperfil/$nombre_foto";
        $foto_de_alumno = $url_foto;
    } else {
        // Asignar la foto de perfil por defecto si no hay foto en la base de datos
        $nombre_foto = "22_Profile.jpg";
        $foto_de_alumno = "https://sistemaescolar.oralisisdataservice.cl/fperfil/$nombre_foto";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["nueva_foto"])) {
    $nombre_temporal = $_FILES["nueva_foto"]["tmp_name"];
    $nombre_foto = $_FILES["nueva_foto"]["name"];
    
    // Mover la nueva foto a la carpeta de perfil
    move_uploaded_file($nombre_temporal, "/var/www/html/sistemaescolar/fperfil/$nombre_foto");
    
    // Actualizar el nombre de la foto en la base de datos
    $queryActualizarFoto = "UPDATE alumno SET foto_de_alumno = '$nombre_foto' WHERE id_usuario = $id_usuario";
    $conn->query($queryActualizarFoto);
    
    // Redirigir para evitar reenvíos del formulario
    header("Location: bienvenido.php");
    exit;
} */
include 'nav.php';
?>



<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bienvenido</title>
    <!-- Agregamos Bootstrap -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <!-- Estilos personalizados -->
    <style>
        .custom-container {
            max-width: 600px; /* Ajusta el ancho según tus necesidades */
            margin: auto; /* Centra horizontalmente */
            display: flex;
            flex-direction: column;
            min-height: 100vh; /* Mantiene el centrado vertical */
        }

        .custom-button {
            width: auto; /* Ancho automático según el contenido del botón */
            margin: auto; /* Centra horizontalmente */
        }

        /* Ajustamos el tamaño de la letra del título */
        h1 {
            font-size: 1.2em; /* Puedes ajustar este valor según tu preferencia */
        }
        h2 {
            font-size: 1.2em; /* Puedes ajustar este valor según tu preferencia */
        }
        h3 {
            font-size: 1.2em; /* Puedes ajustar este valor según tu preferencia */
        }
        #formCambioFoto {
            display: none;
        }

        /* Estilo adicional para el botón de cambio de foto */
        #btnCambiarFoto {
            cursor: pointer;
            text-decoration: underline;
            color: blue;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="container mt-5 custom-container">
         <!-- Mostrar la foto de perfil del alumno -->
         <!-- <form id="cambiarFotoForm" method="post" action="" enctype="multipart/form-data" style="display: none;">
            <label for="nueva_foto">Cambiar foto de perfil:</label>
            <input type="file" name="nueva_foto" id="nueva_foto" style="display: none;">
            <button type="submit">Cargar Nueva Foto</button>
        </form>

        <img id="fotoPerfil" src="<?php echo $foto_de_alumno; ?>" alt="Foto de Perfil" class="img-fluid" style="border-radius: 50%; max-width: 200px; margin: 20px auto; cursor: pointer;" onclick="document.getElementById('btnCambiarFoto').click();"> -->

        <?php
        if (isset($_GET['page'])) {
            $page = $_GET['page'];
            switch ($page) {
                case 'datos_alumno':
                    include 'datos_alumno.php';
                    break;
                case 'emergencias':
                    include 'emergencias.php';
                    break;
                case 'padres_apoderados':
                    include 'padres_apoderados.php';
                    break;
                case 'tutor_economico':
                    include 'tutor_economico.php';
                    break;
                case 'pago_electronico':
                    include 'pago_electronico.php';
                    break;
                case 'vista_pago_automatico':
                    include 'vista_pago_automatico.php';
                    break;
                case 'pago_rut_alumno':
                    include 'pago_rut_alumno.php';
                    break;
                case 'cuadratura_caja':
                    include 'cuadratura_caja.php';
                    break;
                case 'agregar_alumno':
                    include 'agregar_alumno.php';
                    break;
                case 'agregar_contacto_emergencia':
                    include 'agregar_contacto_emergencia.php';
                    break;
                case 'editar_apoderado':
                    include 'editar_apoderado.php';
                    break;
                case 'pago_cheque_anual':
                    include 'pago_cheque_anual.php';
                    break;
                case 'becas':
                    include 'becas.php';
                    break;
                /* case 'registro':
                    include 'registro.php';
                    break; */
                case 'inicio':
                default:
                    include 'inicio.php';
                    break;
            }
        } else {
            // Si no hay parámetro 'page', muestra la pantalla de inicio por defecto
            include 'inicio.php';
        }
        ?>
        <div class="mt-3"></div> <!-- Espacio adicional -->
    </div>

    <script>
    // Activar el clic en la imagen para abrir el selector de archivos
    document.getElementById('fotoPerfil').addEventListener('click', function () {
        document.getElementById('nueva_foto').click();
    });

    // Enviar el formulario al seleccionar una nueva foto
    document.getElementById('nueva_foto').addEventListener('change', function () {
        document.getElementById('cambiarFotoForm').submit();
    });

    // Mostrar el formulario al hacer clic en el botón
    document.getElementById('btnCambiarFoto').addEventListener('click', function (e) {
        e.preventDefault();
        document.getElementById('nueva_foto').click();
    });
</script>


    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
</body>

</html>
