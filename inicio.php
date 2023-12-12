<div class="welcome-message text-center">
    <h1 class="display-4">Bienvenido a la Aplicación</h1>
    <p class="lead">Tu portal para la gestión escolar.</p>
    <hr class="my-4">
    <div class="button-container d-flex flex-wrap justify-content-center">
        <a href="bienvenido.php?page=inicio" class="btn btn-outline-primary custom-button m-2">Inicio</a>
        <a href="registro.php" class="btn btn-outline-primary custom-button m-2">Registro de Usuarios</a>
        <a href="bienvenido.php?page=datos_alumno" class="btn btn-outline-primary custom-button m-2">Datos Alumno</a>
        <a href="bienvenido.php?page=agregar_alumno" class="btn btn-outline-primary custom-button m-2">Agregar Alumno</a>
        <a href="bienvenido.php?page=emergencias" class="btn btn-outline-primary custom-button m-2">Emergencias</a>
        <a href="bienvenido.php?page=agregar_contacto_emergencia" class="btn btn-outline-primary custom-button m-2">Agregar contacto de emergencia</a>
        <a href="bienvenido.php?page=padres_apoderados" class="btn btn-outline-primary custom-button m-2">Padres/Apoderados</a>
        <a href="bienvenido.php?page=tutor_economico" class="btn btn-outline-primary custom-button m-2">Tutor Económico</a>
        <a href="bienvenido.php?page=becas" class="btn btn-outline-primary custom-button m-2">Becas</a>
        <!-- <a href="bienvenido.php?page=pago_electronico" class="btn btn-outline-primary custom-button m-2">Pago Electrónico</a> -->
        <a href="bienvenido.php?page=pago_cheque_anual" class="btn btn-outline-primary custom-button m-2">Pago Anual con Cheques</a>
        <a href="bienvenido.php?page=pago_rut_alumno" class="btn btn-outline-primary custom-button m-2">Pago con RUT</a>
        <a href="logout.php" class="btn btn-outline-danger custom-button m-2">Cerrar Sesión</a>
    </div>
</div>

<style>
    .welcome-message {
        padding: 40px 15px;
    }
    .custom-button {
        width: auto;
        padding: 0.5rem 1.5rem;
    }
    .btn-outline-primary {
        border-width: 2px;
    }
    .btn-outline-danger {
        border-width: 2px;
    }
</style>
