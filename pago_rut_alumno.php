<?php
require_once 'db.php'; // Asegúrate de que este es el camino correcto hacia tu archivo db.php

$saldoPeriodoAnterior = [];
$cuotasPeriodoActual = [];
$mensaje = '';

// Recolectar los montos de los diferentes medios de pago
$montoEfectivo = isset($_POST['montoEfectivo']) ? (float) $_POST['montoEfectivo'] : 0;
$montoPos = isset($_POST['montoPos']) ? (float) $_POST['montoPos'] : 0;
$montoCheque = isset($_POST['montoCheque']) ? (float) $_POST['montoCheque'] : 0;

// Recolectar los IDs de pago seleccionados
$idsPagosSeleccionados = isset($_POST['seleccionarPago']) ? $_POST['seleccionarPago'] : [];


// Calcular el monto total a pagar
$totalAPagar = $montoEfectivo + $montoPos + $montoCheque;

$bancos = [];
$stmtBancos = $conn->prepare("SELECT NOMBRE_BANCO FROM BANCOS");
$stmtBancos->execute();
$resultadoBancos = $stmtBancos->get_result();

while ($banco = $resultadoBancos->fetch_assoc()) {
    $bancos[] = $banco['NOMBRE_BANCO'];
}
$stmtBancos->close();

