<?php
require_once 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recuperar los datos del formulario
    $idPago = $_POST['idPago'];
    $descuentoBeca = $_POST['descuentoBeca'];
    $otrosDescuentos = $_POST['otrosDescuentos'];
    $valorAPagar = $_POST['valorAPagar'];
    $rutAlumnoBuscado = $_POST['rutAlumnoBuscado']; // Asegúrate de que este valor se está enviando correctamente desde becas.php
    $descuentoSeleccionado = $_POST['descuentoSeleccionado'];
    $periodoEscolarSeleccionado = $_POST['periodoEscolarSeleccionado'];

    // Obtener ID_ALUMNO a partir de RUT_ALUMNO
    $stmtAlumno = $conn->prepare("SELECT ID_ALUMNO FROM ALUMNO WHERE RUT_ALUMNO = ?");
    $stmtAlumno->bind_param("s", $rutAlumnoBuscado);
    $stmtAlumno->execute();
    $resultAlumno = $stmtAlumno->get_result();
    $idAlumno = null;
    if ($alumnoData = $resultAlumno->fetch_assoc()) {
        $idAlumno = $alumnoData['ID_ALUMNO'];
    }
    $stmtAlumno->close();

    if (!$idAlumno) {
        echo "No se encontró el alumno con el RUT proporcionado.";
        exit; // Detener la ejecución si no se encuentra el alumno
    }

    // Actualizar la tabla HISTORIAL_PAGOS con el ID_ALUMNO
    $stmt = $conn->prepare("UPDATE HISTORIAL_PAGOS SET DESCUENTO_BECA = ?, OTROS_DESCUENTOS = ?, VALOR_A_PAGAR = ? WHERE ID_PAGO = ? AND ID_ALUMNO = ?");
    $stmt->bind_param("dddii", $descuentoBeca, $otrosDescuentos, $valorAPagar, $idPago, $idAlumno);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "Actualización exitosa en HISTORIAL_PAGOS<br />";
    } else {
        echo "Error en la actualización de HISTORIAL_PAGOS<br />";
    }
    $stmt->close();

    // Insertar en la tabla BECAS
    $fechaActual = date('Y-m-d');
    $estadoBeca = "ACTIVA";
    $insertStmt = $conn->prepare("INSERT INTO BECAS (ID_ALUMNO, DESCUENTO, ESTADO_BECA, FECHA_INGRESO, FECHA_ACTIVACION, PERIODO_ESCOLAR) VALUES (?, ?, ?, ?, ?, ?)");
    $insertStmt->bind_param("iisssi", $idAlumno, $descuentoSeleccionado, $estadoBeca, $fechaActual, $fechaActual, $periodoEscolarSeleccionado);
    $insertStmt->execute();

    if ($insertStmt->affected_rows > 0) {
        echo "Inserción exitosa en BECAS";
    } else {
        echo "Error en la inserción en BECAS: " . $insertStmt->error;
    }
    $insertStmt->close();
}
?>