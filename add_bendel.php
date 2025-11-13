<?php
session_start();

// Cek login and role
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "penginput") {
    header("Location: index.php");
    exit();
}
require_once "koneksi.php";

$success = "";
$error   = "";

// =========================
// Jikok list kantor
// =========================
$stmt = $db->query("SELECT id, nama_kantor FROM kantor ORDER BY nama_kantor");
$kantor_list = $stmt->fetchAll();

// =========================
// Jikok nomor bendel terakhir
// =========================
$stmt = $db->query("SELECT no_bendel FROM bendel ORDER BY id DESC LIMIT 1");
$last_bendel = $stmt->fetch();
$nomer_bendel_otomatis = $last_bendel ? ((int)$last_bendel["no_bendel"] + 1) : 1;

// =========================
// Jikok nomor transaksi terakhir
// =========================
$stmt = $db->query("SELECT nomor_sampai FROM transaksi ORDER BY id DESC LIMIT 1");
$last_transaksi = $stmt->fetch();
$nomor_mulai_otomatis = $last_transaksi ? ((int)$last_transaksi["nomor_sampai"] + 1) : 1;
$nomor_sampai_otomatis = $nomor_mulai_otomatis + 49;

// helper untuk old value
function old($key, $default = "") {
    return htmlspecialchars($_POST[$key] ?? $default);
}

// =========================
// Handle POST
// =========================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // yang bendel
    $no_bendel         = trim($_POST["no_bendel"] ?? "");
    $tgl_terima        = trim($_POST["tgl_terima"] ?? date("Y-m-d"));
    $id_kantor_penerima = (int)($_POST["id_kantor_penerima"] ?? 0);
    $id_user_input     = $_SESSION["user_id"];
    // yang transaksi
    $tipe_transaksi     = trim($_POST["tipe_transaksi"] ?? "");
    $nomor_mulai        = (int)($_POST["nomor_mulai"] ?? 0);
    $nomor_sampai       = (int)($_POST["nomor_sampai"] ?? 0);
    $nama_penyetor      = trim($_POST["nama_penyetor"] ?? "");
    $id_kantor_pengirim = (int)($_POST["id_kantor_pengirim"] ?? 0);

    // Validasi yang minimal aja
    if ($no_bendel === "" || $tgl_terima === "" || $id_kantor_penerima <= 0 || $tipe_transaksi === "" || $nomor_mulai <= 0 || $nomor_sampai <= 0 || $nama_penyetor === "" || $id_kantor_pengirim <= 0) {
        $error = "Semua field wajib diisi dengan benar.";
    } else {
        // ngeCek duplikasi bendel
        $stmt = $db->query("SELECT id FROM bendel WHERE no_bendel = ?", [$no_bendel]);
        $exists = $stmt->fetch();

        if ($exists) {
            $error = "Nomor bendel udah ada ya kak!";
        } else {

            try {
                // Transactionnys lah atomic
                $pdo->beginTransaction();

                // Insert ke bendel
                $db->query(
                    "INSERT INTO bendel (no_bendel, tgl_terima, id_kantor_penerima, id_user_input)
                     VALUES (?, ?, ?, ?)",
                    [$no_bendel, $tgl_terima, $id_kantor_penerima, $id_user_input]
                );

                $id_bendel = $pdo->lastInsertId();

                // Insert transaksi
                $db->query(
                    "INSERT INTO transaksi (id_bendel, tipe_transaksi, nomor_mulai, nomor_sampai, nama_penyetor, id_kantor_pengirim)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [$id_bendel, $tipe_transaksi, $nomor_mulai, $nomor_sampai, $nama_penyetor, $id_kantor_pengirim]
                );

                $pdo->commit();
                $success = "Data bendel + transaksi sukses ditambah! 🔥";

                // reset auto valuesnya
                $nomer_bendel_otomatis = (int)$no_bendel + 1;
                $nomor_mulai_otomatis  = $nomor_sampai + 1;
                $nomor_sampai_otomatis = $nomor_mulai_otomatis + 49;

                // kosongkan POST supaya form kosong setelah sukses
                $_POST = [];
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Gagal menambahkan data!";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Bendel - Aplikasi Bendel</title>
    <link href="./output.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-blue-600 text-white p-4 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">Aplikasi Manajemen Bendel</h1>
            <div class="flex items-center gap-4">
                <a href="dashboard.php" class="hover:underline">Dashboard</a>
                <span class="text-sm"><?php echo htmlspecialchars($_SESSION["nama"]); ?></span>
                <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded text-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6 max-w-3xl">
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-2xl font-bold mb-6">Tambah Bendel Baru</h2>

            <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
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
                               value="<?php echo old('no_bendel', $nomer_bendel_otomatis); ?>">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">Tanggal Terima *</label>
                        <input type="date" name="tgl_terima" required
                               value="<?php echo old('tgl_terima', date("Y-m-d")); ?>"
                               class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">Kantor Penerima *</label>
                        <select name="id_kantor_penerima" required
                                class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500">
                            <option value="">Pilih Kantor</option>
                            <?php foreach ($kantor_list as $kantor): ?>
                            <option value="<?php echo $kantor['id']; ?>"
                                <?php echo ((int)($_POST['id_kantor_penerima'] ?? 0) === (int)$kantor['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($kantor['nama_kantor']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Data Transaksi -->
                <div>
                    <h3 class="text-lg font-semibold mb-3">Data Transaksi</h3>

                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">Tipe Transaksi *</label>
                        <select name="tipe_transaksi" required
                                class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500">
                            <option value="">Pilih Tipe</option>
                            <option value="setoran" <?php echo (($_POST['tipe_transaksi'] ?? '') === 'setoran') ? 'selected' : ''; ?>>Setoran</option>
                            <option value="penarikan" <?php echo (($_POST['tipe_transaksi'] ?? '') === 'penarikan') ? 'selected' : ''; ?>>Penarikan</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Nomor Mulai *</label>
                            <input type="number" name="nomor_mulai" required
                                   value="<?php echo old('nomor_mulai', $nomor_mulai_otomatis); ?>"
                                   id="nomor_mulai"
                                   class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Auto: <?php echo $nomor_mulai_otomatis; ?> (bisa diubah manual)</p>
                        </div>

                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Nomor Sampai *</label>
                            <input type="number" name="nomor_sampai" required
                                   value="<?php echo old('nomor_sampai', $nomor_sampai_otomatis); ?>"
                                   id="nomor_sampai"
                                   class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Auto: <?php echo $nomor_sampai_otomatis; ?> (pattern +50)</p>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">Nama Penyetor *</label>
                        <input type="text" name="nama_penyetor" required
                               value="<?php echo old('nama_penyetor'); ?>"
                               class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">Kantor Pengirim *</label>
                        <select name="id_kantor_pengirim" required
                                class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500">
                            <option value="">Pilih Kantor</option>
                            <?php foreach ($kantor_list as $kantor): ?>
                            <option value="<?php echo $kantor['id']; ?>"
                                <?php echo ((int)($_POST['id_kantor_pengirim'] ?? 0) === (int)$kantor['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($kantor['nama_kantor']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <button type="submit"
                            class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-6 rounded">
                        Simpan
                    </button>
                    <a href="dashboard.php"
                       class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.getElementById('nomor_mulai').addEventListener('input', function() {
            let mulai = parseInt(this.value) || 0;
            document.getElementById('nomor_sampai').value = mulai + 49;
        });
    </script>
</body>
</html>