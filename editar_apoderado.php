<?php
// Iniciar sesión y conectar a la base de datos
session_start();
require_once 'db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); 


if (!isset($_SESSION['EMAIL'])) {
    header('Location: login.php');
    exit;
}


$EMAIL = $_SESSION['EMAIL'];

$queryUsuario = "SELECT ID FROM USERS WHERE EMAIL = '$EMAIL'";
$resultadoUsuario = $conn->query($queryUsuario);


$mensaje = '';
$apoderado = null;

if (isset($_GET['rut'])) {
    $rutApoderado = $_GET['rut'];

    // Consulta para obtener los datos del apoderado
    $stmt = $conn->prepare("SELECT * FROM APODERADO WHERE RUT_APODERADO = ?");
    $stmt->bind_param("s", $rutApoderado);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $apoderado = $resultado->fetch_assoc();
    } else {
        $mensaje = "No se encontró el apoderado.";
    }
    $stmt->close();
}

if (isset($_POST['actualizar_apoderado'])) {
    $rutOriginal = $_POST['rut_original'];
    $parentesco = $_POST['parentesco'];
    $nombre = $_POST['nombre'];
    $apellidoPaterno = $_POST['apellidoPaterno'];
    $apellidoMaterno = $_POST['apellidoMaterno'];
    $fechaNac = $_POST['fecha_nac'];
    $calle = $_POST['calle'];
    $nCalle = $_POST['n_calle'];
    $obsDireccion = $_POST['obsDireccion'];
    $villaPoblacion = $_POST['villaPoblacion'];
    $comuna = $_POST['comuna'];
    $fonoPart = $_POST['telefonoParticular'];
    $mailPart = $_POST['correoElectronicoPersonal'];
    $mailLab = $_POST['correoElectronicoTrabajo'];
    if (empty($_POST['rut'])) {
        // Genera un hash aleatorio de 10 caracteres como máximo
        $rut = substr(md5(uniqid(rand(), true)), 0, 10); // Usar md5 y uniqid para generar un hash y luego cortarlo a 10 caracteres
    } else {
        // Si no está vacío, usa el valor de $_POST['rut']
        $rut = $_POST['rut'];
    }
    $tutorAcademico = isset($_POST['tutorAcademico']) ? 1 : 0;
    $tutorEconomico = isset($_POST['tutorEconomico']) ? 1 : 0;
    

    // Necesitas encontrar el ID_REGION correspondiente a esta comuna
    $consultaRegion = $conn->prepare("SELECT ID_REGION FROM COMUNA WHERE ID_COMUNA = ?");
    $consultaRegion->bind_param("i", $comuna);
    $consultaRegion->execute();
    $resultadoRegion = $consultaRegion->get_result();

    if ($resultadoRegion->num_rows > 0) {
        $filaRegion = $resultadoRegion->fetch_assoc();
        $idRegion = $filaRegion['ID_REGION'];

        // Verifica si algún dato ha cambiado
        $hayCambios = $parentesco != $apoderado['PARENTESCO'] ||
              $nombre != $apoderado['NOMBRE'] ||
              $apellidoPaterno != $apoderado['AP_PATERNO'] ||
              $apellidoMaterno != $apoderado['AP_MATERNO'] ||
              $fechaNac != $apoderado['FECHA_NAC'] ||
              $calle != $apoderado['CALLE'] ||
              $nCalle != $apoderado['NRO_CALLE'] ||
              $obsDireccion != $apoderado['OBS_DIRECCION'] ||
              $villaPoblacion != $apoderado['VILLA'] ||
              $comuna != $apoderado['ID_COMUNA'] ||
              $fonoPart != $apoderado['FONO_PART'] ||
              $mailPart != $apoderado['MAIL_PART'] ||
              $mailLab != $apoderado['MAIL_LAB'] ||
              $rut != $apoderado['RUT_APODERADO'] || // Incluye la comparación del RUT
              $tutorAcademico != $apoderado['TUTOR_ACADEMICO'] ||
              $tutorEconomico != $apoderado['TUTOR_ECONOMICO'];

        if ($hayCambios) {
            // Actualiza los datos en la base de datos con el ID_COMUNA e ID_REGION
        	$fechaNac2 = date("Y-m-d", strtotime($fechaNac));
            $stmtActualizar = $conn->prepare("UPDATE APODERADO SET PARENTESCO = ?, NOMBRE = ?, AP_PATERNO = ?, AP_MATERNO = ?, FECHA_NAC = ?, CALLE = ?, NRO_CALLE = ?, OBS_DIRECCION = ?, VILLA = ?, ID_COMUNA = ?, ID_REGION = ?, FONO_PART = ?, MAIL_PART = ?, MAIL_LAB = ?, TUTOR_ACADEMICO = ?, RUT_APODERADO = ?, TUTOR_ECONOMICO = ? WHERE RUT_APODERADO = ?");
            $stmtActualizar->bind_param("ssssssssssssssssis", $parentesco, $nombre, $apellidoPaterno, $apellidoMaterno, $fechaNac2, $calle, $nCalle, $obsDireccion, $villaPoblacion, $comuna, $idRegion, $fonoPart, $mailPart, $mailLab, $tutorAcademico, $rut, $tutorEconomico, $rutOriginal);
            $stmtActualizar->execute();

            // Obtener el ID del usuario que inició sesión
        $emailUsuario = $_SESSION['EMAIL'];
        $queryUsuario = "SELECT ID FROM USERS WHERE EMAIL = ?";
        $stmtUsuario = $conn->prepare($queryUsuario);
        $stmtUsuario->bind_param("s", $emailUsuario);
        $stmtUsuario->execute();
        $resultadoUsuario = $stmtUsuario->get_result();
        $usuario = $resultadoUsuario->fetch_assoc();
        $idUsuario = $usuario['ID'];
        $stmtUsuario->close();

        // Obtener el ID del apoderado
        $idApoderado = $apoderado['ID']; // Asumiendo que 'ID' es la columna que contiene el ID del apoderado

        // Tipo de cambio
        $tipoCambio = "INFORMACION DE APODERADO EDITADA";

        // Insertar registro en HISTORIAL_CAMBIOS
        $stmtHistorial = $conn->prepare("INSERT INTO HISTORIAL_CAMBIOS (ID_USUARIO, TIPO_CAMBIO, ID_APODERADO) VALUES (?, ?, ?)");
        $stmtHistorial->bind_param("isi", $idUsuario, $tipoCambio, $idApoderado);
        $stmtHistorial->execute();
        $stmtHistorial->close();
            if ($stmtActualizar->affected_rows > 0) {
                $_SESSION['mensaje_exito'] = "Datos del apoderado actualizados correctamente.";
                header("Location: https://antilen.pat-pac.cl/sistemaescolar/bienvenido.php?page=padres_apoderados");
                exit;
            } else {
                $_SESSION['mensaje_exito'] = "No se realizaron cambios en los datos.";
                header("Location: https://antilen.pat-pac.cl/sistemaescolar/bienvenido.php?page=padres_apoderados");
                exit;

            }
        } else {
            // Si no hay cambios, envía un mensaje de éxito
            $_SESSION['mensaje_exito'] = "No se realizaron cambios en los datos.";
            header("Location: https://antilen.pat-pac.cl/sistemaescolar/bienvenido.php?page=padres_apoderados");
            exit;
        }
    } else {
        $mensaje = "No se pudo encontrar la región para la comuna seleccionada.";
    }
    $consultaRegion->close();
    if (isset($stmtActualizar)) {
        $stmtActualizar->close();
    }
}


