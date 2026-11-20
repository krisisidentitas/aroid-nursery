<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

$error = '';

// Default periode: awal bulan sampai hari ini
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Validasi tanggal
$start = DateTime::createFromFormat('!Y-m-d', $startDate);
$end = DateTime::createFromFormat('!Y-m-d', $endDate);

if (
    !$start ||
    $start->format('Y-m-d') !== $startDate ||
    !$end ||
    $end->format('Y-m-d') !== $endDate
) {
    $error = 'Format tanggal tidak valid.';
    $startDate = date('Y-m-01');
    $endDate = date('Y-m-d');

    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
}

if ($end < $start) {
    $error = 'Tanggal akhir tidak boleh lebih awal dari tanggal awal.';

    $startDate = date('Y-m-01');
    $endDate = date('Y-m-d');

    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
}

// Batas akhir dibuat eksklusif agar seluruh transaksi pada
// tanggal akhir tetap ikut dihitung, termasuk pukul 23:59:59.
$endExclusive = (clone $end)->modify('+1 day')->format('Y-m-d');

$channels = [
    'direct' => 'Penjualan langsung',
    'live' => 'Live',
    'social_media' => 'Media sosial'
];

$params = [
    $startDate . ' 00:00:00',
    $endExclusive . ' 00:00:00'
];

