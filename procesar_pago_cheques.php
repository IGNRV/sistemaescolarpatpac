<?php
require_once 'db.php'; 
/* ini_set('display_errors', 1); */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json);

    $conn->begin_transaction(); // Iniciar una transacción

    try {
        foreach ($data->pagos as $pago) {
            $fechaEmision = new DateTime($pago->fechaEmision);
            $fechaCobro = $fechaEmision->modify('+1 day')->format('Y-m-d');
            $stmt = $conn->prepare("INSERT INTO DETALLES_TRANSACCION 
        (ANO, VALOR, FECHA_PAGO, MEDIO_DE_PAGO, ESTADO, TIPO_DOCUMENTO, NUMERO_DOCUMENTO, FECHA_EMISION, FECHA_COBRO, BANCO, N_CUOTAS, ID_PAGO, CTA_CORRIENTE) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("idssisssssisi", 
                $pago->ano,
                $pago->monto,
                $pago->fechaPago,
                $pago->medioDePago,
                $pago->estado,
                $pago->tipoDocumento,
                $pago->nDocumento,
                $pago->fechaEmision,
                $fechaCobro,  // Usar la fecha calculada
                $pago->banco,
                $pago->nCuotas,
                $pago->idPago,
                $pago->nCuentaCorriente
            );
            $stmt->execute();

            // Consulta para obtener el valor actual pagado
            $query = $conn->prepare("SELECT VALOR_PAGADO FROM HISTORIAL_PAGOS WHERE ID_PAGO = ?");
            $query->bind_param("i", $pago->idPago);
            $query->execute();
            $resultado = $query->get_result();
            if ($fila = $resultado->fetch_assoc()) {
                $valorActual = $fila['VALOR_PAGADO'];
            } else {
                $valorActual = 0; // O manejar el caso de no encontrar el pago
            }
            $query->close();

            // Calcula el nuevo valor
            $nuevoValor = $valorActual + $pago->monto;

            // Actualiza VALOR_PAGADO sumando el nuevo valor
            $updateStmt = $conn->prepare("UPDATE HISTORIAL_PAGOS SET VALOR_PAGADO = ? WHERE ID_PAGO = ?");
            $updateStmt->bind_param("di", $nuevoValor, $pago->idPago);
            $updateStmt->execute();
            $updateStmt->close();

            // Consulta para obtener el VALOR_A_PAGAR
            $queryValorAPagar = $conn->prepare("SELECT VALOR_A_PAGAR FROM HISTORIAL_PAGOS WHERE ID_PAGO = ?");
            $queryValorAPagar->bind_param("i", $pago->idPago);
            $queryValorAPagar->execute();
            $resultadoValorAPagar = $queryValorAPagar->get_result();
            if ($filaValorAPagar = $resultadoValorAPagar->fetch_assoc()) {
                $valorAPagar = $filaValorAPagar['VALOR_A_PAGAR'];
            } else {
                $valorAPagar = 0; // O manejar el caso de no encontrar el pago
            }
            $queryValorAPagar->close();

            // Compara VALOR_PAGADO con VALOR_A_PAGAR y actualiza ESTADO_PAGO
            $estadoPago = ($nuevoValor == $valorAPagar) ? 2 : 3;
            $updateEstadoPagoStmt = $conn->prepare("UPDATE HISTORIAL_PAGOS SET ESTADO_PAGO = ? WHERE ID_PAGO = ?");
            $updateEstadoPagoStmt->bind_param("ii", $estadoPago, $pago->idPago);
            $updateEstadoPagoStmt->execute();
            $updateEstadoPagoStmt->close();
        }

        $conn->commit(); // Si todo salió bien, confirma los cambios
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback(); // Si algo salió mal, revierte los cambios
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
