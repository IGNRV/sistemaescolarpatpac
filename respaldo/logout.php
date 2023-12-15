<?php
// Inicia sesión
session_start();

// Elimina todos los datos de sesión
$_SESSION = array();

// Destruye la sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Redirecciona al login
header('Location: login.php');
exit;
?>
