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
      max-width: 450px;
      margin: 60px auto;
    }

    .checkout-container h2 {
      margin-bottom: 20px;
      text-align: center;
      color: #1a2a4f;
    }

    #card-container {
      margin-top: 20px;
    }

    #payButton {
      width: 100%;
      padding: 14px;
      margin-top: 20px;
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
  </style>

  <!-- Square SDK -->
  <script src="https://web.squarecdn.com/v1/square.js"></script>

</head>
<body>

<!-- 🔷 Header -->
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

<!-- 🔷 Checkout -->
<div class="checkout-container">
  <h2>Complete Your Payment</h2>

  <p id="planText"></p>

  <div id="card-container"></div>
  <button id="payButton">Pay Now</button>

  <div id="error"></div>
</div>

<!-- 🔷 Footer -->
<footer>
  <p>© 2025 MonkyBite</p>
</footer>

<script>
  // Mobile menu
  const hamburgerBtn = document.getElementById("hamburger-btn");
  const mobileNav = document.getElementById("mobile-nav");
  hamburgerBtn.addEventListener("click", () => {
    mobileNav.classList.toggle("hidden");
  });

  // Get URL params
  const params = new URLSearchParams(window.location.search);
  const plan = params.get("plan");
  const email = params.get("email");
  const name = params.get("name");
  const password = params.get("password");

  document.getElementById("planText").innerText = "Selected plan: " + plan.toUpperCase();

  // Square Payment
  async function startSquare() {
    const payments = Square.payments(
      "sq0idp-JHruqkfGcQdQfmgDQYjnUQ",
      "LTZ1WY5B11Q9Q"
    );

    const card = await payments.card();
    await card.attach("#card-container");

    document.getElementById("payButton").addEventListener("click", async () => {
      const result = await card.tokenize();

      if (result.status === "OK") {
        fetch("process-payment.php", {
          method: "POST",
          headers: {"Content-Type": "application/json"},
          body: JSON.stringify({
            token: result.token,
            plan: plan,
            email: email
          })
        })
        .then(r => r.json())
        .then(r => {
          if (r.success) {
            window.location.href =
              "payment-success.php?plan=" + encodeURIComponent(plan) +
              "&email=" + encodeURIComponent(email) +
              "&name=" + encodeURIComponent(name) +
              "&password=" + encodeURIComponent(password);
          } else {
            document.getElementById("error").innerText = r.error;
          }
        });
      }
    });
  }

  startSquare();
</script>

</body>
</html>