if (isset($_POST['btnBuscarAlumno'])) {
    $rutAlumno = $_POST['rutAlumno'];
    $fechaActual = date('Y-m-d');

    // Consulta a la base de datos
    $stmt = $conn->prepare("SELECT 
        hp.ID_PAGO,
        hp.ID_ALUMNO,
        a.RUT_ALUMNO,
        a.NOMBRE,
        a.AP_PATERNO,
        a.AP_MATERNO,
        hp.CODIGO_PRODUCTO,
        hp.FOLIO_PAGO,
        hp.VALOR_ARANCEL,
        hp.DESCUENTO_BECA,
        hp.OTROS_DESCUENTOS,
        hp.VALOR_A_PAGAR,
        hp.FECHA_PAGO,
        hp.MEDIO_PAGO,
        hp.NRO_MEDIOPAGO,
        hp.FECHA_SUSCRIPCION,
        hp.BANCO_EMISOR,
        hp.TIPO_MEDIOPAGO,
        hp.ESTADO_PAGO,
        hp.TIPO_DOCUMENTO,
        hp.NUMERO_DOCUMENTO,
        hp.FECHA_VENCIMIENTO,
        hp.FECHA_INGRESO,
        hp.FECHA_EMISION,
        hp.FECHA_COBRO,
        hp.ID_PERIODO_ESCOLAR,
        hp.CODIGO_PRODUCTO,
        hp.VALOR_PAGADO
    FROM
        c1occsyspay.HISTORIAL_PAGOS AS hp
        LEFT JOIN
        ALUMNO AS a ON a.ID_ALUMNO = hp.ID_ALUMNO
    WHERE
        a.RUT_ALUMNO = ?
    ORDER BY
        hp.FECHA_VENCIMIENTO ASC");
    $stmt->bind_param("s", $rutAlumno);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $fechaVencimiento = new DateTime($fila['FECHA_VENCIMIENTO']);
            if ($fechaVencimiento < new DateTime($fechaActual) && $fila['ESTADO_PAGO'] == 0) {
                $updateStmt = $conn->prepare("UPDATE HISTORIAL_PAGOS SET ESTADO_PAGO = 1 WHERE ID_PAGO = ?");
                $updateStmt->bind_param("i", $fila['ID_PAGO']);
                $updateStmt->execute();
                $updateStmt->close();
                $fila['ESTADO_PAGO'] = 1;
            }

            if ($fila['CODIGO_PRODUCTO'] == 2) {
                $saldoPeriodoAnterior[] = $fila;
            } elseif ($fila['CODIGO_PRODUCTO'] == 1) {
                $cuotasPeriodoActual[] = $fila;
            }
            // Imprime un script para establecer variables de JavaScript
        echo "<script type='text/javascript'>";
        echo "window.datosAlumno = {";
        echo "rut: '{$fila['RUT_ALUMNO']}',";
        echo "nombre: '{$fila['NOMBRE']}',";
        echo "apellidoPaterno: '{$fila['AP_PATERNO']}',";
        echo "apellidoMaterno: '{$fila['AP_MATERNO']}'";
        echo "};";
        echo "</script>";
        }
        $mensaje = "Datos encontrados.";
    } else {
        $mensaje = "No se encontraron datos para el RUT ingresado.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Portal de Pago</title>
    <!-- Agrega los enlaces a los estilos de Bootstrap -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>    
    
</head>
<body>
<?php if (!empty($mensaje)): ?>
    <div class="alert alert-info"><?php echo $mensaje; ?></div>
<?php endif; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h2 class="text-center">Portal de Pago</h2>
                </div>
                <div class="card-body">
                    <!-- Formulario de pago -->
                    <form method="post"> <!-- Agrega el método POST y la acción al formulario -->
                        <!-- Campo RUT del alumno -->
                        <div class="form-group">
                            <label for="rutAlumno">Rut del alumno:</label>
                            <input type="text" class="form-control" id="rutAlumno" name="rutAlumno" placeholder="Ingrese RUT del alumno" required>
                            <button type="submit" class="btn btn-primary custom-button mt-3" id="btnBuscarAlumno" name="btnBuscarAlumno">Buscar</button>
                        </div>
                        
                        <!-- Campo RUT del padre/poderado -->
                        <!-- <div class="form-group">
                            <label for="rutPadre">RUT del Padre/Apoderado</label>
                            <input type="text" class="form-control" id="rutPadre" placeholder="Ingrese el RUT del Padre/Apoderado">
                            <button type="button" class="btn btn-primary custom-button mt-3" id="btnBuscarApoderado">Buscar</button>
                        </div> -->


<!-- Tabla de pagos -->

<div id="datosAlumnos">
    <!-- Las tablas generadas se insertarán aquí -->
</div>
<div class="mt-4 table-responsive">
    <h4>Saldo Periodo Anterior</h4>
    <table class="table" id="tablaSaldoPeriodoAnterior">
        <thead>
            <tr>
                <th>N° Cuota</th>
                <th>Fecha Vencimiento</th>
                <th>Monto</th>
                <th>Valor Pagado</th>
                <th>Fecha de Pago</th>
                <th>Estado</th>
                <th>Seleccione Valor a Pagar</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($saldoPeriodoAnterior as $index => $pago): ?>
               

                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($pago['FECHA_VENCIMIENTO']); ?></td>
                    <td><?php echo htmlspecialchars($pago['VALOR_A_PAGAR']); ?></td>
                    <td><?php echo htmlspecialchars($pago['VALOR_PAGADO']); ?></td>

<!--                     <td><?php echo htmlspecialchars($pago['MEDIO_PAGO']); ?></td>
 -->                    <td><?php echo htmlspecialchars($pago['FECHA_PAGO']); ?></td>
                    <td>
                        <?php if ($pago['ESTADO_PAGO'] == 0): ?>
                            VIGENTE
                        <?php elseif ($pago['ESTADO_PAGO'] == 1): ?>
                            VENCIDA
                        <?php elseif ($pago['ESTADO_PAGO'] == 2): ?>
                            PAGADA
                        <?php elseif ($pago['ESTADO_PAGO'] == 3): ?>
                            DOCUMENTADA
                        <?php elseif ($pago['ESTADO_PAGO'] == 4): ?>
                            PAGO PARCIAL
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($pago['ESTADO_PAGO'] != 2 && $pago['ESTADO_PAGO'] != 3): ?>
                            <input type="checkbox" class="seleccionarPago" value="<?php echo htmlspecialchars($pago['VALOR_A_PAGAR']); ?>" data-id-pago="<?php echo $pago['ID_PAGO']; ?>">
                        <?php else: ?>
                            <input type="checkbox" class="seleccionarPago" value="<?php echo htmlspecialchars($pago['VALOR_A_PAGAR']); ?>" data-id-pago="<?php echo $pago['ID_PAGO']; ?>" disabled>
                        <?php endif; ?>
                    </td>
                </tr>

            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Tabla de cuotas del periodo actual -->
<div class="mt-4 table-responsive">
    <h4>Cuotas Periodo Actual</h4>
    <table class="table" id="tablaCuotasPeriodoActual">
        <thead>
            <tr>
                <th>N° Cuota</th>
                <th>Fecha Vencimiento</th>
                <th>Monto</th>
                <th>Valor Pagado</th>
                <th>Fecha de Pago</th>
                <th>Estado</th>
                <th>Seleccione Valor a Pagar</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cuotasPeriodoActual as $index => $pago): ?>

                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($pago['FECHA_VENCIMIENTO']); ?></td>
                    <td><?php echo htmlspecialchars($pago['VALOR_A_PAGAR']); ?></td>
                    <td><?php echo htmlspecialchars($pago['VALOR_PAGADO']); ?></td>
                    <!-- <td><?php echo htmlspecialchars($pago['MEDIO_PAGO']); ?></td> -->
                    <td><?php echo htmlspecialchars($pago['FECHA_PAGO']); ?></td>
                    <td>
                    <?php if ($pago['ESTADO_PAGO'] == 0): ?>
                            VIGENTE
                        <?php elseif ($pago['ESTADO_PAGO'] == 1): ?>
                            VENCIDA
                        <?php elseif ($pago['ESTADO_PAGO'] == 2): ?>
                            PAGADA
                        <?php elseif ($pago['ESTADO_PAGO'] == 3): ?>
                            DOCUMENTADA
                        <?php elseif ($pago['ESTADO_PAGO'] == 4): ?>
                            PAGO PARCIAL
                        <?php endif; ?>
                    </td>
                    <td>
                    <?php if ($pago['ESTADO_PAGO'] != 2 && $pago['ESTADO_PAGO'] != 3): ?>
                            <input type="checkbox" class="seleccionarPago" value="<?php echo htmlspecialchars($pago['VALOR_A_PAGAR']); ?>" data-id-pago="<?php echo $pago['ID_PAGO']; ?>">
                        <?php else: ?>
                            <input type="checkbox" class="seleccionarPago" value="<?php echo htmlspecialchars($pago['VALOR_A_PAGAR']); ?>" data-id-pago="<?php echo $pago['ID_PAGO']; ?>" disabled>
                        <?php endif; ?>
                    </td>
                </tr>

            <?php endforeach; ?>
        </tbody>
    </table>
