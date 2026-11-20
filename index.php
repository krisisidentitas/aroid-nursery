<?php

declare(strict_types=1);

date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

/* ---------- Sapaan dan tanggal ---------- */

function sapaan(): string
{
    $jam = (int) date('G');

    return match (true) {
        $jam < 11 => 'Selamat pagi',
        $jam < 15 => 'Selamat siang',
        $jam < 18 => 'Selamat sore',
        default   => 'Selamat malam',
    };
}

$hari = [
    'Minggu', 'Senin', 'Selasa', 'Rabu',
    'Kamis', 'Jumat', 'Sabtu'
];

$bulan = [
    '', 'Januari', 'Februari', 'Maret',
    'April', 'Mei', 'Juni', 'Juli',
    'Agustus', 'September', 'Oktober',
    'November', 'Desember'
];

$tanggal = $hari[(int) date('w')]
    . ', ' . date('j')
    . ' ' . $bulan[(int) date('n')]
    . ' ' . date('Y');

/* ---------- Informasi pemilik ---------- */

// Belum ada tabel pemilik pada database.
$pemilik = [
    'nama' => 'Pemilik',
    'inisial' => 'P'
];

/* ---------- Navigasi ---------- */

$menu = [
    ['Dashboard', 'bi-grid-1x2', true, 'index.php'],
    ['Inventaris Tanaman', 'bi-flower1', false, 'plants/index.php'],
    ['Riwayat Harga', 'bi-tag', false, 'plants/price.history.php'],
    ['Penjualan', 'bi-bag-check', false, 'sales/index.php'],
    ['Laporan', 'bi-bar-chart', false, 'reports/index.php'],
];

/* ---------- Statistik inventaris ---------- */

// Total jenis tanaman aktif.
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM plant_types
    WHERE is_active = 1
");

$totalJenis = (int) $stmt->fetchColumn();

// Total stok berdasarkan status.
$stmt = $pdo->query("
    SELECT
        i.status,
        COALESCE(SUM(i.quantity), 0) AS total
    FROM inventories i
    INNER JOIN plant_types p
        ON p.id = i.plant_type_id
    WHERE p.is_active = 1
    GROUP BY i.status
");

$stokStatus = [
    'quarantine' => 0,
    'cultivation' => 0,
    'ready' => 0
];

foreach ($stmt->fetchAll() as $row) {
    $stokStatus[$row['status']] = (int) $row['total'];
}

$statistik = [
    [
        'Total jenis tanaman',
        $totalJenis,
        'bi-collection',
        'sage',
        'Jenis aktif di katalog'
    ],
    [
        'Karantina',
        $stokStatus['quarantine'],
        'bi-shield-check',
        'amber',
        'Tanaman baru masuk'
    ],
    [
        'Budidaya',
        $stokStatus['cultivation'],
        'bi-moisture',
        'leaf',
        'Dalam perawatan'
    ],
    [
        'Siap jual',
        $stokStatus['ready'],
        'bi-bag-heart',
        'moss',
        'Tersedia untuk dijual'
    ],
];

/* ---------- Ringkasan penjualan bulan ini ---------- */

$awalBulan = date('Y-m-01 00:00:00');
$awalBulanDepan = date('Y-m-01 00:00:00', strtotime('+1 month'));

$stmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(si.subtotal), 0) AS total,
        COUNT(DISTINCT s.id) AS transaksi
    FROM sales s
    INNER JOIN sale_items si
        ON si.sale_id = s.id
    WHERE s.sale_date >= :awal
      AND s.sale_date < :akhir
");

$stmt->execute([
    'awal' => $awalBulan,
    'akhir' => $awalBulanDepan
]);

$hasilPenjualan = $stmt->fetch();

$totalPenjualan = (int) $hasilPenjualan['total'];
$totalTransaksi = (int) $hasilPenjualan['transaksi'];

$rataRata = $totalTransaksi > 0
    ? $totalPenjualan / $totalTransaksi
    : 0;

