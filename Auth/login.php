<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>E-School Login</title>
  <link rel="stylesheet" href="css/login.css" />
  <link rel="stylesheet" href="css/forgot-password.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
  <div class="login-container" id="page-content">
    <div class="login-left">
      <div class="login-box">
        <img src="../assets/logo-elearning.png" alt="E-School Logo" class="logo" />
        <h2>Login</h2>
        
        <?php
        // Tampilkan pesan error jika ada
        session_start();
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-error">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        ?>
        
        <form id="loginForm" method="POST" action="login-process.php" novalidate>
          <div class="input-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Email" />
            <div class="error-message" id="email-error">Wajib diisi.</div>
          </div>
          <div class="input-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Password" />
            <div class="error-message" id="password-error">Wajib diisi.</div>
          </div>
          <div class="options">
            <a href="../Auth/forgot-password.php" class="forgot">Lupa Password?</a>
          </div>
          <button type="submit" class="btn-login">Login</button>
        </form>

        <button type="button" class="btn-back" id="btnBack">Kembali ke Beranda</button>
      </div>
    </div>
    <div class="login-right">
      <img src="../assets/studybg.png" alt="Login Illustration" />
    </div>
  </div>

  <script>
    const form = document.getElementById("loginForm");
    const email = document.getElementById("email");
    const password = document.getElementById("password");
    const emailError = document.getElementById("email-error");
    const passwordError = document.getElementById("password-error");
    const btnBack = document.getElementById("btnBack");
    const page = document.getElementById("page-content");

    // Tombol kembali
    btnBack.addEventListener("click", () => {
      page.classList.add("slide-out");
      setTimeout(() => {
        window.location.href = "../index.php";
      }, 600);
    });

    // Validasi manual (TANPA alert simulasi)
    form.addEventListener("submit", (e) => {
      let valid = true;

      [email, password].forEach(input => input.classList.remove("error"));
      [emailError, passwordError].forEach(msg => msg.classList.remove("active"));

      if (email.value.trim() === "") {
        e.preventDefault();
        email.classList.add("error");
        emailError.classList.add("active");
        valid = false;
      }

      if (password.value.trim() === "") {
        e.preventDefault();
        password.classList.add("error");
        passwordError.classList.add("active");
        valid = false;
      }

      // Jika valid, form akan otomatis submit ke process_login.php
      // TIDAK ADA alert() lagi di sini
    });

    [email, password].forEach(input => {
      input.addEventListener("input", () => {
        input.classList.remove("error");
        if (input.id === "email") emailError.classList.remove("active");
        if (input.id === "password") passwordError.classList.remove("active");
      });
    });
  </script>
</body>
</html>