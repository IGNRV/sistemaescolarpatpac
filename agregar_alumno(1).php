<?php
// Incluye la conexión a la base de datos
require_once 'db.php';
ini_set('display_errors', 1);

// Inicia sesión

// Define una variable para el mensaje
$mensaje = '';
$observaciones = []; // Array para almacenar las observaciones
$rutAlumno = ''; // Variable para almacenar el RUT del alumno buscado

$cursos = [];
$stmtCursos = $conn->prepare("SELECT ID_CURSO, NOMBRE_CURSO FROM CURSOS");
$stmtCursos->execute();
$resultadoCursos = $stmtCursos->get_result();

if ($resultadoCursos->num_rows > 0) {
    while ($filaCurso = $resultadoCursos->fetch_assoc()) {
        $cursos[] = $filaCurso['NOMBRE_CURSO'];
    }
}
$stmtCursos->close();

// Consulta para obtener las comunas
$comunas = [];
$stmtComunas = $conn->prepare("SELECT ID_COMUNA, NOM_COMUNA, ID_REGION FROM COMUNA");
$stmtComunas->execute();
$resultadoComunas = $stmtComunas->get_result();

if ($resultadoComunas->num_rows > 0) {
    while ($filaComuna = $resultadoComunas->fetch_assoc()) {
        $comunas[] = $filaComuna['NOM_COMUNA'];
    }
}
$stmtComunas->close();

// Obtener los periodos escolares
$periodosEscolares = [];
$stmtPeriodos = $conn->prepare("SELECT ID, PERIODO FROM PERIODO_ESCOLAR");
$stmtPeriodos->execute();
$resultadoPeriodos = $stmtPeriodos->get_result();

if ($resultadoPeriodos->num_rows > 0) {
    while ($filaPeriodo = $resultadoPeriodos->fetch_assoc()) {
        $periodosEscolares[$filaPeriodo['ID']] = $filaPeriodo['PERIODO'];
    }
}
$stmtPeriodos->close();


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

// Verifica si se ha enviado el formulario de búsqueda
if (isset($_POST['buscarAlumno'])) {
    $rutAlumno = $_POST['rutAlumno'];
    $stmt = $conn->prepare("SELECT * FROM ALUMNO WHERE RUT_ALUMNO = ?");
    $stmt->bind_param("s", $rutAlumno);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $mensaje = "Alumno encontrado.";
        $alumno = $resultado->fetch_assoc();

        // Consulta para obtener las observaciones del alumno
        $stmtObs = $conn->prepare("SELECT * FROM OBSERVACIONES WHERE RUT_ALUMNO = ?");
        $stmtObs->bind_param("s", $rutAlumno);
        $stmtObs->execute();
        $resultadoObs = $stmtObs->get_result();

        if ($resultadoObs->num_rows > 0) {
            while ($filaObs = $resultadoObs->fetch_assoc()) {
                $observaciones[] = $filaObs;
            }
        }
        $stmtObs->close();
    } else {
        $mensaje = "Alumno no encontrado.";
    }
    $stmt->close();
}


