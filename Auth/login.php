<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>E-School Login</title>
  <link rel="stylesheet" href="css/login.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
  <div class="login-container" id="page-content">
    <div class="login-left">
      <div class="login-box">
        <img src="../assets/logo-elearning.png" alt="E-School Logo" class="logo" />
        <h2>Login</h2>
        <form id="loginForm" novalidate>
          <div class="input-group">
            <label for="email">Email</label>
            <input type="email" id="email" placeholder="Email" />
            <div class="error-message" id="email-error">Wajib diisi.</div>
          </div>
          <div class="input-group">
            <label for="password">Password</label>
            <input type="password" id="password" placeholder="Password" />
            <div class="error-message" id="password-error">Wajib diisi.</div>
          </div>
          <div class="options">
            <a href="#" class="forgot">Lupa Password?</a>
          </div>
          <button type="submit" class="btn-login">Login</button>
        </form>

        <!-- Tombol kembali, sekarang bisa dianimasikan -->
        <button type="button" class="btn-back" id="btnBack">Kembali ke Beranda</button>
      </div>
    </div>
    <div class="login-right">
      <img src="../assets/studybg.png" alt="Login Illustration" />
    </div>
  </div>
</body>
  <script>
    const form = document.getElementById("loginForm");
    const email = document.getElementById("email");
    const password = document.getElementById("password");
    const emailError = document.getElementById("email-error");
    const passwordError = document.getElementById("password-error");
    const btnBack = document.getElementById("btnBack");
    const page = document.getElementById("page-content");

    // Tombol kembali (dengan animasi)
    btnBack.addEventListener("click", () => {
      page.classList.add("slide-out");
      setTimeout(() => {
        window.location.href = "../index.php";
      }, 600);
    });

    // Validasi manual
    form.addEventListener("submit", (e) => {
      e.preventDefault();

      let valid = true;

      [email, password].forEach(input => input.classList.remove("error"));
      [emailError, passwordError].forEach(msg => msg.classList.remove("active"));

      if (email.value.trim() === "") {
        email.classList.add("error");
        emailError.classList.add("active");
        valid = false;
      }

      if (password.value.trim() === "") {
        password.classList.add("error");
        passwordError.classList.add("active");
        valid = false;
      }

      if (valid) {
        alert("Login berhasil! (simulasi)");
      }
    });

    [email, password].forEach(input => {
      input.addEventListener("input", () => {
        input.classList.remove("error");
        if (input.id === "email") emailError.classList.remove("active");
        if (input.id === "password") passwordError.classList.remove("active");
      });
    });
  </script>
</html>