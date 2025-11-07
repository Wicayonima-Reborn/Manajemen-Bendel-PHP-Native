<?php
session_start();

// kalau sudah login → ke dashboard
if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit();
}

require_once "koneksi.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");

    // querynya pakai wrapper
    $stmt = $db->query(
        "SELECT id, nama, username, password, role 
         FROM users 
         WHERE username = ? LIMIT 1",
        [$username]
    );

    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["password"])) {

        // mengamankan session
        session_regenerate_id(true);

        $_SESSION["user_id"]  = $user["id"];
        $_SESSION["nama"]     = $user["nama"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["role"]     = $user["role"];

        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Aplikasi Bendel</title>
    <link href="./output.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md">
            <h1 class="text-2xl font-bold text-center mb-6 text-gray-800">Login Aplikasi Bendel</h1>

            <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Username</label>
                    <input type="text" name="username" autocomplete="off" required
                           maxlength="50"
                           class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-blue-500">
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                    <input type="password" name="password" autocomplete="off" required
                           maxlength="100"
                           class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-blue-500">
                </div>

                <button type="submit"
                        class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded focus:outline-none">
                    Login
                </button>
            </form>

            <p class="text-center text-gray-600 text-sm mt-4">
                Sistem Manajemen Bendel
            </p>
        </div>
    </div>
</body>
</html>