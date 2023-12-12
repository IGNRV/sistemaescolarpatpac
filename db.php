<?php
$dbHost = 'localhost';
$dbUsername = 'root';
$dbPassword = 'OCCServidor.2021';
$dbName = 'c1occsyspay';

$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>