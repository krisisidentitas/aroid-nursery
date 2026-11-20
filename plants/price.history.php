<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

/* ---------- Ambil riwayat harga ---------- */

$stmt = $pdo->query("
    SELECT
        ph.id,
        ph.old_price,
        ph.new_price,
        ph.note,
        ph.changed_at,
        pt.type_name
    FROM price_histories ph
    INNER JOIN plant_types pt
        ON pt.id = ph.plant_type_id
    ORDER BY ph.changed_at DESC, ph.id DESC
");

$histories = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Riwayat Harga | Aroid Plant Nursery</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="../assets/css/style.css" rel="stylesheet">
</head>

<body>

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold">Riwayat Harga</h2>
            <p class="text-secondary">
                Catatan perubahan harga jual tanaman.
            </p>
        </div>

        <a href="index.php" class="btn btn-success">
            Inventaris Tanaman
        </a>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body">

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Nama Tanaman</th>
                            <th>Harga Lama</th>
                            <th>Harga Baru</th>
                            <th>Perubahan</th>
                            <th>Tanggal</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (empty($histories)): ?>

                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="fw-semibold">
                                    Belum ada riwayat perubahan harga.
                                </div>
                                <small class="text-secondary">
                                    Riwayat akan muncul setelah harga tanaman diubah.
                                </small>
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($histories as $index => $history): ?>

                            <?php
                                $selisih = (int) $history['new_price']
                                    - (int) $history['old_price'];
                            ?>

                            <tr>
                                <td><?= $index + 1 ?></td>

                                <td class="fw-semibold">
                                    <?= e($history['type_name']) ?>
                                </td>

                                <td>
                                    <?= rupiah($history['old_price']) ?>
                                </td>

                                <td class="fw-semibold">
                                    <?= rupiah($history['new_price']) ?>
                                </td>

                                <td>
                                    <?php if ($selisih > 0): ?>

                                        <span class="badge bg-success">
                                            Naik <?= rupiah($selisih) ?>
                                        </span>

                                    <?php elseif ($selisih < 0): ?>

                                        <span class="badge bg-danger">
                                            Turun <?= rupiah(abs($selisih)) ?>
                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-secondary">
                                            Tidak berubah
                                        </span>

                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= e(date(
                                        'd-m-Y H:i',
                                        strtotime($history['changed_at'])
                                    )) ?>
                                </td>

                                <td>
                                    <?= e($history['note'] ?? '-') ?>
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
