<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

$error = '';
$success = isset($_GET['success']);

// Kanal penjualan sesuai ENUM database
$channels = [
    'direct' => 'Penjualan langsung',
    'live' => 'Live',
    'social_media' => 'Media sosial'
];

// Proses transaksi
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

    $channel = $_POST['sales_channel'] ?? '';
    $saleDateInput = $_POST['sale_date'] ?? '';
    $note = trim($_POST['note'] ?? '');

    if (
        !$plantId ||
        !$quantity ||
        $quantity < 1 ||
        !isset($channels[$channel])
    ) {
        $error = 'Periksa kembali data penjualan.';

    } else {

        // Validasi tanggal
        $saleDate = $saleDateInput
            ? DateTime::createFromFormat('!Y-m-d\TH:i', $saleDateInput)
            : new DateTime();

        if (
            !$saleDate ||
            ($saleDateInput && $saleDate->format('Y-m-d\TH:i') !== $saleDateInput)
        ) {
            $error = 'Format tanggal penjualan tidak valid.';

        } else {

            try {

                $pdo->beginTransaction();

                // Ambil jenis tanaman dan harga saat transaksi
                $plantStmt = $pdo->prepare("
                    SELECT id, type_name, current_price
                    FROM plant_types
                    WHERE id = ?
                      AND is_active = 1
                    FOR UPDATE
                ");

                $plantStmt->execute([$plantId]);
                $plant = $plantStmt->fetch();

                if (!$plant) {
                    throw new RuntimeException(
                        'Tanaman tidak ditemukan atau sudah nonaktif.'
                    );
                }

                // Kunci stok siap jual
                $stockStmt = $pdo->prepare("
                    SELECT id, quantity
                    FROM inventories
                    WHERE plant_type_id = ?
                      AND status = 'ready'
                    FOR UPDATE
                ");

                $stockStmt->execute([$plantId]);
                $stock = $stockStmt->fetch();

                if (!$stock || (int) $stock['quantity'] < $quantity) {
                    throw new RuntimeException(
                        'Stok siap jual tidak mencukupi.'
                    );
                }

                $unitPrice = (int) $plant['current_price'];
                $subtotal = $unitPrice * $quantity;

                // Buat kode transaksi
                $saleCode = 'APN-' .
                    date('Ymd') . '-' .
                    strtoupper(bin2hex(random_bytes(4)));

                // 1. Simpan transaksi utama
                $saleStmt = $pdo->prepare("
                    INSERT INTO sales
                    (sale_code, sales_channel, sale_date, note)
                    VALUES (?, ?, ?, ?)
                ");

                $saleStmt->execute([
                    $saleCode,
                    $channel,
                    $saleDate->format('Y-m-d H:i:s'),
                    $note !== '' ? $note : null
                ]);

                $saleId = (int) $pdo->lastInsertId();

                // 2. Simpan detail transaksi
                $itemStmt = $pdo->prepare("
                    INSERT INTO sale_items
                    (
                        sale_id,
                        plant_type_id,
                        quantity,
                        unit_price,
                        subtotal
                    )
                    VALUES (?, ?, ?, ?, ?)
                ");

                $itemStmt->execute([
                    $saleId,
                    $plantId,
                    $quantity,
                    $unitPrice,
                    $subtotal
                ]);

                $saleItemId = (int) $pdo->lastInsertId();

                // 3. Kurangi stok siap jual
                $updateStock = $pdo->prepare("
                    UPDATE inventories
                    SET quantity = quantity - ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");

                $updateStock->execute([
                    $quantity,
                    $stock['id']
                ]);

                // 4. Catat pergerakan stok keluar
                $movementStmt = $pdo->prepare("
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
                    VALUES (?, ?, 'SALE', 'ready', ?, ?, NOW())
                ");

                $movementStmt->execute([
                    $plantId,
                    $saleItemId,
                    $quantity,
                    'Penjualan melalui ' . $channels[$channel]
                ]);

                // Semua berhasil
                $pdo->commit();

                header('Location: index.php?success=1');
                exit;

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                if ($e instanceof RuntimeException) {
                    $error = $e->getMessage();
                } else {
                    error_log($e->getMessage());
                    $error = 'Transaksi gagal disimpan. Periksa struktur database.';
                }
            }
        }
    }
}

// Daftar tanaman yang mempunyai stok siap jual
$plants = $pdo->query("
    SELECT
        p.id,
        p.type_name,
        p.current_price,
        i.quantity AS stock

    FROM plant_types p

    INNER JOIN inventories i
        ON p.id = i.plant_type_id

    WHERE p.is_active = 1
      AND i.status = 'ready'
      AND i.quantity > 0

    ORDER BY p.type_name ASC
")->fetchAll();

// Riwayat penjualan
$sales = $pdo->query("
    SELECT
        s.id,
        s.sale_code,
        s.sales_channel,
        s.sale_date,
        p.type_name,
        si.quantity,
        si.unit_price,
        si.subtotal

    FROM sales s

    INNER JOIN sale_items si
        ON s.id = si.sale_id

    INNER JOIN plant_types p
        ON si.plant_type_id = p.id

    ORDER BY s.sale_date DESC, s.id DESC

    LIMIT 50
")->fetchAll();

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Penjualan | Aroid Plant Nursery</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="../assets/css/style.css"
          rel="stylesheet">

</head>

<body>

<div class="container py-5">

    <div class="mb-4">

        <h2 class="fw-bold">Penjualan</h2>

        <p class="text-secondary">
            Catat transaksi dan pantau riwayat penjualan nursery.
        </p>

    </div>

    <?php if ($success): ?>

        <div class="alert alert-success">
            Penjualan berhasil dicatat dan stok telah diperbarui.
        </div>

    <?php endif; ?>

    <?php if ($error): ?>

        <div class="alert alert-danger">
            <?= e($error) ?>
        </div>

    <?php endif; ?>

    <!-- Form penjualan -->

    <div class="card border-0 shadow-sm rounded-4 mb-4">

        <div class="card-body p-4">

            <h5 class="fw-bold mb-3">
                Catat Penjualan
            </h5>

            <form method="POST">

                <input type="hidden"
                       name="csrf_token"
                       value="<?= e(csrfToken()) ?>">

                <div class="row g-3">

                    <div class="col-md-6">

                        <label class="form-label">
                            Tanaman
                        </label>

                        <select name="plant_type_id"
                                id="plantSelect"
                                class="form-select"
                                required>

                            <option value="">
                                Pilih tanaman
                            </option>

                            <?php foreach ($plants as $plant): ?>

                                <option
                                    value="<?= $plant['id'] ?>"
                                    data-price="<?= $plant['current_price'] ?>"
                                    data-stock="<?= $plant['stock'] ?>">

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
                               id="quantity"
                               class="form-control"
                               min="1"
                               required>

                    </div>

                    <div class="col-md-3">

                        <label class="form-label">
                            Kanal Penjualan
                        </label>

                        <select name="sales_channel"
                                class="form-select"
                                required>

                            <?php foreach ($channels as $key => $label): ?>

                                <option value="<?= $key ?>">
                                    <?= e($label) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            Tanggal Penjualan
                        </label>

                        <input type="datetime-local"
                               name="sale_date"
                               class="form-control"
                               value="<?= date('Y-m-d\TH:i') ?>"
                               required>

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            Harga per Tanaman
                        </label>

                        <input type="text"
                               id="unitPrice"
                               class="form-control"
                               value="Rp 0"
                               readonly>

                    </div>

                    <div class="col-12">

                        <label class="form-label">
                            Catatan (opsional)
                        </label>

                        <textarea name="note"
                                  class="form-control"
                                  rows="2"></textarea>

                    </div>

                    <div class="col-12">

                        <div class="bg-light rounded-3 p-3">

                            <div class="d-flex justify-content-between">

                                <span>Stok tersedia</span>

                                <strong id="availableStock">-</strong>

                            </div>

                            <div class="d-flex justify-content-between mt-2">

                                <span>Estimasi subtotal</span>

                                <strong id="subtotal">Rp 0</strong>

                            </div>

                        </div>

                    </div>

                    <div class="col-12">

                        <button type="submit"
                                class="btn btn-success">

                            Simpan Penjualan

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>

    <!-- Riwayat penjualan -->

    <div class="card border-0 shadow-sm rounded-4">

        <div class="card-body p-4">

            <h5 class="fw-bold mb-3">
                Riwayat Penjualan
            </h5>

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>

                        <tr>
                            <th>Kode</th>
                            <th>Tanggal</th>
                            <th>Tanaman</th>
                            <th>Kanal</th>
                            <th>Jumlah</th>
                            <th>Harga</th>
                            <th>Subtotal</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php if (empty($sales)): ?>

                        <tr>
                            <td colspan="7"
                                class="text-center py-4">

                                Belum ada transaksi penjualan.

                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($sales as $sale): ?>

                            <tr>

                                <td>
                                    <?= e($sale['sale_code']) ?>
                                </td>

                                <td>
                                    <?= date(
                                        'd/m/Y H:i',
                                        strtotime($sale['sale_date'])
                                    ) ?>
                                </td>

                                <td class="fw-semibold">
                                    <?= e($sale['type_name']) ?>
                                </td>

                                <td>
                                    <?= e($channels[$sale['sales_channel']] ?? '-') ?>
                                </td>

                                <td>
                                    <?= (int) $sale['quantity'] ?>
                                </td>

                                <td>
                                    <?= rupiah((int) $sale['unit_price']) ?>
                                </td>

                                <td class="fw-semibold">
                                    <?= rupiah((int) $sale['subtotal']) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <a href="../index.php"
               class="btn btn-light mt-3">

                Kembali ke Dashboard

            </a>

        </div>

    </div>

</div>

<script>

const plantSelect = document.getElementById('plantSelect');
const quantityInput = document.getElementById('quantity');

const unitPrice = document.getElementById('unitPrice');
const availableStock = document.getElementById('availableStock');
const subtotal = document.getElementById('subtotal');

const rupiah = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
});

function updateCalculation() {

    const option = plantSelect.selectedOptions[0];

    const price = Number(option?.dataset.price || 0);
    const stock = Number(option?.dataset.stock || 0);
    const quantity = Number(quantityInput.value || 0);

    unitPrice.value = rupiah.format(price);
    availableStock.textContent = stock + ' pcs';

    subtotal.textContent = rupiah.format(price * quantity);

    quantityInput.max = stock > 0 ? stock : '';

    if (quantity > stock) {
        quantityInput.setCustomValidity(
            'Jumlah melebihi stok siap jual.'
        );
    } else {
        quantityInput.setCustomValidity('');
    }
}

plantSelect.addEventListener('change', updateCalculation);
quantityInput.addEventListener('input', updateCalculation);

</script>

</body>
</html>
