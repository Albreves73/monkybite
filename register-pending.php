<?php
// -------------------------------
// 1. Database connection
// -------------------------------
$host = "localhost";
$user = "root";          // coloque seu usuário do MySQL
$pass = "";              // coloque sua senha do MySQL
$dbname = "monkybite";   // coloque o nome do seu banco

$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// -------------------------------
// 2. Receive form data
// -------------------------------
$email     = trim($_POST['email']);
$firstName = trim($_POST['firstName']);
$lastName  = trim($_POST['lastName']);
$password  = trim($_POST['password']);
$plan      = trim($_POST['plan']);

// -------------------------------
// 3. Validate email
// -------------------------------
$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    // Email already exists
    echo "<script>
            alert('This email is already registered.');
            window.location.href = 'get-started.html?plan=$plan';
          </script>";
    exit;
}
$check->close();

// -------------------------------
// 4. Hash password
// -------------------------------
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// -------------------------------
// 5. Insert pending user
// -------------------------------
$stmt = $conn->prepare("
    INSERT INTO users (email, firstName, lastName, password, plan, status, created_at)
    VALUES (?, ?, ?, ?, ?, 'pending', NOW())
");

$stmt->bind_param("sssss", $email, $firstName, $lastName, $hashedPassword, $plan);

if (!$stmt->execute()) {
    die("Error saving pending registration: " . $stmt->error);
}

$stmt->close();
$conn->close();

// -------------------------------
// 6. Redirect to checkout
// -------------------------------
header("Location: checkout?plan=$plan&email=$email");
exit;

?>
