<?php
session_start();
if (isset($_POST['idsCuotas'])) {
    $_SESSION['idsCuotas'] = json_decode($_POST['idsCuotas']);
}
?>
