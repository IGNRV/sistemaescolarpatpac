<?php
// Asegúrate de que un usuario haya iniciado sesión
if (!isset($_SESSION['EMAIL'])) {
    header('Location: login.php');
    exit;
}

// Conexión a la base de datos
require_once 'db.php';

// Consulta para obtener los nombres de los alumnos junto con sus IDs
$consultaAlumnos = "SELECT ID_ALUMNO, NOMBRE, AP_PATERNO, AP_MATERNO FROM ALUMNO ORDER BY AP_PATERNO, AP_MATERNO ASC";
$resultadoAlumnos = $conn->query($consultaAlumnos);
$alumnos = $resultadoAlumnos->fetch_all(MYSQLI_ASSOC);


// Obtener el id_usuario del usuario que ha iniciado sesión
$EMAIL = $_SESSION['EMAIL'];
$queryUsuario = "SELECT ID FROM USERS WHERE EMAIL = '$EMAIL'";
$resultadoUsuario = $conn->query($queryUsuario);

$apoderados = []; // Array para almacenar los datos de los apoderados
$rutAlumno = ''; // Inicializa la variable $rutAlumno


// Consulta para obtener los apoderados
$consultaApoderados = $conn->query("SELECT ID_APODERADO, NOMBRE, AP_PATERNO, AP_MATERNO FROM APODERADO ORDER BY AP_PATERNO, AP_MATERNO ASC");
$listaApoderados = $consultaApoderados->fetch_all(MYSQLI_ASSOC);

$consultaComunas = $conn->query("SELECT ID_COMUNA, ID_REGION, NOM_COMUNA FROM COMUNA ORDER BY NOM_COMUNA ASC");
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
                                raa.DELETE_FLAG
                            FROM
                                ALUMNO AS a
                                    LEFT JOIN
                                REL_ALUM_APOD AS raa ON raa.ID_ALUMNO = a.ID_ALUMNO
                                    LEFT JOIN
                                APODERADO AS ap ON ap.ID_APODERADO = raa.ID_APODERADO
                            WHERE
                                a.RUT_ALUMNO = ? AND raa.DELETE_FLAG = 0");
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

    // Obtener el ID_APODERADO basado en el RUT_APODERADO
    $stmtObtenerIdApoderado = $conn->prepare("SELECT ID_APODERADO FROM APODERADO WHERE RUT_APODERADO = ?");
    $stmtObtenerIdApoderado->bind_param("s", $rutApoderado);
    

    $stmtObtenerIdApoderado->execute();
    $resultadoIdApoderado = $stmtObtenerIdApoderado->get_result();
    if ($filaIdApoderado = $resultadoIdApoderado->fetch_assoc()) {
        $idApoderado = $filaIdApoderado['ID_APODERADO'];

        // Actualizar el DELETE_FLAG de las relaciones de este apoderado en REL_ALUM_APOD
        $stmtEliminarRelacion = $conn->prepare("UPDATE REL_ALUM_APOD SET DELETE_FLAG = 1 WHERE ID_APODERADO = ?");
        $stmtEliminarRelacion->bind_param("i", $idApoderado);
        $stmtEliminarRelacion->execute();

        // Verificar si se realizó correctamente la actualización
        if ($stmtEliminarRelacion->affected_rows > 0) {
            $mensaje = "Relaciones del apoderado eliminadas correctamente.";
        } else {
            $mensaje = "Error al eliminar las relaciones del apoderado.";
        }
        $stmtEliminarRelacion->close();
    } else {
        $mensaje = "No se encontró un apoderado con ese RUT.";
    }
    $stmtObtenerIdApoderado->close();
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
    $idAlumno = $_POST['idAlumnoHidden'] ?? '';

    

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
        	$fechaNac2 = date("Y-m-d", strtotime($fechaNac));


            // Inserta o actualiza en la tabla APODERADO
            $stmt = $conn->prepare("INSERT INTO APODERADO (RUT_APODERADO, PARENTESCO, NOMBRE, AP_PATERNO, AP_MATERNO, FECHA_NAC, CALLE, NRO_CALLE, OBS_DIRECCION, VILLA, ID_COMUNA, ID_REGION, FONO_PART, MAIL_PART, MAIL_LAB, TUTOR_ACADEMICO, TUTOR_ECONOMICO) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssssiiissii", $rut, $parentesco, $nombre, $apellidoPaterno, $apellidoMaterno, $fechaNac2, $calle, $nCalle, $obsDireccion, $villaPoblacion, $idComunaSeleccionada, $idRegionCorrespondiente, $telefonoParticular, $correoElectronicoPersonal, $correoElectronicoTrabajo, $tutorAcademico, $tutorEconomico);
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

