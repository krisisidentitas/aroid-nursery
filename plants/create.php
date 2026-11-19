<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    $name = trim($_POST['type_name'] ?? '');
    $price = trim($_POST['current_price'] ?? '');

    if ($name === '' || !ctype_digit($price)) {
        $error = 'Nama dan harga tanaman harus diisi dengan benar.';
    } elseif ((int) $price < 0) {
        $error = 'Harga tidak boleh negatif.';
    } else {

        try {

            $stmt = $pdo->prepare("
                INSERT INTO plant_types
                (type_name, current_price, is_active)
                VALUES (?, ?, 1)
            ");

            $stmt->execute([
                $name,
                (int) $price
            ]);

            header('Location: index.php');
            exit;

        } catch (PDOException $e) {

            if ($e->getCode() === '23000') {
                $error = 'Nama tanaman sudah terdaftar.';
            } else {
                throw $e;
            }

        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Tanaman</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-5">

    <div class="card border-0 shadow-sm rounded-4 mx-auto"
         style="max-width: 600px">

        <div class="card-body p-4">

            <h3 class="fw-bold mb-4">Tambah Jenis Tanaman</h3>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <input type="hidden"
                       name="csrf_token"
                       value="<?= e(csrfToken()) ?>">

                <div class="mb-3">
                    <label class="form-label">Nama Tanaman</label>

                    <input type="text"
                           name="type_name"
                           class="form-control"
                           maxlength="150"
                           required
                           value="<?= e($_POST['type_name'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label">Harga Jual (Rp)</label>

                    <input type="number"
                           name="current_price"
                           class="form-control"
                           min="0"
                           step="1"
                           required
                           value="<?= e($_POST['current_price'] ?? '') ?>">
                </div>

                <div class="d-flex gap-2">

                    <button type="submit" class="btn btn-success">
                        Simpan Tanaman
                    </button>

                    <a href="index.php" class="btn btn-light">
                        Batal
                    </a>

                </div>

            </form>

        </div>
    </div>

</div>

</body>
</html>