// Ringkasan penjualan
$summaryStmt = $pdo->prepare("
    SELECT
        COUNT(DISTINCT s.id) AS total_transactions,
        COALESCE(SUM(si.quantity), 0) AS total_plants_sold,
        COALESCE(SUM(si.subtotal), 0) AS total_revenue

    FROM sales s

    INNER JOIN sale_items si
        ON s.id = si.sale_id

    WHERE s.sale_date >= ?
      AND s.sale_date < ?
");

$summaryStmt->execute($params);
$summary = $summaryStmt->fetch();

// Rincian transaksi
$detailStmt = $pdo->prepare("
    SELECT
        s.sale_code,
        s.sale_date,
        s.sales_channel,
        p.type_name,
        si.quantity,
        si.unit_price,
        si.subtotal

    FROM sales s

    INNER JOIN sale_items si
        ON s.id = si.sale_id

    INNER JOIN plant_types p
        ON si.plant_type_id = p.id

    WHERE s.sale_date >= ?
      AND s.sale_date < ?

    ORDER BY s.sale_date DESC, s.id DESC
");

$detailStmt->execute($params);
$details = $detailStmt->fetchAll();

// Ringkasan stok saat ini
$inventoryStmt = $pdo->query("
    SELECT
        COALESCE(SUM(
            CASE WHEN status = 'quarantine'
            THEN quantity ELSE 0 END
        ), 0) AS quarantine,

        COALESCE(SUM(
            CASE WHEN status = 'cultivation'
            THEN quantity ELSE 0 END
        ), 0) AS cultivation,

        COALESCE(SUM(
            CASE WHEN status = 'ready'
            THEN quantity ELSE 0 END
        ), 0) AS ready

    FROM inventories
");

$inventory = $inventoryStmt->fetch();

$totalStock =
    (int) $inventory['quarantine'] +
    (int) $inventory['cultivation'] +
    (int) $inventory['ready'];

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Laporan | Aroid Plant Nursery</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="../assets/css/style.css"
          rel="stylesheet">
</head>

<body>

<div class="container py-5">

    <!-- Header -->

    <div class="mb-4">

        <h2 class="fw-bold">Laporan</h2>

        <p class="text-secondary">
            Ringkasan penjualan dan persediaan tanaman.
        </p>

    </div>

    <!-- Filter tanggal -->

    <div class="card border-0 shadow-sm rounded-4 mb-4">

        <div class="card-body p-4">

            <h5 class="fw-bold mb-3">
                Filter Periode Penjualan
            </h5>

            <form method="GET">

                <div class="row g-3 align-items-end">

                    <div class="col-md-4">

                        <label class="form-label">
                            Tanggal Awal
                        </label>

                        <input type="date"
                               name="start_date"
                               class="form-control"
                               value="<?= e($startDate) ?>"
                               required>

                    </div>

                    <div class="col-md-4">

                        <label class="form-label">
                            Tanggal Akhir
                        </label>

                        <input type="date"
                               name="end_date"
                               class="form-control"
                               value="<?= e($endDate) ?>"
                               required>

                    </div>

                    <div class="col-md-4">

                        <button type="submit"
                                class="btn btn-success">
                            Tampilkan Laporan
                        </button>

                    </div>

                </div>

            </form>

            <?php if ($error): ?>

                <div class="alert alert-danger mt-3 mb-0">
                    <?= e($error) ?>
                </div>

            <?php endif; ?>

        </div>

    </div>

    <!-- Ringkasan penjualan -->

    <h5 class="fw-bold mb-3">
        Ringkasan Penjualan
    </h5>

    <div class="row g-3 mb-4">

        <div class="col-md-4">

            <div class="card border-0 shadow-sm rounded-4 h-100">

                <div class="card-body p-4">

                    <p class="text-secondary mb-2">
                        Total Pendapatan
                    </p>

                    <h4 class="fw-bold text-success">
                        <?= rupiah($summary['total_revenue']) ?>
                    </h4>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card border-0 shadow-sm rounded-4 h-100">

                <div class="card-body p-4">

                    <p class="text-secondary mb-2">
                        Jumlah Transaksi
                    </p>

                    <h4 class="fw-bold">
                        <?= number_format((int) $summary['total_transactions'], 0, ',', '.') ?>
                    </h4>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card border-0 shadow-sm rounded-4 h-100">

                <div class="card-body p-4">

                    <p class="text-secondary mb-2">
                        Tanaman Terjual
                    </p>

                    <h4 class="fw-bold">
                        <?= number_format((int) $summary['total_plants_sold'], 0, ',', '.') ?>
                        <small class="fs-6 text-secondary">pcs</small>
                    </h4>

                </div>

            </div>

        </div>

    </div>

    <!-- Rincian penjualan -->

    <div class="card border-0 shadow-sm rounded-4 mb-4">

        <div class="card-body p-4">

            <h5 class="fw-bold mb-3">
                Rincian Penjualan
            </h5>

            <p class="text-secondary small">
                Periode:
                <?= e($startDate) ?>
                sampai
                <?= e($endDate) ?>
            </p>

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Kode Transaksi</th>
                            <th>Tanggal</th>
                            <th>Tanaman</th>
                            <th>Kanal</th>
                            <th>Jumlah</th>
                            <th>Harga Satuan</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (empty($details)): ?>

                        <tr>
                            <td colspan="8"
                                class="text-center py-4">
                                Tidak ada transaksi pada periode ini.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($details as $index => $item): ?>

                            <tr>

                                <td>
                                    <?= $index + 1 ?>
                                </td>

                                <td>
                                    <?= e($item['sale_code']) ?>
                                </td>

                                <td>
                                    <?= e(date(
                                        'd/m/Y H:i',
                                        strtotime($item['sale_date'])
                                    )) ?>
                                </td>

                                <td class="fw-semibold">
                                    <?= e($item['type_name']) ?>
                                </td>

                                <td>
                                    <?= e(
                                        $channels[$item['sales_channel']] ?? '-'
                                    ) ?>
                                </td>

                                <td>
                                    <?= (int) $item['quantity'] ?>
                                </td>

                                <td>
                                    <?= rupiah($item['unit_price']) ?>
                                </td>

                                <td class="fw-semibold">
                                    <?= rupiah($item['subtotal']) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <!-- Ringkasan persediaan -->

    <div class="card border-0 shadow-sm rounded-4">

        <div class="card-body p-4">

            <h5 class="fw-bold mb-1">
                Ringkasan Persediaan
            </h5>

            <p class="text-secondary small">
                Kondisi persediaan saat ini, bukan berdasarkan periode penjualan.
            </p>

            <div class="row g-3 mt-2">

                <div class="col-md-3">

                    <div class="bg-light rounded-3 p-3">

                        <p class="text-secondary mb-1">
                            Karantina
                        </p>

                        <h4 class="fw-bold mb-0">
                            <?= (int) $inventory['quarantine'] ?>
                        </h4>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="bg-light rounded-3 p-3">

                        <p class="text-secondary mb-1">
                            Budidaya
                        </p>

                        <h4 class="fw-bold mb-0">
                            <?= (int) $inventory['cultivation'] ?>
                        </h4>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="bg-light rounded-3 p-3">

                        <p class="text-secondary mb-1">
                            Siap Jual
                        </p>

                        <h4 class="fw-bold mb-0">
                            <?= (int) $inventory['ready'] ?>
                        </h4>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="bg-success bg-opacity-10 rounded-3 p-3">

                        <p class="text-secondary mb-1">
                            Total Persediaan
                        </p>

                        <h4 class="fw-bold text-success mb-0">
                            <?= $totalStock ?>
                        </h4>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- Navigasi -->

    <div class="mt-4 d-flex flex-wrap gap-2">

        <a href="../index.php"
           class="btn btn-light">
            Kembali ke Dashboard
        </a>

        <a href="../sales/index.php"
           class="btn btn-outline-success">
            Halaman Penjualan
        </a>

    </div>

</div>

</body>
</html>
