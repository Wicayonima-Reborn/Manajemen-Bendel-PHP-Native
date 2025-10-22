<?php
session_start();

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'koneksi.php';

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Filter dan sorting
$filter_tipe = isset($_GET['tipe']) ? mysqli_real_escape_string($conn, $_GET['tipe']) : '';
$filter_tanggal = isset($_GET['tanggal']) ? mysqli_real_escape_string($conn, $_GET['tanggal']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'default';

// Query WHERE
$where = [];
if ($role == 'penginput') {
    $where[] = "b.id_user_input = $user_id";
}
if (!empty($filter_tipe)) {
    $where[] = "t.tipe_transaksi = '$filter_tipe'";
}
if (!empty($filter_tanggal)) {
    $where[] = "b.tgl_terima = '$filter_tanggal'";
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Sorting
if ($sort_by == 'tanggal') {
    $order_by = "ORDER BY b.tgl_terima DESC";
} elseif ($sort_by == 'nomor') {
    $order_by = "ORDER BY CAST(t.nomor_mulai AS UNSIGNED) DESC";
} else {
    $order_by = "ORDER BY b.id DESC"; // default
}

// Query final
$query = "SELECT b.*, k.nama_kantor, u.nama as nama_input,
t.tipe_transaksi, t.nama_penyetor, t.nomor_mulai, t.nomor_sampai
FROM bendel b
LEFT JOIN kantor k ON b.id_kantor_penerima = k.id
LEFT JOIN users u ON b.id_user_input = u.id
LEFT JOIN transaksi t ON b.id = t.id_bendel
$where_clause
$order_by";

$result_bendel = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lihat Bendel - Aplikasi Bendel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-blue-600 text-white p-4 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">Aplikasi Manajemen Bendel</h1>
            <div class="flex items-center gap-4">
                <a href="dashboard.php" class="hover:underline">Dashboard</a>
                <span class="text-sm"><?php echo htmlspecialchars($_SESSION['nama']); ?></span>
                <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded text-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6">
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-2xl font-bold mb-4">
                <?php echo $role == 'pengawas' ? 'Semua Data Bendel' : 'Data Bendel Saya'; ?>
            </h2>

            <!-- Filter -->
            <form method="GET" class="mb-6">
                <!-- button -->
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Filter Tipe Transaksi:</label>
                    <div class="flex gap-2">
                        <a href="view_bendel.php?sort=<?php echo $sort_by; ?>&tanggal=<?php echo $filter_tanggal; ?>" 
                           class="px-4 py-2 rounded font-semibold <?php echo $filter_tipe == '' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                            Semua
                        </a>
                        <a href="view_bendel.php?tipe=setoran&sort=<?php echo $sort_by; ?>&tanggal=<?php echo $filter_tanggal; ?>" 
                           class="px-4 py-2 rounded font-semibold <?php echo $filter_tipe == 'setoran' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                            Setoran
                        </a>
                        <a href="view_bendel.php?tipe=penarikan&sort=<?php echo $sort_by; ?>&tanggal=<?php echo $filter_tanggal; ?>" 
                           class="px-4 py-2 rounded font-semibold <?php echo $filter_tipe == 'penarikan' ? 'bg-orange-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                            Penarikan
                        </a>
                    </div>
                </div>

                <!-- sorting -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb--4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Urutkan:</label>
                        <select name="sort" class="w-full px-4 py-2 border rounded focus:outline-none focus:border-blue-500" onchange="this.form.submit()">
                            <option value="tanggal" <?php echo $sort_by == 'tanggal' ? 'selected' : ''; ?>>
                                Berdasarkan Tanggal
                            </option>
                            <option value="nomor" <?php echo $sort_by == 'nomor' ? 'selected' : ''; ?>>
                                Berdasarkan Nomor
                            </option>
                        </select>
                    </div>

                    <!-- Button -->
                    <div class="flex items-end gap-2">
                        <a href="view_bendel.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded font-semibold">
                            Reset
                        </a>
                        <a href="export.php" class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded font-semibold">
                            Export ke excel
                        </a>
                    </div>
                </div>
            </form>

            <!-- Tabel -->
            <?php if (mysqli_num_rows($result_bendel) > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="px-4 py-3">No</th>
                            <th class="px-4 py-3">No Bendel</th>
                            <th class="px-4 py-3">Tgl Terima</th>
                            <th class="px-4 py-3">Kantor Penerima</th>
                            <th class="px-4 py-3">Nomor</th>
                            <th class="px-4 py-3">Penyetor</th>
                            <?php if ($role == 'pengawas'): ?>
                            <th class="px-4 py-3">Diinput Oleh</th>
                            <?php endif; ?>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($result_bendel)): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3"><?php echo $no++; ?></td>
                            <td class="px-4 py-3 font-semibold"><?php echo htmlspecialchars($row['no_bendel']); ?></td>
                            <td class="px-4 py-3"><?php echo date('d/m/Y', strtotime($row['tgl_terima'])); ?></td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($row['nama_kantor']); ?></td>
                            <td class="px-4 py-3 text-sm">
                                <?php echo htmlspecialchars($row['nomor_mulai']); ?> - <?php echo htmlspecialchars($row['nomor_sampai']); ?>
                            </td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($row['nama_penyetor']); ?></td>
                            <?php if ($role == 'pengawas'): ?>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($row['nama_input']); ?></td>
                            <?php endif; ?>
                            <td class="px-4 py-3">
                                <div class="flex gap-2 justify-center">
                                    <a href="detail_bendel.php?id=<?php echo $row['id']; ?>" 
                                       class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-xs font-semibold transition">
                                        Detail
                                    </a>
                                    <?php if ($role == 'penginput'): ?>
                                    <a href="edit_bendel.php?id=<?php echo $row['id']; ?>" 
                                       class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded text-xs font-semibold transition">
                                        Edit
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-8">
                <p class="text-gray-600 text-lg">Tidak ada data bendel ditemukan.</p>
                <?php if ($role == 'penginput'): ?>
                <a href="add_bendel.php" class="inline-block mt-4 bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded">
                    Tambah Bendel Pertama
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 