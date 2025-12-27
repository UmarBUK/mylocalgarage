<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Local Garage</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<div class="page-wrapper">

<header class="site-header">
    <div class="container">
        <h1 class="logo">MyLocalGarage</h1>
        <nav>
            <ul class="nav">
                <li><a href="/">Home</a></li>
                <li><a href="/book.php">Find a Garage</a></li>
                <li><a href="/garage/register.php">Register Your Garage</a></li>
                <li><a href="/admin/login.php">Admin</a></li>
            </ul>
        </nav>
    </div>
</header>

<main class="content container">
    <div class="container">

