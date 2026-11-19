<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

$error = '';
$success = '';

$statuses = [
    'quarantine' => 'Karantina',
    'cultivation' => 'Budidaya',
    'ready' => 'Siap jual'
];

// Proses stok masuk
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

    $status = $_POST['status'] ?? '';
    $note = trim($_POST['note'] ?? '');

    if (
        !$plantId ||
        !$quantity ||
        $quantity < 1 ||
        !isset($statuses[$status])
    ) {
        $error = 'Periksa kembali data stok yang dimasukkan.';

    } else {

        try {

            $pdo->beginTransaction();

            // Pastikan tanaman aktif
            $check = $pdo->prepare("
                SELECT id
                FROM plant_types
                WHERE id = ? AND is_active = 1
                FOR UPDATE
            ");

            $check->execute([$plantId]);

            if (!$check->fetch()) {
                throw new RuntimeException(
                    'Tanaman tidak ditemukan atau sudah nonaktif.'
                );
            }

            // Periksa apakah kombinasi tanaman dan status sudah ada
            $find = $pdo->prepare("
                SELECT id
                FROM inventories
                WHERE plant_type_id = ?
                  AND status = ?
                FOR UPDATE
            ");

            $find->execute([$plantId, $status]);

            $inventory = $find->fetch();

            if ($inventory) {

                // Tambahkan stok yang sudah ada
                $update = $pdo->prepare("
                    UPDATE inventories
                    SET quantity = quantity + ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");

                $update->execute([
                    $quantity,
                    $inventory['id']
                ]);

            } else {

                // Buat catatan stok baru
                $insert = $pdo->prepare("
                    INSERT INTO inventories
                    (plant_type_id, status, quantity, updated_at)
                    VALUES (?, ?, ?, NOW())
                ");

                $insert->execute([
                    $plantId,
                    $status,
                    $quantity
                ]);
            }

            // Catat pergerakan stok masuk
            $movement = $pdo->prepare("
                INSERT INTO inventory_movements
                (
                    plant_type_id,
                    sale_item_id,
                    movement_type,
                    status,
                    quantity,
                    note,
                    occurred_at
                )
                VALUES (?, NULL, 'IN', ?, ?, ?, NOW())
            ");

            $movement->execute([
                $plantId,
                $status,
                $quantity,
                $note !== '' ? $note : 'Stok tanaman masuk'
            ]);

            $pdo->commit();

            $success = 'Stok tanaman berhasil ditambahkan.';

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Terjadi kesalahan saat menyimpan stok.';
        }
    }
}

// Ambil daftar tanaman aktif
$plants = $pdo->query("
    SELECT id, type_name
    FROM plant_types
    WHERE is_active = 1
    ORDER BY type_name ASC
")->fetchAll();

// Ambil ringkasan inventaris
$inventories = $pdo->query("
    SELECT
        p.id,
        p.type_name,
        p.current_price,

        COALESCE(SUM(
            CASE WHEN i.status = 'quarantine'
            THEN i.quantity ELSE 0 END
        ), 0) AS quarantine,

        COALESCE(SUM(
            CASE WHEN i.status = 'cultivation'
            THEN i.quantity ELSE 0 END
        ), 0) AS cultivation,

        COALESCE(SUM(
            CASE WHEN i.status = 'ready'
            THEN i.quantity ELSE 0 END
        ), 0) AS ready

    FROM plant_types p

    LEFT JOIN inventories i
        ON p.id = i.plant_type_id

    WHERE p.is_active = 1

    GROUP BY p.id, p.type_name, p.current_price

    ORDER BY p.type_name ASC
")->fetchAll();

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Pengelolaan Stok | Aroid Plant Nursery</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="../assets/css/style.css" rel="stylesheet">
</head>

<body>

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="fw-bold">Pengelolaan Stok</h2>

            <p class="text-secondary">
                Pantau dan catat jumlah tanaman berdasarkan status.
            </p>
        </div>

        <a href="transfer.php" class="btn btn-outline-success">
            <i class="bi bi-arrow-left-right"></i>
            Pindahkan Stok
        </a>

    </div>

    <?php if (isset($_GET['transfer'])): ?>

        <div class="alert alert-success">
            Pemindahan stok berhasil dilakukan.
        </div>

    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= e($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <!-- Form stok masuk -->

    <div class="card border-0 shadow-sm rounded-4 mb-4">

        <div class="card-body p-4">

            <h5 class="fw-bold mb-3">Catat Stok Masuk</h5>

            <form method="POST">

                <input type="hidden"
                       name="csrf_token"
                       value="<?= e(csrfToken()) ?>">

                <div class="row g-3">

                    <div class="col-md-6">

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

                    <div class="col-md-3">

                        <label class="form-label">
                            Jumlah
                        </label>

                        <input type="number"
                               name="quantity"
                               class="form-control"
                               min="1"
                               required>

                    </div>

                    <div class="col-md-3">

                        <label class="form-label">
                            Status
                        </label>

                        <select name="status"
                                class="form-select"
                                required>

                            <option value="">
                                Pilih status
                            </option>

                            <?php foreach ($statuses as $key => $label): ?>

                                <option value="<?= $key ?>">
                                    <?= $label ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="col-12">

                        <label class="form-label">
                            Catatan (opsional)
                        </label>

                        <textarea name="note"
                                  class="form-control"
                                  rows="2"
                                  placeholder="Contoh: Pembelian tanaman baru dari pemasok"></textarea>

                    </div>

                    <div class="col-12">

                        <button type="submit"
                                class="btn btn-success">

                            + Simpan Stok Masuk

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>

    <!-- Tabel inventaris -->

    <div class="card border-0 shadow-sm rounded-4">

        <div class="card-body p-4">

            <h5 class="fw-bold mb-3">
                Ringkasan Inventaris
            </h5>

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>
                        <tr>
                            <th>Tanaman</th>
                            <th>Karantina</th>
                            <th>Budidaya</th>
                            <th>Siap Jual</th>
                            <th>Total</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (empty($inventories)): ?>

                        <tr>
                            <td colspan="5" class="text-center">
                                Belum ada data inventaris.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($inventories as $item): ?>

                            <?php
                            $total =
                                (int) $item['quarantine'] +
                                (int) $item['cultivation'] +
                                (int) $item['ready'];
                            ?>

                            <tr>

                                <td class="fw-semibold">
                                    <?= e($item['type_name']) ?>
                                </td>

                                <td>
                                    <?= $item['quarantine'] ?>
                                </td>

                                <td>
                                    <?= $item['cultivation'] ?>
                                </td>

                                <td>
                                    <?= $item['ready'] ?>
                                </td>

                                <td class="fw-bold">
                                    <?= $total ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <a href="../index.php" class="btn btn-light mt-3">
                Kembali ke Dashboard
            </a>

        </div>

    </div>

</div>

</body>
</html>