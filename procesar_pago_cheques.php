<?php
require_once 'db.php'; 
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json);

    $conn->begin_transaction(); // Iniciar una transacción

    try {
        foreach ($data->pagos as $pago) {
            // Insertar en DETALLES_TRANSACCION
            $stmt = $conn->prepare("INSERT INTO DETALLES_TRANSACCION 
                (ANO, VALOR, FECHA_PAGO, MEDIO_DE_PAGO, ESTADO, TIPO_DOCUMENTO, NUMERO_DOCUMENTO, FECHA_EMISION, FECHA_COBRO, BANCO, N_CUOTAS, ID_PAGO) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("idssisssssis", 
                $pago->ano,
                $pago->monto,
                $pago->fechaPago,
                $pago->medioDePago,
                $pago->estado,
                $pago->tipoDocumento,
                $pago->nDocumento,
                $pago->fechaEmision,
                $pago->fechaCobro,
                $pago->banco,
                $pago->nCuotas,
                $pago->idPago
            );
            $stmt->execute();

            // Si la inserción fue exitosa, actualiza HISTORIAL_PAGOS
            if ($stmt->affected_rows > 0) {
                $updateStmt = $conn->prepare("UPDATE HISTORIAL_PAGOS SET ESTADO_PAGO = 2 WHERE ID_PAGO = ?");
                $updateStmt->bind_param("i", $pago->idPago);
                $updateStmt->execute();
            }
        }

        $conn->commit(); // Si todo salió bien, confirma los cambios
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback(); // Si algo salió mal, revierte los cambios
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