// Verifica si se ha enviado el formulario de actualización
if (isset($_POST['actualizar'])) {
    // Recoge los datos del formulario
    $nombre = $_POST['name'];
    $apPaterno = $_POST['ap_paterno'];
    $apMaterno = $_POST['ap_materno'];
    $fechaNac = $_POST['fecha_nac'];
    $rutAlumno = $_POST['rut_alumno'];
    $rda = $_POST['rda'];
    $calle = $_POST['calle'];
    $nroCalle = $_POST['nro_calle'];
    $obsDireccion = $_POST['obs_direccion'];
    $villa = $_POST['villa'];
    $comuna = $_POST['comuna'];
    $idRegion = $_POST['id_region'];
    $mail = $_POST['mail'];
    $fono = $_POST['fono'];

    // Prepara la consulta SQL para actualizar el alumno
    $stmt = $conn->prepare("UPDATE ALUMNO SET NOMBRE = ?, AP_PATERNO = ?, AP_MATERNO = ?, FECHA_NAC = ?, RDA = ?, CALLE = ?, NRO_CALLE = ?, OBS_DIRECCION = ?, VILLA = ?, COMUNA = ?, ID_REGION = ?, MAIL = ?, FONO = ? WHERE RUT_ALUMNO = ?");
    $stmt->bind_param("ssssssssssssss", $nombre, $apPaterno, $apMaterno, $fechaNac, $rda, $calle, $nroCalle, $obsDireccion, $villa, $comuna, $idRegion, $mail, $fono, $rutAlumno);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $mensaje = "Datos del alumno actualizados con éxito.";
        
        // Vuelve a buscar los datos del alumno para mostrar los datos actualizados
        $stmt = $conn->prepare("SELECT * FROM ALUMNO WHERE RUT_ALUMNO = ?");
        $stmt->bind_param("s", $rutAlumno);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            $alumno = $resultado->fetch_assoc();
        }
    } else {
        $mensaje = "No se pudo actualizar los datos del alumno.";
    }
    $stmt->close();
}

if (isset($_POST['agregar_observacion'])) {
    $categoria = $_POST['categoria'];
    $descripcion = $_POST['descripcion'];
    $fecha = $_POST['fecha'];
    // Asegúrate de usar la misma variable que usas para mostrar el RUT en el formulario
    $rutAlumno = $_POST['rutAlumno']; 

    // Asegúrate de que $rutAlumno esté definido y no sea nulo
    if (isset($rutAlumno) && !empty($rutAlumno)) {
        // Preparar la consulta SQL para insertar la observación
        $stmt = $conn->prepare("INSERT INTO OBSERVACIONES (CATEGORIA, DESCRIPCION, FECHA, RUT_ALUMNO) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $categoria, $descripcion, $fecha, $rutAlumno); // Cambia "i" por "s" si RUT_ALUMNO es VARCHAR
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $mensaje = "Observación agregada con éxito.";
        } else {
            $mensaje = "Error al agregar la observación.";
        }
        $stmt->close();
    } else {
        $mensaje = "RUT del alumno no definido.";
    }
}

