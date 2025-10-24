<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

session_start();

// cek login
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

require_once "koneksi.php";

$role = $_SESSION["role"];
$user_id = $_SESSION["user_id"];
$id_bendel = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

// Query bendel dengan validasi akses
if ($role == "penginput") {
    $query = "SELECT b.*, k.nama_kantor, u.nama as nama_input
              FROM bendel b
              LEFT JOIN kantor k ON b.id_kantor_penerima = k.id
              LEFT JOIN users u ON b.id_user_input = u.id
              WHERE b.id = $id_bendel AND b.id_user_input = $user_id";
} else {
    $query = "SELECT b.*, k.nama_kantor, u.nama as nama_input
              FROM bendel b
              LEFT JOIN kantor k ON b.id_kantor_penerima = k.id
              LEFT JOIN users u ON b.id_user_input = u.id
              WHERE b.id = $id_bendel";
}

$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    header("Location: view_bendel.php");
    exit();
}

$bendel = mysqli_fetch_assoc($result);

// Query transaksi
$query_transaksi = "SELECT t.*, k.nama_kantor as kantor_pengirim
                    FROM transaksi t
                    LEFT JOIN kantor k ON t.id_kantor_pengirim = k.id
                    WHERE t.id_bendel = $id_bendel";
$result_transaksi = mysqli_query($conn, $query_transaksi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Bendel - Aplikasi Bendel</title>
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
                <span class="text-sm"><?php echo htmlspecialchars(
                    $_SESSION["nama"],
                ); ?></span>
                <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded text-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6 max-w-4xl">
        <!-- Info Bendel -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <div class="flex justify-between items-start mb-4">
                <h2 class="text-2xl font-bold">Detail Bendel</h2>
                <a href="view_bendel.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                    ← Kembali
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-gray-600 text-sm">No. Bendel</p>
                    <p class="font-bold text-lg"><?php echo htmlspecialchars(
                        $bendel["no_bendel"],
                    ); ?></p>
                </div>

                <div>
                    <p class="text-gray-600 text-sm">Tanggal Terima</p>
                    <p class="font-semibold"><?php echo date(
                        "d F Y",
                        strtotime($bendel["tgl_terima"]),
                    ); ?></p>
                </div>

                <div>
                    <p class="text-gray-600 text-sm">Kantor Penerima</p>
                    <p class="font-semibold"><?php echo htmlspecialchars(
                        $bendel["nama_kantor"],
                    ); ?></p>
                </div>

                <?php if ($role == "pengawas"): ?>
                <div>
                    <p class="text-gray-600 text-sm">Diinput Oleh</p>
                    <p class="font-semibold"><?php echo htmlspecialchars(
                        $bendel["nama_input"],
                    ); ?></p>
                </div>
                <?php endif; ?>

                <div>
                    <p class="text-gray-600 text-sm">Dibuat Pada</p>
                    <p class="font-semibold"><?php echo date(
                        "d F Y H:i",
                        strtotime($bendel["created_at"]),
                    ); ?></p>
                </div>

                <div>
                    <p class="text-gray-600 text-sm">Terakhir Update</p>
                    <p class="font-semibold"><?php echo date(
                        "d F Y H:i",
                        strtotime($bendel["updated_at"]),
                    ); ?></p>
                </div>
            </div>
        </div>

        <!-- Data Transaksi -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-xl font-bold mb-4">Data Transaksi</h3>

            <?php if (mysqli_num_rows($result_transaksi) > 0): ?>
            <div class="space-y-4">
                <?php while ($trans = mysqli_fetch_assoc($result_transaksi)): ?>
                <div class="border rounded-lg p-4 bg-gray-50">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <p class="text-gray-600 text-sm">Tipe Transaksi</p>
                            <p class="font-semibold">
                                <span class="inline-block px-3 py-1 rounded text-white text-sm
                                    <?php echo $trans["tipe_transaksi"] ==
                                    "setoran"
                                        ? "bg-green-500"
                                        : "bg-orange-500"; ?>">
                                    <?php echo strtoupper(
                                        $trans["tipe_transaksi"],
                                    ); ?>
                                </span>
                            </p>
                        </div>

                        <div>
                            <p class="text-gray-600 text-sm">Nama Penyetor</p>
                            <p class="font-semibold"><?php echo htmlspecialchars(
                                $trans["nama_penyetor"],
                            ); ?></p>
                        </div>

                        <div>
                            <p class="text-gray-600 text-sm">Nomor Mulai</p>
                            <p class="font-semibold"><?php echo htmlspecialchars(
                                $trans["nomor_mulai"],
                            ); ?></p>
                        </div>

                        <div>
                            <p class="text-gray-600 text-sm">Nomor Sampai</p>
                            <p class="font-semibold"><?php echo htmlspecialchars(
                                $trans["nomor_sampai"],
                            ); ?></p>
                        </div>

                        <div class="md:col-span-2">
                            <p class="text-gray-600 text-sm">Kantor Pengirim</p>
                            <p class="font-semibold"><?php echo htmlspecialchars(
                                $trans["kantor_pengirim"],
                            ); ?></p>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <p class="text-gray-600">Tidak ada data transaksi.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
