<?php
require_once 'db.php'; // Asegúrate de que este es el camino correcto hacia tu archivo db.php

$data = json_decode(file_get_contents('php://input'), true);

if (!empty($data['pagos'])) {
    $adicionales = $data['adicionales'];
    $totalAPagar = $adicionales['montoEfectivo'] + $adicionales['montoPos'] + $adicionales['montoCheque'];
    $folioPago = obtenerUltimoFolioPago($conn);
    $numeroDeCuotas = count($data['pagos']);

    // Calcular el monto a distribuir por cada medio de pago
    $montoDistribuidoEfectivo = $adicionales['montoEfectivo'] / $numeroDeCuotas;
    $montoDistribuidoPos = $adicionales['montoPos'] / $numeroDeCuotas;
    $montoDistribuidoCheque = $adicionales['montoCheque'] / $numeroDeCuotas;

    foreach ($data['pagos'] as $pago) {
        if ($totalAPagar <= 0) {
            break; // No hay más fondos para distribuir.
        }

        // Obtener los datos del pago
        $stmtSelect = $conn->prepare("SELECT VALOR_A_PAGAR, VALOR_PAGADO FROM HISTORIAL_PAGOS WHERE ID_PAGO = ?");
        $stmtSelect->bind_param("i", $pago['idPago']);
        $stmtSelect->execute();
        $resultado = $stmtSelect->get_result();
        $filaPago = $resultado->fetch_assoc();

        $montoAPagar = min($filaPago['VALOR_A_PAGAR'] - $filaPago['VALOR_PAGADO'], $totalAPagar);
        $nuevoValorPagado = $filaPago['VALOR_PAGADO'] + $montoAPagar;

        // Actualizar VALOR_PAGADO y posiblemente ESTADO_PAGO
        $estadoPago = ($nuevoValorPagado >= $filaPago['VALOR_A_PAGAR']) ? 2 : 1; // 2 = Pagado, 1 = Pendiente
        $stmtUpdate = $conn->prepare("UPDATE HISTORIAL_PAGOS SET VALOR_PAGADO = ?, ESTADO_PAGO = ? WHERE ID_PAGO = ?");
        $stmtUpdate->bind_param("dii", $nuevoValorPagado, $estadoPago, $pago['idPago']);
        $stmtUpdate->execute();

        // Insertar los detalles de transacción para cada medio de pago
        if ($montoDistribuidoEfectivo > 0) {
            insertarDetalleTransaccion($pago, 'EFECTIVO', $montoDistribuidoEfectivo, $adicionales, $folioPago, $conn);
        }
        if ($montoDistribuidoPos > 0) {
            insertarDetalleTransaccion($pago, 'POS', $montoDistribuidoPos, $adicionales, $folioPago, $conn);
        }
        if ($montoDistribuidoCheque > 0) {
            insertarDetalleTransaccion($pago, 'CHEQUE', $montoDistribuidoCheque, $adicionales, $folioPago, $conn);
        }

        $totalAPagar -= $montoAPagar;

        // Si el total a pagar llega a 0, salir del bucle
        if ($totalAPagar <= 0) {
            break;
        }
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
    $tipoDocumento = determinarTipoDocumento($adicionales);

    actualizarHistorialPagos($pago['idPago'], $totalPagado, $conn, $tipoDocumento);
}

function determinarTipoDocumento($adicionales) {
    if ($adicionales['montoEfectivo'] > 0) {
        return 'EFECTIVO';
    }
    if ($adicionales['montoPos'] > 0) {
        return 'POS';
    }
    if ($adicionales['montoCheque'] > 0) {
        return 'CHEQUE';
    }
    return 'DESCONOCIDO';
}

function insertarDetalleTransaccion($pago, $tipoDocumento, $monto, $adicionales, $folioPago, $conn) {
    // El número de documento varía según el tipo de pago
    $numeroDocumento = null;
    if ($tipoDocumento == 'EFECTIVO') {
        // Incrementa el número de documento para EFECTIVO
        $numeroDocumento = obtenerSiguienteNumeroDocumento($conn); // Modificado para usar una función general
    } elseif ($tipoDocumento == 'POS') {
        // Para POS, utiliza el número de comprobante proporcionado
        $numeroDocumento = $adicionales['numeroComprobantePos'];
    } elseif ($tipoDocumento == 'CHEQUE') {
        // Para CHEQUE, utiliza el número de documento proporcionado
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

function actualizarHistorialPagos($idPago, $totalPagado, $conn, $tipoDocumento) {
    // Obtener VALOR_A_PAGAR y VALOR_PAGADO actuales
    $stmtSelect = $conn->prepare("SELECT VALOR_A_PAGAR, VALOR_PAGADO FROM HISTORIAL_PAGOS WHERE ID_PAGO = ?");
    $stmtSelect->bind_param("i", $idPago);
    $stmtSelect->execute();
    $resultado = $stmtSelect->get_result();
    $fila = $resultado->fetch_assoc();
    $valorAPagar = $fila['VALOR_A_PAGAR'];
    $valorPagado = $fila['VALOR_PAGADO'];

    // Sumar totalPagado a valorPagado
    $nuevoValorPagado = $valorPagado + $totalPagado;

    // Determinar estado del pago
    $estadoPago = ($nuevoValorPagado >= $valorAPagar) ? 2 : 1; // 2 = Pagado, 1 = Pendiente

    // Preparar la consulta de actualización
    $stmtUpdate = $conn->prepare("UPDATE HISTORIAL_PAGOS SET VALOR_PAGADO = ?, ESTADO_PAGO = ?, FECHA_PAGO = ? WHERE ID_PAGO = ?");
    $fechaActual = date('Y-m-d');
    $stmtUpdate->bind_param("iisi", $nuevoValorPagado, $estadoPago, $fechaActual, $idPago);
    $stmtUpdate->execute();
    $stmtUpdate->close();
}



function obtenerSiguienteNumeroDocumentoParaEfectivo($conn) {
    $resultado = $conn->query("SELECT MAX(NUMERO_DOCUMENTO) AS ultimoNumero FROM DETALLES_TRANSACCION WHERE MEDIO_DE_PAGO IN ('EFECTIVO', 'TRANSFERENCIA', 'DEPOSITO DIRECTO')");
    $fila = $resultado->fetch_assoc();
    return $fila['ultimoNumero'] + 1;
}

function obtenerSiguienteNumeroDocumento($conn) {
    // Obtiene el siguiente número de documento para cualquier tipo de pago
    $resultado = $conn->query("SELECT MAX(NUMERO_DOCUMENTO) AS ultimoNumero FROM DETALLES_TRANSACCION");
    $fila = $resultado->fetch_assoc();
    return $fila['ultimoNumero'] + 1; // Incrementa el último número de documento
}
function obtenerUltimoFolioPago($conn) {
    // Selecciona el mayor folioPago y lo incrementa en la base de datos
    $resultado = $conn->query("SELECT MAX(FOLIO_PAGO) + 1 AS siguienteFolio FROM DETALLES_TRANSACCION");
    $fila = $resultado->fetch_assoc();
    // Si no hay folios, comienza en 1, de lo contrario toma el siguiente folio
    return $fila['siguienteFolio'] ?? 1;
}

?>
