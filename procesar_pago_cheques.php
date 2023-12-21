<?php
require_once 'db.php'; 
/* ini_set('display_errors', 1); */

session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json);

    $conn->begin_transaction(); // Iniciar una transacción

    try {
        $stmtFolio = $conn->prepare("SELECT FOLIO_PAGO FROM DETALLES_TRANSACCION ORDER BY FOLIO_PAGO DESC LIMIT 1");
        $stmtFolio->execute();
        $resultFolio = $stmtFolio->get_result();
        $ultimoFolio = $resultFolio->fetch_assoc();
        $folioActual = isset($ultimoFolio['FOLIO_PAGO']) ? $ultimoFolio['FOLIO_PAGO'] : 0;
        $stmtFolio->close();

        foreach ($data->pagos as $pago) {
            $folioActual++;
            $fechaEmision = new DateTime($pago->fechaEmision);
            $fechaCobro = $fechaEmision->modify('+1 day')->format('Y-m-d');
            $stmt = $conn->prepare("INSERT INTO DETALLES_TRANSACCION 
            (FOLIO_PAGO, ANO, VALOR, FECHA_PAGO, MEDIO_DE_PAGO, ESTADO, TIPO_DOCUMENTO, NUMERO_DOCUMENTO, FECHA_EMISION, FECHA_COBRO, BANCO, N_CUOTAS, ID_PAGO, CTA_CORRIENTE) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iidssisssssisi", 
                $folioActual,
                $pago->ano,
                $pago->monto,
                $pago->fechaPago,
                $pago->medioDePago,
                $pago->estado,
                $pago->tipoDocumento,
                $pago->nDocumento,
                $pago->fechaEmision,
                $fechaCobro,
                $pago->banco,
                $pago->nCuotas,
                $pago->idPago,
                $pago->nCuentaCorriente
            );
            $stmt->execute();

            $query = $conn->prepare("SELECT VALOR_PAGADO FROM HISTORIAL_PAGOS WHERE ID_PAGO = ?");
            $query->bind_param("i", $pago->idPago);
            $query->execute();
            $resultado = $query->get_result();
            $valorActual = $resultado->fetch_assoc()['VALOR_PAGADO'] ?? 0;
            $query->close();

            $nuevoValor = $valorActual + $pago->monto;

            $updateStmt = $conn->prepare("UPDATE HISTORIAL_PAGOS SET VALOR_PAGADO = ? WHERE ID_PAGO = ?");
            $updateStmt->bind_param("di", $nuevoValor, $pago->idPago);
            $updateStmt->execute();
            $updateStmt->close();

            $queryValorAPagar = $conn->prepare("SELECT VALOR_A_PAGAR FROM HISTORIAL_PAGOS WHERE ID_PAGO = ?");
            $queryValorAPagar->bind_param("i", $pago->idPago);
            $queryValorAPagar->execute();
            $resultadoValorAPagar = $queryValorAPagar->get_result();
            $valorAPagar = $resultadoValorAPagar->fetch_assoc()['VALOR_A_PAGAR'] ?? 0;
            $queryValorAPagar->close();

            $estadoPago = ($nuevoValor >= $valorAPagar) ? 2 : 3;

            $updateEstadoPagoStmt = $conn->prepare("UPDATE HISTORIAL_PAGOS SET ESTADO_PAGO = ? WHERE ID_PAGO = ?");
            $updateEstadoPagoStmt->bind_param("ii", $estadoPago, $pago->idPago);
            $updateEstadoPagoStmt->execute();
            $updateEstadoPagoStmt->close();
        }

        // Obtener el ID del usuario actual
        $EMAIL = $_SESSION['EMAIL'];
        $queryUsuario = "SELECT ID FROM USERS WHERE EMAIL = ?";
        $stmtUsuario = $conn->prepare($queryUsuario);
        $stmtUsuario->bind_param("s", $EMAIL);
        $stmtUsuario->execute();
        $resultadoUsuario = $stmtUsuario->get_result();
        $idUsuario = null;
        if ($filaUsuario = $resultadoUsuario->fetch_assoc()) {
            $idUsuario = $filaUsuario['ID'];
        }
        $stmtUsuario->close();

        if (!$idUsuario) {
            throw new Exception("No se pudo obtener el ID del usuario.");
        }

        // Insertar en la tabla HISTORIAL_CAMBIOS
        foreach ($data->pagos as $pago) {
            $tipoCambio = "REGISTRO DE PAGO CON CHEQUE ANUAL";
            $stmtHistorial = $conn->prepare("INSERT INTO HISTORIAL_CAMBIOS (ID_USUARIO, TIPO_CAMBIO, ID_ALUMNO) VALUES (?, ?, ?)");
            $stmtHistorial->bind_param("isi", $idUsuario, $tipoCambio, $pago->idAlumno);
            $stmtHistorial->execute();

            if ($stmtHistorial->affected_rows == 0) {
                throw new Exception("Error al insertar en HISTORIAL_CAMBIOS.");
            }
            $stmtHistorial->close();
        }

        $conn->commit(); // Si todo salió bien, confirma los cambios
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback(); // Si algo salió mal, revierte los cambios
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
