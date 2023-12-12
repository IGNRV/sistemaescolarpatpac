<?php
require_once 'db.php';

$fecha = $_GET['fecha'] ?? '';
$medioPago = $_GET['medioPago'] ?? '';

$query = "SELECT * FROM DETALLES_TRANSACCION WHERE FECHA_PAGO = '$fecha' AND MEDIO_DE_PAGO = '$medioPago' AND ESTADO = 1";
$resultado = $conn->query($query);

$datos = [];
if ($resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        $datos[] = $fila;
    }
}

echo json_encode($datos);
?>