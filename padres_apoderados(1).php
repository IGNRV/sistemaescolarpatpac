<?php
// Asegúrate de que un usuario haya iniciado sesión
if (!isset($_SESSION['EMAIL'])) {
    header('Location: login.php');
    exit;
}

// Conexión a la base de datos
require_once 'db.php';

// Obtener el id_usuario del usuario que ha iniciado sesión
$EMAIL = $_SESSION['EMAIL'];
$queryUsuario = "SELECT ID FROM USERS WHERE EMAIL = '$EMAIL'";
$resultadoUsuario = $conn->query($queryUsuario);

$apoderados = []; // Array para almacenar los datos de los apoderados
$rutAlumno = ''; // Inicializa la variable $rutAlumno


$consultaComunas = $conn->query("SELECT ID_COMUNA, ID_REGION, NOM_COMUNA FROM COMUNA");
$comunas = $consultaComunas->fetch_all(MYSQLI_ASSOC);

if (isset($_POST['buscarAlumno'])) {
    $rutAlumno = $_POST['rutAlumno'];

    // Consulta a la base de datos
    $stmt = $conn->prepare("SELECT 
                                a.ID_ALUMNO,
                                a.RUT_ALUMNO,
                                ap.RUT_APODERADO,
                                ap.NOMBRE,
                                ap.AP_PATERNO,
                                ap.AP_MATERNO,
                                ap.PARENTESCO,
                                ap.MAIL_PART,
                                ap.FONO_PART,
                                ap.DELETE_FLAG
                            FROM
                                ALUMNO AS a
                                    LEFT JOIN
                                REL_ALUM_APOD AS raa ON raa.ID_ALUMNO = a.ID_ALUMNO
                                    LEFT JOIN
                                APODERADO AS ap ON ap.ID_APODERADO = raa.ID_APODERADO
                            WHERE
                                a.RUT_ALUMNO = ? AND ap.DELETE_FLAG = 0");
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

if (isset($_POST['eliminar_apoderado'])) {
    $rutApoderado = $_POST['rut_apoderado_eliminar'];

    // Actualizar el DELETE_FLAG del apoderado
    $stmtEliminar = $conn->prepare("UPDATE APODERADO SET DELETE_FLAG = 1 WHERE RUT_APODERADO = ?");
    $stmtEliminar->bind_param("s", $rutApoderado);
    $stmtEliminar->execute();

    // Verificar si se realizó correctamente la actualización
    if ($stmtEliminar->affected_rows > 0) {
        $mensaje = "Apoderado eliminado correctamente.";
    } else {
        $mensaje = "Error al eliminar el apoderado.";
    }
    $stmtEliminar->close();
}


if (isset($_POST['actualizar_datos'])) {
    // Recoge los datos del formulario
    $rutAlumno = $_POST['rutAlumno'];
    $rut = $_POST['rut'];
    $parentesco = $_POST['parentesco'];
    $nombre = $_POST['nombre'];
    $apellidoPaterno = $_POST['apellidoPaterno'];
    $apellidoMaterno = $_POST['apellidoMaterno'];
    $fechaNac = $_POST['fecha_nac'];
    $calle = $_POST['calle'];
    $nCalle = $_POST['n_calle'];
    $obsDireccion = $_POST['obsDireccion'];
    $villaPoblacion = $_POST['villaPoblacion'];
    $idComunaSeleccionada = $_POST['comuna']; // ID de la comuna seleccionada
    $telefonoParticular = $_POST['telefonoParticular'];
    $correoElectronicoPersonal = $_POST['correoElectronicoPersonal'];
    $correoElectronicoTrabajo = $_POST['correoElectronicoTrabajo'];
    $tutorAcademico = isset($_POST['tutorAcademico']) ? 1 : 0;
    $tutorEconomico = isset($_POST['tutorEconomico']) ? 1 : 0;

    // Buscar ID_REGION correspondiente a ID_COMUNA seleccionada
    $consultaRegion = $conn->prepare("SELECT ID_REGION FROM COMUNA WHERE ID_COMUNA = ?");
    $consultaRegion->bind_param("i", $idComunaSeleccionada);
    $consultaRegion->execute();
    $resultadoRegion = $consultaRegion->get_result();
    $filaRegion = $resultadoRegion->fetch_assoc();
    $idRegionCorrespondiente = $filaRegion['ID_REGION'];
    

    if (!empty($rutAlumno)) {
        $stmtAlumno = $conn->prepare("SELECT ID_ALUMNO FROM ALUMNO WHERE RUT_ALUMNO = ?");
        $stmtAlumno->bind_param("s", $rutAlumno);
        $stmtAlumno->execute();
        $resultadoAlumno = $stmtAlumno->get_result();

        if ($resultadoAlumno->num_rows > 0) {
            $filaAlumno = $resultadoAlumno->fetch_assoc();
            $idAlumno = $filaAlumno['ID_ALUMNO'];
            $fechaActual = date("Y-m-d H:i:s");
            $fechaActualizacion = date("Y-m-d H:i:s");


            // Inserta o actualiza en la tabla APODERADO
            $stmt = $conn->prepare("INSERT INTO APODERADO (RUT_APODERADO, PARENTESCO, NOMBRE, AP_PATERNO, AP_MATERNO, FECHA_NAC, CALLE, NRO_CALLE, OBS_DIRECCION, VILLA, ID_COMUNA, ID_REGION, FONO_PART, MAIL_PART, MAIL_LAB, TUTOR_ACADEMICO, TUTOR_ECONOMICO) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssssiiissii", $rut, $parentesco, $nombre, $apellidoPaterno, $apellidoMaterno, $fechaNac, $calle, $nCalle, $obsDireccion, $villaPoblacion, $idComunaSeleccionada, $idRegionCorrespondiente, $telefonoParticular, $correoElectronicoPersonal, $correoElectronicoTrabajo, $tutorAcademico, $tutorEconomico);
            $stmt->execute();
            $idApoderado = $conn->insert_id;

            $ultimoIdRelacion = $conn->query("SELECT MAX(ID_RELACION) AS ultimo_id FROM REL_ALUM_APOD")->fetch_assoc();
            $nuevoIdRelacion = $ultimoIdRelacion['ultimo_id'] + 1;
            $tipoRelacion = $parentesco == 'MADRE' ? 2 : ($parentesco == 'PADRE' ? 1 : 0); // Ejemplo de asignación de tipo de relación


            $stmtRel = $conn->prepare("INSERT INTO REL_ALUM_APOD (ID_RELACION, ID_ALUMNO, ID_APODERADO, STATUS, DELETE_FLAG, TIPO_RELACION, DATE_CREATED, DATE_UPDATED) VALUES (?, ?, ?, 1, 0, ?, ?, ?)");
            $stmtRel->bind_param("iiiiss", $nuevoIdRelacion, $idAlumno, $idApoderado, $tipoRelacion, $fechaActual, $fechaActualizacion);
            $stmtRel->execute();

            $_SESSION['mensaje_exito'] = "Datos del apoderado actualizados correctamente.";
        } else {
            $mensaje = "Alumno no encontrado para el RUT proporcionado.";
        }
        $stmtAlumno->close();
    } else {
        $mensaje = "RUT del alumno no proporcionado.";
    }
}


// Verifica si existe un mensaje de éxito en la variable de sesión
if (isset($_SESSION['mensaje_exito'])) {
    $mensaje = $_SESSION['mensaje_exito'];
    // Una vez mostrado el mensaje, elimina la variable de sesión
    unset($_SESSION['mensaje_exito']);
}
?>


<?php if (!empty($mensaje)): ?>
    <div class="alert alert-success" role="alert">
        <?php echo $mensaje; ?>
    </div>
<?php endif; ?>
<div class="parents-apoderados">
<h2>Datos padres/apoderados</h2>
    <div class="table-responsive">
        <table class="table">
        <thead>
    <tr>
        <th>RUT</th>
        <th>Nombre completo</th>
        <th>Parentesco</th>
        <th>Mail</th>
        <th>Teléfono</th>
        <th>Editar</th>
        <th>Eliminar</th>
    </tr>
</thead>
<tbody>
    <?php foreach ($apoderados as $apoderado): ?>
        <tr>
            <td><?php echo htmlspecialchars($apoderado['RUT_APODERADO']); ?></td>
            <td><?php echo htmlspecialchars($apoderado['NOMBRE']) . " " . htmlspecialchars($apoderado['AP_PATERNO']) . " " . htmlspecialchars($apoderado['AP_MATERNO']); ?></td>
            <td><?php echo htmlspecialchars($apoderado['PARENTESCO']); ?></td>
            <td><?php echo htmlspecialchars($apoderado['MAIL_PART']); ?></td>
            <td><?php echo htmlspecialchars($apoderado['FONO_PART']); ?></td>
            <td>
                <button onclick="location.href='editar_apoderado.php?rut=<?php echo $apoderado['RUT_APODERADO']; ?>'" class="btn btn-info">Editar</button>
            </td>
            <td>
                <form method="post">
                    <input type="hidden" name="rut_apoderado_eliminar" value="<?php echo $apoderado['RUT_APODERADO']; ?>">
                    <button type="submit" class="btn btn-danger" name="eliminar_apoderado">Eliminar</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>
        </table>
    </div>


    <form method="post">
        <div class="form-group">
            <label for="rutAlumno">Rut del alumno:</label>
            <!-- Utiliza el valor de $rutAlumno para mantener el valor después de enviar el formulario -->
            <input type="text" class="form-control" id="rutAlumno" name="rutAlumno" placeholder="Ingrese RUT del alumno" value="<?php echo isset($rutAlumno) ? htmlspecialchars($rutAlumno) : ''; ?>">
            <button type="submit" class="btn btn-primary custom-button mt-3" name="buscarAlumno">Buscar</button>
        </div>
    </form>
    
    <h3>Información de padres/apoderados</h3>
    <form method="post">
    <input type="hidden" name="rutAlumno" value="<?php echo htmlspecialchars($rutAlumno); ?>">

        <div class="form-group">
            <label for="rut">RUT</label>
            <input type="text" class="form-control" name="rut" id="rut" maxlength="10">
        </div>
        <div class="form-group">
            <label for="parentesco">Parentesco</label>
            <input type="text" class="form-control" name="parentesco">
        </div>
        <div class="form-group">
            <label for="nombres">Nombre</label>
            <input type="text" class="form-control" name="nombre">
        </div>
        <div class="form-group">
            <label for="apellidoPaterno">Apellido Paterno</label>
            <input type="text" class="form-control" name="apellidoPaterno">
        </div>
        <div class="form-group">
            <label for="apellidoMaterno">Apellido Materno</label>
            <input type="text" class="form-control" name="apellidoMaterno">
        </div>
        <div class="form-group">
            <label>Fecha de Nacimiento:</label>
            <input type="text" class="form-control" name="fecha_nac">
        </div>
        <div class="form-group">
            <label for="calle">Calle</label>
            <input type="text" class="form-control" name="calle">
        </div>
        <div class="form-group">
            <label for="n_calle">N°</label>
            <input type="text" class="form-control" name="n_calle">
        </div>
        <div class="form-group">
            <label for="restoDireccion">Resto Dirección</label>
            <input type="text" class="form-control" name="obsDireccion">
        </div>
        <div class="form-group">
            <label for="villaPoblacion">Villa/Población</label>
            <input type="text" class="form-control" name="villaPoblacion">
        </div>
        <div class="form-group">
            <label for="comuna">Comuna</label>
            <select class="form-control" name="comuna" id="comuna">
                <?php foreach ($comunas as $comuna): ?>
                    <option value="<?php echo htmlspecialchars($comuna['ID_COMUNA']); ?>">
                        <?php echo htmlspecialchars($comuna['NOM_COMUNA']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- <div class="form-group">
            <label for="ciudad">Region</label>
            <input type="text" class="form-control" name="idRegion">
        </div> -->
        <div class="form-group">
            <label for="telefonoParticular">Teléfono Particular</label>
            <input type="tel" class="form-control" name="telefonoParticular">
        </div>
        <div class="form-group">
            <label for="correoElectronicoPersonal">Correo Electrónico Personal</label>
            <input type="email" class="form-control" name="correoElectronicoPersonal">
        </div>
        <div class="form-group">
            <label for="correoElectronicoTrabajo">Correo Electrónico Trabajo</label>
            <input type="email" class="form-control" name="correoElectronicoTrabajo">
        </div>
        <div class="form-group">
            <label for="tutorAcademico">Tutor Academico</label>
            <input type="checkbox" id="tutorAcademico" name="tutorAcademico" value="1">
        </div>
        <div class="form-group">
            <label for="tutorEconomico">Tutor Economico</label>
            <input type="checkbox" id="tutorEconomico" name="tutorEconomico" value="1">
        </div>
        <button type="submit" class="btn btn-primary btn-block custom-button" name="actualizar_datos">AGREGAR APODERADO</button>
</form>
</div>
