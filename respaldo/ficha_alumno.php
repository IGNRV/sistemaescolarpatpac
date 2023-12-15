<?php
// Incluye la conexión a la base de datos
require_once 'db.php';

// Verifica si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    // Si no está logueado, redirecciona al login
    header('Location: login.php');
    exit;
}

// Manejar el envío del formulario de ficha del alumno
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar_ficha'])) {
    // Aquí procesas la lógica para guardar la ficha del alumno en la base de datos
    // Puedes obtener los datos del formulario utilizando $_POST
    $categoria = $conn->real_escape_string($_POST['categoria']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);
    $fecha = $conn->real_escape_string($_POST['fecha']);

    // Puedes realizar la inserción en la base de datos según tus necesidades
    $insertQuery = "INSERT INTO ficha_alumno (id_alumno, categoria, descripcion, fecha) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param('isss', $idAlumno, $categoria, $descripcion, $fecha);

    // Supongamos que $idAlumno es la variable que almacena el ID del alumno actual
    $idAlumno = obtenerIdAlumno(); // Debes implementar una función para obtener el ID del alumno actual

    $stmt->execute();
    $stmt->close();
}
?>

<!-- Formulario de ficha del alumno -->
<form action="" method="post">
    <h2 class="mt-4 text-center">Ficha del Alumno</h2>
    <div class="form-group">
        <label for="categoria">Categoría:</label>
        <input type="text" class="form-control" name="categoria" required>
    </div>
    <div class="form-group">
        <label for="descripcion">Descripción:</label>
        <textarea class="form-control" name="descripcion" rows="3" required></textarea>
    </div>
    <div class="form-group">
        <label for="fecha">Fecha:</label>
        <input type="date" class="form-control" name="fecha" required>
    </div>
    <!-- Botón de guardar con clase Bootstrap y personalizada -->
    <button type="submit" class="btn btn-primary btn-block custom-button" name="guardar_ficha">Guardar</button>
</form>
