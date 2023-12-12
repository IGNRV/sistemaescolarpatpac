<?php
require 'vendor/autoload.php';
$config = require('config.php');

use Dnetix\Redirection\PlacetoPay;

// Asegúrate de iniciar la sesión si aún no está iniciada
if(session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$placetopay = new PlacetoPay([
    'login' => $config['placetopay']['login'],
    'tranKey' => $config['placetopay']['tranKey'],
    'baseUrl' => $config['placetopay']['baseUrl'],
]);

// Recuperar el requestId de la sesión
// Suponiendo que se haya guardado en la sesión después de hacer la solicitud de pago
$requestId = isset($_SESSION['requestId']) ? $_SESSION['requestId'] : null;

if ($requestId) {
    $response = $placetopay->query($requestId);

    if ($response->isSuccessful()) {
        if ($response->status()->isApproved()) {
            // El pago ha sido aprobado
            echo "Pago aprobado";
        } else {
            // El pago ha sido rechazado o está pendiente
            echo "Pago rechazado o pendiente. Estado: " . $response->status()->status();
        }
    } else {
        // Error al obtener la información del pago
        echo "Error consultando el pago: " . $response->status()->message();
    }
} else {
    echo "El ID de la solicitud no está disponible.";
}

// No olvides limpiar la sesión una vez que ya no necesites el requestId
unset($_SESSION['requestId']);
