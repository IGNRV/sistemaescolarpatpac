<?php
require_once 'db.php'; // Asegúrate de incluir tu archivo de conexión a la base de datos aquí

$pagosPendientes = [];
$pagosActuales = [];
$mensaje = '';

if (isset($_POST['buscarAlumno'])) {
    $rutAlumno = $_POST['rutAlumno'];
    $anioActual = date("Y");

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
                                hp.ID_PERIODO_ESCOLAR
                            FROM
                                c1occsyspay.HISTORIAL_PAGOS AS hp
                                    LEFT JOIN
                                ALUMNO AS a ON a.ID_ALUMNO = hp.ID_ALUMNO
                            WHERE
                                a.RUT_ALUMNO = ?");
    $stmt->bind_param("s", $rutAlumno);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        while ($pago = $resultado->fetch_assoc()) {
            $anioVencimiento = substr($pago['FECHA_VENCIMIENTO'], 0, 4);
            if ($anioVencimiento < $anioActual) {
                $pagosPendientes[] = $pago;
            } else {
                $pagosActuales[] = $pago;
            }
        }
        $mensaje = "Datos encontrados.";
    } else {
        $mensaje = "No se encontraron datos para el RUT ingresado.";
    }
    $stmt->close();
}
?>


<head>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">

    <style>
        .pago-electronico {
    background-color: #f8f9fa; /* Un fondo claro */
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.pago-electronico h2, .pago-electronico h3 {
    color: #333; /* Un color oscuro para los títulos */
}

.pago-electronico .btn-primary {
    background-color: #007bff; /* Color principal de Bootstrap */
    border-color: #007bff;
}

.pago-electronico .table-striped {
    background-color: #fff; /* Fondo blanco para las tablas */
}

    </style>
</head>
<div class="pago-electronico container my-4">
    <h2 class="mb-4">Pago electrónico de cuotas</h2>
    <form method="post">
    <div class="form-group">
        <label for="rutAlumno">Rut del alumno:</label>
        <!-- Asegúrate de que el valor se mantenga después de enviar el formulario -->
        <input type="text" class="form-control" id="rutAlumno" name="rutAlumno" placeholder="Ingrese RUT del alumno" value="<?php echo isset($rutAlumno) ? htmlspecialchars($rutAlumno) : ''; ?>">
        <button type="submit" class="btn btn-primary mt-3" name="buscarAlumno">Buscar</button>
    </div>
</form>


    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-info"><?php echo $mensaje; ?></div>
    <?php endif; ?>
    
    <div id="resultadoBusqueda" class="my-3"></div>

    <h3 class="mt-4">Valores pendientes de año anterior</h3>
<div id="tablaValoresPendientes" class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>N° Cuota</th>
                <th>Fecha Vencimiento</th>
                <th>Monto</th>
                <th>Medio de Pago</th>
                <th>Fecha de Pago</th>
                <th>Estado</th>
                <th>Seleccione Valor a Pagar</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pagosPendientes as $index => $pago): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo $pago['FECHA_VENCIMIENTO']; ?></td>
                    <td><?php echo $pago['VALOR_A_PAGAR']; ?></td>
                    <td><?php echo $pago['MEDIO_PAGO']; ?></td>
                    <td><?php echo $pago['FECHA_PAGO']; ?></td>
                    <td><?php 
                        if ($pago['ESTADO_PAGO'] == 2) {
                            echo 'PAGADA';
                        } elseif ($pago['ESTADO_PAGO'] == 1) {
                            echo 'VENCIDA';
                        } elseif ($pago['ESTADO_PAGO'] == 0) {
                            echo 'VIGENTE';
                        } else {
                            echo 'DESCONOCIDO';
                        }
                        ?></td>
                    <td>
                        <input type="checkbox" name="seleccionarPago[]" value="<?php echo $pago['ID_PAGO']; ?>" <?php echo $pago['ESTADO_PAGO'] == 2 ? 'disabled' : ''; ?>>
                    </td>

                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

    <h3 class="mt-4">Plan de pago año en curso</h3>
