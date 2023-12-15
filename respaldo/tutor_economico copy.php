<?php

/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
 */
// Verifica si existe un mensaje en la sesión y guárdalo en una variable
$mensaje = isset($_SESSION['mensaje']) ? $_SESSION['mensaje'] : '';
unset($_SESSION['mensaje']); // Limpia el mensaje para que no se muestre de nuevo en futuras cargas de página


if (!isset($_SESSION['EMAIL'])) {
    header('Location: login.php');
    exit;
}

// Incluye la conexión a la base de datos
require_once 'db.php';

// Al inicio de tu script, después de iniciar la sesión
$rutAlumno = isset($_POST['rutAlumno']) ? $_POST['rutAlumno'] : '';

// Similar para $idComunaApoderado
$idComunaApoderado = isset($apoderados[0]['ID_COMUNA']) ? $apoderados[0]['ID_COMUNA'] : null;

// Obtener el ID del usuario que ha iniciado sesión
$EMAIL = $_SESSION['EMAIL'];
$queryUsuario = "SELECT ID FROM USERS WHERE EMAIL = '$EMAIL'";
$resultadoUsuario = $conn->query($queryUsuario);

$apoderados = []; // Array para almacenar los datos de los apoderados
$rutTutor = '';
$nombreTutor = '';
$apPaternoTutor = '';
$apMaternoTutor = '';
$telefonoParticular = '';
$telefonoTrabajo = '';
$calle = '';
$nCalle = '';
$restoDireccion = '';
$villaPoblacion = '';
$comuna = '';
$ciudad = '';
$correoPersonal = '';
$correoTrabajo = '';
$rutOriginal= '';

// Asegúrate de que tienes esta consulta para obtener las comunas
$consultaComunas = $conn->query("SELECT ID_COMUNA, NOM_COMUNA FROM COMUNA");
$comunas = $consultaComunas->fetch_all(MYSQLI_ASSOC);

if (isset($_POST['buscarAlumno'])) {
    $rutAlumno = $_POST['rutAlumno'];

    // Consulta a la base de datos
    $stmt = $conn->prepare("SELECT 
                                a.ID_ALUMNO,
                                a.RUT_ALUMNO,
                                ap.ID_APODERADO,
                                ap.RUT_APODERADO,
                                ap.NOMBRE,
                                ap.AP_PATERNO,
                                ap.AP_MATERNO,
                                ap.FECHA_NAC,
                                ap.PARENTESCO,
                                ap.MAIL_LAB,
                                ap.MAIL_PART,
                                ap.FONO_PART,
                                ap.CALLE,
                                ap.NRO_CALLE,
                                ap.OBS_DIRECCION,
                                ap.VILLA,
                                ap.COMUNA,
                                ap.ID_COMUNA,
                                ap.ID_REGION,
                                ap.FECHA_INGRESO,
                                ap.PERIODO_ESCOLAR,
                                ap.FONO_LAB,
                                ap.TUTOR_ACADEMICO,
                                ap.TUTOR_ECONOMICO,
                                a.PERIODO_ESCOLAR,
                                ap.DELETE_FLAG
                            FROM
                                ALUMNO AS a
                                    LEFT JOIN
                                REL_ALUM_APOD AS raa ON raa.ID_ALUMNO = a.ID_ALUMNO
                                    LEFT JOIN
                                APODERADO AS ap ON ap.ID_APODERADO = raa.ID_APODERADO
                            WHERE
                                a.RUT_ALUMNO = ? AND ap.TUTOR_ECONOMICO = 1 AND ap.DELETE_FLAG = 0");
    $stmt->bind_param("s", $rutAlumno);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $mensaje = "Datos del alumno encontrados.";
        $apoderados = $resultado->fetch_all(MYSQLI_ASSOC);
    } else {
        $mensaje = "No se encontró ningún alumno con ese RUT.";
    }
    $stmt->close();
}

if (!empty($apoderados)) {
    // Asigna los valores a las variables
    $rutTutor = $apoderados[0]['RUT_APODERADO'];
    $nombreTutor = $apoderados[0]['NOMBRE'];
    $apPaternoTutor = $apoderados[0]['AP_PATERNO'];
    $apMaternoTutor = $apoderados[0]['AP_MATERNO'];
    $telefonoParticular = $apoderados[0]['FONO_PART'];
    $telefonoTrabajo = $apoderados[0]['FONO_LAB'];
    $calle = $apoderados[0]['CALLE'];
    $nCalle = $apoderados[0]['NRO_CALLE'];
    $restoDireccion = $apoderados[0]['OBS_DIRECCION'];
    $villaPoblacion = $apoderados[0]['VILLA'];
    $comuna = $apoderados[0]['COMUNA'];
    $ciudad = $apoderados[0]['ID_REGION'];
    $correoPersonal = $apoderados[0]['MAIL_PART'];
    $correoTrabajo = $apoderados[0]['MAIL_LAB'];
    $periodoescolar = $apoderados[0]['PERIODO_ESCOLAR'];
    $idComunaApoderado = $apoderados[0]['ID_COMUNA'] ?? null;

}

