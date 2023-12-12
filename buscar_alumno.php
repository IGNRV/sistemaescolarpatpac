<?php
// buscar_alumno.php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rut = $_POST['rut'];

    // Preparar la consulta para actualizar el estado de las cuotas
    $queryUpdate = "UPDATE cuotas_pago AS cp
                    JOIN alumno AS a ON a.id = cp.id_alumno
                    SET cp.estado_cuota = 1
                    WHERE a.rut = ? AND cp.fecha_cuota_deuda <= CURDATE() AND cp.estado_cuota = 0";
    $stmtUpdate = $conn->prepare($queryUpdate);
    $stmtUpdate->bind_param("s", $rut);

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // Ejecutar actualización
        $stmtUpdate->execute();

        // Preparar consulta para obtener los datos de las cuotas de pago del alumno
        $query = "SELECT cp.id, a.rut, cp.monto, cp.fecha_cuota_deuda, cp.estado_cuota, cp.id_alumno
                  FROM cuotas_pago AS cp
                  LEFT JOIN alumno AS a ON a.id = cp.id_alumno
                  WHERE a.rut = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $rut);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $datos_cuotas_anterior = [];
            $datos_cuotas_actual = [];
            $ano_actual = date('Y');

            while ($cuota = $result->fetch_assoc()) {
                $ano_cuota = date('Y', strtotime($cuota['fecha_cuota_deuda']));
                if ($ano_cuota < $ano_actual) {
                    $datos_cuotas_anterior[] = $cuota;
                } else {
                    $datos_cuotas_actual[] = $cuota;
                }
            }

            // Devolver arrays al frontend
            echo json_encode(['anterior' => $datos_cuotas_anterior, 'actual' => $datos_cuotas_actual]);
        } else {
            echo "Alumno no encontrado.";
        }

        // Confirmar transacción
        $conn->commit();
    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        $conn->rollback();
        echo "Error al procesar la solicitud: " . $e->getMessage();
    }

    // Cerrar las declaraciones preparadas
    $stmtUpdate->close();
    $stmt->close();

    exit;
}
?>
