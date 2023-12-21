<?php
// procesar_cambio_estado.php

session_start();
require_once 'db.php'; // Asegúrate de que este archivo contiene la conexión a la base de datos

if (!isset($_SESSION['EMAIL'])) {
    header('Location: login.php');
    exit;
}

// Verificar si la petición proviene de un formulario
if (isset($_POST['cambiarEstado'])) {
    $nroMedioPago = $_POST['nroMedioPago'];
    $estadoActual = $_POST['estadoActual'];
    
    // Calcular el nuevo estado
    $nuevoEstado = $estadoActual == 1 ? 2 : 1;

    // Preparar la consulta para actualizar el estado
    $stmt = $conn->prepare("UPDATE MEDIOS_DE_PAGO SET ESTADO_MP = ? WHERE NRO_MEDIOPAGO = ?");
    $stmt->bind_param("ii", $nuevoEstado, $nroMedioPago);
    $resultado = $stmt->execute();
    
    // Verificar si la actualización fue exitosa
    if ($resultado) {
        $_SESSION['mensaje'] = "Estado actualizado correctamente.";

        // Inserción en la tabla HISTORIAL_CAMBIOS
        $EMAIL = $_SESSION['EMAIL'];
        $queryUsuario = "SELECT ID FROM USERS WHERE EMAIL = '$EMAIL'";
        $resultadoUsuario = $conn->query($queryUsuario);

        if ($filaUsuario = $resultadoUsuario->fetch_assoc()) {
            $idUsuario = $filaUsuario['ID'];

            // Obtener el ID del apoderado asociado al medio de pago
            $stmtObtenerIdApoderado = $conn->prepare("SELECT ID_APODERADO FROM APODERADO WHERE RUT_APODERADO = (SELECT RUT_PAGADOR FROM MEDIOS_DE_PAGO WHERE NRO_MEDIOPAGO = ?)");
            $stmtObtenerIdApoderado->bind_param("i", $nroMedioPago);
            $stmtObtenerIdApoderado->execute();
            $resultadoIdApoderado = $stmtObtenerIdApoderado->get_result();

            if ($filaIdApoderado = $resultadoIdApoderado->fetch_assoc()) {
                $idApoderado = $filaIdApoderado['ID_APODERADO'];

                // Cambiar el tipo de cambio según el estado actual
                $tipoCambio = $nuevoEstado == 1 ? "ACTIVACION DE MEDIO DE PAGO" : "DESACTIVACION DE MEDIO DE PAGO";
                $stmtHistorial = $conn->prepare("INSERT INTO HISTORIAL_CAMBIOS (ID_USUARIO, TIPO_CAMBIO, ID_APODERADO) VALUES (?, ?, ?)");
                $stmtHistorial->bind_param("isi", $idUsuario, $tipoCambio, $idApoderado);
                $stmtHistorial->execute();

                $stmtHistorial->close();
            }
            $stmtObtenerIdApoderado->close();
        }
    } else {
        $_SESSION['mensaje'] = "Error al actualizar el estado.";
    }
    
    // Cerrar la declaración preparada
    $stmt->close();
    
    // Redirigir de vuelta a la página del tutor económico
    header('Location: https://antilen.pat-pac.cl/sistemaescolar/bienvenido.php?page=tutor_economico');
    exit;
}

// Si no se ha accedido al script a través del formulario, redirigir a la página de inicio o manejarlo como se considere
header('Location: index.php');
exit;
?>