$mediosDePago = [];
if (!empty($rutTutor)) {
    $stmtMediosDePago = $conn->prepare("SELECT MEDIO_PAGO, BANCO_EMISOR, FECHA_SUSCRIPCION, ESTADO_MP, NRO_MEDIOPAGO FROM MEDIOS_DE_PAGO WHERE RUT_PAGADOR = ?");
    $stmtMediosDePago->bind_param("s", $rutTutor);
    $stmtMediosDePago->execute();
    $resultadoMediosDePago = $stmtMediosDePago->get_result();
    $mediosDePago = $resultadoMediosDePago->fetch_all(MYSQLI_ASSOC);
    $stmtMediosDePago->close();
}


if (isset($_POST['INGRESAR_DATOS'])) {
    $medioPago = $_POST['medioPago'];
    $bancoEmisor = $_POST['bancoEmisor'];
    $tipoMedioPago = $_POST['tipoMedioPago'];
    $rutPagador = $_POST['rut']; // Utiliza el valor del input 'rut' del formulario



    // Obtener el último número de medio de pago y aumentarlo en 1
    $stmt = $conn->prepare("SELECT MAX(NRO_MEDIOPAGO) AS ultimo_numero FROM MEDIOS_DE_PAGO");
    $stmt->execute();
    $resultado = $stmt->get_result();
    $fila = $resultado->fetch_assoc();
    $nroMedioPago = (int)$fila['ultimo_numero'] + 1;

    // Fecha actual
    $fechaActual = date('Y-m-d');

    // Insertar en la base de datos
    $stmt = $conn->prepare("INSERT INTO MEDIOS_DE_PAGO (MEDIO_PAGO, BANCO_EMISOR, TIPO_MEDIOPAGO, RUT_PAGADOR, NRO_MEDIOPAGO, FECHA_SUSCRIPCION, ESTADO_MP, FECHA_VENCIMIENTO_MP, FECHA_INGRESO, FECHA_ACTIVACION, PERIODO_ESCOLAR) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $estadoMP = 1; // Estado activo
    $fechaVencimientoMP = '2100-01-01';
    $periodoEscolar = 2; // Número fijo según tu indicación

    $stmt->bind_param("ssssssssssi", $medioPago, $bancoEmisor, $tipoMedioPago, $rutPagador, $nroMedioPago, $fechaActual, $estadoMP, $fechaVencimientoMP, $fechaActual, $fechaActual, $periodoEscolar);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $mensaje = "Datos del medio de pago ingresados con éxito.";
    } else {
        $mensaje = "Error al ingresar los datos del medio de pago.";
    }
    $stmt->close();
}

if (!empty($mensaje)) {
    echo '<div class="alert alert-success">' . htmlspecialchars($mensaje) . '</div>';
}

