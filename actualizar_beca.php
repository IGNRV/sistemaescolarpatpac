<?php
require_once 'db.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recuperar los datos del formulario
    $idPago = $_POST['idPago'];
    $descuentoBeca = $_POST['descuentoBeca'];
    $otrosDescuentos = $_POST['otrosDescuentos'];
    $valorAPagar = $_POST['valorAPagar'];
    $rutAlumnoBuscado = $_POST['rutAlumnoBuscado'];
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
        echo "Inserción exitosa en BECAS<br />";
    } else {
        echo "Error en la inserción en BECAS: " . $insertStmt->error . "<br />";
    }
    $insertStmt->close();

    // Obtener el ID del usuario actual
    $EMAIL = $_SESSION['EMAIL'];
    $queryUsuario = "SELECT ID FROM USERS WHERE EMAIL = ?";
    $stmtUsuario = $conn->prepare($queryUsuario);
    $stmtUsuario->bind_param("s", $EMAIL);
    $stmtUsuario->execute();
    $resultadoUsuario = $stmtUsuario->get_result();
    $idUsuario = null;
    if ($filaUsuario = $resultadoUsuario->fetch_assoc()) {
        $idUsuario = $filaUsuario['ID'];
    }
    $stmtUsuario->close();

    if (!$idUsuario) {
        echo "No se pudo obtener el ID del usuario.<br />";
        exit; // Detener la ejecución si no se encuentra el usuario
    }

    // Insertar en la tabla HISTORIAL_CAMBIOS
    $tipoCambio = "REGISTRO DE BECA"; // Asegúrate de que este string sea adecuado para tu aplicación
    $stmtHistorial = $conn->prepare("INSERT INTO HISTORIAL_CAMBIOS (ID_USUARIO, TIPO_CAMBIO, ID_ALUMNO) VALUES (?, ?, ?)");
    $stmtHistorial->bind_param("isi", $idUsuario, $tipoCambio, $idAlumno);
    $stmtHistorial->execute();

    if ($stmtHistorial->affected_rows > 0) {
        echo "Registro exitoso en HISTORIAL_CAMBIOS<br />";
    } else {
        echo "Error en el registro en HISTORIAL_CAMBIOS: " . $stmtHistorial->error . "<br />";
    }
    $stmtHistorial->close();
}
?>