</div>

                        <!-- <div>
                            <button type="button" class="btn btn-primary" id="btnSeleccionarValores">Seleccionar valores</button>
                        </div> -->

                        <!-- Sección "Total a Pagar $" -->
                        <div class="mt-4">
                            <h4 id="totalAPagar">Total a Pagar $</h4>
                            <h6>Seleccione Medio de Pago</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="metodoPago" value="efectivo" id="efectivo">
                                <label class="form-check-label" for="efectivo">Efectivo</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="metodoPago" value="pagoPos" id="pagoPos">
                                <label class="form-check-label" for="pagoPos">Pago Tarjeta POS</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="metodoPago" value="cheque" id="cheque">
                                <label class="form-check-label" for="cheque">Cheque</label>
                            </div>
                        </div>

                        <!-- Sección "PAGO CON EFECTIVO" -->
                        <div id="seccionEfectivo" class="mt-4" style="display:none;">
                            <h4>PAGO CON EFECTIVO</h4>
                            <div class="form-group">
                            <label for="tipoDocumento">Tipo Documento</label>
                                <select class="form-control" id="tipoDocumento" name="tipoDocumento">
                                    <option value="EFECTIVO">EFECTIVO</option>
                                    <option value="TRANSFERENCIA">TRANSFERENCIA</option>
                                    <option value="DEPOSITO DIRECTO">DEPOSITO DIRECTO</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="montoEfectivo">Monto</label>
                                <input type="text" class="form-control" id="montoEfectivo" placeholder="Ingrese el monto">
                            </div>
                            <div class="form-group">
                                <label for="fechaPagoEfectivo">Fecha Pago</label>
                                <input type="date" class="form-control" id="fechaPagoEfectivo">
                            </div>
                        </div>
                        <!-- Sección "PAGO CON CHEQUE" -->
                        <div id="seccionCheque" class="mt-4" style="display:none;">
                            <h4>PAGO CON CHEQUE</h4>
                            <div class="form-group">
                                <label for="tipoDocumentoCheque">Tipo Documento</label>
                                <input type="text" class="form-control" id="tipoDocumentoCheque" placeholder="Ingrese el tipo de documento">
                            </div>
                            <div class="form-group">
                                <label for="numeroDocumentoCheque">N°Documento</label>
                                <input type="text" class="form-control" id="numeroDocumentoCheque" placeholder="Ingrese el número de documento">
                            </div>
                            <div class="form-group">
                                <label for="fechaEmisionCheque">Fecha Emisión</label>
                                <input type="date" class="form-control" id="fechaEmisionCheque">
                            </div>
                            <div class="form-group">
                                <label for="bancoCheque">Banco</label>
                                <select class="form-control" id="bancoCheque" name="bancoCheque">
                                    <option value="">Seleccione un banco</option>
                                    <?php foreach ($bancos as $nombreBanco): ?>
                                        <option value="<?php echo htmlspecialchars($nombreBanco); ?>">
                                            <?php echo htmlspecialchars($nombreBanco); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="montoCheque">Monto</label>
                                <input type="text" class="form-control" id="montoCheque" placeholder="Ingrese el monto">
                            </div>
                            <div class="form-group">
                                <label for="fechaDepositoCheque">Fecha Depósito</label>
                                <input type="date" class="form-control" id="fechaDepositoCheque">
                            </div>
                        </div>
                        <!-- Sección "PAGO CON TARJETA POS" -->
                        <div id="seccionPagoPos" class="mt-4" style="display:none;">
                            <h4>PAGO CON TARJETA POS</h4>
                            <div class="form-group">
                                <label for="tipoDocumentoPos">Tipo Documento</label>
                                <input type="text" class="form-control" id="tipoDocumentoPos" placeholder="Ingrese el tipo de documento">
                            </div>
                            <div class="form-group">
                                <label for="montoPos">Monto</label>
                                <input type="text" class="form-control" id="montoPos" placeholder="Ingrese el monto">
                            </div>
                            <div class="form-group">
                                <label for="fechaPagoPos">Fecha Pago</label>
                                <input type="date" class="form-control" id="fechaPagoPos">
                            </div>
                            <div class="form-group">
                                <label for="comprobantePos">N°Comprobante o Voucher</label>
                                <input type="text" class="form-control" id="comprobantePos" placeholder="Ingrese el número de comprobante o voucher">
                            </div>
                            <div class="form-group">
                                <label for="tipoTarjetaPos">Tipo Tarjeta</label>
                                <select class="form-control" id="tipoTarjetaPos">
                                    <option value="-">Selecciona un tipo de tarjeta</option>
                                    <option value="credito">Tarjeta Crédito</option>
                                    <option value="debito">Tarjeta Débito</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="cuotasPos">Cantidad de Cuotas</label>
                                <input type="text" class="form-control" id="cuotasPos" placeholder="Ingrese la cantidad de cuotas">
                            </div>
                        </div>
                        <!-- Botón "REGISTRAR PAGO" en azul -->
