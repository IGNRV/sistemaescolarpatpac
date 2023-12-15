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
    <div class="form-group">
        <label for="rutAlumno">Rut del alumno:</label>
        <input type="text" class="form-control" id="rutAlumno" placeholder="Ingrese RUT del alumno">
        <button class="btn btn-primary mt-3" id="btnBuscarAlumno">Buscar</button>

    </div>
    <div id="resultadoBusqueda" class="my-3"></div>

    <h3 class="mt-4">Valores pendientes de año anterior</h3>
    <!-- Tabla de valores pendientes del año anterior -->
    <!-- La tabla deberá ser llenada con datos dinámicamente -->
    <div id="tablaValoresPendientes" class="table-responsive">
        <table class="table table-striped">
                <thead>
                    <tr>
                        <th>N° Cuota</th>
                        <th>Fecha Vencimiento</th>
                        <th>Monto</th> <!-- Nueva columna agregada -->
                        <th>Medio de Pago</th>
                        <th>Fecha de Pago</th>
                        <th>Estado</th>
                        <th>Seleccione Valor a Pagar</th>
                    </tr>
                </thead>
            <tbody>
                <!-- Los datos de las cuotas se insertarán aquí -->
            </tbody>
        </table>
    </div>

    <h3 class="mt-4">Plan de pago año en curso</h3>
    <!-- Tabla de plan de pago del año en curso -->
    <!-- La tabla deberá ser llenada con datos dinámicamente -->
    <div id="tablaPlanPagoActual" class="table-responsive">
        <table class="table table-striped">
                <thead>
                    <tr>
                        <th>N° Cuota</th>
                        <th>Fecha Vencimiento</th>
                        <th>Monto</th> <!-- Nueva columna agregada -->
                        <th>Medio de Pago</th>
                        <th>Fecha de Pago</th>
                        <th>Estado</th>
                        <th>Seleccione Valor a Pagar</th>
                    </tr>
                </thead>
            <tbody>
                <!-- Los datos de las cuotas se insertarán aquí -->
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
        <!-- Aquí se mostrará el total a pagar calculado -->
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

<script type="text/javascript">
    document.getElementById('btnPagoAutomatico').addEventListener('click', function() {
        window.location.href = 'bienvenido.php?page=vista_pago_automatico';
    });
</script>

<script type="text/javascript">
document.getElementById('btnBuscarAlumno').addEventListener('click', function() {
    var rut = document.getElementById('rutAlumno').value;
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'buscar_alumno.php', true);
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    xhr.onload = function () {
        // Parseamos la respuesta JSON
        var respuesta = JSON.parse(this.responseText);
        if (respuesta.anterior || respuesta.actual) {
            // Llamamos a las funciones para mostrar los datos en las tablas
            mostrarDatos(respuesta.anterior, 'tablaValoresPendientes');
            mostrarDatos(respuesta.actual, 'tablaPlanPagoActual');
        } else {
            document.getElementById('resultadoBusqueda').innerHTML = "Alumno no encontrado.";
        }
    };
    xhr.send('rut=' + rut);
});

