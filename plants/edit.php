
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('ID tanaman tidak valid.');
}

$stmt = $pdo->prepare("
    SELECT * FROM plant_types WHERE id = ?
");

$stmt->execute([$id]);
$plant = $stmt->fetch();

if (!$plant) {
    http_response_code(404);
    exit('Tanaman tidak ditemukan.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    $name = trim($_POST['type_name'] ?? '');
    $price = trim($_POST['current_price'] ?? '');
    $active = isset($_POST['is_active']) ? 1 : 0;

    if ($name === '' || !ctype_digit($price)) {
        $error = 'Nama dan harga harus diisi dengan benar.';
    } elseif ((int) $price < 0) {
        $error = 'Harga tidak boleh negatif.';
    } else {

        try {

            $newPrice = (int) $price;
            $oldPrice = (int) $plant['current_price'];

            $pdo->beginTransaction();

            $update = $pdo->prepare("
                UPDATE plant_types
                SET type_name = ?,
                    current_price = ?,
                    is_active = ?
                WHERE id = ?
            ");

            $update->execute([
                $name,
                $newPrice,
                $active,
                $id
            ]);

            if ($newPrice !== $oldPrice) {

                $history = $pdo->prepare("
                    INSERT INTO price_histories
                    (plant_type_id, old_price, new_price, note)
                    VALUES (?, ?, ?, ?)
                ");

                $history->execute([
                    $id,
                    $oldPrice,
                    $newPrice,
                    'Perubahan harga melalui aplikasi'
                ]);
            }

            $pdo->commit();

            header('Location: index.php');
            exit;

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e->getCode() === '23000') {
                $error = 'Nama tanaman sudah digunakan.';
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
    <title>Edit Tanaman</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-5">

    <div class="card border-0 shadow-sm rounded-4 mx-auto"
         style="max-width: 600px">

        <div class="card-body p-4">

            <h3 class="fw-bold mb-4">Edit Jenis Tanaman</h3>

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
                           value="<?= e($_POST['type_name'] ?? $plant['type_name']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Harga Jual (Rp)</label>

                    <input type="number"
                           name="current_price"
                           class="form-control"
                           min="0"
                           step="1"
                           required
                           value="<?= e($_POST['current_price'] ?? $plant['current_price']) ?>">
                </div>

                <div class="form-check mb-4">

                    <input type="checkbox"
                           name="is_active"
                           id="is_active"
                           class="form-check-input"
                           value="1"
                           <?= (isset($_POST['is_active'])
                                ? isset($_POST['is_active'])
                                : $plant['is_active']) ? 'checked' : '' ?>>

                    <label for="is_active" class="form-check-label">
                        Tanaman aktif
                    </label>

                </div>

                <button type="submit" class="btn btn-success">
                    Simpan Perubahan
                </button>

                <a href="index.php" class="btn btn-light">
                    Batal
                </a>

            </form>

        </div>
    </div>

</div>

</body>
</html>
