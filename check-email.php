<?php
header('Content-Type: application/json');

if (!isset($_POST['email'])) {
    echo json_encode(['exists' => false]);
    exit;
}

$email = trim($_POST['email']);

$conn = new mysqli("localhost", "DB_USER", "DB_PASS", "DB_NAME");

if ($conn->connect_error) {
    echo json_encode(['exists' => false]);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

echo json_encode(['exists' => $result->num_rows > 0]);

$stmt->close();
$conn->close();
?>
