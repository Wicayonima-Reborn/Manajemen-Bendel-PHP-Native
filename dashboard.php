<?php
// Security: Session Configuration
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = in_array($host, ['localhost', '127.0.0.1']);

$cookieParams = [
    'lifetime' => 0,
    'path' => '/',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax'
];

if ($isLocal) {
    unset($cookieParams['domain']);
}

session_set_cookie_params($cookieParams);
session_start();

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'koneksi.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$nama = $_SESSION['nama'];

// Ambil statistik
if ($role == 'penginput') {
    // Penginput only see data sendiri
    $query_count = "SELECT COUNT(*) as total FROM bendel WHERE id_user_input = $user_id";
} else {
    // Pengawas lihat all data
    $query_count = "SELECT COUNT(*) as total FROM bendel";
}

$result_count = mysqli_query($conn, $query_count);
$total_bendel = mysqli_fetch_assoc($result_count)['total'];

// Pick data bendel
if ($role == 'penginput') {
    $query = "SELECT b.*, k.nama_kantor, u.nama as nama_input 
              FROM bendel b 
              LEFT JOIN kantor k ON b.id_kantor_penerima = k.id 
              LEFT JOIN users u ON b.id_user_input = u.id 
              WHERE b.id_user_input = $user_id 
              ORDER BY b.created_at DESC 
              LIMIT 10";
} else {
    $query = "SELECT b.*, k.nama_kantor, u.nama as nama_input 
              FROM bendel b 
              LEFT JOIN kantor k ON b.id_kantor_penerima = k.id 
              LEFT JOIN users u ON b.id_user_input = u.id 
              ORDER BY b.created_at DESC 
              LIMIT 10";
}

$result_bendel = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Aplikasi Bendel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-blue-600 text-white p-4 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">Aplikasi Manajemen Bendel</h1>
            <div class="flex items-center gap-4">
                <span class="text-sm">Halo, <strong><?php echo htmlspecialchars($nama); ?></strong> (<?php echo ucfirst($role); ?>)</span>
                <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded text-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6">
        <!-- kertu statistik -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-gray-600 text-sm font-semibold">Total Bendel</h3>
                <p class="text-3xl font-bold text-blue-600 mt-2"><?php echo $total_bendel; ?></p>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-gray-600 text-sm font-semibold">Role Anda</h3>
                <p class="text-2xl font-bold text-green-600 mt-2"><?php echo ucfirst($role); ?></p>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-gray-600 text-sm font-semibold">Status</h3>
                <p class="text-2xl font-bold text-purple-600 mt-2">Aktif</p>
            </div>
        </div>

        <!-- Menu Aksi -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-xl font-bold mb-4">Menu</h2>
            <div class="flex gap-3">
                <?php if ($role == 'penginput'): ?>
                <a href="add_bendel.php" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded font-semibold">
                    Tambah Bendel
                </a>
                <?php endif; ?>
                
                <a href="view_bendel.php" class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded font-semibold">
                    Lihat Semua Bendel
                </a>
            </div>
        </div>

        <!-- Tabel Data Bendel Terbaru -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-xl font-bold mb-4">Data Bendel Terbaru</h2>
            
            <?php if (mysqli_num_rows($result_bendel) > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="px-4 py-2 text-left">No. Bendel</th>
                            <th class="px-4 py-2 text-left">Tanggal Terima</th>
                            <th class="px-4 py-2 text-left">Kantor Penerima</th>
                            <?php if ($role == 'pengawas'): ?>
                            <th class="px-4 py-2 text-left">Diinput Oleh</th>
                            <?php endif; ?>
                            <th class="px-4 py-2 text-left">Dibuat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result_bendel)): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3"><?php echo htmlspecialchars($row['no_bendel']); ?></td>
                            <td class="px-4 py-3"><?php echo date('d/m/Y', strtotime($row['tgl_terima'])); ?></td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($row['nama_kantor']); ?></td>
                            <?php if ($role == 'pengawas'): ?>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($row['nama_input']); ?></td>
                            <?php endif; ?>
                            <td class="px-4 py-3"><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
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