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