<button type="button" class="btn btn-primary btn-block mt-4" id="btnRegistrarPago">REGISTRAR PAGO</button>


                    </form>
                </div>   
            </div>
        </div>
    </div>
</div>

<!-- Agrega el script de Bootstrap -->
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
<!-- ... Resto del HTML anterior ... -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
            // Obtiene los checkboxes y los divs correspondientes
            var efectivoCheckbox = document.getElementById('efectivo');
            var pagoPosCheckbox = document.getElementById('pagoPos');
            var chequeCheckbox = document.getElementById('cheque');
            var seccionEfectivo = document.getElementById('seccionEfectivo');
            var seccionPagoPos = document.getElementById('seccionPagoPos');
            var seccionCheque = document.getElementById('seccionCheque');

            // Función para actualizar la visibilidad de los divs
            function actualizarVisibilidad() {
                seccionEfectivo.style.display = efectivoCheckbox.checked ? 'block' : 'none';
                seccionPagoPos.style.display = pagoPosCheckbox.checked ? 'block' : 'none';
                seccionCheque.style.display = chequeCheckbox.checked ? 'block' : 'none';
            }

            // Event listeners para los cambios en los checkboxes
            efectivoCheckbox.addEventListener('change', actualizarVisibilidad);
            pagoPosCheckbox.addEventListener('change', actualizarVisibilidad);
            chequeCheckbox.addEventListener('change', actualizarVisibilidad);

            // Llamada inicial para establecer la visibilidad correcta al cargar la página
            actualizarVisibilidad();
        });
    document.addEventListener('DOMContentLoaded', function () {
    var checkboxes = Array.from(document.querySelectorAll('input[type="checkbox"][name="seleccionarPago[]"]'));
    var btnSeleccionarValores = document.getElementById('btnSeleccionarValores');
    var totalPagarElement = document.querySelector('.total-pagar strong');
    var resumenValoresTableBody = document.querySelector('#resumenValores tbody');
    var rutAlumnoInput = document.getElementById('rutAlumno');
    var payWithTransferButton = document.getElementById('payWithTransfer');
    var transferPaymentForm = document.getElementById('transferPaymentForm');

    // Ordenar los checkboxes por fecha de vencimiento de forma ascendente
    checkboxes.sort(function(a, b) {
        var dateA = new Date(a.dataset.fechaVencimiento), dateB = new Date(b.dataset.fechaVencimiento);
        return dateA - dateB;
    });

    checkboxes.forEach(function(checkbox, index) {
        // Deshabilitar todos los checkboxes excepto el primero
        if(index > 0) checkbox.disabled = true;

        checkbox.addEventListener('change', function(event) {
            handleCheckboxChange(event.target, index, checkboxes);
        });
    });

    function handleCheckboxChange(changedCheckbox, changedIndex, allCheckboxes) {
        // Si se desmarca una casilla, también desmarca todas las casillas posteriores
        if (!changedCheckbox.checked) {
            for (var i = changedIndex + 1; i < allCheckboxes.length; i++) {
                allCheckboxes[i].checked = false;
                allCheckboxes[i].disabled = true;
            }
        } else {
            // Si se marca una casilla, habilita la siguiente casilla
            if (changedIndex + 1 < allCheckboxes.length) {
                allCheckboxes[changedIndex + 1].disabled = false;
            }
        }
    }

    document.getElementById('btnSeleccionarValores').addEventListener('click', function() {
        var checkboxes = document.querySelectorAll('.seleccionarPago:checked');
        var totalAPagar = 0;
        checkboxes.forEach(function(checkbox) {
            totalAPagar += parseFloat(checkbox.value);
        });
        document.getElementById('totalAPagar').textContent = 'Total a Pagar $' + totalAPagar.toFixed(0);
    });

    payWithTransferButton.addEventListener('click', function() {
        // Tomar el monto total a pagar del elemento de texto
        var totalAmount = totalPagarElement.textContent.replace('Total a pagar $', '').trim();

        // Asignar el monto total al input del formulario de Khipu
        document.getElementById('transferAmountToPay').value = totalAmount;

        // Enviar el formulario de Khipu
        transferPaymentForm.submit();
    });
});
document.addEventListener('DOMContentLoaded', function() {
        var btnSeleccionarValores = document.getElementById('btnSeleccionarValores');
        var totalAPagarElement = document.getElementById('totalAPagar');

        btnSeleccionarValores.addEventListener('click', function() {
            var checkboxes = document.querySelectorAll('.seleccionarPago:checked');
            var totalAPagar = 0;
            checkboxes.forEach(function(checkbox) {
                totalAPagar += parseFloat(checkbox.value);
            });
            totalAPagarElement.textContent = 'Total a Pagar $' + totalAPagar.toFixed(0);
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
    var btnRegistrarPago = document.getElementById('btnRegistrarPago');
    var montoEfectivo = document.getElementById('montoEfectivo');
    var totalAPagarElement = document.getElementById('totalAPagar');
    var tipoDocumento = document.getElementById('tipoDocumento');
    var tipoTarjetaPosSelect = document.getElementById('tipoTarjetaPos');
    var cuotasPosInput = document.getElementById('cuotasPos');

    // Función para manejar el cambio en la selección del tipo de tarjeta
    function handleTipoTarjetaChange() {
        if (tipoTarjetaPosSelect.value === 'debito') {
            cuotasPosInput.value = '1'; // Establece el valor a 1
            cuotasPosInput.disabled = true; // Desactiva el campo
        } else {
            cuotasPosInput.disabled = false; // Reactiva el campo para otros tipos de tarjeta
        }
    }

    // Agrega el controlador de eventos para cuando cambie la selección del tipo de tarjeta
    tipoTarjetaPosSelect.addEventListener('change', handleTipoTarjetaChange);

    // Llamar a la función inicialmente en caso de que la selección por defecto sea 'debito'
    handleTipoTarjetaChange();

    btnRegistrarPago.addEventListener('click', function() {
        var montoEfectivo = parseFloat(document.getElementById('montoEfectivo').value || 0);
        var montoPos = parseFloat(document.getElementById('montoPos').value || 0);
        var montoCheque = parseFloat(document.getElementById('montoCheque').value || 0);
        var totalAPagar = parseFloat(document.getElementById('totalAPagar').textContent.replace('Total a Pagar $', ''));
        var tipoTarjetaPos = document.getElementById('tipoTarjetaPos').value;
        var cuotasPos = document.getElementById('cuotasPos').value;
        var tipoDocumentoCheque = document.getElementById('tipoDocumentoCheque').value;
        var numeroDocumentoCheque = document.getElementById('numeroDocumentoCheque').value;
        var fechaEmisionCheque = document.getElementById('fechaEmisionCheque').value;
        var bancoSeleccionado = document.getElementById('bancoCheque').value;


       /*  if (montoEfectivo + montoPos + montoCheque !== totalAPagar) {
            alert('La suma de los montos no coincide con el total a pagar.');
            return;
        } */

        var pagosSeleccionados = document.querySelectorAll('.seleccionarPago:checked');
        var datosPagos = Array.from(pagosSeleccionados).map(function(checkbox) {
            return {
                idPago: checkbox.getAttribute('data-id-pago'),
                codigoProducto: checkbox.getAttribute('data-codigo-producto'),
                folioPago: checkbox.getAttribute('data-folio-pago'),
                valor: checkbox.value,
                fechaVencimiento: checkbox.getAttribute('data-fecha-vencimiento')
            };
        });

        var datosAdicionales = {
            tipoDocumentoEfectivo: document.getElementById('tipoDocumento').value,
            tipoDocumentoPos: tipoTarjetaPos,
            cuotasPos: cuotasPos,
            tipoDocumentoCheque: tipoDocumentoCheque,
            numeroDocumentoCheque: numeroDocumentoCheque,
            numeroComprobantePos: document.getElementById('comprobantePos').value,
            fechaEmisionCheque: fechaEmisionCheque,
            montoEfectivo: montoEfectivo,
            montoPos: montoPos,
            montoCheque: montoCheque,
            fechaPago: new Date().toISOString().split('T')[0],
            bancoSeleccionado: bancoSeleccionado
        };

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'procesar_pago.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onload = function() {
            if (xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);
                alert(response.mensaje);
                window.folioPago = response.folioPago; // Almacena el folioPago para su uso posterior
                window.estadosActualizados = response.estados; // Almacena los estados actualizados
                generarPDF(montoEfectivo, montoPos, montoCheque);
            } else {
                alert('Error al registrar el pago.');
            }
        };
        xhr.send(JSON.stringify({ pagos: datosPagos, adicionales: datosAdicionales }));
    });
});

