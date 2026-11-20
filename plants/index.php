<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

$stmt = $pdo->query("
    SELECT
        id,
        type_name,
        current_price,
        is_active,
        created_at
    FROM plant_types
    ORDER BY id DESC
");

$plants = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Inventaris Tanaman | Aroid Plant Nursery</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="../assets/css/style.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
      rel="stylesheet">
</head>

<body>

<div class="container py-5">

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold">Inventaris Tanaman</h2>
            <p class="text-secondary mb-0">
                Kelola jenis tanaman, persediaan, dan harga jual.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="../inventory/index.php"
            class="btn btn-outline-success">
                <i class="bi bi-box-seam me-1"></i>
                Pengelolaan Stok
            </a>

            <a href="../plants/price.history.php"
            class="btn btn-outline-success">
                <i class="bi bi-clock-history me-1"></i>
                Riwayat Harga
            </a>

            <a href="create.php"
            class="btn btn-success">
                <i class="bi bi-plus-lg me-1"></i>
                Tambah Tanaman
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body">

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Nama Tanaman</th>
                            <th>Harga Jual</th>
                            <th>Status</th>
                            <th>Tanggal Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (empty($plants)): ?>

                        <tr>
                            <td colspan="6" class="text-center py-4">
                                Belum ada data tanaman.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($plants as $index => $plant): ?>

                            <tr>
                                <td><?= $index + 1 ?></td>

                                <td class="fw-semibold">
                                    <?= e($plant['type_name']) ?>
                                </td>

                                <td>
                                    <?= rupiah($plant['current_price']) ?>
                                </td>

                                <td>
                                    <?php if ($plant['is_active']): ?>

                                        <span class="badge bg-success">
                                            Aktif
                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-secondary">
                                            Nonaktif
                                        </span>

                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= e($plant['created_at']) ?>
                                </td>

                                <td>
                                    <a href="edit.php?id=<?= $plant['id'] ?>"
                                       class="btn btn-sm btn-outline-success">
                                        Edit
                                    </a>

                                    <?php if ($plant['is_active']): ?>

                                        <form action="delete.php"
                                              method="POST"
                                              class="d-inline"
                                              onsubmit="return confirm('Nonaktifkan tanaman ini?')">

                                            <input type="hidden"
                                                   name="csrf_token"
                                                   value="<?= e(csrfToken()) ?>">

                                            <input type="hidden"
                                                   name="id"
                                                   value="<?= $plant['id'] ?>">

                                            <button type="submit"
                                                    class="btn btn-sm btn-outline-danger">
                                                Nonaktifkan
                                            </button>

                                        </form>

                                    <?php endif; ?>
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
