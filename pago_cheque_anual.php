<?php
require_once 'db.php'; // Asegúrate de que este es el camino correcto hacia tu archivo db.php
/* ini_set('display_errors', 1); */
$saldoPeriodoAnterior = [];
$cuotasPeriodoActual = [];
$mensaje = '';

// Consulta para obtener los nombres de los bancos
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
    hp.FECHA_VENCIMIENTO ASC"); // Ordenar por FECHA_VENCIMIENTO ascendente
    $stmt->bind_param("s", $rutAlumno);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $fechaVencimiento = new DateTime($fila['FECHA_VENCIMIENTO']);
            if ($fechaVencimiento < new DateTime($fechaActual) && $fila['ESTADO_PAGO'] == 0) {
                // Actualizar el estado a vencido (1) si la fecha de vencimiento es anterior a la fecha actual
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
        }
        $mensaje = "Datos encontrados.";
    } else {
        $mensaje = "No se encontraron datos para el RUT ingresado.";
    }
    $stmt->close();
}

if (isset($_SESSION['pagoRegistrado'])) {
    $mensaje = "Pago registrado con éxito.";
    unset($_SESSION['pagoRegistrado']); // Borrar el indicador de sesión
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
                    <h2 class="text-center">Pago de cheques anual</h2>
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
                        
                        <div class="form-group">
				<table class="table" id="datosBanco">
                <tr>
                <td>            
                <label for="bancoCheque">Seleccione el Banco</label>
                <select class="form-control selectBanco" name="bancoCheque[]">
                    <?php foreach($bancos as $nombreBanco): ?>
                        <option value="<?php echo htmlspecialchars($nombreBanco); ?>">
                            <?php echo htmlspecialchars($nombreBanco); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                </td>
         		<td>              
                <label for="nCtaCte">Ingrese N° Cuenta Corriente</label>
                <input type="number" class="form-control numeroCtaCte" name="nCtaCte[]" value="">
                </td>
                </tr>
                </table>
         		</div>


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
            <th>Monto Cuota</th>
            <th>Banco Cheque</th>
            <th>N° Documento Cheque</th>
            <th>Monto Cheque</th>
            <th>Fecha Emisión Cheque</th>
            <!-- <th>Fecha Depósito Cheque</th> -->
            <th>N° Cta Corriente</th>
            <th>Valor Pagado</th>
            <th>Estado Cuota</th>
            <th>Seleccione Valor a Pagar</th>

        </tr>
    </thead>
        <tbody>
            <?php foreach ($saldoPeriodoAnterior as $index => $pago): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($pago['FECHA_VENCIMIENTO']); ?></td>
                    <td><?php echo htmlspecialchars($pago['VALOR_A_PAGAR']); ?></td>
                    <td>
                    <select class="form-control selectBanco" name="bancoCheque[]">
                    <?php foreach($bancos as $nombreBanco): ?>
                        <option value="<?php echo htmlspecialchars($nombreBanco); ?>">
                            <?php echo htmlspecialchars($nombreBanco); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <input type="number" class="form-control" name="nDocumentoCheque[]" value="">
            </td>
            <td>
                <input type="number" class="form-control" name="montoCheque[]" value="">
            </td>
            <td>
                <input type="date" class="form-control" name="fechaEmisionCheque[]" value="">
            </td>
            <!-- <td>
                <input type="date" class="form-control" name="fechaDepositoCheque[]" value="">
            </td> -->
            <td>
                <input type="number" class="form-control numeroCtaCte" name="nCtaCteCopia[]" value="" readonly>
            </td>
            <td><?php echo htmlspecialchars($pago['VALOR_PAGADO']); ?></td> 
                    <td>
                        <?php if ($pago['ESTADO_PAGO'] == 0): ?>
                            VIGENTE
                        <?php elseif ($pago['ESTADO_PAGO'] == 1): ?>
                            VENCIDA
                        <?php elseif ($pago['ESTADO_PAGO'] == 2): ?>
                            DOCUMENTADA
                        <?php elseif ($pago['ESTADO_PAGO'] == 3): ?>
                            DOCUMENTADA
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($pago['ESTADO_PAGO'] != 2): ?>
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
            <th>Monto Cuota</th>
            <th>Banco Cheque</th>
            <th>N° Documento Cheque</th>
            <th>Monto Cheque</th>
            <th>Fecha Emisión Cheque</th>
            <!-- <th>Fecha Depósito Cheque</th> -->
            <th>N° Cta Corriente</th>
            <th>Valor Pagado</th>
            <th>Estado Cuota</th>
            <th>Seleccione Valor a Pagar</th>

        </tr>
    </thead>
        <tbody>
            <?php foreach ($cuotasPeriodoActual as $index => $pago): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($pago['FECHA_VENCIMIENTO']); ?></td>
                    <td><?php echo htmlspecialchars($pago['VALOR_A_PAGAR']); ?></td>
                    <td>
                    <select class="form-control selectBanco" name="bancoCheque[]">
                    <?php foreach($bancos as $nombreBanco): ?>
                        <option value="<?php echo htmlspecialchars($nombreBanco); ?>">
                            <?php echo htmlspecialchars($nombreBanco); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <input type="number" class="form-control" name="nDocumentoCheque[]" value="">
            </td>
            <td>
                <input type="number" class="form-control" name="montoCheque[]" value="">
            </td>
            <td>
                <input type="date" class="form-control" name="fechaEmisionCheque[]" value="">
            </td>
            <!-- <td>
                <input type="date" class="form-control" name="fechaDepositoCheque[]" value="">
            </td> -->
            <td>
                <input type="number" class="form-control numeroCtaCte" name="nCtaCteCopia[]" value="" readonly>
            </td>
            <td><?php echo htmlspecialchars($pago['VALOR_PAGADO']); ?></td> 
                    <td>
                        <?php if ($pago['ESTADO_PAGO'] == 0): ?>
                            VIGENTE
                        <?php elseif ($pago['ESTADO_PAGO'] == 1): ?>
                            VENCIDA
                        <?php elseif ($pago['ESTADO_PAGO'] == 2): ?>
                            DOCUMENTADA
                        <?php elseif ($pago['ESTADO_PAGO'] == 3): ?>
                            DOCUMENTADA
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($pago['ESTADO_PAGO'] != 2): ?>
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
                            <!-- <h6>Seleccione Medio de Pago</h6> -->
                            <div class="form-check" style="display:none;">
                                <input class="form-check-input" type="checkbox" name="metodoPago" value="efectivo" id="efectivo" >
                                <label class="form-check-label" for="efectivo">Efectivo</label>
                            </div>
                            <div class="form-check" style="display:none;">
                                <input class="form-check-input" type="checkbox" name="metodoPago" value="pagoPos" id="pagoPos" >
                                <label class="form-check-label" for="pagoPos">Pago Tarjeta POS</label>
                            </div>
                            <div class="form-check" style="display:none;">
                                <input class="form-check-input" type="checkbox" name="metodoPago" value="cheque" id="cheque">
                                <label class="form-check-label" for="cheque">Cheque</label>
                            </div>
                        </div>

                        <!-- Sección "PAGO CON EFECTIVO" -->
                        <div id="seccionEfectivo" class="mt-4" style="display:none;">
                            <h4>PAGO CON EFECTIVO</h4>
                            <div class="form-group">
                                <label for="tipoDocumento">Tipo Documento</label>
                                <input type="text" class="form-control" id="tipoDocumento" placeholder="Ingrese el tipo de documento">
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
                                <input type="text" class="form-control" id="bancoCheque" placeholder="Ingrese el banco">
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
        var checkboxes = document.querySelectorAll('.seleccionarPago');

  

        // Función para calcular el total a pagar y actualizarlo cuando cambien las selecciones
        function calcularTotalAPagar() {
            var totalAPagar = 0;
            checkboxes.forEach(function(checkbox) {
                if (checkbox.checked) {
                    totalAPagar += parseFloat(checkbox.value);
                }
            });

            // Actualizar el elemento en el HTML
            var totalAPagarElement = document.getElementById('totalAPagar');
            totalAPagarElement.textContent = 'Total a Pagar $' + totalAPagar.toFixed(0);
        }

        // Llamar a la función al cargar la página y cuando cambie una selección
        calcularTotalAPagar();
        checkboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', calcularTotalAPagar);
        });
    });

    document.getElementById('btnRegistrarPago').addEventListener('click', function() {
    var pagosSeleccionados = document.querySelectorAll('.seleccionarPago:checked');
    var pagos = [];
    var datosParaPDF = []; // Array para almacenar los datos que irán en el PDF

    pagosSeleccionados.forEach(function(checkbox, index) {
        var idPago = checkbox.getAttribute('data-id-pago');
        var fila = checkbox.closest('tr'); // Encuentra la fila del checkbox

        var banco = fila.querySelector('[name="bancoCheque[]"]').value;
        var nDocumento = fila.querySelector('[name="nDocumentoCheque[]"]').value;
        var monto = parseFloat(fila.querySelector('[name="montoCheque[]"]').value); // Obtener el valor del monto
        var fechaEmision = fila.querySelector('[name="fechaEmisionCheque[]"]').value;
        /* var fechaDeposito = fila.querySelector('[name="fechaDepositoCheque[]"]').value; */
        var numeroCtaCte = fila.querySelector('.numeroCtaCte').value; // Captura el valor del número de cuenta corriente


        var fechaCobro = new Date(fechaEmision);
        fechaCobro.setDate(fechaCobro.getDate() + 1);

        pagos.push({
            idPago: idPago,
            banco: banco,
            nDocumento: nDocumento,
            monto: monto,
            valorPagado: monto, // Agregar el monto a los datos del pago
            fechaEmision: fechaEmision,
            /* fechaDeposito: fechaDeposito, */
            fechaCobro: fechaCobro.toISOString().split('T')[0],
            ano: new Date().getFullYear(),
            fechaPago: new Date().toISOString().split('T')[0],
            medioDePago: 'CHEQUE',
            estado: 3,
            tipoDocumento: 'CHEQUE',
            nCuentaCorriente: numeroCtaCte, // Agrega el número de cuenta corriente a los datos del pago

            nCuotas: 1
        });

        // Agregar datos al array para el PDF
        datosParaPDF.push({
            'Cuota': index + 1,
            'Fecha Vencimiento': fila.cells[1].innerText,
            'Monto Cuota': fila.cells[2].innerText,
            'Banco Cheque': banco,
            'Número Documento': nDocumento,
            'Monto Cheque': monto,
            'Fecha Emisión': fechaEmision,
     /*        'Fecha Depósito': fechaDeposito */
        });
    });

    // Envía los datos al servidor mediante una solicitud AJAX
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'procesar_pago_cheques.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onload = function() {
        if (xhr.status === 200) {
            alert('Pago registrado con éxito.');
            generarPDF(datosParaPDF); // Llamar a la función para generar el PDF
        } else {
            alert('Error al registrar el pago.');
        }
    };
    xhr.send(JSON.stringify({pagos: pagos}));
});

