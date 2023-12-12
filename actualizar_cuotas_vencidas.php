<?php
include 'db.php';
$rut = $_POST['rut'];
$fechaActual = date("Y-m-d");

// Actualizar cuotas vencidas a estado 1
$sqlVencidas = "UPDATE cuotas_pago AS cp 
                JOIN alumno AS a ON a.id = cp.id_alumno 
                SET cp.estado_cuota = 1 
                WHERE a.rut = '$rut' AND cp.fecha_cuota_deuda < '$fechaActual' AND cp.estado_cuota = 0";

if ($conn->query($sqlVencidas) === TRUE) {
    echo "Cuotas vencidas actualizadas correctamente.";
} else {
    echo "Error actualizando cuotas vencidas: " . $conn->error;
}

// Actualizar cuotas no vencidas a estado 0
$sqlNoVencidas = "UPDATE cuotas_pago AS cp 
                  JOIN alumno AS a ON a.id = cp.id_alumno 
                  SET cp.estado_cuota = 0 
                  WHERE a.rut = '$rut' AND cp.fecha_cuota_deuda >= '$fechaActual' AND cp.estado_cuota = 1";

if ($conn->query($sqlNoVencidas) === TRUE) {
    echo " Cuotas no vencidas actualizadas correctamente.";
} else {
    echo " Error actualizando cuotas no vencidas: " . $conn->error;
}

$conn->close();
?>
