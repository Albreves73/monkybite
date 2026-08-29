<?php
$config = include "config.php";

$plan     = $_GET["plan"]     ?? "starter";
$email    = $_GET["email"]    ?? "";
$name     = $_GET["name"]     ?? "";
$password = $_GET["password"] ?? "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Checkout — MonkyBite</title>
  <link rel="stylesheet" href="style.css" />

  <style>
    .checkout-container {
      background: white;
      padding: 40px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 480px;
      margin: 60px auto;
    }
    .checkout-container h2 {
      margin-bottom: 10px;
      text-align: center;
      color: #1a2a4f;
    }
    .checkout-container p {
      text-align: center;
      margin-bottom: 20px;
    }
    #card-container {
      margin-top: 20px;
    }
    #payButton {
      width: 100%;
      padding: 14px;
      margin-top: 15px;
      background-color: #1a73e8;
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 16px;
      cursor: pointer;
    }
    #payButton:hover {
      background-color: #0049c6;
    }
    #error {
      color: red;
      text-align: center;
      margin-top: 10px;
    }
    .wallet-buttons {
      margin-bottom: 20px;
    }
    .wallet-buttons > div {
      margin-bottom: 10px;
    }
    .divider {
      text-align: center;
      margin: 15px 0;
      color: #777;
      font-size: 14px;
    }
  </style>

  <!-- Square SDK (produção) -->
  <script src="https://web.squarecdn.com/v1/square.js"></script>

  <!-- PayPal SDK -->
  <script src="https://www.paypal.com/sdk/js?client-id=SEU_CLIENT_ID_PAYPAL&currency=USD"></script>
</head>
<body>

<header>
  <a href="index.html" class="logo">
    <img src="logo.png" alt="MonkyBite Logo" />
    <span class="brand-name">MonkyBite</span>
  </a>

  <div class="nav-wrapper">
    <nav class="nav-desktop">
      <a href="index.html">HOME</a>
      <a href="login.html">LOGIN</a>
      <a href="plans.html">SIGN UP</a>
      <a href="contact.html">CONTACT</a>
    </nav>

    <button class="hamburger" id="hamburger-btn">☰</button>
    <nav class="nav-mobile hidden" id="mobile-nav">
      <a href="index.html">HOME</a>
      <a href="login.html">LOGIN</a>
      <a href="plans.html">SIGN UP</a>
      <a href="contact.html">CONTACT</a>
    </nav>
  </div>
</header>

<div class="go-back-wrapper">
  <a href="javascript:history.back()" class="go-back">Go Back</a>
</div>

<div class="checkout-container">
  <h2>Complete Your Payment</h2>
  <p>Selected plan: <?php echo strtoupper(htmlspecialchars($plan)); ?></p>

  <!-- Wallet buttons -->
  <div class="wallet-buttons">
    <div id="apple-pay-button"></div>
    <div id="google-pay-button"></div>
    <div id="paypal-button-container"></div>
  </div>

  <div class="divider">Or pay with card</div>

  <div id="card-container"></div>
  <button id="payButton">Pay with Card</button>

  <div id="error"></div>
</div>

<footer>
  <p>© 2025 MonkyBite</p>
</footer>

<script>
  const plan     = "<?php echo htmlspecialchars($plan); ?>";
  const email    = "<?php echo htmlspecialchars($email); ?>";
  const name     = "<?php echo htmlspecialchars($name); ?>";
  const password = "<?php echo htmlspecialchars($password); ?>";

  // ============================
  // PAYPAL
  // ============================
  paypal.Buttons({
    style: {
      layout: 'vertical',
      color: 'blue',
      shape: 'rect',
      label: 'paypal'
    },
    createOrder: function(data, actions) {
      const value =
        plan === "starter"    ? "4.99" :
        plan === "pro"        ? "9.99" :
        plan === "enterprise" ? "19.99" : "4.99";

      return actions.order.create({
        purchase_units: [{
          amount: { value: value },
          description: "MonkyBite subscription (" + plan + ")"
        }]
      });
    },
    onApprove: function(data, actions) {
      return actions.order.capture().then(function(details) {
        fetch("process-payment.php", {
          method: "POST",
          headers: {"Content-Type": "application/json"},
          body: JSON.stringify({
            token: details.id,
            plan: plan,
            email: email,
            name: name,
            password: password,
            method: "paypal"
          })
        })
        .then(r => r.json())
        .then(r => {
          if (r.success) {
            window.location.href =
              "payment-success.php?plan=" + encodeURIComponent(plan) +
              "&email=" + encodeURIComponent(email) +
              "&name=" + encodeURIComponent(name);
          } else {
            document.getElementById("error").innerText = r.error || "Payment failed.";
          }
        });
      });
    },
    onError: function(err) {
      document.getElementById("error").innerText = "PayPal error: " + err;
    }
  }).render('#paypal-button-container');

  // ============================
  // SQUARE (Apple Pay, Google Pay, Card)
  // ============================
  async function startSquare() {
    const payments = Square.payments(
      "<?php echo $config['square_application_id']; ?>",
      "<?php echo $config['square_location_id']; ?>"
    );

    // Apple Pay
    try {
      const applePay = await payments.applePay();
      const canApplePay = await applePay.canMakePayment();
      if (canApplePay) {
        await applePay.attach('#apple-pay-button');
        document.querySelector('#apple-pay-button button')
          .addEventListener('click', async () => {
            const result = await applePay.tokenize();
            if (result.status === "OK") {
              await sendPayment(result.token, "apple_pay");
            }
          });
      }
    } catch (e) {}

    // Google Pay
    try {
      const googlePay = await payments.googlePay();
      const canGooglePay = await googlePay.canMakePayment();
      if (canGooglePay) {
        await googlePay.attach('#google-pay-button');
        document.querySelector('#google-pay-button button')
          .addEventListener('click', async () => {
            const result = await googlePay.tokenize();
            if (result.status === "OK") {
              await sendPayment(result.token, "google_pay");
            }
          });
      }
    } catch (e) {}

    // Card
    const card = await payments.card();
    await card.attach("#card-container");

    document.getElementById("payButton").addEventListener("click", async () => {
      const result = await card.tokenize();
      if (result.status === "OK") {
        await sendPayment(result.token, "card");
      } else {
        document.getElementById("error").innerText = "Card payment failed.";
      }
    });
  }

  async function sendPayment(token, method) {
    const res = await fetch("process-payment.php", {
      method: "POST",
      headers: {"Content-Type": "application/json"},
      body: JSON.stringify({
        token: token,
        plan: plan,
        email: email,
        name: name,
        password: password,
        method: method
      })
    });

    const r = await res.json();
    if (r.success) {
      window.location.href =
        "payment-success.php?plan=" + encodeURIComponent(plan) +
        "&email=" + encodeURIComponent(email) +
        "&name=" + encodeURIComponent(name);
    } else {
      document.getElementById("error").innerText = r.error || "Payment failed.";
    }
  }

  startSquare();
</script>

</body>
</html>








