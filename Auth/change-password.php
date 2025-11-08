<?php
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
  header("Location: login.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Ubah Password - E-School</title>
  <link rel="stylesheet" href="css/login.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
  <div class="login-container" id="page-content">
    <div class="login-left">
      <div class="login-box">
        <img src="../assets/logo-elearning.png" alt="E-School Logo" class="logo" />
        <h2>Ubah Password</h2>
        <p style="text-align:center; color:#555;">Halo, <b><?= htmlspecialchars($_SESSION['nama']); ?></b> 👋<br>
        Kamu bisa ubah password default-mu di sini (atau lewati jika sudah).</p>

        <?php
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-error">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        ?>

        <form id="changeForm" method="POST" action="change-password-process.php" novalidate>
          <div class="input-group">
            <label for="new_password">Password Baru</label>
            <input type="password" id="new_password" name="new_password" placeholder="Masukkan password baru" />
            <div class="error-message" id="newpass-error">Wajib diisi.</div>
          </div>

          <div class="input-group">
            <label for="confirm_password">Konfirmasi Password</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Ulangi password baru" />
            <div class="error-message" id="confirmpass-error">Wajib diisi dan harus sama.</div>
          </div>

          <button type="submit" class="btn-login">Simpan Password</button>
        </form>

        <form method="POST" action="skip-password.php">
          <button type="submit" class="btn-back" style="margin-top:10px;">Lewati</button>
        </form>
      </div>
    </div>

    <div class="login-right">
      <img src="../assets/studybg.png" alt="Change Password Illustration" />
    </div>
  </div>

  <script>
    const form = document.getElementById("changeForm");
    const newPass = document.getElementById("new_password");
    const confirmPass = document.getElementById("confirm_password");
    const newPassErr = document.getElementById("newpass-error");
    const confirmPassErr = document.getElementById("confirmpass-error");

    form.addEventListener("submit", (e) => {
      let valid = true;
      [newPass, confirmPass].forEach(i => i.classList.remove("error"));
      [newPassErr, confirmPassErr].forEach(e => e.classList.remove("active"));

      if (newPass.value.trim() === "") {
        e.preventDefault();
        newPass.classList.add("error");
        newPassErr.classList.add("active");
        valid = false;
      }

      if (confirmPass.value.trim() === "" || confirmPass.value !== newPass.value) {
        e.preventDefault();
        confirmPass.classList.add("error");
        confirmPassErr.classList.add("active");
        valid = false;
      }

      if (!valid) return false;
    });

    [newPass, confirmPass].forEach(input => {
      input.addEventListener("input", () => {
        input.classList.remove("error");
        newPassErr.classList.remove("active");
        confirmPassErr.classList.remove("active");
      });
    });
  </script>
</body>
</html>
