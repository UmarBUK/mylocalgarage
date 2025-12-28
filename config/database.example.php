<?php

$host = '127.0.0.1';
$db   = 'mylocalgarage';
$user = 'DB_USERNAME';
$pass = 'DB_PASSWORD';

$pdo = new PDO(
    "mysql:host=$host;dbname=$db;charset=utf8mb4",
    $user,
    $pass,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);