if ($apoderado != null) {
    // Consulta para obtener todas las comunas
    $consultaComunas = $conn->query("SELECT ID_COMUNA, ID_REGION, NOM_COMUNA FROM COMUNA ORDER BY NOM_COMUNA ASC");
    $comunas = $consultaComunas->fetch_all(MYSQLI_ASSOC);
    $fechaNacFormatted = date("d-m-Y", strtotime($apoderado['FECHA_NAC']));

}

function esRutValido($rut) {
    // Verifica si el RUT termina en "-K"
    if (preg_match('/-\K$/', $rut)) {
        return true;
    }
    // Verifica si el RUT tiene solo números seguido de un guion y luego un número o una K
    if (preg_match('/^\d+-[\dkK]$/', $rut)) {
        return true;
    }
    // En cualquier otro caso, devuelve falso
    return false;
}

// Lógica para determinar qué valor mostrar en el campo RUT
$valorRut = "";
if ($apoderado != null) {
    if (esRutValido($apoderado['RUT_APODERADO'])) {
        $valorRut = $apoderado['RUT_APODERADO']; // Muestra el RUT si es válido
    } else {
        $valorRut = ""; // Muestra un guion si el RUT no es válido
    }
}
?>
<head>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
</head>

<?php if (!empty($mensaje)): ?>
    <div class="alert alert-warning"><?php echo $mensaje; ?></div>
<?php endif; ?>

