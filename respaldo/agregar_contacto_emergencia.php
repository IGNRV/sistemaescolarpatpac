<?php
// Incluye la conexión a la base de datos
require_once 'db.php';

// Define una variable para el mensaje
$mensaje = '';

// Verifica si el usuario está logueado
if (!isset($_SESSION['EMAIL'])) {
    header('Location: login.php');
    exit;
}

$EMAIL = $_SESSION['EMAIL'];

// Recupera la lista de periodos escolares de la base de datos
$periodosEscolares = [];
$stmtPeriodos = $conn->prepare("SELECT ID, PERIODO FROM PERIODO_ESCOLAR");
$stmtPeriodos->execute();
$resultadoPeriodos = $stmtPeriodos->get_result();
while ($filaPeriodo = $resultadoPeriodos->fetch_assoc()) {
    $periodosEscolares[] = $filaPeriodo; // Guarda la fila entera que contiene ID y PERIODO
}
$stmtPeriodos->close();


// Recupera la lista de alumnos de la base de datos
$alumnos = [];
$stmtAlumnos = $conn->prepare("SELECT ID_ALUMNO, NOMBRE FROM ALUMNO");
$stmtAlumnos->execute();
$resultadoAlumnos = $stmtAlumnos->get_result();
while ($filaAlumno = $resultadoAlumnos->fetch_assoc()) {
    $alumnos[] = $filaAlumno['NOMBRE'];
}
$stmtAlumnos->close();



if (isset($_POST['insertar_contacto'])) {
    // Recoger datos del formulario
    $rut = $_POST['rut'];
    $nombre = $_POST['nombre'];
    $apPaterno = $_POST['ap_paterno'];
    $apMaterno = $_POST['ap_materno'];
    $fonoEmergencia = $_POST['fono_emergencia'];
    $mailEmergencia = $_POST['mail_emergencia'];
    $parentesco = $_POST['parentesco'];
    $nombreAlumno = $_POST['alumno']; // Este será el nombre del alumno, necesitas el ID
    $periodoEscolarId = $_POST['periodoEscolar']; // Este es el ID del periodo escolar

    // Obtener el ID del alumno basado en el nombre seleccionado
    $stmtAlumno = $conn->prepare("SELECT ID_ALUMNO FROM ALUMNO WHERE NOMBRE = ?");
    $stmtAlumno->bind_param("s", $nombreAlumno);
    $stmtAlumno->execute();
    $resultadoAlumno = $stmtAlumno->get_result();
    $filaAlumno = $resultadoAlumno->fetch_assoc();
    $idAlumno = $filaAlumno['ID_ALUMNO'];
    $stmtAlumno->close();

    // Obtener el último ID_CONTACTO y sumarle 1 para el nuevo contacto
    $ultimoIDContacto = $conn->query("SELECT MAX(ID_CONTACTO) as ultimoID FROM CONTACTO_EMERGENCIA")->fetch_assoc()['ultimoID'];
    $idContacto = $ultimoIDContacto + 1;

    // Insertar los datos en la tabla CONTACTO_EMERGENCIA
$stmtInsertar = $conn->prepare("INSERT INTO CONTACTO_EMERGENCIA (ID_CONTACTO, RUT_APODERADO, ID_ALUMNO, PARENTESCO, NOMBRE, AP_PATERNO, AP_MATERNO, MAIL_EMERGENCIA, FONO_EMERGENCIA, FECHA_INGRESO, PERIODO_ESCOLAR, STATUS, DELETE_FLAG) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, 1, 0)");
$stmtInsertar->bind_param("isissssssi", $idContacto, $rut, $idAlumno, $parentesco, $nombre, $apPaterno, $apMaterno, $mailEmergencia, $fonoEmergencia, $periodoEscolarId);
$stmtInsertar->execute();

    if ($stmtInsertar->affected_rows > 0) {
        $mensaje = "Contacto de emergencia agregado con éxito.";
    } else {
        $mensaje = "Error al agregar el contacto de emergencia.";
    }
    $stmtInsertar->close();
}
$rutAlumno = isset($_POST['rutAlumno']) ? $_POST['rutAlumno'] : '';

?>
<?php if (!empty($mensaje)): ?>
        <div class="alert alert-success" role="alert">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>
<div class="emergency-contact">
    <h1>Agregar contacto de emergencia</h1>
    <form method="post">
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="inputRUT">RUT (Con guion)</label>
                    <input type="text" name="rut" class="form-control to-uppercase" id="inputRUT" value="">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="inputNombres">Nombres</label>
                    <input type="text" name="nombre" class="form-control to-uppercase" id="inputNombres" value="">
                </div>
                <div class="form-group col-md-4">
                    <label for="inputApellidoPaterno">Ap. Paterno</label>
                    <input type="text" name="ap_paterno" class="form-control to-uppercase" id="inputApellidoPaterno" value="">
                </div>
                <div class="form-group col-md-4">
                    <label for="inputApellidoMaterno">Ap. Materno</label>
                    <input type="text" name="ap_materno" class="form-control to-uppercase" id="inputApellidoMaterno" value="">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="inputTelefono">Teléfono</label>
                    <input type="text" name="fono_emergencia" class="form-control to-uppercase" id="inputTelefono" value="">
                </div>
                <div class="form-group col-md-6">
                    <label for="inputEmail">Correo Electrónico</label>
                    <input type="email" name="mail_emergencia" class="form-control" id="inputEmail" value="">
                </div>
                <div class="form-group col-md-6">
                    <label for="parentesco">Parentesco</label>
                    <input type="text" name="parentesco" class="form-control to-uppercase" id="parentesco" value="">
                </div>
            </div>
            <div class="form-row">
            <div class="form-group col-md-6">
    <label for="alumno">Alumno</label>
    <select name="alumno" class="form-control" id="alumno">
        <?php foreach ($alumnos as $nombreAlumno): ?>
            <option value="<?php echo htmlspecialchars($nombreAlumno); ?>">
                <?php echo htmlspecialchars($nombreAlumno); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<div class="form-group col-md-6">
    <label for="periodoEscolar">Periodo Escolar</label>
    <select name="periodoEscolar" class="form-control" id="periodoEscolar">
        <?php foreach ($periodosEscolares as $periodo): ?>
            <option value="<?php echo htmlspecialchars($periodo['ID']); ?>">
                <?php echo htmlspecialchars($periodo['PERIODO']); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
            </div>
            <button type="submit" class="btn btn-primary btn-block" name="insertar_contacto">AGREGAR CONTACTO DE EMERGENCIA</button>
        </form>
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
</script>