function mostrarDatos(datos, idTabla) {
    var tabla = document.getElementById(idTabla).getElementsByTagName('tbody')[0];
    tabla.innerHTML = ''; // Limpiamos la tabla antes de agregar nuevos datos

    datos.sort((a, b) => new Date(a.fecha_cuota_deuda) - new Date(b.fecha_cuota_deuda)); // Ordena los datos por fecha de vencimiento


    datos.forEach(function(cuota, index) {
        var fila = tabla.insertRow();
        fila.insertCell(-1).textContent = index + 1; // N° Cuota
        fila.insertCell(-1).textContent = cuota.fecha_cuota_deuda; // Fecha Vencimiento
        fila.insertCell(-1).textContent = cuota.monto; // Monto
        fila.insertCell(-1).textContent = ''; // Medio de Pago (aquí va el valor correcto)
        fila.insertCell(-1).textContent = ''; // Fecha de Pago (aquí va el valor correcto)
        fila.insertCell(-1).textContent = cuota.estado_cuota == 0 ? 'VIGENTE' : (cuota.estado_cuota == 1 ? 'VENCIDA' : 'PAGADA'); // Estado
        
        // Agregar el input de tipo check si el estado de la cuota es 0
        var cellCheck = fila.insertCell(-1);
        if (cuota.estado_cuota == 0 || cuota.estado_cuota == 1 ) {
            var inputCheck = document.createElement('input');
            inputCheck.type = 'checkbox';
            inputCheck.name = 'cuotaSeleccionada[]';
            inputCheck.value = cuota.id;
            inputCheck.dataset.fecha = cuota.fecha_cuota_deuda; // Agrega el atributo de fecha al checkbox
            cellCheck.appendChild(inputCheck);
        } else {
            cellCheck.textContent = '';
        }
    });

    habilitarCheckbox(); // Llama a una nueva función para habilitar el primer checkbox
}

function habilitarCheckbox() {
    var checkboxes = document.querySelectorAll('input[type="checkbox"][name="cuotaSeleccionada[]"]');
    if (checkboxes.length > 0) {
        checkboxes[0].disabled = false; // Habilita solo el primer checkbox

        checkboxes.forEach(function(checkbox, index) {
            if (index > 0) {
                checkbox.disabled = true; // Deshabilita los demás checkboxes
            }

            // Evento para habilitar el siguiente checkbox cuando se marque el actual
            checkbox.addEventListener('change', function() {
                if (checkboxes[index + 1]) {
                    checkboxes[index + 1].disabled = !checkbox.checked;
                }
            });
        });
    }
}

function seleccionarValores() {
    var cuotasSeleccionadas = document.querySelectorAll('input[name="cuotaSeleccionada[]"]:checked');
    var tablaResumen = document.getElementById('resumenValores').getElementsByTagName('tbody')[0];
    var rutAlumno = document.getElementById('rutAlumno').value; // Asumiendo que el RUT se mantiene en el input
    
    cuotasSeleccionadas.forEach(function(checkbox, index) {
        var fila = checkbox.closest('tr');
        var nuevaFila = tablaResumen.insertRow();
        
        nuevaFila.insertCell(-1).textContent = index + 1; // N° Cupón
        nuevaFila.insertCell(-1).textContent = fila.cells[1].textContent; // Fecha Vencimiento
        nuevaFila.insertCell(-1).textContent = rutAlumno; // RUT alumno
        nuevaFila.insertCell(-1).textContent = fila.cells[2].textContent; // Monto
        
        // Botón Eliminar
        var cellEliminar = nuevaFila.insertCell(-1);
        var btnEliminar = document.createElement('button');
        btnEliminar.textContent = 'Eliminar';
        btnEliminar.onclick = function() { 
            // Eliminar la fila del resumen
            tablaResumen.deleteRow(nuevaFila.rowIndex - 1);
        };
        cellEliminar.appendChild(btnEliminar);
    });
}

// Función para actualizar el total a pagar
function actualizarTotalAPagar() {
    var total = 0;
    var filas = document.querySelectorAll('#resumenValores tbody tr');
    filas.forEach(function(fila) {
        total += parseFloat(fila.cells[3].textContent); // Asumiendo que la columna de Monto está en el índice 3
    });
    document.querySelector('.total-pagar strong').textContent = "Total a pagar $" + total.toFixed(0);
}

