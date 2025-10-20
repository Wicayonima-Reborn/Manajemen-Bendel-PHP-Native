<?php
echo "<h2> Generate Password Hash</h2>";
echo "<hr>";
$password = "admin12345sandiadmin";
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h3>Password: <strong>" . $password . "</strong></h3>";
echo "<h3>Hash:</h3>";
echo "<textarea style='width:100%;height:80px;font-family:monospace;padding:10px'>" . $hash . "</textarea>";

echo "<hr>";
echo "<ul>";
echo "<li>Username: <strong>admin</strong> | Password: <strong>admin12345sandiadmin</strong></li>";
echo "<li>Username: <strong>budi</strong> | Password: <strong>12345</strong></li>";
echo "<li>Username: <strong>siti</strong> | Password: <strong>12345</strong></li>";
echo "<li>Username: <strong>andi</strong> | Password: <strong>12345</strong></li>";
echo "</ul>";
echo "<hr>";
echo "<h3> Test Verify (untuk memastikan):</h3>";
if (password_verify("admin12345sandiadmin", $hash)) {
    echo "<p style='color:green;font-weight:bold'> Hash VALID! Password 'admin12345sandiadmin' akan work!</p>";
} else {
    echo "<p style='color:red;font-weight:bold'> Ada masalah dengan hash</p>";
}

?>
