<?php
session_start();

$db = mysqli_connect('localhost', 'root', '', 'manege_db');


if (!$db) {
    echo "Databaseverbinding mislukt.";
    exit;
}
?>


