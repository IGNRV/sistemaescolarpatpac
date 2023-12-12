<div class="vista-pago-automatico">
    <h2>PAGO ELECTRÓNICO DE CUOTAS VENCIDAS</h2>
    <div class="form-group">
        <label for="rutAlumno">RUT del Alumno:</label>
        <input type="text" class="form-control" id="rutAlumno" placeholder="Buscar por RUT">
        <button class="btn btn-primary">BUSCAR</button>
    </div>

    <h3>VALORES PENDIENTES DE AÑO ANTERIOR</h3>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>N° Cuota</th>
                    <th>Tipo Pago</th>
                    <th>Fecha Vencimiento</th>
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

    <h3>PLAN DE PAGO AÑO EN CURSO</h3>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>N° Cuota</th>
                    <th>Tipo Pago</th>
                    <th>Fecha Vencimiento</th>
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

    <button class="btn btn-primary custom-button mt-3">SELECCIONAR VALORES</button>

    <h3>RESUMEN DE VALORES A PAGAR</h3>
    <div class="table-responsive">
        <table class="table">
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
        <button class="btn btn-success custom-button">PAGAR CON TARJETA CR/DB</button>
        <button class="btn btn-info custom-button">PAGAR CON TRANSFERENCIA</button>
        <button class="btn btn-secondary custom-button">AGREGAR OTRO ALUMNO</button>
    </div>
</div>
