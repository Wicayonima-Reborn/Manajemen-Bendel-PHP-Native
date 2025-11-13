<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

require_once "koneksi.php"; 

$user_id = $_SESSION["user_id"];
$role    = $_SESSION["role"];
$nama    = $_SESSION["nama"];

// Statistik bendel
if ($role === "penginput") {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM bendel WHERE id_user_input = :uid");
    $stmt->execute(["uid" => $user_id]);
} else {
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM bendel");
}
$total_bendel = $stmt->fetch(PDO::FETCH_ASSOC)["total"];

// Data bendel terbaru
if ($role === "penginput") {
    $stmt = $pdo->prepare("
        SELECT b.*, k.nama_kantor, u.nama AS nama_input
        FROM bendel b
        LEFT JOIN kantor k ON b.id_kantor_penerima = k.id
        LEFT JOIN users u ON b.id_user_input = u.id
        WHERE b.id_user_input = :uid
        ORDER BY b.created_at DESC
        LIMIT 10
    ");
    $stmt->execute(["uid" => $user_id]);
} else {
    $stmt = $pdo->query("
        SELECT b.*, k.nama_kantor, u.nama AS nama_input
        FROM bendel b
        LEFT JOIN kantor k ON b.id_kantor_penerima = k.id
        LEFT JOIN users u ON b.id_user_input = u.id
        ORDER BY b.created_at DESC
        LIMIT 10
    ");
}
$result_bendel = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Aplikasi Bendel</title>
    <link href="./output.css" rel="stylesheet">
</head>
<body class="bg-gray-100">

    <!-- NAVBAR -->
    <nav class="bg-blue-600 text-white p-4 shadow-lg">
        <div class="max-w-7xl mx-auto flex flex-wrap justify-between items-center gap-3">
            <h1 class="text-lg md:text-xl font-bold">Aplikasi Manajemen Bendel</h1>

            <div class="flex items-center gap-3 text-sm md:text-base">
                <span>Halo, <strong><?= htmlspecialchars($nama) ?></strong> (<?= ucfirst($role) ?>)</span>
                <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded">Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto p-4 md:p-6">

        <!-- STATISTIC CARDS -->
        <div class="w-full max-w-6xl mx-auto grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6 mb-6">

            <div class="bg-white p-4 md:p-6 rounded-xl shadow border border-gray-100">
                <h3 class="text-gray-600 text-sm font-semibold">Total Bendel</h3>
                <p class="text-3xl md:text-4xl font-bold text-blue-600 mt-2"><?= $total_bendel ?></p>
            </div>

            <div class="bg-white p-4 md:p-6 rounded-xl shadow border border-gray-100">
                <h3 class="text-gray-600 text-sm font-semibold">Role Anda</h3>
                <p class="text-2xl md:text-3xl font-bold text-green-600 mt-2"><?= ucfirst($role) ?></p>
            </div>
            <div class="bg-white p-4 md:p-6 rounded-xl shadow border border-gray-100">
                <h3 class="text-gray-600 text-sm font-semibold">Status</h3>
                <p class="text-2xl md:text-3xl font-bold text-purple-600 mt-2">Aktif</p>
            </div>

        </div>

        <!-- MENU -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-xl font-bold mb-4">Menu</h2>

            <div class="flex flex-wrap gap-3">
                <?php if ($role === "penginput"): ?>
                <a href="add_bendel.php" class="bg-blue-500 hover:bg-blue-600 text-white px-5 py-3 rounded font-semibold w-full sm:w-auto text-center">
                    Tambah Bendel
                </a>
                <?php endif; ?>

                <a href="view_bendel.php" class="bg-green-500 hover:bg-green-600 text-white px-5 py-3 rounded font-semibold w-full sm:w-auto text-center">
                    Lihat Semua Bendel
                </a>
            </div>
        </div>
        <!-- DATA BENDEL TERBARU -->
        <div class="bg-white p-6 rounded-lg shadow">

            <h2 class="text-xl font-bold mb-4">Data Bendel Terbaru</h2>

            <?php if (count($result_bendel) > 0): ?>

            <div class="overflow-x-auto">
                <table class="w-full min-w-max text-sm md:text-base">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left">No. Bendel</th>
                            <th class="px-4 py-3 text-left">Tanggal Terima</th>
                            <th class="px-4 py-3 text-left">Kantor Penerima</th>
                            <?php if ($role === "pengawas"): ?>
                            <th class="px-4 py-3 text-left">Diinput Oleh</th>
                            <?php endif; ?>
                            <th class="px-4 py-3 text-left">Dibuat</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($result_bendel as $row): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3"><?= htmlspecialchars($row["no_bendel"]) ?></td>
                            <td class="px-4 py-3"><?= date("d/m/Y", strtotime($row["tgl_terima"])) ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($row["nama_kantor"]) ?></td>
                            <?php if ($role === "pengawas"): ?>
                            <td class="px-4 py-3"><?= htmlspecialchars($row["nama_input"]) ?></td>
                            <?php endif; ?>
                            <td class="px-4 py-3"><?= date("d/m/Y H:i", strtotime($row["created_at"])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-gray-600">Belum ada data bendel.</p>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>