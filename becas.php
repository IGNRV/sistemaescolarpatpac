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

$periodos = [];
$stmtPeriodos = $conn->prepare("SELECT ID, PERIODO FROM PERIODO_ESCOLAR");
$stmtPeriodos->execute();
$resultadoPeriodos = $stmtPeriodos->get_result();

while ($fila = $resultadoPeriodos->fetch_assoc()) {
    $periodos[] = $fila; // Guarda cada fila (que es un array) en el array $periodos
}
$stmtPeriodos->close();



if (isset($_POST['btnBuscarAlumno'])) {
    $rutAlumno = $_POST['rutAlumno'];
    $fechaActual = date('Y-m-d');

    // Consulta a la base de datos
    $stmt = $conn->prepare("SELECT 
    hp.ID_PAGO,
    hp.ID_ALUMNO,
    a.RUT_ALUMNO,
    a.CURSO,
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
    hp.ID_PERIODO_ESCOLAR
FROM
    c1occsyspay.HISTORIAL_PAGOS AS hp
        LEFT JOIN
    ALUMNO AS a ON a.ID_ALUMNO = hp.ID_ALUMNO
WHERE
    a.RUT_ALUMNO = ?
    AND hp.CODIGO_PRODUCTO = 1
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
                $updateStmt = $conn->prepare("UPDATE HISTORIAL_PAGOS SET DESCUENTO_BECA = ?, OTROS_DESCUENTOS = ? , VALOR_A_PAGAR = ? WHERE ID_PAGO = ?");
                $updateStmt->bind_param("i", $fila['DESCUENTO_BECA'], $fila['OTROS_DESCUENTOS'], $fila['VALOR_A_PAGAR'], $fila['ID_PAGO']);
                $updateStmt->execute();
                $updateStmt->close();
                $fila['ESTADO_PAGO'] = 1;
            }
            
            if ($fechaVencimiento->format('Y') < date('Y')) {
                // Agregar a saldo del período anterior
                $saldoPeriodoAnterior[] = $fila;
            } else {
                // Agregar a cuotas del período actual
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
                    <h2 class="text-center">Mantenedor de BECAS</h2>
                </div>
                <div class="card-body">
                    <!-- Formulario de pago -->
                    <form method="post"> 
                    <input type="hidden" id="rutAlumnoBuscado" name="rutAlumnoBuscado" value="<?php echo isset($rutAlumno) ? htmlspecialchars($rutAlumno) : ''; ?>">

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

<div>
Descuento de la beca en pesos

<input type="text" id="campoTextoDescuento" name="descuento" value="">
</div>

Periodo Escolar
<select class="form-control" name="periodos[]">
    <?php foreach($periodos as $periodo): ?> <!-- Usa $periodo, no $Periodos -->
        <option value="<?php echo htmlspecialchars($periodo['ID']); ?>"> <!-- Muestra el ID del periodo -->
            <?php echo htmlspecialchars($periodo['PERIODO']); ?> <!-- Muestra el nombre del periodo -->
        </option>
    <?php endforeach; ?>
</select>
                Seleccione el porcentaje de beca asignada            
                <select class="form-control" name="descuento">
                    	<option value="Seleccione Beca">Seleccione Beca</option>
                        <option value="0">0%</option>
                     	<option value="5">5%</option>
                    	<option value="10">10%</option>
                     	<option value="15">15%</option>
                     	<option value="20">20%</option>
                     	<option value="25">25%</option>
                    	<option value="30">30%</option>
                    	<option value="35">35%</option>
                     	<option value="40">40%</option>
                    	<option value="45">45%</option>
                     	<option value="50">50%</option>
                        <option value="55">55%</option>
                        <option value="60">60%</option>
                        <option value="65">65%</option>
                        <option value="70">70%</option>
                        <option value="75">75%</option>
                        <option value="80">80%</option>
                        <option value="85">85%</option>
                        <option value="90">90%</option>
                        <option value="95">95%</option>
                        <option value="100">100%</option>
                </select>
      

<!-- Tabla de cuotas del periodo actual -->
<div class="mt-4 table-responsive">
    <h4>Cuotas Periodo Actual</h4>
    <table class="table" id="tablaCuotasPeriodoActual">
    <thead>
        <tr>
            <th>N° Cuota</th>
            <th>Fecha Vencimiento</th>
            <th>Valor Arancel</th>
            <th>Descuento Beca</th>
            <th>Otros Descuentos</th>
            <th>Valor a Pagar</th>
            <!-- <th>Estado Cuota</th>
            <th>Seleccione Valor a Pagar</th> -->

        </tr>
    </thead>
        <tbody>
        <?php foreach ($cuotasPeriodoActual as $index => $pago): ?>
            <tr id="fila-<?php echo $pago['ID_PAGO']; ?>">
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($pago['FECHA_VENCIMIENTO']); ?></td>
                    <td><?php echo htmlspecialchars($pago['VALOR_A_PAGAR']); ?></td>
 
            <td>
                <input type="number" class="form-control" name="DescuentoBeca[]" value="<?php echo htmlspecialchars($pago['VALOR_A_PAGAR']); ?>" disabled>
            </td>
            <td>
                <input type="number" class="form-control" name="OtrosDescuentos[]" value="" disabled>
            </td>
            <td>
                <input type="number" class="form-control" name="ValoraPagar[]" value="" disabled>
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
                        <button type="button" class="btn btn-primary btn-block mt-4" id="btnRegistrarBeca" onclick="registrarBeca()">REGISTRAR BECA</button>


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



document.addEventListener('DOMContentLoaded', function() {
    // Borrar el indicador de éxito de sessionStorage
    sessionStorage.removeItem('pagoRegistrado');
});

document.addEventListener('DOMContentLoaded', function() {
    // Función para actualizar los cálculos de pago
    function actualizarCalculos() {
        var filasCuotas = document.querySelectorAll('#tablaCuotasPeriodoActual tbody tr');

        filasCuotas.forEach(function(fila) {
            var valorArancel = parseFloat(fila.querySelector('td:nth-child(3)').textContent);
            var descuentoBeca = parseFloat(fila.querySelector('input[name="DescuentoBeca[]"]').value) || 0;
            var otrosDescuentosInput = fila.querySelector('input[name="OtrosDescuentos[]"]');
            var otrosDescuentos = parseFloat(otrosDescuentosInput.value) || parseFloat(document.getElementById('campoTextoDescuento').value) || 0; // Incluye el valor del campoTextoDescuento
            var valorAPagarInput = fila.querySelector('input[name="ValoraPagar[]"]');

            var valorAPagar = valorArancel - descuentoBeca - otrosDescuentos;
            valorAPagarInput.value = valorAPagar.toFixed(0);
        });
    }

    // Event listener para cambios en el campo de texto de descuento
    document.getElementById('campoTextoDescuento').addEventListener('input', function() {
        actualizarOtrosDescuentos();
        actualizarCalculos(); // Actualiza los cálculos cuando se introduce un descuento en pesos
    });


    // Define la función para actualizar los descuentos de beca
    function actualizarDescuentos() {
        var descuentoSelect = document.querySelector('select[name="descuento"]');
        var descuentoPorcentaje = parseFloat(descuentoSelect.value) || 0;
        var filasCuotas = document.querySelectorAll('#tablaCuotasPeriodoActual tbody tr');

        filasCuotas.forEach(function(fila) {
            var celdaValorArancel = fila.querySelector('td:nth-child(3)');
            var valorArancel = parseFloat(celdaValorArancel.textContent);
            var inputDescuentoBeca = fila.querySelector('input[name="DescuentoBeca[]"]');
            var descuentoCalculado = (descuentoPorcentaje / 100) * valorArancel;
            inputDescuentoBeca.value = descuentoCalculado.toFixed(0);
        });
    }

    // Selecciona el campo de texto y los campos de otros descuentos
    var campoTextoDescuento = document.getElementById('campoTextoDescuento');
    var camposOtrosDescuentos = document.querySelectorAll('input[name="OtrosDescuentos[]"]');

    // Función para actualizar los campos de otros descuentos
    function actualizarOtrosDescuentos() {
        var valorDescuento = parseFloat(campoTextoDescuento.value) || 0;

        camposOtrosDescuentos.forEach(function(input) {
            input.value = valorDescuento.toFixed(0);
        });
    }

    // Agrega event listeners a los campos relevantes
    document.querySelector('select[name="descuento"]').addEventListener('change', function() {
        actualizarDescuentos();
        actualizarCalculos(); // Actualiza los cálculos cuando cambia el descuento
    });

    document.querySelectorAll('#tablaCuotasPeriodoActual input[name="OtrosDescuentos[]"]').forEach(function(input) {
        input.addEventListener('input', actualizarCalculos); // Actualiza los cálculos cuando cambian los otros descuentos
    });

    // Event listener para cambios en el campo de texto de descuento
    campoTextoDescuento.addEventListener('input', actualizarOtrosDescuentos);

    // Inicializa los cálculos al cargar la página
    actualizarDescuentos();
    actualizarCalculos();
});

function registrarBeca() {
    var filas = document.querySelectorAll('#tablaCuotasPeriodoActual tbody tr');
    var rutAlumnoBuscado = document.getElementById('rutAlumnoBuscado').value;
    var descuentoSeleccionado = document.querySelector('select[name="descuento"]').value;
    var periodoEscolarSeleccionado = document.querySelector('select[name="periodos[]"]').value;

    // Contador para las solicitudes AJAX
    var ajaxRequestsCompleted = 0;

    filas.forEach(function(fila, index, array) {
        var idPago = fila.id.split('-')[1];
        var descuentoBeca = fila.querySelector('input[name="DescuentoBeca[]"]').value;
        var otrosDescuentos = fila.querySelector('input[name="OtrosDescuentos[]"]').value;
        var valorAPagar = fila.querySelector('input[name="ValoraPagar[]"]').value;

        var formData = 'idPago=' + idPago +
            '&descuentoBeca=' + descuentoBeca +
            '&otrosDescuentos=' + otrosDescuentos +
            '&valorAPagar=' + valorAPagar +
            '&rutAlumnoBuscado=' + encodeURIComponent(rutAlumnoBuscado) +
            '&descuentoSeleccionado=' + descuentoSeleccionado +
            '&periodoEscolarSeleccionado=' + periodoEscolarSeleccionado;

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'actualizar_beca.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                ajaxRequestsCompleted++;
                if (xhr.status === 200) {
                    if (ajaxRequestsCompleted === array.length) {
                        // Si todas las solicitudes fueron exitosas, muestra un solo mensaje y recarga la página
                        alert('Todas las becas han sido registradas con éxito.');
                        window.location.reload(); // Recarga la página
                    }
                } else {
                    // Si alguna solicitud falla, muestra un mensaje de error
                    alert('Error al registrar una de las becas. Por favor, revisa los datos e inténtalo de nuevo.');
                }
            }
        };
        xhr.send(formData);
    });
}




</script>

</body>
</html>