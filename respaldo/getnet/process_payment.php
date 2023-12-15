<?php
require 'vendor/autoload.php';
$config = require('config.php');

use Dnetix\Redirection\PlacetoPay;

$placetopay = new PlacetoPay([
    'login' => $config['placetopay']['login'],
    'tranKey' => $config['placetopay']['tranKey'],
    'baseUrl' => $config['placetopay']['baseUrl'],
]);

$reference = 'TEST_' . time();
$request = [
    'payment' => [
        'reference' => $reference,
        'description' => 'Pago de prueba',
        'amount' => [
            'currency' => 'CLP',
            'total' => $_POST['amount'],
        ],
    ],
    'expiration' => date('c', strtotime('+2 days')),
    'returnUrl' => 'http://localhost:8000/response.php?reference=' . $reference,
    'ipAddress' => $_SERVER['REMOTE_ADDR'],
    'userAgent' => $_SERVER['HTTP_USER_AGENT'],
];

//... código anterior

$response = $placetopay->request($request);

if ($response->isSuccessful()) {
    // Guardar el requestId en la sesión o en la base de datos para su posterior uso.
    $_SESSION['requestId'] = $response->requestId();

    // Redireccionar al usuario al proceso de pago.
    header('Location: ' . $response->processUrl());
} else {
    // Error al generar el pago
    echo $response->status()->message();
}
