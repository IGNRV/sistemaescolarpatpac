<?php
require 'db.php'; // Asegúrate de cambiar esto por tu ruta correcta al archivo de conexión

if(isset($_POST['rut'])) {
    $rut = $_POST['rut'];
    $fecha_actual = date("Y-m-d"); // Obtener la fecha actual

    // Consulta para actualizar el estado de las cuotas
    $query = "UPDATE cuotas_pago SET estado_cuota = 1 
              WHERE rut_alumno = '$rut' AND fecha_cuota_deuda <= '$fecha_actual' AND estado_cuota = 0";

    if ($conn->query($query) === TRUE) {
        echo "Estado de cuotas actualizado con éxito.";
    } else {
        echo "Error al actualizar el estado de las cuotas: " . $conn->error;
    }

    $conn->close();
}
?>
