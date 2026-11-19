<?php
/**
 * Aroid Plant Nursery - Dashboard Pemilik
 * Tahap 1: tampilan saja (data dummy, tanpa database dan login).
 */
declare(strict_types=1);
date_default_timezone_set('Asia/Jakarta');

/* ---------- Helper ---------- */
function rupiah(int $n): string
{
    return 'Rp ' . number_format($n, 0, ',', '.');
}

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

$hari  = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
          'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$tanggal = $hari[(int) date('w')] . ', ' . date('j') . ' ' . $bulan[(int) date('n')] . ' ' . date('Y');

/* ---------- Data dummy ---------- */
$pemilik = ['nama' => 'Pak Raka', 'inisial' => 'PR'];

$menu = [
    ['Dashboard', 'bi-grid-1x2', true, 'index.php'],
    ['Inventaris Tanaman', 'bi-flower1', false, 'inventory/index.php'],
    ['Riwayat Harga', 'bi-tag', false, '#'],
    ['Penjualan', 'bi-bag-check', false, '#'],
    ['Laporan', 'bi-bar-chart', false, '#'],
    ['Pengaturan', 'bi-gear', false, '#'],
];

$statistik = [
    ['Total jenis tanaman', 24,  'bi-collection',  'sage',  'Jenis aktif di katalog'],
    ['Karantina',           38,  'bi-shield-check', 'amber', 'Tanaman baru masuk'],
    ['Budidaya',            112, 'bi-moisture',    'leaf',  'Dalam perawatan'],
    ['Siap jual',           67,  'bi-bag-heart',   'moss',  'Tersedia untuk dijual'],
];

$penjualan = [
    'total'     => 18450000,
    'transaksi' => 27,
    'rata2'     => 683333,
    // Data grafik 7 hari terakhir (juta rupiah)
    'grafik'    => [
        ['label' => 'Sen', 'nilai' => 1.2],
        ['label' => 'Sel', 'nilai' => 0.8],
        ['label' => 'Rab', 'nilai' => 2.4],
        ['label' => 'Kam', 'nilai' => 1.6],
        ['label' => 'Jum', 'nilai' => 3.1],
        ['label' => 'Sab', 'nilai' => 4.2],
        ['label' => 'Min', 'nilai' => 2.7],
    ],
];

// 'foto' => nama file di assets/img/plants/ (opsional, boleh null)
$tanaman = [
    ['Monstera Albo',             null, 12, 'Siap jual', 4,  3500000],
    ['Philodendron Pink Princess', null, 11, 'Siap jual', 6,  1250000],
    ['Anthurium Crystallinum',    null, 9,  'Karantina', 4,  850000],
    ['Alocasia Frydek',           null, 14, 'Budidaya',  8,  450000],
    ['Syngonium Neon Robusta',    null, 20, 'Budidaya',  12, 125000],
];

$statusKelas = ['Karantina' => 'amber', 'Budidaya' => 'leaf', 'Siap jual' => 'moss'];

$aktivitas = [
    ['bi-box-arrow-in-down', 'amber', 'Tanaman masuk',    '5 Anthurium Crystallinum masuk karantina', 'Hari ini, 09.15'],
    ['bi-bag-check',         'moss',  'Penjualan',        '2 Monstera Albo terjual via live Instagram', 'Hari ini, 08.40'],
    ['bi-arrow-left-right',  'leaf',  'Perubahan status', '6 Alocasia Frydek pindah ke budidaya', 'Kemarin, 16.20'],
    ['bi-tag',               'sage',  'Perubahan harga',  'Pink Princess naik ke Rp 1.250.000', 'Kemarin, 11.05'],
    ['bi-bag-check',         'moss',  'Penjualan',        '1 Syngonium Neon Robusta terjual langsung', '16 Nov, 14.30'],
];
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

    <button type="button" class="btn-logout" id="btnLogout">
        <i class="bi bi-box-arrow-left"></i><span>Keluar</span>
    </button>
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
                            <?php foreach ($tanaman as [$nama, $foto, $stok, $status, , $harga]): ?>
                                <?php $adaFoto = $foto && file_exists(__DIR__ . '/assets/img/plants/' . $foto); ?>
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
                                    <td><span class="status-pill tone-<?= $statusKelas[$status] ?>"><?= $status ?></span></td>
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