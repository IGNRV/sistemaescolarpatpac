<?php
require_once 'db.php'; // Asegúrate de que este es el camino correcto hacia tu archivo db.php

$data = json_decode(file_get_contents('php://input'), true);

if (!empty($data['pagos'])) {
    $adicionales = $data['adicionales'];
    $folioPago = obtenerUltimoFolioPago($conn);

    foreach ($data['pagos'] as $pago) {
        procesarPago($pago, $adicionales, $folioPago, $conn);
    }
    echo json_encode(['mensaje' => 'Pago registrado con éxito.', 'folioPago' => $folioPago]);
} else {
    echo json_encode(['mensaje' => 'No hay pagos para procesar.']);
}

function procesarPago($pago, $adicionales, $folioPago, $conn) {
    $folioPago = obtenerUltimoFolioPago($conn);
    $totalPagado = $adicionales['montoEfectivo'] + $adicionales['montoPos'] + $adicionales['montoCheque'];


    if ($adicionales['montoEfectivo'] > 0) {
        insertarDetalleTransaccion($pago, 'EFECTIVO', $adicionales['montoEfectivo'], $adicionales, $folioPago, $conn);
    }
    if ($adicionales['montoPos'] > 0) {
        insertarDetalleTransaccion($pago, 'POS', $adicionales['montoPos'], $adicionales, $folioPago, $conn);
    }
    if ($adicionales['montoCheque'] > 0) {
        insertarDetalleTransaccion($pago, 'CHEQUE', $adicionales['montoCheque'], $adicionales, $folioPago, $conn);
    }
    actualizarHistorialPagos($pago['idPago'], $totalPagado, $conn);
}

function insertarDetalleTransaccion($pago, $tipoDocumento, $monto, $adicionales, $folioPago, $conn) {
    // El número de documento varía según el tipo de pago
    $numeroDocumento = null;
    if ($tipoDocumento == 'EFECTIVO') {
        $numeroDocumento = obtenerSiguienteNumeroDocumentoParaEfectivo($conn);
        $tipoDocumento = $adicionales['tipoDocumentoEfectivo']; // Aquí usas la selección del formulario
    } elseif ($tipoDocumento == 'POS') {
        $numeroDocumento = $adicionales['numeroComprobantePos'];
    } else { // CHEQUE
        $numeroDocumento = $adicionales['numeroDocumentoCheque'];
    }

    // Asignar medio de pago y número de cuotas si es POS
    $medioPago = ($tipoDocumento == 'POS') ? $adicionales['tipoDocumentoPos'] : $tipoDocumento;
    $nCuotas = ($tipoDocumento == 'POS' && !empty($adicionales['cuotasPos'])) ? $adicionales['cuotasPos'] : 0;

    $stmt = $conn->prepare("INSERT INTO DETALLES_TRANSACCION (ANO, CODIGO_PRODUCTO, FOLIO_PAGO, VALOR, FECHA_PAGO, MEDIO_DE_PAGO, N_CUOTAS, ESTADO, FECHA_VENCIMIENTO, TIPO_DOCUMENTO, NUMERO_DOCUMENTO, FECHA_EMISION, FECHA_COBRO, BANCO, ID_PAGO) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sissssisssssssi", $ano, $codigoProducto, $folioPago, $valor, $fechaPago, $medioPago, $nCuotas, $estado, $fechaVencimiento, $tipoDocumento, $numeroDocumento, $fechaEmision, $fechaCobro, $banco, $idPago);

    // Asignaciones comunes
    $ano = date('Y');
    $codigoProducto = $pago['codigoProducto'];
    $valor = $monto;
    $fechaPago = $adicionales['fechaPago'];
    $estado = 1;
    $fechaVencimiento = $pago['fechaVencimiento'];
    $fechaEmision = date('Y-m-d');
    $fechaCobro = date('Y-m-d');
    $idPago = $pago['idPago'];

    if ($tipoDocumento == 'CHEQUE') {
        $fechaEmisionCheque = $adicionales['fechaEmisionCheque'];

        // Convertir la fecha de emisión a DateTime
        $fechaEmisionDateTime = new DateTime($fechaEmisionCheque);

        // Agregar un día a la fecha de emisión
        $fechaEmisionDateTime->modify('+1 day');

        // Actualizar $fechaEmision y $fechaCobro para reflejar la fecha de emisión del cheque y la fecha de cobro
        $fechaEmision = $fechaEmisionCheque;
        $fechaCobro = $fechaEmisionDateTime->format('Y-m-d');
        $banco = $adicionales['bancoSeleccionado']; // Añadir esta línea
        
    }

    $stmt->execute();
    $stmt->close();
}

function actualizarHistorialPagos($idPago, $totalPagado, $conn) {
    // Primero, obtenemos el valor actual a pagar
    $stmtSelect = $conn->prepare("SELECT VALOR_A_PAGAR FROM HISTORIAL_PAGOS WHERE ID_PAGO = ?");
    $stmtSelect->bind_param("i", $idPago);
    $stmtSelect->execute();
    $resultado = $stmtSelect->get_result();
    $fila = $resultado->fetch_assoc();
    $valorAPagar = $fila['VALOR_A_PAGAR'];

    // Restamos el total pagado del valor a pagar
    $nuevoValorAPagar = $valorAPagar - $totalPagado;

    // Preparamos la consulta de actualización
    $stmtUpdate = $conn->prepare("UPDATE HISTORIAL_PAGOS SET VALOR_A_PAGAR = ?, ESTADO_PAGO = ?, FECHA_PAGO = ? WHERE ID_PAGO = ?");

    // Determinamos el estado de pago
    $estadoPago = ($nuevoValorAPagar == 0) ? 2 : 1; // Aquí puedes ajustar la lógica según necesites

    $fechaActual = date('Y-m-d');
    $stmtUpdate->bind_param("iisi", $nuevoValorAPagar, $estadoPago, $fechaActual, $idPago);
    $stmtUpdate->execute();
    $stmtUpdate->close();
}


function obtenerSiguienteNumeroDocumentoParaEfectivo($conn) {
    $resultado = $conn->query("SELECT MAX(NUMERO_DOCUMENTO) AS ultimoNumero FROM DETALLES_TRANSACCION WHERE MEDIO_DE_PAGO IN ('EFECTIVO', 'TRANSFERENCIA', 'DEPOSITO DIRECTO')");
    $fila = $resultado->fetch_assoc();
    return $fila['ultimoNumero'] + 1;
}

function obtenerSiguienteNumeroDocumento($conn) {
    $resultado = $conn->query("SELECT MAX(NUMERO_DOCUMENTO) AS ultimoNumero FROM DETALLES_TRANSACCION");
    $fila = $resultado->fetch_assoc();
    return $fila['ultimoNumero'] + 1;
}
function obtenerUltimoFolioPago($conn) {
    // Selecciona el mayor folioPago y lo incrementa en la base de datos
    $resultado = $conn->query("SELECT MAX(FOLIO_PAGO) + 1 AS siguienteFolio FROM DETALLES_TRANSACCION");
    $fila = $resultado->fetch_assoc();
    // Si no hay folios, comienza en 1, de lo contrario toma el siguiente folio
    return $fila['siguienteFolio'] ?? 1;
}

?>
