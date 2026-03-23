<?php

date_default_timezone_set('America/Bahia');

$host = "localhost";
$user = "bacuridi"; // padrão do XAMPP
$pass = "9:BO1a4gQg6:Ii";     // padrão do XAMPP
$db   = "bacuridi_barbearia_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}
?>