/* ---------- Grafik penjualan 7 hari terakhir ---------- */

$awalGrafik = date('Y-m-d 00:00:00', strtotime('-6 days'));
$akhirGrafik = date('Y-m-d 00:00:00', strtotime('+1 day'));

$stmt = $pdo->prepare("
    SELECT
        DATE(s.sale_date) AS tanggal,
        SUM(si.subtotal) AS total
    FROM sales s
    INNER JOIN sale_items si
        ON si.sale_id = s.id
    WHERE s.sale_date >= :awal
      AND s.sale_date < :akhir
    GROUP BY DATE(s.sale_date)
    ORDER BY tanggal ASC
");

$stmt->execute([
    'awal' => $awalGrafik,
    'akhir' => $akhirGrafik
]);

$hasilGrafik = [];

foreach ($stmt->fetchAll() as $row) {
    $hasilGrafik[$row['tanggal']] =
        (float) $row['total'] / 1000000;
}

$singkatanHari = [
    'Min', 'Sen', 'Sel', 'Rab',
    'Kam', 'Jum', 'Sab'
];

$grafik = [];

for ($i = 6; $i >= 0; $i--) {
    $tanggalGrafik = date(
        'Y-m-d',
        strtotime("-{$i} days")
    );

    $grafik[] = [
        'label' => $singkatanHari[
            (int) date('w', strtotime($tanggalGrafik))
        ],
        'nilai' => $hasilGrafik[$tanggalGrafik] ?? 0
    ];
}

$penjualan = [
    'total' => $totalPenjualan,
    'transaksi' => $totalTransaksi,
    'rata2' => $rataRata,
    'grafik' => $grafik
];

/* ---------- Tanaman terbaru ---------- */

$stmt = $pdo->query("
    SELECT
        p.id,
        p.type_name,
        p.photo,
        p.current_price,
        COALESCE(SUM(i.quantity), 0) AS total_stok,
        COALESCE(SUM(
            CASE WHEN i.status = 'quarantine'
            THEN i.quantity ELSE 0 END
        ), 0) AS stok_karantina,
        COALESCE(SUM(
            CASE WHEN i.status = 'cultivation'
            THEN i.quantity ELSE 0 END
        ), 0) AS stok_budidaya,
        COALESCE(SUM(
            CASE WHEN i.status = 'ready'
            THEN i.quantity ELSE 0 END
        ), 0) AS stok_siap_jual
    FROM plant_types p
    LEFT JOIN inventories i
        ON i.plant_type_id = p.id
    WHERE p.is_active = 1
    GROUP BY
        p.id,
        p.type_name,
        p.photo,
        p.current_price,
        p.created_at
    ORDER BY p.created_at DESC
    LIMIT 5
");

$tanaman = [];

foreach ($stmt->fetchAll() as $row) {

    $stokKarantina = (int) $row['stok_karantina'];
    $stokBudidaya = (int) $row['stok_budidaya'];
    $stokSiapJual = (int) $row['stok_siap_jual'];

    // Status utama mengikuti tahap dengan jumlah stok terbesar.
    $jumlahStatus = [
        'Karantina' => $stokKarantina,
        'Budidaya' => $stokBudidaya,
        'Siap jual' => $stokSiapJual
    ];

    $statusUtama = 'Belum ada stok';

    if ((int) $row['total_stok'] > 0) {
        $statusUtama = array_search(
            max($jumlahStatus),
            $jumlahStatus
        );
    }

    $tanaman[] = [
        'nama' => $row['type_name'],
        'foto' => $row['photo'],
        'stok' => (int) $row['total_stok'],
        'status' => $statusUtama,
        'harga' => (int) $row['current_price']
    ];
}

$statusKelas = [
    'Karantina' => 'amber',
    'Budidaya' => 'leaf',
    'Siap jual' => 'moss',
    'Belum ada stok' => 'sage'
];

/* ---------- Aktivitas terbaru ---------- */

$stmt = $pdo->query("
    SELECT
        m.movement_type,
        m.status,
        m.quantity,
        m.note,
        m.occurred_at,
        p.type_name,
        s.sales_channel
    FROM inventory_movements m
    INNER JOIN plant_types p
        ON p.id = m.plant_type_id
    LEFT JOIN sale_items si
        ON si.id = m.sale_item_id
    LEFT JOIN sales s
        ON s.id = si.sale_id
    ORDER BY m.occurred_at DESC, m.id DESC
    LIMIT 5
");

$aktivitas = [];

$namaStatus = [
    'quarantine' => 'karantina',
    'cultivation' => 'budidaya',
    'ready' => 'siap jual'
];

$namaChannel = [
    'direct' => 'penjualan langsung',
    'live' => 'live',
    'social_media' => 'media sosial'
];

foreach ($stmt->fetchAll() as $row) {

    if ($row['movement_type'] === 'IN') {
        $ikon = 'bi-box-arrow-in-down';
        $warna = 'amber';
        $judul = 'Tanaman masuk';

        $isi = (int) $row['quantity']
            . ' ' . $row['type_name']
            . ' masuk ' . $namaStatus[$row['status']];
    } else {
        $ikon = 'bi-bag-check';
        $warna = 'moss';
        $judul = 'Penjualan';

        $channel = $namaChannel[$row['sales_channel']]
            ?? 'penjualan';

        $isi = (int) $row['quantity']
            . ' ' . $row['type_name']
            . ' terjual via ' . $channel;
    }

    if (!empty($row['note'])) {
        $isi .= ' — ' . $row['note'];
    }

    $aktivitas[] = [
        $ikon,
        $warna,
        $judul,
        $isi,
        date('d M, H:i', strtotime($row['occurred_at']))
    ];
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard | Aroid Plant Nursery</title>

    <!-- Font, Bootstrap 5, Bootstrap Icons (CDN: butuh internet saat dijalankan) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- ===== SIDEBAR ===== -->
<aside class="sidebar" id="sidebar" aria-label="Navigasi utama">
    <div class="brand">
        <span class="brand-logo"><i class="bi bi-flower2"></i></span>
        <span class="brand-name">Aroid Plant<br>Nursery</span>
    </div>

    <nav class="nav-menu">
        <?php foreach ($menu as [$nama, $ikon, $aktif, $url]): ?>

            <a href="<?= htmlspecialchars($url) ?>"
                class="nav-item-link<?= $aktif ? ' active' : '' ?>">

                <i class="bi <?= $ikon ?>"></i>
                <span><?= htmlspecialchars($nama) ?></span>

            </a>

        <?php endforeach; ?>
    </nav>

</aside>
<div class="sidebar-overlay" id="overlay"></div>

<!-- ===== KONTEN UTAMA ===== -->
<div class="main">

    <!-- Header -->
    <header class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn-menu" id="btnMenu" type="button" aria-label="Buka menu">
                <i class="bi bi-list"></i>
            </button>
            <div>
                <h1 class="page-title">Dashboard</h1>
                <p class="page-sub"><?= sapaan() ?>, <?= htmlspecialchars($pemilik['nama']) ?>. Ini ringkasan nursery hari ini.</p>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="date-chip d-none d-md-inline-flex"><i class="bi bi-calendar3"></i><?= $tanggal ?></span>
            <span class="avatar" title="<?= htmlspecialchars($pemilik['nama']) ?>"><?= $pemilik['inisial'] ?></span>
        </div>
    </header>

    <main class="content">

        <!-- Statistik inventaris -->
        <section class="row g-3 mb-4" aria-label="Statistik inventaris">
            <?php foreach ($statistik as [$label, $angka, $ikon, $warna, $ket]): ?>
                <div class="col-6 col-xl-3">
                    <div class="card-box stat">
                        <span class="icon-badge tone-<?= $warna ?>"><i class="bi <?= $ikon ?>"></i></span>
                        <div class="stat-value" data-count="<?= $angka ?>"><?= $angka ?></div>
                        <div class="stat-label"><?= $label ?></div>
                        <div class="stat-note"><?= $ket ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>

        <!-- Ringkasan penjualan -->
        <section class="card-box mb-4" aria-label="Ringkasan penjualan">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h2 class="section-title">Penjualan bulan ini</h2>
                    <div class="sales-total"><?= rupiah($penjualan['total']) ?></div>
                    <div class="d-flex gap-4 mt-3">
                        <div>
                            <div class="mini-value"><?= $penjualan['transaksi'] ?></div>
                            <div class="mini-label">Transaksi</div>
                        </div>
                        <div>
                            <div class="mini-value"><?= rupiah($penjualan['rata2']) ?></div>
                            <div class="mini-label">Rata-rata per transaksi</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <h2 class="section-title">Penjualan 7 hari terakhir <small>(juta rupiah)</small></h2>
                    <!-- Grafik digambar oleh app.js dari atribut data-chart -->
                    <div id="salesChart" class="chart"
                         data-chart='<?= htmlspecialchars(json_encode($penjualan['grafik']), ENT_QUOTES) ?>'></div>
                </div>
            </div>
        </section>

        <div class="row g-4">

            <!-- Tanaman terbaru -->
            <section class="col-xl-7" aria-label="Tanaman terbaru">
                <div class="card-box h-100">
                    <h2 class="section-title">Tanaman terbaru</h2>
                    <div class="table-responsive">
                        <table class="table plant-table align-middle mb-0">
                            <thead>
                                <tr><th>Tanaman</th><th>Stok</th><th>Status utama</th><th class="text-end">Harga jual</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($tanaman as $item): ?>
                                <?php
                                    $nama = $item['nama'];
                                    $foto = $item['foto'];
                                    $stok = $item['stok'];
                                    $status = $item['status'];
                                    $harga = $item['harga'];

                                    $adaFoto = $foto
                                        && file_exists(__DIR__ . '/assets/img/plants/' . $foto);
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <?php if ($adaFoto): ?>
                                                <img class="plant-thumb" src="assets/img/plants/<?= htmlspecialchars($foto) ?>" alt="<?= htmlspecialchars($nama) ?>">
                                            <?php else: ?>
                                                <span class="plant-thumb placeholder"><i class="bi bi-flower3"></i></span>
                                            <?php endif; ?>
                                            <span class="plant-name"><?= htmlspecialchars($nama) ?></span>
                                        </div>
                                    </td>
                                    <td><?= $stok ?> pcs</td>
                                    <td>
                                        <span class="status-pill tone-<?= $statusKelas[$status] ?>">
                                            <?= e($status) ?>
                                        </span>
                                    </td>
                                    <td class="text-end fw-semibold"><?= rupiah($harga) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Aktivitas terbaru -->
            <section class="col-xl-5" aria-label="Aktivitas terbaru">
                <div class="card-box h-100">
                    <h2 class="section-title">Aktivitas terbaru</h2>
                    <ul class="activity">
                        <?php foreach ($aktivitas as [$ikon, $warna, $judul, $isi, $waktu]): ?>
                            <li>
                                <span class="icon-badge small tone-<?= $warna ?>"><i class="bi <?= $ikon ?>"></i></span>
                                <div>
                                    <div class="act-title"><?= $judul ?></div>
                                    <div class="act-text"><?= htmlspecialchars($isi) ?></div>
                                    <div class="act-time"><?= $waktu ?></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>
        </div>

        <footer class="foot">Aroid Plant Nursery Management System &middot; Bogor</footer>
    </main>
</div>

<!-- Notifikasi (dipakai tombol yang belum aktif) -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="appToast" class="toast align-items-center text-bg-success border-0" role="status" aria-live="polite">
        <div class="d-flex">
            <div class="toast-body" id="toastMsg"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Tutup"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>