<?php if ($apoderado != null): ?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>Editar Apoderado</h3>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="rut_original" value="<?php echo $apoderado['RUT_APODERADO']; ?>">

                            <div class="form-group">
                                <label for="parentesco">Parentesco</label>
                                <input type="text" class="form-control to-uppercase" name="parentesco" value="<?php echo $apoderado['PARENTESCO']; ?>">
                            </div>

                            <div class="form-group">
                                <label for="nombre">RUT</label>
                                <input type="text" class="form-control to-uppercase" name="rut" value="<?php echo $valorRut; ?>">
                            </div>

                            <div class="form-group">
                                <label for="nombre">Nombre</label>
                                <input type="text" class="form-control to-uppercase" name="nombre" value="<?php echo $apoderado['NOMBRE']; ?>">
                            </div>

                            <div class="form-group">
                                <label for="apellidoPaterno">Apellido Paterno</label>
                                <input type="text" class="form-control to-uppercase" name="apellidoPaterno" value="<?php echo $apoderado['AP_PATERNO']; ?>">
                            </div>

                            <div class="form-group">
                                <label for="apellidoMaterno">Apellido Materno</label>
                                <input type="text" class="form-control to-uppercase" name="apellidoMaterno" value="<?php echo $apoderado['AP_MATERNO']; ?>">
                            </div>

                            <div class="form-group">
                                <label for="fechaNac">Fecha de Nacimiento</label>
                                <input type="text" class="form-control to-uppercase" id="fechaNac" name="fecha_nac" value="<?php echo $fechaNacFormatted; ?>">
                            </div>

                            <div class="form-group">
                                <label for="calle">Calle</label>
                                <input type="text" class="form-control to-uppercase" name="calle" value="<?php echo $apoderado['CALLE']; ?>">
                            </div>

                            <div class="form-group">
                                <label for="nCalle">Número de Calle</label>
                                <input type="text" class="form-control to-uppercase" name="n_calle" value="<?php echo $apoderado['NRO_CALLE']; ?>">
                            </div>

                            <div class="form-group">
                                <label for="obsDireccion">Observaciones de Dirección</label>
                                <input type="text" class="form-control to-uppercase" name="obsDireccion" value="<?php echo $apoderado['OBS_DIRECCION']; ?>">
                            </div>

                            <div class="form-group">
                                <label for="villaPoblacion">Villa/Población</label>
                                <input type="text" class="form-control to-uppercase" name="villaPoblacion" value="<?php echo $apoderado['VILLA']; ?>">
                            </div>

                            <div class="form-group">
        <label for="comuna">Comuna</label>
        <select class="form-control" name="comuna" id="comuna">
            <?php foreach ($comunas as $comuna): ?>
                <option value="<?php echo htmlspecialchars($comuna['ID_COMUNA']); ?>" <?php echo $comuna['ID_COMUNA'] == $apoderado['ID_COMUNA'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($comuna['NOM_COMUNA']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

                            <!-- <div class="form-group">
                                <label for="idRegion">Región</label>
                                <input type="text" class="form-control" name="idRegion" value="<?php echo $apoderado['ID_REGION']; ?>">
                            </div> -->

                            <div class="form-group">
                                <label for="telefonoParticular">Teléfono Particular</label>
                                <input type="tel" class="form-control" name="telefonoParticular" value="<?php echo $apoderado['FONO_PART']; ?>">
                            </div>

                            <div class="form-group">
                                <label for="correoElectronicoPersonal">Correo Electrónico Personal</label>
                                <input type="email" class="form-control" name="correoElectronicoPersonal" value="<?php echo $apoderado['MAIL_PART']; ?>">
                            </div>

                            <div class="form-group">
                                <label for="correoElectronicoTrabajo">Correo Electrónico de Trabajo</label>
                                <input type="email" class="form-control" name="correoElectronicoTrabajo" value="<?php echo $apoderado['MAIL_LAB']; ?>">
                            </div>

                            <div class="form-group">
                                <label for="tutorAcademico">Tutor Academico</label>
                                <input type="checkbox" id="tutorAcademico" name="tutorAcademico" value="1" <?php echo $apoderado['TUTOR_ACADEMICO'] ? 'checked' : ''; ?>>
                            </div>
                            <div class="form-group">
                                <label for="tutorEconomico">Tutor Economico</label>
                                <input type="checkbox" id="tutorEconomico" name="tutorEconomico" value="1" <?php echo $apoderado['TUTOR_ECONOMICO'] ? 'checked' : ''; ?>>
                            </div>

                            <button type="submit" name="actualizar_apoderado" class="btn btn-primary">Actualizar</button>
                        </form>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
        var inputs = document.querySelectorAll('.to-uppercase');
        inputs.forEach(function(input) {
            input.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        });
    });
    $(function() {
        $("#fechaNac").datepicker({
            dateFormat: "dd-mm-yy"
        });
    });
    </script>
<?php endif; ?>

