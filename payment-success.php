<?php
// Get the plan from Square redirect
$plan = $_GET['plan'] ?? 'starter';

// Redirect to signup page with payment confirmation
header("Location: signup.html?paid=true&plan=" . $plan);
exit();
?>
