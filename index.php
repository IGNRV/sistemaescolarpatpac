<?php
// Incluye la conexión a la base de datos
require_once 'db.php';

// Inicia sesión
session_start();

// Verifica si el usuario ya está logueado
if (isset($_SESSION['usuario'])) {
    // Si está logueado, redirecciona a la página de bienvenida
    header('Location: bienvenido.php');
    exit;
} else {
    // Si no está logueado, redirecciona al login
    header('Location: login.php');
    exit;
}
?>
