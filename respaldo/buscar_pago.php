<?php
require_once 'db.php'; // Asegúrate de incluir el archivo correcto para la conexión a la base de datos

if (isset($_POST['rutAlumno'])) {
    $rutAlumno = $_POST['rutAlumno'];

    // Preparar y ejecutar la consulta
    $stmt = $conn->prepare("SELECT 
                                hp.ID_PAGO,
                                hp.ID_ALUMNO,
                                a.RUT_ALUMNO,
                                // ... (resto de las columnas)
                            FROM
                                c1occsyspay.HISTORIAL_PAGOS AS hp
                                LEFT JOIN ALUMNO AS a ON a.ID_ALUMNO = hp.ID_ALUMNO
                            WHERE a.RUT_ALUMNO = ?");
    $stmt->bind_param("s", $rutAlumno);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        // Procesar los resultados
        echo "<table>"; // Empieza a construir la tabla HTML
        // Aquí agregas las filas de la tabla con los datos
        while ($fila = $resultado->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($fila['RUT_ALUMNO']) . "</td>";
            // ... otras celdas ...
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No se encontraron pagos para el alumno con RUT: $rutAlumno";
    }
}
?>
