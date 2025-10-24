<?php
session_start();

// Cek login dan role
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "penginput") {
    header("Location: index.php");
    exit();
}

require_once "koneksi.php";

$success = "";
$error = "";
$id_bendel = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($id_bendel == 0) {
    header("Location: view_bendel.php");
    exit();
}

// ambil data bendel (cek ownership)
$query_bendel =
    "SELECT * FROM bendel WHERE id = $id_bendel AND id_user_input = " .
    $_SESSION["user_id"];
$result_bendel = mysqli_query($conn, $query_bendel);

if (!$result_bendel || mysqli_num_rows($result_bendel) == 0) {
    header("Location: view_bendel.php");
    exit();
}

$bendel = mysqli_fetch_assoc($result_bendel);

// ambil data transaksi
$query_transaksi = "SELECT * FROM transaksi WHERE id_bendel = $id_bendel";
$result_transaksi = mysqli_query($conn, $query_transaksi);
$transaksi = mysqli_fetch_assoc($result_transaksi);

// data kantor
$query_kantor = "SELECT * FROM kantor ORDER BY nama_kantor";
$result_kantor = mysqli_query($conn, $query_kantor);

// Prosesupdate
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $no_bendel = mysqli_real_escape_string($conn, trim($_POST["no_bendel"]));
    $tgl_terima = mysqli_real_escape_string($conn, trim($_POST["tgl_terima"]));
    $id_kantor_penerima = (int) $_POST["id_kantor_penerima"];

    // data transaksi
    $tipe_transaksi = $_POST["tipe_transaksi"];
    $nomor_mulai = (int) $_POST["nomor_mulai"];
    $nomor_sampai = (int) $_POST["nomor_sampai"];
    $nama_penyetor = mysqli_real_escape_string(
        $conn,
        trim($_POST["nama_penyetor"]),
    );
    $id_kantor_pengirim = (int) $_POST["id_kantor_pengirim"];

    // update bendel
    $query_update_bendel = "UPDATE bendel SET
                            no_bendel = '$no_bendel',
                            tgl_terima = '$tgl_terima',
                            id_kantor_penerima = $id_kantor_penerima
                            WHERE id = $id_bendel";

    if (mysqli_query($conn, $query_update_bendel)) {
        // update transaksi
        $query_update_transaksi = "UPDATE transaksi SET
                               tipe_transaksi = '$tipe_transaksi',
                               nomor_mulai = '$nomor_mulai',
                               nomor_sampai = '$nomor_sampai',
                               nama_penyetor = '$nama_penyetor',
                               id_kantor_pengirim = $id_kantor_pengirim
                               WHERE id_bendel = $id_bendel";

        if (mysqli_query($conn, $query_update_transaksi)) {
            $success = "Data berhasil diupdate!";

            // refresh data bendel dan transaksi
            $bendel["no_bendel"] = $no_bendel;
            $bendel["tgl_terima"] = $tgl_terima;
            $bendel["id_kantor_penerima"] = $id_kantor_penerima;

            if ($transaksi) {
                $transaksi["tipe_transaksi"] = $tipe_transaksi;
                $transaksi["nomor_mulai"] = $nomor_mulai;
                $transaksi["nomor_sampai"] = $nomor_sampai;
                $transaksi["nama_penyetor"] = $nama_penyetor;
                $transaksi["id_kantor_pengirim"] = $id_kantor_pengirim;
            }
        } else {
            $error = "Gagal update transaksi!";
        }
    } else {
        $error = "Gagal update bendel!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Bendel - Aplikasi bendel</title>
    <link href="./output.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!--Navbar-->
    <nav class="bg-blue-600 text-white p-4 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">Aplikasi manajemen bendel</h1>
            <div class="flex items-center gap-4">
                <a href="dashboard.php" class="hover:underline">Dashboard</a>
                <a href="view_bendel.php" class="hover:underline">Lihat bendel</a>
                <span class="text-sm"><?php echo htmlspecialchars(
                    $_SESSION["nama"],
                ); ?></span>
                <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded text-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6 max-w-3xl">
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-2xl font-bold mb-6">Edit Bendel</h2>

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
                        <input type="text" name="no_bendel" required value="<?php echo htmlspecialchars(
                            $bendel["no_bendel"],
                        ); ?>" class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">Tanggal Terima *</label>
                        <input type="date" name="tgl_terima" required value="<?php echo $bendel[
                            "tgl_terima"
                        ]; ?>" class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">Kantor Penerima *</label>
                        <select name="id_kantor_penerima" required
                                class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500">
                            <option value="">  Pilih Kantor  </option>
                            <?php
                            mysqli_data_seek($result_kantor, 0);
                            while (
                                $kantor = mysqli_fetch_assoc($result_kantor)
                            ): ?>
                            <option value="<?php echo $kantor["id"]; ?>"
                                    <?php echo $bendel["id_kantor_penerima"] ==
                                    $kantor["id"]
                                        ? "selected"
                                        : ""; ?>>
                                <?php echo htmlspecialchars(
                                    $kantor["nama_kantor"],
                                ); ?>
                            </option>
                            <?php endwhile;
                            ?>
                        </select>
                    </div>

                <!-- Data Transaksi -->
                <?php if ($transaksi): ?>
                <div>
                    <h3 class="text-lg font-semibold mb-3">Data Transaksi</h3>

                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">Tipe Transaksi *</label>
                        <select name="tipe_transaksi" required
                                class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500">
                            <option value="">  Pilih Tipe  </option>
                            <option value="setoran" <?php echo $transaksi[
                                "tipe_transaksi"
                            ] == "setoran"
                                ? "selected"
                                : ""; ?>>Setoran</option>
                            <option value="penarikan" <?php echo $transaksi[
                                "tipe_transaksi"
                            ] == "penarikan"
                                ? "selected"
                                : ""; ?>>Penarikan</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Nomor Mulai *</label>
                            <input type="number" name="nomor_mulai" required
                                   value="<?php echo htmlspecialchars(
                                       $transaksi["nomor_mulai"],
                                   ); ?>"
                                   id="nomor_mulai"
                                   class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Nomor Sampai *</label>
                            <input type="number" name="nomor_sampai" required
                                   value="<?php echo htmlspecialchars(
                                       $transaksi["nomor_sampai"],
                                   ); ?>"
                                   id="nomor_sampai"
                                   class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Pattern +50 dari nomor mulai</p>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">Nama Penyetor *</label>
                        <input type="text" name="nama_penyetor" required maxlength="100"
                               value="<?php echo htmlspecialchars(
                                   $transaksi["nama_penyetor"],
                               ); ?>"
                               class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">Kantor Pengirim *</label>
                        <select name="id_kantor_pengirim" required
                                class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500">
                            <option value="">  Pilih Kantor  </option>
                            <?php
                            mysqli_data_seek($result_kantor, 0);
                            while (
                                $kantor = mysqli_fetch_assoc($result_kantor)
                            ): ?>
                            <option value="<?php echo $kantor["id"]; ?>"
                                    <?php echo $transaksi[
                                        "id_kantor_pengirim"
                                    ] == $kantor["id"]
                                        ? "selected"
                                        : ""; ?>>
                                <?php echo htmlspecialchars(
                                    $kantor["nama_kantor"],
                                ); ?>
                            </option>
                            <?php endwhile;
                            ?>
                        </select>
                    </div>
                </div>
                <?php endif; ?>

                <div class="flex gap-3 mt-6">
                    <button type="submit"
                            class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-6 rounded">
                        Update
                    </button>
                    <a href="view_bendel.php"
                       class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded inline-block text-center">
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