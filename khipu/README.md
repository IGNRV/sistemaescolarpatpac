## Instalation

Add the dependency _khipu/khipu-api-client_ to _composer.json_ and run

```
composer install
```


## Usage

### Basic usage
```php
<?php
require __DIR__ . '/vendor/autoload.php';

// Configuración con credenciales de prueba de Khipu
$c = new Khipu\Configuration();
$c->setSecret("ffef340fb1fc00a8748e0ab93f1335bf93e77442"); // Clave secreta de prueba
$c->setReceiverId(458576); // ID de receptor de prueba
$c->setDebug(true);

$cl = new Khipu\ApiClient($c);
$kh = new Khipu\Client\PaymentsApi($cl);

// Establecer una fecha de expiración para el pago
$exp = new DateTime();
$exp->setDate(2020, 11, 3); // Asegúrate de establecer una fecha válida para las pruebas

// Información del pago
try {
    $opts = array(
        "expires_date" => $exp,
        "body" => "test body",
        "return_url" => "http://tu-sitio-web.com/pago_retorno", // URL a la que retorna el pagador
        "cancel_url" => "http://tu-sitio-web.com/pago_cancelado", // URL de cancelación
        "notify_url" => "http://tu-sitio-web.com/pago_notificacion" // URL de notificación instantánea
    );
    $resp = $kh->paymentsPost("Test de api", "CLP", 1570, $opts);
    print_r($resp); // Imprime la respuesta del intento de pago

    // Obtener detalles del pago usando el Payment ID
    $r2 = $kh->paymentsIdGet($resp->getPaymentId());
    print_r($r2); // Imprime los detalles del pago
} catch(Exception $e) {
    echo $e->getMessage(); // Maneja cualquier excepción que ocurra
}
?>


```
