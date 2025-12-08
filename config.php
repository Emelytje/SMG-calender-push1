<?php
$db = mysqli_connect('localhost', 'root', '', 'manege_db');

if (!$db) {
    die('Database fout');
}

    session_start();