document.addEventListener('DOMContentLoaded', function() {
    inicializarCheckboxes();
});

function inicializarCheckboxes() {
    var checkboxes = document.querySelectorAll('.seleccionarPago');

    // Establece el estado inicial correcto de los checkboxes
    checkboxes.forEach(function(checkbox) {
        var estadoPago = checkbox.closest('tr').cells[5].innerText.trim();
        checkbox.disabled = estadoPago === 'PAGADA';
    });

    // Agrega los controladores de eventos
    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            manejarCambioCheckbox(checkbox, checkboxes);
            calcularTotalAPagar();
        });
    });
}

function manejarCambioCheckbox(checkboxCambiado, todosLosCheckboxes) {
    // Si un checkbox que no es 'PAGADA' se marca, desactiva todos los demás
    if (checkboxCambiado.checked) {
        todosLosCheckboxes.forEach(function(otroCheckbox) {
            if (otroCheckbox !== checkboxCambiado) {
                otroCheckbox.disabled = true;
            }
        });
    } else {
        // Si se desmarca un checkbox, reactiva todos los checkboxes hasta el primero con estado "PAGADA" o "DOCUMENTADA"
        todosLosCheckboxes.forEach(function(otroCheckbox) {
            var estadoPago = otroCheckbox.closest('tr').cells[5].innerText.trim();
            otroCheckbox.disabled = estadoPago === 'PAGADA' || estadoPago === 'DOCUMENTADA';
        });
    }
}

