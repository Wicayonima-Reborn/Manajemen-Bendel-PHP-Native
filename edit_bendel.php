<?php
session_start();
// ngeCek login dan role
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "penginput") {
    header("Location: index.php");
    exit();
}
require_once "koneksi.php";

$success = "";
$error = "";
$id_bendel = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($id_bendel === 0) {
    header("Location: view_bendel.php");
    exit();
}

// Ambil data bendel (cek ownership)
try {
    $stmt = $pdo->prepare("SELECT * FROM bendel WHERE id = ? AND id_user_input = ?");
    $stmt->execute([$id_bendel, $_SESSION["user_id"]]);
    $bendel = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Jika query error, redirect aman
    header("Location: view_bendel.php");
    exit();
}

if (!$bendel) {
    header("Location: view_bendel.php");
    exit();
}

// jikok data transaksi (if there are)
$stmt = $pdo->prepare("SELECT * FROM transaksi WHERE id_bendel = ?");
$stmt->execute([$id_bendel]);
$transaksi = $stmt->fetch(PDO::FETCH_ASSOC);

// Jikok list kantor
$stmt = $pdo->query("SELECT * FROM kantor ORDER BY nama_kantor");
$kantor_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prosesupdate
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Ambil and sanitize input sederhana
    $no_bendel = trim($_POST["no_bendel"] ?? "");
    $tgl_terima = trim($_POST["tgl_terima"] ?? "");
    $id_kantor_penerima = (int)($_POST["id_kantor_penerima"] ?? 0);

    // data transaksi
    $tipe_transaksi = trim($_POST["tipe_transaksi"] ?? "");
    $nomor_mulai = (int)($_POST["nomor_mulai"] ?? 0);
    $nomor_sampai = (int)($_POST["nomor_sampai"] ?? 0);
    $nama_penyetor = trim($_POST["nama_penyetor"] ?? "");
    $id_kantor_pengirim = (int)($_POST["id_kantor_pengirim"] ?? 0);

    // validasi Basic
    if ($no_bendel === "" || $tgl_terima === "" || $id_kantor_penerima <= 0) {
        $error = "Field Bendel wajib diisi dengan benar.";
    } elseif ($tipe_transaksi === "" || $nomor_mulai <= 0 || $nomor_sampai <= 0 || $nama_penyetor === "" || $id_kantor_pengirim <= 0) {
        $error = "Field Transaksi wajib diisi dengan benar.";
    } else {
        try {
            // Transactionnya lah atomic
            $pdo->beginTransaction();

            // Update bendel
            $stmt = $pdo->prepare("UPDATE bendel SET no_bendel = ?, tgl_terima = ?, id_kantor_penerima = ? WHERE id = ?");
            $ok1 = $stmt->execute([$no_bendel, $tgl_terima, $id_kantor_penerima, $id_bendel]);
            if (!$ok1) {
                throw new Exception("Gagal update bendel");
            }
            // Jika transaksi ada trs update, kalau tidak trs insert baru
            if ($transaksi) {
                $stmt = $pdo->prepare("UPDATE transaksi SET tipe_transaksi = ?, nomor_mulai = ?, nomor_sampai = ?, nama_penyetor = ?, id_kantor_pengirim = ? WHERE id_bendel = ?");
                $ok2 = $stmt->execute([$tipe_transaksi, $nomor_mulai, $nomor_sampai, $nama_penyetor, $id_kantor_pengirim, $id_bendel]);
                if (!$ok2) {
                    throw new Exception("Gagal update transaksi");
                }
            } else {
                $stmt = $pdo->prepare("INSERT INTO transaksi (id_bendel, tipe_transaksi, nomor_mulai, nomor_sampai, nama_penyetor, id_kantor_pengirim) VALUES (?, ?, ?, ?, ?, ?)");
                $ok2 = $stmt->execute([$id_bendel, $tipe_transaksi, $nomor_mulai, $nomor_sampai, $nama_penyetor, $id_kantor_pengirim]);
                if (!$ok2) {
                    throw new Exception("Gagal insert transaksi");
                }
                // refresh transaksi variable to reflect newly inserted row
                $stmt = $pdo->prepare("SELECT * FROM transaksi WHERE id_bendel = ?");
                $stmt->execute([$id_bendel]);
                $transaksi = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            $pdo->commit();

            // refresh bendel data to show updated values
            $stmt = $pdo->prepare("SELECT * FROM bendel WHERE id = ?");
            $stmt->execute([$id_bendel]);
            $bendel = $stmt->fetch(PDO::FETCH_ASSOC);
            $success = "Data berhasil diupdate!";
        } catch (Exception $e) {
            $pdo->rollBack();
            // pesan error
            $error = "Gagal menyimpan perubahan!";
        }
    }
}
// helper old() sederhana supaya form gak ilang saat error
function old_post($key, $default = "") {
    return htmlspecialchars($_POST[$key] ?? $default);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Edit Bendel - Aplikasi Bendel</title>
    <link href="./output.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-blue-600 text-white p-4 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">Aplikasi Manajemen Bendel</h1>
            <div class="flex items-center gap-4">
                <a href="dashboard.php" class="hover:underline">Dashboard</a>
                <a href="view_bendel.php" class="hover:underline">Lihat Bendel</a>
                <span class="text-sm"><?= htmlspecialchars($_SESSION["nama"]) ?></span>
                <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded text-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6 max-w-3xl">
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-2xl font-bold mb-6">Edit Bendel</h2>

            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="" autocomplete="off">
                <!-- Data Bendel -->
                <div class="border-b pb-4 mb-4">
                    <h3 class="text-lg font-semibold mb-3">Data Bendel</h3>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">No. Bendel *</label>
                        <input type="text" name="no_bendel" required
                               class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500"
                               value="<?= old_post('no_bendel', htmlspecialchars($bendel['no_bendel'])) ?>">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">Tanggal Terima *</label>
                        <input type="date" name="tgl_terima" required
                               class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500"
                               value="<?= old_post('tgl_terima', htmlspecialchars($bendel['tgl_terima'])) ?>">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">Kantor Penerima *</label>
                        <select name="id_kantor_penerima" required class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500">
                            <option value="">Pilih Kantor</option>
                            <?php foreach ($kantor_list as $k): ?>
                                <option value="<?= htmlspecialchars($k['id']) ?>"
                                    <?= ((int)($_POST['id_kantor_penerima'] ?? $bendel['id_kantor_penerima']) === (int)$k['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($k['nama_kantor']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Data Transaksi -->
                <?php if ($transaksi): ?>
                <div>
                    <h3 class="text-lg font-semibold mb-3">Data Transaksi</h3>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">Tipe Transaksi *</label>
                        <select name="tipe_transaksi" required class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500">
                            <option value="">Pilih Tipe</option>
                            <option value="setoran" <?= (($_POST['tipe_transaksi'] ?? $transaksi['tipe_transaksi']) === 'setoran') ? 'selected' : '' ?>>Setoran</option>
                            <option value="penarikan" <?= (($_POST['tipe_transaksi'] ?? $transaksi['tipe_transaksi']) === 'penarikan') ? 'selected' : '' ?>>Penarikan</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Nomor Mulai *</label>
                            <input type="number" name="nomor_mulai" id="nomor_mulai" required
                                   class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500"
                                   value="<?= old_post('nomor_mulai', htmlspecialchars($transaksi['nomor_mulai'])) ?>">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Nomor Sampai *</label>
                            <input type="number" name="nomor_sampai" id="nomor_sampai" required
                                   class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500"
                                   value="<?= old_post('nomor_sampai', htmlspecialchars($transaksi['nomor_sampai'])) ?>">
                            <p class="text-xs text-gray-500 mt-1">Pattern +50 dari nomor mulai</p>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">Nama Penyetor *</label>
                        <input type="text" name="nama_penyetor" maxlength="100" required
                               class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500"
                               value="<?= old_post('nama_penyetor', htmlspecialchars($transaksi['nama_penyetor'])) ?>">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">Kantor Pengirim *</label>
                        <select name="id_kantor_pengirim" required class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500">
                            <option value="">Pilih Kantor</option>
                            <?php foreach ($kantor_list as $k): ?>
                                <option value="<?= htmlspecialchars($k['id']) ?>"
                                    <?= ((int)($_POST['id_kantor_pengirim'] ?? $transaksi['id_kantor_pengirim']) === (int)$k['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($k['nama_kantor']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <?php else: ?>
                    <!-- Jika nggak ada transaksi, tampilkan form kosong untuk menambahkan -->
                    <div>
                        <h3 class="text-lg font-semibold mb-3">Data Transaksi (Tambah Baru)</h3>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-semibold mb-2">Tipe Transaksi *</label>
                            <select name="tipe_transaksi" required class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500">
                                <option value="">Pilih Tipe</option>
                                <option value="setoran" <?= (($_POST['tipe_transaksi'] ?? '') === 'setoran') ? 'selected' : '' ?>>Setoran</option>
                                <option value="penarikan" <?= (($_POST['tipe_transaksi'] ?? '') === 'penarikan') ? 'selected' : '' ?>>Penarikan</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Nomor Mulai *</label>
                                <input type="number" name="nomor_mulai" id="nomor_mulai" required
                                       class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500"
                                       value="<?= old_post('nomor_mulai', '') ?>">
                            </div>

                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Nomor Sampai *</label>
                                <input type="number" name="nomor_sampai" id="nomor_sampai" required
                                       class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500"
                                       value="<?= old_post('nomor_sampai', '') ?>">
                                <p class="text-xs text-gray-500 mt-1">Pattern +50 dari nomor mulai</p>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 font-semibold mb-2">Nama Penyetor *</label>
                            <input type="text" name="nama_penyetor" maxlength="100" required
                                   class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500"
                                   value="<?= old_post('nama_penyetor', '') ?>">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-semibold mb-2">Kantor Pengirim *</label>
                            <select name="id_kantor_pengirim" required class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500">
                                <option value="">Pilih Kantor</option>
                                <?php foreach ($kantor_list as $k): ?>
                                    <option value="<?= htmlspecialchars($k['id']) ?>" <?= ((int)($_POST['id_kantor_pengirim'] ?? 0) === (int)$k['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($k['nama_kantor']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="flex gap-3 mt-6">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-6 rounded">Update</button>
                    <a href="view_bendel.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded inline-block text-center">Batal</a>
                </div>
            </form>
        </div>
    </div>
    <script>
    // auto hitung nomor_sampai = nomor_mulai + 49
    const mulaiEl = document.getElementById('nomor_mulai');
    const sampaiEl = document.getElementById('nomor_sampai');
    if (mulaiEl && sampaiEl) {
        mulaiEl.addEventListener('input', () => {
            const angka = parseInt(mulaiEl.value) || 0;
            sampaiEl.value = angka + 49;
        });
    }
    </script>
</body>
</html>