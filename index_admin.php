<?php
session_start();
require_once 'database.php';
require_once 'functions.php';

// Vérification rapide
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Redirection directe vers le dashboard admin
header('Location: admin.php');
exit;