function calcularTotalAPagar() {
    var checkboxes = document.querySelectorAll('.seleccionarPago:checked');
    var totalAPagar = 0;
    checkboxes.forEach(function(checkbox) {
        var fila = checkbox.closest('tr');
        var valorAPagar = parseFloat(fila.cells[2].innerText); // Monto a pagar
        var valorPagado = parseFloat(fila.cells[3].innerText) || 0; // Valor ya pagado
        totalAPagar += (valorAPagar - valorPagado);
    });

    var totalAPagarElement = document.getElementById('totalAPagar');
    totalAPagarElement.textContent = 'Total a Pagar $' + totalAPagar.toFixed(0);
}


    function generarPDF(montoEfectivo, montoPos, montoCheque) {
    var doc = new jspdf.jsPDF();
    var pagosSeleccionados = document.querySelectorAll('.seleccionarPago:checked');
    var datosParaPDF = [];
    var totalAPagar = 0;

    var fechaPagoEfectivo = document.getElementById('fechaPagoEfectivo').value;
    var fechaDepositoCheque = document.getElementById('fechaDepositoCheque').value;
    var fechaPagoPos = document.getElementById('fechaPagoPos').value;
    var rutAlumno = window.datosAlumno ? window.datosAlumno.rut : 'No disponible';
    var nombreAlumno = window.datosAlumno ? window.datosAlumno.nombre : 'No disponible';
    var apellidoPaterno = window.datosAlumno ? window.datosAlumno.apellidoPaterno : '';
    var apellidoMaterno = window.datosAlumno ? window.datosAlumno.apellidoMaterno : '';

    // Si es posible, obtener el nombre y apellido del alumno desde los datos obtenidos
    // Nota: Debes ajustar este código para que coincida con la estructura de tus datos
    if (window.datosAlumno) {
        nombreAlumno = window.datosAlumno.nombre;
        apellidoAlumno = window.datosAlumno.apellido;
    }



    pagosSeleccionados.forEach(function(checkbox, index) {
        var fila = checkbox.closest('tr');
        var idPago = checkbox.getAttribute('data-id-pago');
        var estadoActualizado = window.estadosActualizados[idPago] || fila.cells[5].innerText; // Usa el estado actualizado si está disponible
        var estadoParaMostrar;

        // Convierte el estado actualizado a un string legible
        switch(estadoActualizado) {
            case 2:
                estadoParaMostrar = 'PAGADA';
                break;
            case 4:
                estadoParaMostrar = 'PAGO PARCIAL';
                break;
            default:
                estadoParaMostrar = fila.cells[5].innerText; // El texto original si no es 2 o 4
        }

        var cuota = fila.cells[0].innerText;
        var fechaVencimiento = fila.cells[1].innerText;
        var monto = parseFloat(fila.cells[2].innerText);

        // Determinar qué fecha de pago usar
        var fechaPago;
        if (montoEfectivo > 0) fechaPago = fechaPagoEfectivo;
        else if (montoCheque > 0) fechaPago = fechaDepositoCheque;
        else if (montoPos > 0) fechaPago = fechaPagoPos;
        else fechaPago = 'N/A'; // En caso de que no se haya seleccionado un método de pago

        var estado = fila.cells[5].innerText; // Añadir estado del pago

        totalAPagar += monto;

        // Agregar datos adicionales al array
        datosParaPDF.push([cuota, fechaVencimiento, monto.toFixed(0), fechaPago, estadoParaMostrar]);
    });

    doc.setFontSize(18);
    doc.text('Recibo de Pagos', 14, 20);
    doc.setFontSize(12);

    var yPos = 30; // Posición inicial para el texto en el eje Y

    // Añadir información del alumno al PDF
    doc.text('RUT Alumno: ' + rutAlumno, 14, yPos);
    yPos += 10;
    doc.text('Nombre Alumno: ' + nombreAlumno + ' ' + apellidoPaterno + ' ' + apellidoMaterno, 14, yPos);
    yPos += 10;
    doc.text('Número de Folio: ' + window.folioPago, 14, yPos);

    yPos += 10; // Incrementa la posición Y para la siguiente línea de texto
    doc.text('Monto cancelado de la cuota en efectivo: $' + montoEfectivo.toFixed(0), 14, yPos);
    yPos += 10; // Incrementa la posición Y para la siguiente línea de texto
    doc.text('Monto cancelado de la cuota con POS: $' + montoPos.toFixed(0), 14, yPos);
    yPos += 10; // Incrementa la posición Y para la siguiente línea de texto
    doc.text('Monto cancelado de la cuota con Cheque: $' + montoCheque.toFixed(0), 14, yPos);
    yPos += 10; // Incrementa la posición Y para la siguiente línea de texto
    doc.text('Monto total cancelado: $' + (montoEfectivo + montoPos + montoCheque).toFixed(0), 14, yPos);

    yPos += 10; // Incrementa la posición Y para la tabla
    doc.autoTable({
        startY: yPos,
        head: [['Cuota', 'Fecha Vencimiento', 'Monto', 'Fecha de Pago', 'Estado']],
        body: datosParaPDF
    });

    doc.save('recibo_pagos.pdf');
    // Suponiendo que se desea recargar la página después de guardar el PDF
    setTimeout(function() {
        window.location.reload();
    }, 1000); // Recarga después de 1 segundo
}


