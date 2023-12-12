<?php
// Este script manejaría las notificaciones de pago de Khipu
// Por simplicidad, solo imprimirá los datos recibidos
file_put_contents('notificaciones.txt', print_r($_POST, true));
echo "Notificación recibida.";
