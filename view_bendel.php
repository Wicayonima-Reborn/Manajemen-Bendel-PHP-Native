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

// Filter
$filter_tanggal = isset($_GET['tanggal']) ? mysqli_real_escape_string($conn, $_GET['tanggal']) : '';
$filter_kantor = isset($_GET['kantor']) ? (int)$_GET['kantor'] : 0;
$filter_tipe = isset($_GET['tipe']) ? mysqli_real_escape_string($conn, $_GET['tipe']) : '';
$filter_search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';

// Query bendel
$where = [];
if ($role == 'penginput') {
    $where[] = "b.id_user_input = $user_id";
}
if ($filter_tanggal) {
    $where[] = "b.tgl_terima = '$filter_tanggal'";
}
if ($filter_kantor > 0) {
    $where[] = "b.id_kantor_penerima = $filter_kantor";
}
if ($filter_tipe) {
    $where[] = "t.tipe_transaksi = '$filter_tipe'";
}
if ($filter_search) {
    if (is_numeric($filter_search)) {
        $search_num = (int)$filter_search;
        $where[] = "(b.no_bendel LIKE '%$filter_search%' 
                     OR t.nama_penyetor LIKE '%$filter_search%' 
                     OR $search_num BETWEEN t.nomor_mulai AND t.nomor_sampai)";
    } else {
        $where[] = "(b.no_bendel LIKE '%$filter_search%' 
                     OR t.nama_penyetor LIKE '%$filter_search%')";
    }
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$query = "SELECT b.*, k.nama_kantor, u.nama as nama_input, 
          t.tipe_transaksi, t.nama_penyetor, t.nomor_mulai, t.nomor_sampai
          FROM bendel b 
          LEFT JOIN kantor k ON b.id_kantor_penerima = k.id 
          LEFT JOIN users u ON b.id_user_input = u.id 
          LEFT JOIN transaksi t ON b.id = t.id_bendel
          $where_clause
          ORDER BY b.created_at DESC";

$result_bendel = mysqli_query($conn, $query);

// Ambil data kantor untuk filter
$query_kantor = "SELECT * FROM kantor ORDER BY nama_kantor";
$result_kantor = mysqli_query($conn, $query_kantor);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lihat Bendel - Aplikasi Bendel</title>
    <link href="./output.css" rel="stylesheet">
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
                 <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Cari (Bendel/Penyetor/Nomor)</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($filter_search); ?>"
                               placeholder="Ketik untuk mencari..."
                               class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500" autocomplete="off">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Filter Tanggal</label>
                        <input type="date" name="tanggal" value="<?php echo $filter_tanggal; ?>"
                               class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500">
                    </div>          
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Filter Kantor</label>
                        <select name="kantor" class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500">
                            <option value="0">  Semua Kantor  </option>
                            <?php while ($kantor = mysqli_fetch_assoc($result_kantor)): ?>
                            <option value="<?php echo $kantor['id']; ?>" 
                                    <?php echo $filter_kantor == $kantor['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($kantor['nama_kantor']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Filter Tipe</label>
                        <select name="tipe" class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500">
                            <option value="">  Semua Tipe  </option>
                            <option value="setoran" <?php echo $filter_tipe == 'setoran' ? 'selected' : ''; ?>>Setoran</option>
                            <option value="penarikan" <?php echo $filter_tipe == 'penarikan' ? 'selected' : ''; ?>>Penarikan</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex gap-2">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded font-semibold">
                        Filter
                    </button>
                    <a href="view_bendel.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded font-semibold">
                        ↻ Reset
                    </a>
                    <a href="export.php" class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded font-semibold">Export ke excel</a>
                </div>
            </form>
            </form>
            
            <!-- Tabel -->
            <?php if (mysqli_num_rows($result_bendel) > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left">No</th>
                            <th class="px-4 py-3 text-left">No. Bendel</th>
                            <th class="px-4 py-3 text-left">Tanggal Terima</th>
                            <th class="px-4 py-3 text-left">Kantor Penerima</th>
                            <?php if ($role == 'pengawas'): ?>
                            <th class="px-4 py-3 text-left">Diinput Oleh</th>
                            <?php endif; ?>
                            <th class="px-4 py-3 text-left">Dibuat</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($result_bendel)): 
                        ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3"><?php echo $no++; ?></td>
                            <td class="px-4 py-3 font-semibold"><?php echo htmlspecialchars($row['no_bendel']); ?></td>
                            <td class="px-4 py-3"><?php echo date('d/m/Y', strtotime($row['tgl_terima'])); ?></td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($row['nama_kantor']); ?></td>
                            <?php if ($role == 'pengawas'): ?>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($row['nama_input']); ?></td>
                            <?php endif; ?>
                            <td class="px-4 py-3"><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                            <td class="px-4 py-3 text-center">
                                <a href="detail_bendel.php?id=<?php echo $row['id']; ?>" 
                                   class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                    Detail
                                </a>
                                <?php if ($role == 'penginput'): ?>
                                <a href="edit_bendel.php?id=<?php echo $row['id']; ?>" 
                                   class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm">
                                    Edit
                                </a>
                                <?php endif; ?>
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