document.getElementById('btnSeleccionarValores').addEventListener('click', function() {
    var cuotasSeleccionadas = document.querySelectorAll('input[name="cuotaSeleccionada[]"]:checked');
    var tablaResumen = document.getElementById('resumenValores').getElementsByTagName('tbody')[0];
    var rutAlumno = document.getElementById('rutAlumno').value;

    cuotasSeleccionadas.forEach(function(checkbox, index) {
        if (!checkbox.disabled) { // Solo agregamos la fila si el checkbox no está deshabilitado
            var fila = checkbox.closest('tr');
            var nuevaFila = tablaResumen.insertRow();
            
            nuevaFila.insertCell(-1).textContent = tablaResumen.rows.length; // N° Cupón
            nuevaFila.insertCell(-1).textContent = fila.cells[1].textContent; // Fecha Vencimiento
            nuevaFila.insertCell(-1).textContent = rutAlumno; // RUT alumno
            nuevaFila.insertCell(-1).textContent = fila.cells[2].textContent; // Monto
            
            // Botón Eliminar
            var cellEliminar = nuevaFila.insertCell(-1);
            var btnEliminar = document.createElement('button');
            btnEliminar.textContent = 'Eliminar';
            btnEliminar.type = 'button';
            btnEliminar.onclick = function() { 
                tablaResumen.deleteRow(nuevaFila.rowIndex - 1);
                actualizarTotalAPagar(); // Actualizar el total después de eliminar una fila
            };
            cellEliminar.appendChild(btnEliminar);

            checkbox.disabled = true; // Deshabilitamos el checkbox para no volver a agregar la misma fila
        }
    });

    actualizarTotalAPagar(); // Actualizar el total después de agregar nuevas filas
});

var nombreDelUsuario = "<?php echo $_SESSION['nombre']; ?>"; // Asegúrate de que 'nombre' exista en tu sesión
var correoElectronico = "<?php echo $_SESSION['correo_electronico']; ?>"; // Asegúrate de que 'correo_electronico' exista en tu sesión

document.getElementById('payWithCard').addEventListener('click', function() {
    var totalAPagarTexto = document.querySelector('.total-pagar strong').textContent;
    var totalAPagar = totalAPagarTexto.split("$")[1] ? parseFloat(totalAPagarTexto.split("$")[1]) : 0;

    // Verificar si hay un monto total antes de proceder con el pago
    if (totalAPagar > 0) {
        // Llenar el formulario oculto con los datos
        document.getElementById('customerName').value = nombreDelUsuario;
        document.getElementById('customerEmail').value = correoElectronico;
        document.getElementById('amountToPay').value = totalAPagar;

        // Enviar el formulario
        document.getElementById('paymentForm').submit();
    } else {
        alert("Por favor, seleccione al menos una cuota para pagar.");
    }
});

document.getElementById('payWithTransfer').addEventListener('click', function() {
    var totalAPagarTexto = document.querySelector('.total-pagar strong').textContent;
    var totalAPagar = totalAPagarTexto.split("$")[1] ? parseFloat(totalAPagarTexto.split("$")[1]) : 0;
    var rutAlumno = document.getElementById('rutAlumno').value;

    if (totalAPagar > 0 && rutAlumno) {
        var cuotasSeleccionadas = document.querySelectorAll('input[name="cuotaSeleccionada[]"]:checked');
        var identificadorPago = 'pago_' + Date.now(); // Identificador único para el grupo de pagos
        sessionStorage.setItem('identificadorPago', identificadorPago); 

        var datosPago = Array.from(cuotasSeleccionadas).map(function(checkbox) {
            return {
                idCuota: checkbox.value,
                rutAlumno: rutAlumno,
                monto: checkbox.closest('tr').cells[2].textContent,
                fechaCuota: checkbox.dataset.fecha,
                identificadorPago: identificadorPago // Agregar el identificador a los detalles de cada pago
            };
        });

        // Enviar solicitud AJAX al backend
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'procesar_pago_publico.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onload = function() {
            if (xhr.status === 200) {
                alert("Redirigiendo a Khipu...");
                document.getElementById('transferAmountToPay').value = totalAPagar;
                document.getElementById('transferPaymentForm').submit();
            } else {
                alert("Error al procesar el pago.");
            }
        };
        xhr.send(JSON.stringify({ pagos: datosPago, totalAPagar: totalAPagar, identificadorPago: identificadorPago }));
    } else {
        alert("Por favor, seleccione al menos una cuota para pagar y asegúrese de haber buscado un alumno.");
    }
});





</script>
