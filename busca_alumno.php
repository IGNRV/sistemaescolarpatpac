<?php
include 'db.php'; // Asegúrate de que la ruta sea correcta

// Verificar si se recibió 'rut' a través de POST
if(isset($_POST['rut'])) {
    $rut = $_POST['rut'];
    $anioActual = date("Y");

    // Usar placeholders en la consulta SQL
    $sql = "SELECT cp.id, cp.fecha_cuota_deuda, cp.monto, cp.estado_cuota, YEAR(cp.fecha_cuota_deuda) as año
            FROM cuotas_pago AS cp
            LEFT JOIN alumno AS a ON a.id = cp.id_alumno
            WHERE a.rut = ?"; // Placeholder para el RUT

    // Preparar la consulta
    if ($stmt = $conn->prepare($sql)) {
        // Vincular la variable '$rut' al placeholder
        $stmt->bind_param("s", $rut);

        // Ejecutar la consulta
        $stmt->execute();

        // Obtener el resultado
        $result = $stmt->get_result();
        $datosAnterior = [];
        $datosActual = [];

        // Procesar el resultado
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                if($row['año'] == $anioActual) {
                    array_push($datosActual, $row);
                } else {
                    array_push($datosAnterior, $row);
                }
            }
            echo json_encode(['encontrado' => true, 'datosAnterior' => $datosAnterior, 'datosActual' => $datosActual]);
        } else {
            echo json_encode(['encontrado' => false]);
        }
        
        // Cerrar el statement
        $stmt->close();
    } else {
        // Manejar el error en caso de fallo al preparar la consulta
        echo "Error en la preparación de la consulta: " . $conn->error;
    }
} else {
    // Enviar una respuesta en caso de que no se haya recibido el 'rut'
    echo json_encode(['encontrado' => false, 'error' => 'No se recibió el RUT']);
}

// Cerrar la conexión
$conn->close();
?>
