<?php
require_once 'db.php'; 
/* ini_set('display_errors', 1); */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json);

    $conn->begin_transaction(); // Iniciar una transacción

    try {
        foreach ($data->pagos as $pago) {
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
                $pago->nDocumento, // Usar el valor del número de cuenta corriente
                $pago->fechaEmision,
                $pago->fechaCobro,
                $pago->banco,
                $pago->nCuotas,
                $pago->idPago,
                $pago->nCuentaCorriente
            );
            $stmt->execute();

            // Si la inserción fue exitosa, actualiza HISTORIAL_PAGOS
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
    $updateStmt = $conn->prepare("UPDATE HISTORIAL_PAGOS SET ESTADO_PAGO = 3, VALOR_PAGADO = ? WHERE ID_PAGO = ?");
    $updateStmt->bind_param("di", $nuevoValor, $pago->idPago);
    $updateStmt->execute();
    $updateStmt->close();
        }

        $conn->commit(); // Si todo salió bien, confirma los cambios
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback(); // Si algo salió mal, revierte los cambios
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