function generarPDF(datos) {
    var doc = new jspdf.jsPDF();
    var total = datos.reduce((acc, pago) => acc + parseFloat(pago['Monto Cuota'].replace(/[^0-9.-]+/g,"")), 0); // Calcula el total

    doc.setFontSize(18);
    doc.text('Recibo de Pagos', 14, 20);
    doc.setFontSize(12);
    doc.text('Total: $' + total.toFixed(0), 14, 30);

    doc.autoTable({ 
        startY: 35,
        head: [['Cuota', 'Fecha Vencimiento', 'Monto Cuota', 'Banco Cheque', 'Número Documento', 'Monto Cheque', 'Fecha Emisión'/* , 'Fecha Depósito' */]],
        body: datos.map(pago => [pago['Cuota'], pago['Fecha Vencimiento'], pago['Monto Cuota'], pago['Banco Cheque'], pago['Número Documento'], pago['Monto Cheque'], pago['Fecha Emisión']/* , pago['Fecha Depósito' */]])
    });

    // Guardar el PDF
    doc.save('recibo_pagos_cuotas.pdf');

    // Establecer un indicador de éxito en sessionStorage y recargar la página
    sessionStorage.setItem('pagoRegistrado', 'true');
    setTimeout(function() {
        window.location.reload();
    }, 1000); // Recarga la página después de 1 segundo
}

document.addEventListener('DOMContentLoaded', function() {
    // Borrar el indicador de éxito de sessionStorage
    sessionStorage.removeItem('pagoRegistrado');
});

document.addEventListener('DOMContentLoaded', function() {
    var selects = document.querySelectorAll('.selectBanco');
    selects.forEach(function(select) {
        select.addEventListener('change', function(event) {
            var selectedValue = event.target.value;
            selects.forEach(function(otherSelect) {
                otherSelect.value = selectedValue;
            });
        });
    });
});
document.addEventListener('DOMContentLoaded', function() {
    var numeroCtaCteInputs = document.querySelectorAll('.numeroCtaCte');
    numeroCtaCteInputs.forEach(function(input) {
        input.addEventListener('change', function(event) {
            var inputValue = event.target.value;
            numeroCtaCteInputs.forEach(function(otherInput) {
                otherInput.value = inputValue;
            });
        });
    });
});

</script>

</body>
</html>