// ...

if (isset($_POST['asignarApoderado'])) {
    $idApoderadoSeleccionado = $_POST['apoderadoSeleccionado'];
    $rutAlumnoAsignacion = $_POST['rutAlumnoAsignacion']; // Utiliza el RUT del alumno enviado desde el formulario

    if (!empty($rutAlumnoAsignacion)) {
        // Obtener el ID del alumno
        $stmtAlumno = $conn->prepare("SELECT ID_ALUMNO FROM ALUMNO WHERE RUT_ALUMNO = ?");
        $stmtAlumno->bind_param("s", $rutAlumnoAsignacion);
        $stmtAlumno->execute();
        $resultadoAlumno = $stmtAlumno->get_result();

        if ($resultadoAlumno->num_rows > 0) {
            $filaAlumno = $resultadoAlumno->fetch_assoc();
            $idAlumno = $filaAlumno['ID_ALUMNO'];

            // Obtener el último ID_RELACION y calcular el nuevo ID
            $ultimoIdRelacion = $conn->query("SELECT MAX(ID_RELACION) AS ultimo_id FROM REL_ALUM_APOD")->fetch_assoc();
            $nuevoIdRelacion = $ultimoIdRelacion['ultimo_id'] + 1;

            // Fecha actual
            $fechaActual = date("Y-m-d H:i:s");

            // Insertar en REL_ALUM_APOD
            $stmtRel = $conn->prepare("INSERT INTO REL_ALUM_APOD (ID_RELACION, ID_ALUMNO, ID_APODERADO, STATUS, DELETE_FLAG, TIPO_RELACION, DATE_CREATED, DATE_UPDATED) VALUES (?, ?, ?, 1, 0, 3, ?, ?)");
            $stmtRel->bind_param("iiiss", $nuevoIdRelacion, $idAlumno, $idApoderadoSeleccionado, $fechaActual, $fechaActual);
            $stmtRel->execute();

            if ($stmtRel->affected_rows > 0) {
                $mensaje = "Apoderado asignado correctamente.";
            } else {
                $mensaje = "Error al asignar el apoderado.";
            }
            $stmtRel->close();
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

$idAlumnoSeleccionado = '';
if (isset($_POST['buscarAlumnoNombre'])) {
    $idAlumnoSeleccionado = $_POST['nombreAlumno'];

    // Consulta a la base de datos para obtener información basada en ID_ALUMNO
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
                                raa.DELETE_FLAG
                            FROM
                                ALUMNO AS a
                                    LEFT JOIN
                                REL_ALUM_APOD AS raa ON raa.ID_ALUMNO = a.ID_ALUMNO
                                    LEFT JOIN
                                APODERADO AS ap ON ap.ID_APODERADO = raa.ID_APODERADO
                            WHERE
                                a.ID_ALUMNO = ? AND raa.DELETE_FLAG = 0");
    $stmt->bind_param("i", $idAlumnoSeleccionado);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $mensaje = "Datos del alumno encontrados.";
        $apoderados = $resultado->fetch_all(MYSQLI_ASSOC);
    } else {
        $mensaje = "No se encontró ningún alumno con ese ID.";
    }
    $stmt->close();
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
        <td>
            <?php 
            // Verifica si RUT_APODERADO termina en "-K" o si tiene formato de RUT chileno
            if (preg_match('/^(\d{1,8}-[0-9kK])$/', $apoderado['RUT_APODERADO'])) {
                // Si es un RUT válido o termina en "-K", se muestra tal cual
                echo htmlspecialchars($apoderado['RUT_APODERADO']);
            } elseif (preg_match('/[a-zA-Z]/', $apoderado['RUT_APODERADO'])) {
                // Si contiene letras (excepto si termina en "-K"), se muestra un guion (-)
                echo '-';
            } else {
                // En otro caso, se verifica si parece ser un hash y no un RUT
                if (strlen($apoderado['RUT_APODERADO']) > 10 || preg_match('/[a-f0-9]{10,}/i', $apoderado['RUT_APODERADO'])) {
                    // Parece un hash, muestra un guion (-)
                    echo '-';
                } else {
                    // No parece un hash y no es un RUT válido, muestra un guion (-)
                    echo '-';
                }
            }
            ?>
        </td>
            <td><?php echo htmlspecialchars($apoderado['NOMBRE']) . " " . htmlspecialchars($apoderado['AP_PATERNO']) . " " . htmlspecialchars($apoderado['AP_MATERNO']); ?></td>
            <td><?php echo htmlspecialchars($apoderado['PARENTESCO']); ?></td>
            <td><?php echo htmlspecialchars($apoderado['MAIL_PART']); ?></td>
            <td><?php echo htmlspecialchars($apoderado['FONO_PART']); ?></td>
            <td>
                <button onclick="location.href='/sistemaescolar/editar_apoderado.php?rut=<?php echo $apoderado['RUT_APODERADO']; ?>'" class="btn btn-info">Editar</button>
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

    <!-- Añadir el nuevo select aquí -->
    <form method="post">
    <div class="form-group">
        <label for="nombreAlumno">Nombre del alumno:</label>
        <select class="form-control" id="nombreAlumno" name="nombreAlumno">
    <?php foreach ($alumnos as $alumno): ?>
        <option value="<?php echo htmlspecialchars($alumno['ID_ALUMNO']); ?>"
            <?php echo ($alumno['ID_ALUMNO'] == $idAlumnoSeleccionado) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($alumno['AP_PATERNO'] . ' ' . $alumno['AP_MATERNO'] . ' ' . $alumno['NOMBRE']); ?>
        </option>
    <?php endforeach; ?>
</select>
        <input type="hidden" name="idAlumnoHidden" id="idAlumnoHidden" value="">

    </div>
    <button type="submit" class="btn btn-primary" name="buscarAlumnoNombre">Buscar por Nombre</button>
</form>


    <h3>Seleccionar Apoderado</h3>
    <form method="post">
    <input type="hidden" name="rutAlumnoAsignacion" value="<?php echo htmlspecialchars($rutAlumno); ?>">

    <div class="form-group">
        <label for="apoderadoSeleccionado">Apoderado:</label>
        <select class="form-control" id="apoderadoSeleccionado" name="apoderadoSeleccionado">
            <?php foreach($listaApoderados as $apoderado): ?>
                <option value="<?php echo htmlspecialchars($apoderado['ID_APODERADO']); ?>">
                    <?php echo htmlspecialchars($apoderado['AP_PATERNO'] . ' ' . $apoderado['AP_MATERNO'] . ' ' .  $apoderado['NOMBRE']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn btn-primary" name="asignarApoderado">Asignar como apoderado</button>
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
            <input type="date" class="form-control" name="fecha_nac">
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var selectAlumno = document.getElementById('nombreAlumno');
        var inputIdAlumno = document.getElementById('idAlumnoHidden');

        // Establecer el valor inicial del input oculto
        inputIdAlumno.value = selectAlumno.value;

        selectAlumno.addEventListener('change', function() {
            inputIdAlumno.value = selectAlumno.value;
        });
    });
</script>
