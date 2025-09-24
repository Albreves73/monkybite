// Simula dados de sessão para testes locais
sessionStorage.setItem("user_name", "Ana");
sessionStorage.setItem("plan", "starter"); // pode ser: free, starter, pro, enterprise
sessionStorage.setItem("access_token", "demo-token-123");

// Redireciona para o painel
window.location.href = "dashboard.html";
