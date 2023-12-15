<?php
require __DIR__ . '/vendor/autoload.php';
session_start();

// Generar un token único
$_SESSION['payment_token'] = bin2hex(random_bytes(32));

// Comprueba si estamos recibiendo una solicitud de pago
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['amount'])) {
    $monto = $_POST['amount']; // Recibe el monto desde el formulario

    // Configuración con credenciales de prueba de Khipu
    $configuration = new Khipu\Configuration();
    $configuration->setSecret("ffef340fb1fc00a8748e0ab93f1335bf93e77442");
    $configuration->setReceiverId(458576);
    $configuration->setDebug(true);

    $client = new Khipu\ApiClient($configuration);
    $payments = new Khipu\Client\PaymentsApi($client);

    // Establecer una fecha de expiración para el pago en el futuro
    $exp = new DateTime();
    $exp->add(new DateInterval('P1D')); // Añade 1 día a la fecha/hora actual

    // Información del pago
    try {
        $opts = array(
            "expires_date" => $exp->format(DateTime::ATOM), // Formato correcto para la fecha de expiración
            "body" => "Descripción del cuerpo del pago",
            "return_url" => "https://antilen.pat-pac.cl/sistemaescolar/khipu/retorno.php?token=" . $_SESSION['payment_token'],
            "cancel_url" => "https://antilen.pat-pac.cl/sistemaescolar/khipu/cancelado.php",
            "notify_url" => "https://antilen.pat-pac.cl/sistemaescolar/khipu/notificacion.php"
        );
        $response = $payments->paymentsPost("Pago de cuotas", "CLP", $monto, $opts);

        // Redirige al usuario a la página de Khipu para que realice el pago
        header('Location: ' . $response->getPaymentUrl());
        exit();
    } catch (Exception $e) {
        echo 'Error al crear el pago: ', $e->getMessage();
    }
}
?>
