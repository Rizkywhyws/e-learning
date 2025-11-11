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
            <a href="#" class="forgot" id="forgotBtn">Lupa Password?</a>
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

<!-- ======== POPUP FORM LUPA PASSWORD (TANPA EMAIL) ======== -->
<div class="modal" id="forgotModal">
  <div class="modal-content">
    <h3>Atur Ulang Password</h3>
    <p>Masukkan email Anda dan buat password baru.</p>
    <form id="forgotForm" method="POST" action="forgot-password-process.php">
      <input type="email" name="email" placeholder="Masukkan email Anda" required>
      <input type="password" name="new_password" placeholder="Password Baru" required>
      <button type="submit">Ubah Password</button>
      <button type="button" class="close-modal" id="closeModal">Batal</button>
    </form>
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

    // Validasi manual
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
    });

    // Popup lupa password
    const forgotBtn = document.getElementById("forgotBtn");
    const modal = document.getElementById("forgotModal");
    const closeModal = document.getElementById("closeModal");

    forgotBtn.addEventListener("click", (e) => {
      e.preventDefault();
      modal.classList.add("active");
    });

    closeModal.addEventListener("click", () => {
      modal.classList.remove("active");
    });

    // Tutup modal saat klik luar area
    window.addEventListener("click", (e) => {
      if (e.target === modal) {
        modal.classList.remove("active");
      }
    });
  </script>
</body>
</html>