if (isset($_POST['ACTUALIZAR_DATOS'])) {
    $rutOriginal = $_POST['rut_original'];
    $rutTutor = $_POST['rut']; // Asume que 'rut' es el nombre del campo en tu formulario
    $nombreTutor = $_POST['nombre'];
    $apPaternoTutor = $_POST['apellido_paterno'];
    $apMaternoTutor = $_POST['apellido_materno'];
    $telefonoParticular = $_POST['telefono_particular'];
    $telefonoTrabajo = $_POST['telefono_trabajo'];
    $calle = $_POST['calle'];
    $nCalle = $_POST['n_calle'];
    $restoDireccion = $_POST['resto_direccion'];
    $villaPoblacion = $_POST['villa_poblacion'];
    $idComuna = $_POST['comuna']; // Asume que 'comuna' es el nombre del campo en tu formulario y que es un ID válido
    $correoPersonal = $_POST['correo_electronico_particular'];
    $correoTrabajo = $_POST['correo_electronico_trabajo'];
    $tutorEconomico = isset($_POST['tutorEconomico']) ? 1 : 0;

    // Prepara la consulta SQL para actualizar los datos del apoderado
    $stmtActualizar = $conn->prepare("UPDATE APODERADO SET 
        NOMBRE = ?, 
        RUT_APODERADO = ?,
        AP_PATERNO = ?, 
        AP_MATERNO = ?, 
        FONO_PART = ?, 
        FONO_LAB = ?, 
        CALLE = ?, 
        NRO_CALLE = ?, 
        OBS_DIRECCION = ?, 
        VILLA = ?, 
        ID_COMUNA = ?, 
        MAIL_PART = ?, 
        MAIL_LAB = ?, 
        TUTOR_ECONOMICO = ?
        WHERE RUT_APODERADO = ?");
    $stmtActualizar->bind_param("sssssssssssssis", 
        $nombreTutor, 
        $rutTutor,
        $apPaternoTutor, 
        $apMaternoTutor, 
        $telefonoParticular, 
        $telefonoTrabajo, 
        $calle, 
        $nCalle, 
        $restoDireccion, 
        $villaPoblacion, 
        $idComuna, 
        $correoPersonal, 
        $correoTrabajo, 
        $tutorEconomico,
        $rutOriginal);
    $stmtActualizar->execute();

    if ($stmtActualizar->affected_rows > 0) {
        $mensaje = "Datos del tutor económico actualizados correctamente.";
    } else {
        // Si no hay cambios, envía un mensaje de éxito
        $mensaje = "No se realizaron cambios en los datos del tutor económico.";
    }
    $stmtActualizar->close();

    // Restablecer las variables después de actualizar
    $rutTutor = '';
    $nombreTutor = '';
    $apPaternoTutor = '';
    $apMaternoTutor = '';
    $telefonoParticular = '';
    $telefonoTrabajo = '';
    $calle = '';
    $nCalle = '';
    $restoDireccion = '';
    $villaPoblacion = '';
    $comuna = '';
    $ciudad = '';
    $correoPersonal = '';
    $correoTrabajo = '';

    // Muestra el mensaje resultante
    if (!empty($mensaje)) {
        echo '<div class="alert alert-success">' . htmlspecialchars($mensaje) . '</div>';
    }
}





?>
<div class="tutor-economico">
    <form method="post">
        <div class="form-group">
            <label for="rutAlumno">Rut del alumno:</label>
            <!-- Utiliza el valor de $rutAlumno para mantener el valor después de enviar el formulario -->
            <input type="text" class="form-control" id="rutAlumno" name="rutAlumno" placeholder="Ingrese RUT del alumno" value="<?php echo htmlspecialchars($rutAlumno); ?>">
            <button type="submit" class="btn btn-primary custom-button mt-3" name="buscarAlumno">Buscar</button>
        </div>
    </form>
    <h2>Datos tutor económico</h2>
    <form method="post">
    <input type="hidden" name="rut_original" value="<?php echo htmlspecialchars($rutTutor); ?>">

    <div class="form-group">
        <label for="rutTutor">RUT</label>
        <input type="text" class="form-control" name="rut" id="rutTutor" value="<?php echo htmlspecialchars($rutTutor); ?>" maxlength="10">
    </div>
        <div class="form-group">
            <label for="nombresTutor">Nombre</label>
            <input type="text" class="form-control" name="nombre" id="nombresTutor" value="<?php echo htmlspecialchars($nombreTutor); ?>">
        </div>
        <div class="form-group">
            <label for="apPaternoTutor">Apellido Paterno</label>
            <input type="text" class="form-control" name="apellido_paterno" id="apPaternoTutor" value="<?php echo htmlspecialchars($apPaternoTutor); ?>">
        </div>
        <div class="form-group">
            <label for="apMaternoTutor">Apellido Materno</label>
            <input type="text" class="form-control" name="apellido_materno" id="apMaternoTutor" value="<?php echo htmlspecialchars($apMaternoTutor); ?>">
        </div>
        <div class="form-group">
            <label for="telefono_particular">Teléfono particular</label>
            <input type="text" class="form-control" name="telefono_particular" id="telefono_particular" value="<?php echo htmlspecialchars($telefonoParticular); ?>">
        </div>
        <div class="form-group">
            <label for="telefono_trabajo">Teléfono trabajo</label>
            <input type="text" class="form-control" name="telefono_trabajo" id="telefono_trabajo" value="<?php echo htmlspecialchars($telefonoTrabajo); ?>">
        </div>
        <div class="form-group">
            <label for="calleTutor">Calle</label>
            <input type="text" class="form-control" name="calle" id="calleTutor" value="<?php echo htmlspecialchars($calle); ?>">
        </div>
        <div class="form-group">
            <label for="nCalleTutor">N° Calle</label>
            <input type="text" class="form-control" name="n_calle" id="nCalleTutor" value="<?php echo htmlspecialchars($nCalle); ?>">
        </div>
        <div class="form-group">
            <label for="restoDireccionTutor">Resto Dirección</label>
            <input type="text" class="form-control" name="resto_direccion" id="restoDireccionTutor" value="<?php echo htmlspecialchars($restoDireccion); ?>">
        </div>
        <div class="form-group">
            <label for="villaPoblacionTutor">Villa/Población</label>
            <input type="text" class="form-control" name="villa_poblacion" id="villaPoblacionTutor" value="<?php echo htmlspecialchars($villaPoblacion); ?>">
        </div>
        <div class="form-group">
            <label for="comunaTutor">Comuna</label>
            <select class="form-control" name="comuna" id="comunaTutor">
                <?php foreach ($comunas as $comuna): ?>
                    <option value="<?php echo htmlspecialchars($comuna['ID_COMUNA']); ?>" <?php if ($comuna['ID_COMUNA'] == $idComunaApoderado) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($comuna['NOM_COMUNA']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="correoPersonalTutor">Correo Electrónico Personal</label>
            <input type="email" class="form-control" name="correo_electronico_particular" id="correoPersonalTutor" value="<?php echo htmlspecialchars($correoPersonal); ?>">
        </div>
        <div class="form-group">
            <label for="correoTrabajoTutor">Correo Electrónico Trabajo</label>
            <input type="email" class="form-control" name="correo_electronico_trabajo" id="correoTrabajoTutor" value="<?php echo htmlspecialchars($correoTrabajo); ?>">
        </div>

        <div class="form-group">
            <label for="tutorEconomico">Tutor Económico</label>
            <input type="checkbox" id="tutorEconomico" name="tutorEconomico" value="1" <?php echo (isset($apoderados[0]['TUTOR_ECONOMICO']) && $apoderados[0]['TUTOR_ECONOMICO'] == 1) ? 'checked' : ''; ?>>
        </div>

        
       <button type="submit" class="btn btn-primary btn-block custom-button" name="ACTUALIZAR_DATOS">ACTUALIZAR</button>
    </form>

    <h3>Medios de pago suscritos</h3>
<table class="table">
    <thead>
        <tr>
            <th>Tipo Medio de Pago</th>
            <th>Banco Emisor</th>
            <th>Fecha suscripción</th>
            <th>Estado</th>
            <th>Acción</th> <!-- Nueva columna para las acciones -->
        </tr>
    </thead>
    <tbody>
        <?php foreach ($mediosDePago as $medio): ?>
            <tr>
                <td><?php echo htmlspecialchars($medio['MEDIO_PAGO']); ?></td>
                <td><?php echo htmlspecialchars($medio['BANCO_EMISOR']); ?></td>
                <td><?php echo htmlspecialchars($medio['FECHA_SUSCRIPCION']); ?></td>
                <td>
                    <?php 
                    // Verifica el estado y muestra el texto correspondiente
                    echo $medio['ESTADO_MP'] == 1 ? "ACTIVO" : ($medio['ESTADO_MP'] == 2 ? "INACTIVO" : "DESCONOCIDO"); 
                    ?>
                </td>
                <td>
                    <form method="post" action="procesar_cambio_estado.php"> <!-- Cambia 'procesar_cambio_estado.php' por tu script de procesamiento -->
                        <input type="hidden" name="nroMedioPago" value="<?php echo $medio['NRO_MEDIOPAGO']; ?>">
                        <input type="hidden" name="estadoActual" value="<?php echo $medio['ESTADO_MP']; ?>">
                        <button type="submit" name="cambiarEstado" class="btn btn-<?php echo $medio['ESTADO_MP'] == 1 ? 'warning' : 'success'; ?>">
                            <?php echo $medio['ESTADO_MP'] == 1 ? 'Desactivar' : 'Activar'; ?>
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($mediosDePago)): ?>
            <tr>
                <td colspan="5">No se han encontrado medios de pago suscritos.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

    <form method="post">
        <div class="form-group">
            <label for="medioPago">Medio de Pago</label>
            <input type="text" class="form-control" name="medioPago" id="medioPago" value="" maxlength="9">
        </div>
        <div class="form-group">
            <label for="bancoEmisor">Banco Emisor </label>
            <input type="text" class="form-control" name="bancoEmisor" id="bancoEmisor" value="">
        </div>
        <div class="form-group">
            <label for="tipoMedioPago">Tipo de Medio de Pago</label>
            <input type="text" class="form-control" name="tipoMedioPago" id="tipoMedioPago" value="">
        </div>     
        <input type="hidden" name="rut" value="<?php echo htmlspecialchars($rutTutor); ?>">

        <button type="submit" class="btn btn-primary btn-block custom-button" name="INGRESAR_DATOS">INGRESAR DATOS</button>
    </form>
</div>