<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pago en Línea</title>
</head>
<body>
    <form method="post" action="process_payment.php">
        <input type="text" name="customer_name" placeholder="Nombre del Cliente" required />
        <input type="text" name="customer_email" placeholder="Email del Cliente" required />
        <input type="number" name="amount" placeholder="Monto a Pagar" required />
        <input type="submit" value="Pagar" />
    </form>
</body>
</html>