<div id="tablaPlanPagoActual" class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>N° Cuota</th>
                <th>Fecha Vencimiento</th>
                <th>Monto</th>
                <th>Medio de Pago</th>
                <th>Fecha de Pago</th>
                <th>Estado</th>
                <th>Seleccione Valor a Pagar</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pagosActuales as $index => $pago): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo $pago['FECHA_VENCIMIENTO']; ?></td>
                    <td><?php echo $pago['VALOR_A_PAGAR']; ?></td>
                    <td><?php echo $pago['MEDIO_PAGO']; ?></td>
                    <td><?php echo $pago['FECHA_PAGO']; ?></td>
                    <td><?php 
                        if ($pago['ESTADO_PAGO'] == 2) {
                            echo 'PAGADA';
                        } elseif ($pago['ESTADO_PAGO'] == 1) {
                            echo 'VENCIDA';
                        } elseif ($pago['ESTADO_PAGO'] == 0) {
                            echo 'VIGENTE';
                        } else {
                            echo 'DESCONOCIDO';
                        }
                        ?></td>
                    <td>
                        <input type="checkbox" name="seleccionarPago[]" value="<?php echo $pago['ID_PAGO']; ?>" <?php echo $pago['ESTADO_PAGO'] == 2 ? 'disabled' : ''; ?>>
                    </td>

                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

    <button class="btn btn-primary mt-3" id="btnSeleccionarValores">Seleccionar valores</button>


    <h3 class="mt-4">Resumen de valores a pagar</h3>
<div class="table-responsive">
    <table class="table table-striped" id="resumenValores">
        <thead>
            <tr>
                <th>N° Cupón</th>
                <th>Fecha</th>
                <th>RUT alumno</th>
                <th>Monto</th>
                <th>Eliminar</th>
            </tr>
        </thead>
        <tbody>
            <!-- Los datos del resumen de pagos se insertarán aquí -->
        </tbody>
    </table>
</div>

    <div class="total-pagar mt-3">
        <strong>Total a pagar $</strong>
    </div>

    <div class="metodos-pago mt-3">
        <button class="btn btn-success custom-button" id="payWithCard" style="display: none;">Pagar con tarjeta CR/DB</button>
        <button class="btn btn-info custom-button" id="payWithTransfer">Pagar con transferencia</button>
        <button class="btn btn-secondary custom-button">Agregar otro alumno</button>
    </div>
</div>
<form id="paymentForm" method="post" action="process_payment.php" style="display: none;">
    <input type="text" name="customer_name" id="customerName" required />
    <input type="text" name="customer_email" id="customerEmail" required />
    <input type="number" name="amount" id="amountToPay" required />
</form>

<form id="transferPaymentForm" method="post" action="khipu/index.php" style="display: none;">
    <input type="hidden" name="amount" id="transferAmountToPay" />
</form>

<script>
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

  

    btnSeleccionarValores.addEventListener('click', function() {
        var totalPagar = 0;
        var resumenHtml = '';

        checkboxes.forEach(function(checkbox) {
            if (checkbox.checked) {
                var tr = checkbox.closest('tr');
                var valorArancel = tr.cells[2].textContent;
                totalPagar += parseFloat(valorArancel);

                // Agregar fila al resumen de valores a pagar
                resumenHtml += '<tr>' +
                    '<td>' + tr.cells[0].textContent + '</td>' +
                    '<td>' + tr.cells[1].textContent + '</td>' +
                    '<td>' + rutAlumnoInput.value + '</td>' + // Agrega el RUT del alumno
                    '<td>' + valorArancel + '</td>' +
                    '<td><button type="button" class="btn btn-danger btn-sm" onclick="removePayment(this)">Eliminar</button></td>' +
                    '</tr>';
            }
        });

        // Actualizar el total a pagar y el resumen de la tabla
        totalPagarElement.textContent = 'Total a pagar $' + totalPagar.toFixed(2);
        resumenValoresTableBody.innerHTML = resumenHtml;
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

function removePayment(button) {
    var tr = button.closest('tr');
    var valorArancel = tr.cells[3].textContent;
    var totalPagarElement = document.querySelector('.total-pagar strong');
    var totalPagar = parseFloat(totalPagarElement.textContent.replace('Total a pagar $', ''));

    // Restar el monto y actualizar el total
    totalPagar -= parseFloat(valorArancel);
    totalPagarElement.textContent = 'Total a pagar $' + totalPagar.toFixed(2);

    // Remover la fila del resumen
    tr.remove();
}
</script>