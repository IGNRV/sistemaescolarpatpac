
<?php
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Cuadratura de Caja Diaria</title>
    <!-- Agrega los enlaces a los estilos de Bootstrap -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <style>
        /* Estilo personalizado para el tamaño de letra del título */
        .custom-title {
            font-size: 1.5em;
        }

        /* Estilo personalizado para hacer la tabla responsiva */
        .table-responsive {
            overflow-x: auto;
        }

        /* Estilo personalizado para ajustar el ancho del contenedor */
        .custom-container {
            max-width: 600px; /* Ajusta el ancho según tus preferencias */
            margin: auto;
            margin-top: 20px; /* Ajusta el margen superior según tus preferencias */
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>

</head>
<body>

<div class="container mt-5 custom-container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <!-- Título personalizado -->
                    <h2 class="text-center custom-title">CUADRATURA DE CAJA DIARIA</h2>
                </div>
                <div class="card-body">
                    <!-- Formulario de cuadratura de caja -->
                    <form>
                        <!-- Campos de selección de fecha y medio de pago -->
                        <div class="form-group">
                            <label for="fecha">Fecha</label>
                            <input type="date" class="form-control" id="fecha">
                        </div>
                        
                        <div class="form-group">
                            <label for="medioPago">Medio de Pago</label>
                            <select class="form-control" id="medioPago">
                                <option value="Efectivo">Efectivo</option>
                                <option value="Cheque">Cheque</option>
                                <option value="Debito">Debito</option>
                                <option value="Credito">Credito</option>
                                <!-- Agrega más opciones según sea necesario -->
                            </select>
                        </div>

                        <!-- Botón para realizar la cuadratura -->
                        <button type="button" class="btn btn-primary btn-block" id="btnBuscar">Buscar</button>
                    </form>

                    <!-- Tabla de Pago con Efectivo -->
                    <!-- <button type="submit" class="btn btn-primary btn-block">Selecciona Valores</button> -->
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12 mt-4">
        <div class="card">
            <div class="card-header">
            <!-- Título personalizado con un ID para mostrar el total recaudado -->
            <h2 class="text-center custom-title" id="totalRecaudado">TOTAL RECAUDADO $</h2>
                        <h4 class="section-title">PAGO CON EFECTIVO</h4>
            </div>
                    <div class="table-responsive mt-4">
                        
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Fecha Pago</th>
                                    <th>Monto</th>
                                    <th>Medio de Pago</th>
                                    <th>Tipo Documento</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody id="tablaEfectivo">
                                <!-- Agrega filas de datos según tus necesidades -->
                                <tr>
                                    <td>Fecha de Pago</td>
                                    <td>Monto</td>
                                    <td>Medio de Pago</td>
                                    <td>Tipo de Documento</td>
                                    <td>Estado</td>
                                </tr>
                                <!-- Puedes agregar más filas según sea necesario -->
                            </tbody>
                        </table>
                        </div>
            </div>
        </div>
    </div>

    <!-- Nuevo contenedor para PAGO CON CHEQUE -->
    <div class="col-md-12 mt-4">
        <div class="card">
            <div class="card-header">
                <!-- Título y subtítulo personalizados -->
                <h2 class="text-center custom-title" id="totalRecaudadoCheque">TOTAL RECAUDADO $</h2>
                <h5 class="text-center">PAGO CON CHEQUE</h5>
            </div>
            <div class="card-body">
                <!-- Tabla de Pago con Cheque -->
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Fecha Pago</th>
                                <th>Monto</th>
                                <th>Medio de Pago</th>
                                <th>Tipo Documento</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody id="tablaCheque">
                            <!-- Agrega filas de datos según tus necesidades -->
                            <tr>
                                <td>Fecha de Pago</td>
                                <td>Monto</td>
                                <td>Medio de Pago</td>
                                <td>Tipo de Documento</td>
                                <td>Estado</td>
                            </tr>
                            <!-- Puedes agregar más filas según sea necesario -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12 mt-4">
        <div class="card">
            <div class="card-header">
                <!-- Título y subtítulo personalizados -->
                <h3 class="text-center custom-title" id="totalRecaudadoDebito">TOTAL RECAUDADO $</h3>
                <h5 class="text-center">PAGO CON DEBITO</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Fecha Pago</th>
                                <th>Monto</th>
                                <th>Medio de Pago</th>
                                <th>Tipo Documento</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody id="tablaDebito">
                            <!-- Agrega filas de datos según tus necesidades -->
                            <tr>
                                <td>Fecha de Pago</td>
                                <td>Monto</td>
                                <td>Medio de Pago</td>
                                <td>Tipo de Documento</td>
                                <td>Estado</td>
                            </tr>
                            <!-- Puedes agregar más filas según sea necesario -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12 mt-4">
        <div class="card">
            <div class="card-header">
                <!-- Título y subtítulo personalizados -->
                <h3 class="text-center custom-title" id="totalRecaudadoCredito">TOTAL RECAUDADO $</h3>
                <h5 class="text-center">PAGO CON Credito</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Fecha Pago</th>
                                <th>Monto</th>
                                <th>Medio de Pago</th>
                                <th>Tipo Documento</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody id="tablaCredito">
                            <!-- Agrega filas de datos según tus necesidades -->
                            <tr>
                                <td>Fecha de Pago</td>
                                <td>Monto</td>
                                <td>Medio de Pago</td>
                                <td>Tipo de Documento</td>
                                <td>Estado</td>
                            </tr>
                            <!-- Puedes agregar más filas según sea necesario -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <h5 class="text-center" id="totalRecaudadoGeneral">TOTAL RECAUDADO $</h5>
    <button type="button" class="btn btn-primary btn-block" id="btnGenerarReporte">Generar Reporte</button>

</div>

<!-- Agrega el script de Bootstrap -->
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
<script>

document.getElementById('btnBuscar').addEventListener('click', function() {
    var fecha = document.getElementById('fecha').value;
    var medioPago = document.getElementById('medioPago').value;

    // Verificar si el medio de pago es 'Efectivo', 'Cheque' o 'Debito'
    if ((medioPago === 'Efectivo' || medioPago === 'Cheque' || medioPago === 'Debito' || medioPago === 'Credito') && fecha) {
        fetch('busca_pagos.php?fecha=' + fecha + '&medioPago=' + medioPago)
            .then(response => response.json())
            .then(datos => {
                let totalRecaudado = 0;
                let tabla;

                // Determinar qué tabla actualizar en función del medio de pago seleccionado
                if (medioPago === 'Efectivo') {
                    tabla = document.getElementById('tablaEfectivo');
                } else if (medioPago === 'Cheque') {
                    tabla = document.getElementById('tablaCheque');
                } else if (medioPago === 'Debito') {
                    tabla = document.getElementById('tablaDebito');
                } else if (medioPago === 'Credito') {
                    tabla = document.getElementById('tablaCredito');
                }

                tabla.innerHTML = ''; // Limpiar la tabla antes de agregar nuevos datos

                // Iterar a través de los datos y actualizar la tabla correspondiente
                datos.forEach(function(pago) {
                    let estadoTexto = pago.ESTADO === '1' ? 'PAGADA' : pago.ESTADO;

                    var fila = `<tr>
                        <td>${pago.FECHA_PAGO}</td>
                        <td>${pago.VALOR}</td>
                        <td>${pago.MEDIO_DE_PAGO}</td>
                        <td>${pago.TIPO_DOCUMENTO}</td>
                        <td>${estadoTexto}</td>
                    </tr>`;
                    tabla.innerHTML += fila;

                    totalRecaudado += parseFloat(pago.VALOR);
                });

                // Actualizar el total recaudado en la interfaz
                if (medioPago === 'Efectivo') {
                    document.getElementById('totalRecaudado').textContent = 'TOTAL RECAUDADO $' + totalRecaudado.toFixed(0);
                } else if (medioPago === 'Cheque') {
                    document.getElementById('totalRecaudadoCheque').textContent = 'TOTAL RECAUDADO $' + totalRecaudado.toFixed(0);
                } else if (medioPago === 'Debito') {
                    document.getElementById('totalRecaudadoDebito').textContent = 'TOTAL RECAUDADO $' + totalRecaudado.toFixed(0);
                } else if (medioPago === 'Credito') {
                    document.getElementById('totalRecaudadoCredito').textContent = 'TOTAL RECAUDADO $' + totalRecaudado.toFixed(0);
                }
                actualizarTotalGeneral();

            })
            .catch(error => console.error('Error:', error));
    } else {
        alert('Por favor, seleccione un medio de pago y una fecha válida.');
    }
});




document.getElementById('btnGenerarReporte').addEventListener('click', function() {
    var doc = new jspdf.jsPDF();
    var finalY = 10; // Inicializamos el eje Y para que comience después del título del reporte

    doc.setFontSize(18);
    doc.text('Reporte de Cuadratura de Caja', 14, finalY);

    finalY += 10; // Espacio después del título

    // Agregar tablas y totales al PDF
    var mediosPago = ['Efectivo', 'Cheque', 'Debito', 'Credito'];

    mediosPago.forEach(function(medioPago) {
        var seccion = medioPago.charAt(0).toUpperCase() + medioPago.slice(1).toLowerCase();
        var tabla = document.getElementById('tabla' + seccion);
        var totalId = 'totalRecaudado' + seccion;
        var totalElement = document.getElementById(totalId);

        // Verificar si la tabla tiene datos (filas) antes de agregarla al PDF
        if (tabla && tabla.rows.length > 1) { // Asegurarse de que hay más de una fila (la fila de encabezado)
            doc.setFontSize(14);
            finalY += 7; // Espacio antes de cada sección
            doc.text('Pago con ' + seccion, 14, finalY);

            doc.setFontSize(11);
            finalY += 3; // Espacio para comenzar la tabla
            doc.autoTable({
                html: '#tabla' + seccion,
                startY: finalY,
                margin: { top: 30 },
                didDrawPage: function (data) {
                    finalY = data.cursor.y; // Actualizar finalY al final de cada tabla
                }
            });

            // Mostrar total recaudado por sección
            if (totalElement) {
                finalY += 7; // Espacio antes del total
                doc.text(totalElement.textContent, 14, finalY);
                finalY += 5; // Espacio después del total antes de la siguiente sección
            }
        }
    });

    // Agregar total general al final
    var totalGeneralElement = document.getElementById('totalRecaudadoGeneral');
    if (totalGeneralElement) {
        finalY += 7; // Espacio antes del total general
        doc.text(totalGeneralElement.textContent, 14, finalY);
    }

    // Guardar el PDF
    doc.save('reporte_cuadratura.pdf');
});

function actualizarTotalGeneral() {
    let totalGeneral = 0;
    const idsTotales = ['totalRecaudado', 'totalRecaudadoCheque', 'totalRecaudadoDebito', 'totalRecaudadoCredito'];

    idsTotales.forEach(id => {
        let totalTexto = document.getElementById(id).textContent;
        let totalValor = parseFloat(totalTexto.replace('TOTAL RECAUDADO $', '').trim()) || 0;
        totalGeneral += totalValor;
    });

    document.getElementById('totalRecaudadoGeneral').textContent = 'TOTAL RECAUDADO $' + totalGeneral.toFixed(0);
}

</script>


</body>
</html>
