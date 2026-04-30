<?php
// -------------------------------
// 1. Database connection
// -------------------------------
$host = "localhost";
$user = "monky";
$pass = "Cu123!";
$dbname = "monkybite";

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
$plan      = trim($_POST['plan']);

// -------------------------------
// 3. Validate email (professional logic)
// -------------------------------
$check = $conn->prepare("SELECT id, status FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();
$check->bind_result($existingId, $existingStatus);
$check->fetch();

if ($check->num_rows > 0) {

    if ($existingStatus === "active") {
        // Usuário já existe e já pagou → bloquear
        echo "<script>
                alert('This email is already registered.');
                window.location.href = 'get-started.html?plan=$plan';
              </script>";
        exit;
    }

    if ($existingStatus === "pending") {
        // Usuário já existe mas não pagou → continuar fluxo
        // Não cria novo usuário, apenas segue para o checkout
        $userId = $existingId;

        header("Location: /checkout/?plan=$plan&email=$email");
        exit;
    }
}

$check->close();

// -------------------------------
// 4. Insert pending user (first time)
// -------------------------------
$stmt = $conn->prepare("
    INSERT INTO users (email, firstName, lastName, plan, status, created_at)
    VALUES (?, ?, ?, ?, 'pending', NOW())
");

$stmt->bind_param("ssss", $email, $firstName, $lastName, $plan);

if (!$stmt->execute()) {
    die("Error saving pending registration: " . $stmt->error);
}

$stmt->close();
$conn->close();

// -------------------------------
// 5. Redirect to checkout
// -------------------------------
header("Location: /checkout/?plan=$plan&email=$email");
exit;

?>

