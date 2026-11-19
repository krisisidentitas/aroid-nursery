<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

$error = '';

$statuses = [
    'quarantine' => 'Karantina',
    'cultivation' => 'Budidaya',
    'ready' => 'Siap jual'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    $plantId = filter_input(
        INPUT_POST,
        'plant_type_id',
        FILTER_VALIDATE_INT
    );

    $quantity = filter_input(
        INPUT_POST,
        'quantity',
        FILTER_VALIDATE_INT
    );

    $from = $_POST['from_status'] ?? '';
    $to = $_POST['to_status'] ?? '';

    if (
        !$plantId ||
        !$quantity ||
        $quantity < 1 ||
        !isset($statuses[$from]) ||
        !isset($statuses[$to]) ||
        $from === $to
    ) {
        $error = 'Periksa kembali data pemindahan stok.';

    } else {

        try {

            $pdo->beginTransaction();

            // Pastikan tanaman aktif
            $plantStmt = $pdo->prepare("
                SELECT id
                FROM plant_types
                WHERE id = ? AND is_active = 1
                FOR UPDATE
            ");

            $plantStmt->execute([$plantId]);

            if (!$plantStmt->fetch()) {
                throw new RuntimeException(
                    'Tanaman tidak ditemukan atau sudah nonaktif.'
                );
            }

            // Kunci stok asal
            $sourceStmt = $pdo->prepare("
                SELECT id, quantity
                FROM inventories
                WHERE plant_type_id = ?
                  AND status = ?
                FOR UPDATE
            ");

            $sourceStmt->execute([$plantId, $from]);
            $source = $sourceStmt->fetch();

            if (!$source || (int) $source['quantity'] < $quantity) {
                throw new RuntimeException(
                    'Stok pada status asal tidak mencukupi.'
                );
            }

            // Kurangi stok asal
            $updateSource = $pdo->prepare("
                UPDATE inventories
                SET quantity = quantity - ?,
                    updated_at = NOW()
                WHERE id = ?
            ");

            $updateSource->execute([
                $quantity,
                $source['id']
            ]);

            // Periksa stok tujuan
            $destinationStmt = $pdo->prepare("
                SELECT id
                FROM inventories
                WHERE plant_type_id = ?
                  AND status = ?
                FOR UPDATE
            ");

            $destinationStmt->execute([$plantId, $to]);
            $destination = $destinationStmt->fetch();

            if ($destination) {

                // Tambahkan ke stok tujuan
                $updateDestination = $pdo->prepare("
                    UPDATE inventories
                    SET quantity = quantity + ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");

                $updateDestination->execute([
                    $quantity,
                    $destination['id']
                ]);

            } else {

                // Buat stok tujuan jika belum tersedia
                $insertDestination = $pdo->prepare("
                    INSERT INTO inventories
                    (plant_type_id, status, quantity, updated_at)
                    VALUES (?, ?, ?, NOW())
                ");

                $insertDestination->execute([
                    $plantId,
                    $to,
                    $quantity
                ]);
            }

            $pdo->commit();

            header('Location: index.php?transfer=success');
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e instanceof RuntimeException) {
                $error = $e->getMessage();
            } else {
                error_log($e->getMessage());
                $error = 'Pemindahan stok gagal dilakukan.';
            }
        }
    }
}

// Tanaman aktif
$plants = $pdo->query("
    SELECT id, type_name
    FROM plant_types
    WHERE is_active = 1
    ORDER BY type_name ASC
")->fetchAll();

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Pemindahan Stok | Aroid Plant Nursery</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="../assets/css/style.css"
          rel="stylesheet">

</head>

<body class="bg-light">

<div class="container py-5">

    <div class="card border-0 shadow-sm rounded-4 mx-auto"
         style="max-width: 650px">

        <div class="card-body p-4">

            <h3 class="fw-bold mb-2">
                Pemindahan Stok
            </h3>

            <p class="text-secondary mb-4">
                Pindahkan jumlah tanaman dari satu status ke status lainnya.
            </p>

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

                    <label class="form-label">
                        Jenis Tanaman
                    </label>

                    <select name="plant_type_id"
                            class="form-select"
                            required>

                        <option value="">
                            Pilih tanaman
                        </option>

                        <?php foreach ($plants as $plant): ?>

                            <option value="<?= $plant['id'] ?>">
                                <?= e($plant['type_name']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Status Asal
                    </label>

                    <select name="from_status"
                            class="form-select"
                            required>

                        <option value="">
                            Pilih status asal
                        </option>

                        <?php foreach ($statuses as $key => $label): ?>

                            <option value="<?= $key ?>">
                                <?= $label ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Status Tujuan
                    </label>

                    <select name="to_status"
                            class="form-select"
                            required>

                        <option value="">
                            Pilih status tujuan
                        </option>

                        <?php foreach ($statuses as $key => $label): ?>

                            <option value="<?= $key ?>">
                                <?= $label ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="mb-4">

                    <label class="form-label">
                        Jumlah Tanaman
                    </label>

                    <input type="number"
                           name="quantity"
                           class="form-control"
                           min="1"
                           required>

                </div>

                <div class="d-flex gap-2">

                    <button type="submit"
                            class="btn btn-success">

                        Pindahkan Stok

                    </button>

                    <a href="index.php"
                       class="btn btn-light">

                        Batal

                    </a>

                </div>

            </form>

        </div>

    </div>

</div>

</body>
</html>