document.addEventListener('DOMContentLoaded', function() {
    var checkboxes = Array.from(document.querySelectorAll('.seleccionarPago'));
    var lastCheckedIndex = -1; // Comienza con -1 para indicar que no se ha seleccionado ninguna cuota aún

    // Deshabilita todos los checkboxes por defecto
    checkboxes.forEach(function(checkbox) {
        checkbox.disabled = true;
    });

    // Habilita los checkboxes desde la cuota más atrasada hasta la primera cuota no pagada completamente
    for (var i = 0; i < checkboxes.length; i++) {
        var estadoPago = checkboxes[i].closest('tr').cells[5].innerText.trim();
        if (estadoPago !== 'PAGADA' && estadoPago !== 'DOCUMENTADA') {
            checkboxes[i].disabled = false;
            lastCheckedIndex = i; // Actualiza el índice del último checkbox habilitado
            break; // Detiene el bucle después de habilitar el primer checkbox permitido
        }
    }

    // Evento para manejar los cambios en los checkboxes
    checkboxes.forEach(function(checkbox, index) {
        checkbox.addEventListener('change', function() {
            if (checkbox.checked) {
                lastCheckedIndex = index; // Actualiza el índice del último checkbox marcado
                // Habilita el siguiente checkbox si no está pagado completamente
                if (index + 1 < checkboxes.length) {
                    var nextCheckboxState = checkboxes[index + 1].closest('tr').cells[5].innerText.trim();
                    if (nextCheckboxState !== 'PAGADA' && nextCheckboxState !== 'DOCUMENTADA') {
                        checkboxes[index + 1].disabled = false;
                    }
                }
            } else {
                // Si se desmarca un checkbox, deshabilita todos los siguientes
                for (var i = index + 1; i < checkboxes.length; i++) {
                    checkboxes[i].checked = false;
                    checkboxes[i].disabled = true;
                }
                lastCheckedIndex = index - 1; // Actualiza el índice para el último checkbox marcado
            }
        });
    });
});


</script>

</body>
</html>