// Verifica si se ha enviado el formulario de agregar alumno
if (isset($_POST['agregarAlumno'])) {
    // Recoge los datos del formulario
    $nombreNuevo = strtoupper($_POST['nombreNuevo']);
    $apPaternoNuevo = strtoupper($_POST['apPaternoNuevo']);
    $apMaternoNuevo = strtoupper($_POST['apMaternoNuevo']);
    $fechaNacNuevo = strtoupper($_POST['fechaNacNuevo']);
    $rutAlumnoNuevo = strtoupper($_POST['rutAlumnoNuevo']);
    $rdaNuevo = strtoupper($_POST['rdaNuevo']);
    $calleNuevo = strtoupper($_POST['calleNuevo']);
    $nroCalleNuevo = strtoupper($_POST['nroCalleNuevo']);
    $obsDireccionNuevo = strtoupper($_POST['obsDireccionNuevo']);
    $villaNuevo = strtoupper($_POST['villaNuevo']);
    $comunaSeleccionada = strtoupper($_POST['comunaNuevo']);
    $fonoNuevo = strtoupper($_POST['fonoNuevo']);
    $cursoSeleccionado = strtoupper($_POST['curso']);
    $fotoalumno = strtoupper($_POST['fotoalumno']);
    $fechaingreso = strtoupper($_POST['fechaingreso']);
    $mailNuevo = $_POST['mailNuevo'];

    $periodoescolar = $_POST['periodoEscolar'];
    $status = 1;
    $deleteflag = 1;

    // Obtener ID_CURSO basado en la selección
    $stmtCurso = $conn->prepare("SELECT ID_CURSO FROM CURSOS WHERE NOMBRE_CURSO = ?");
    $stmtCurso->bind_param("s", $cursoSeleccionado);
    $stmtCurso->execute();
    $resultadoCurso = $stmtCurso->get_result();
    $idcurso = ($resultadoCurso->num_rows > 0) ? $resultadoCurso->fetch_assoc()['ID_CURSO'] : null;

    // Obtener ID_COMUNA e ID_REGION basado en la selección de comuna
    $stmtComuna = $conn->prepare("SELECT ID_COMUNA, ID_REGION FROM COMUNA WHERE NOM_COMUNA = ?");
    $stmtComuna->bind_param("s", $comunaSeleccionada);
    $stmtComuna->execute();
    $resultadoComuna = $stmtComuna->get_result();
    $comunaData = ($resultadoComuna->num_rows > 0) ? $resultadoComuna->fetch_assoc() : null;
    $idcomuna = $comunaData ? $comunaData['ID_COMUNA'] : null;
    $idRegion = $comunaData ? $comunaData['ID_REGION'] : null;

    $stmtNuevo = $conn->prepare("INSERT INTO ALUMNO (NOMBRE, AP_PATERNO, AP_MATERNO, FECHA_NAC, RUT_ALUMNO, RDA, CALLE, NRO_CALLE, OBS_DIRECCION, VILLA, COMUNA, ID_REGION, MAIL, FONO, CURSO, ID_CURSO, ID_COMUNA, FOTO_ALUMNO, FECHA_INGRESO, PERIODO_ESCOLAR, STATUS, DELETE_FLAG) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtNuevo->bind_param("ssssssssssssssssssssss", $nombreNuevo, $apPaternoNuevo, $apMaternoNuevo, $fechaNacNuevo, $rutAlumnoNuevo, $rdaNuevo, $calleNuevo, $nroCalleNuevo, $obsDireccionNuevo, $villaNuevo, $comunaSeleccionada, $idRegion, $mailNuevo, $fonoNuevo, $cursoSeleccionado, $idcurso, $idcomuna, $fotoalumno, $fechaingreso, $periodoescolar, $status, $deleteflag);
    $stmtNuevo->execute();

    if ($stmtNuevo->affected_rows > 0) {
        $mensaje = "Nuevo alumno agregado con éxito.";
        $idAlumno = $conn->insert_id;

        // Obtén el último ID_PAGO de HISTORIAL_PAGOS
        $ultimoIDPago = $conn->query("SELECT MAX(ID_PAGO) as ultimoID FROM HISTORIAL_PAGOS")->fetch_assoc()['ultimoID'];
        $idPago = $ultimoIDPago + 1;

        // Fechas de vencimiento
        $periodoEscolarID = $_POST['periodoEscolar'];
        $anioPeriodoEscolar = substr($periodosEscolares[$periodoEscolarID], 0, 4); // Obtén el año del periodo escolar seleccionado
    
        // Fechas de vencimiento con el año ajustado
        $fechasVencimiento = [
            "$anioPeriodoEscolar-03-05", "$anioPeriodoEscolar-04-05", "$anioPeriodoEscolar-05-05", 
            "$anioPeriodoEscolar-06-05", "$anioPeriodoEscolar-07-05", "$anioPeriodoEscolar-08-05", 
            "$anioPeriodoEscolar-09-05", "$anioPeriodoEscolar-10-05", "$anioPeriodoEscolar-11-05", 
            "$anioPeriodoEscolar-12-05", "$anioPeriodoEscolar-12-22"
        ];

        // Preparar la consulta para insertar en HISTORIAL_PAGOS
        $stmtHistorial = $conn->prepare("INSERT INTO HISTORIAL_PAGOS (ID_PAGO, ID_ALUMNO, RUT_ALUMNO, CODIGO_PRODUCTO, VALOR_ARANCEL, DESCUENTO_BECA, OTROS_DESCUENTOS, VALOR_A_PAGAR, FECHA_PAGO, MEDIO_PAGO, NRO_MEDIOPAGO, FECHA_SUSCRIPCION, ESTADO_PAGO, FECHA_VENCIMIENTO, FECHA_INGRESO, FECHA_COBRO, ID_PERIODO_ESCOLAR) VALUES (?, ?, ?, 1, 95000, 0, 0, 95000, '2100-01-01', 0, 0, '2100-01-01', 0, ?, ?, '2100-01-01', 1)");

        foreach ($fechasVencimiento as $fechaVencimiento) {
            $stmtHistorial->bind_param("iisss", $idPago, $idAlumno, $rutAlumnoNuevo, $fechaVencimiento, $fechaActual);
            $stmtHistorial->execute();
            $idPago++; // Incrementa el ID_PAGO para la próxima inserción
        }

        $stmtHistorial->close();
    } else {
        $mensaje = "Error al agregar el nuevo alumno.";
    }
    $stmtNuevo->close();
}



?>

<?php if (!empty($mensaje)): ?>
    <div class="alert alert-success" role="alert">
        <?php echo $mensaje; ?>
    </div>
<?php endif; ?>

<h2>Agregar Nuevo Alumno</h2>
<form action="" method="post">
    <div class="form-group">
        <label>Nombre:</label>
        <input type="text" class="form-control to-uppercase" name="nombreNuevo" required>
    </div>
    <div class="form-group">
        <label>Apellido Paterno:</label>
        <input type="text" class="form-control to-uppercase" name="apPaternoNuevo" required>
    </div>
    <div class="form-group">
        <label>Apellido Materno:</label>
        <input type="text" class="form-control to-uppercase" name="apMaternoNuevo" required>
    </div>
    <div class="form-group">
        <label>Fecha de Nacimiento:</label>
        <input type="date" class="form-control to-uppercase" name="fechaNacNuevo" required>
    </div>
    <div class="form-group">
        <label>RUT:</label>
        <input type="text" class="form-control to-uppercase" name="rutAlumnoNuevo" required>
    </div>
    <div class="form-group">
        <label>RDA:</label>
        <input type="text" class="form-control to-uppercase" name="rdaNuevo">
    </div>
    <div class="form-group">
        <label>Calle:</label>
        <input type="text" class="form-control to-uppercase" name="calleNuevo">
    </div>
    <div class="form-group">
        <label>Número de Calle:</label>
        <input type="text" class="form-control to-uppercase" name="nroCalleNuevo">
    </div>
    <div class="form-group">
        <label>Observaciones Dirección:</label>
        <input type="text" class="form-control to-uppercase" name="obsDireccionNuevo">
    </div>
    <div class="form-group">
        <label>Villa/Población:</label>
        <input type="text" class="form-control to-uppercase" name="villaNuevo">
    </div>
    <div class="form-group">
        <label>Comuna:</label>
        <select class="form-control to-uppercase" name="comunaNuevo">
            <?php foreach ($comunas as $comuna): ?>
                <option value="<?php echo htmlspecialchars($comuna); ?>">
                    <?php echo htmlspecialchars($comuna); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="form-group">
        <label>Email:</label>
        <input type="email" class="form-control" name="mailNuevo">
    </div>
    <div class="form-group">
        <label>Teléfono:</label>
        <input type="text" class="form-control" name="fonoNuevo">
    </div>
    <div class="form-group">
        <label>Curso:</label>
        <select class="form-control" name="curso">
            <?php foreach ($cursos as $curso): ?>
                <option value="<?php echo htmlspecialchars($curso); ?>">
                    <?php echo htmlspecialchars($curso); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
    <label>Periodo Escolar:</label>
    <select class="form-control" name="periodoEscolar">
        <?php foreach ($periodosEscolares as $id => $periodo): ?>
            <option value="<?php echo htmlspecialchars($id); ?>">
                <?php echo htmlspecialchars($periodo); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
    <div class="form-group">
        <label>Foto alumno:</label>
        <input type="text" class="form-control" name="fotoalumno">
    </div>
    <div class="form-group">
        <label>Fecha de Ingreso:</label>
        <input type="date" class="form-control" name="fechaingreso" required>
    </div>
    <button type="submit" class="btn btn-success" name="agregarAlumno">Agregar Alumno</button>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var inputs = document.querySelectorAll('.to-uppercase');
        inputs.forEach(function(input) {
            input.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        });
    });
function confirmDelete() {
    return confirm("¿Estás seguro de que quieres eliminar esta observación?");
}
</script>