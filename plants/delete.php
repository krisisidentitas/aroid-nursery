<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode tidak diizinkan.');
}

verifyCsrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('ID tanaman tidak valid.');
}

$stmt = $pdo->prepare("
    UPDATE plant_types
    SET is_active = 0
    WHERE id = ?
");

$stmt->execute([$id]);

header('Location: index.php');
exit;
