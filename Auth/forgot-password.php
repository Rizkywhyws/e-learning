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
        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
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

<!-- ======== POPUP FORM LUPA PASSWORD (DUA TAHAP) ======== -->
<div class="modal" id="forgotModal">
  <div class="modal-content">
    <span class="close-btn" id="closeModal">&times;</span>

    <!-- Tahap 1: Masukkan Email dan Password Lama -->
    <div id="step1">
      <div class="modal-header">
        <h3>Verifikasi Akun</h3>
        <p>Masukkan email dan password lama Anda.</p>
      </div>
      <form id="verifyForm">
        <div class="form-group">
          <input type="email" id="verify-email" placeholder="Email Anda" required>
        </div>
        <div class="form-group">
          <input type="password" id="verify-old-password" placeholder="Password Lama" required>
        </div>
        <div class="error-message" id="verification-error" style="display: none;">
          Email atau password lama salah.
        </div>
        <button type="submit" class="btn-submit">Verifikasi</button>
        <button type="button" class="btn-cancel" id="closeModal2">Batal</button>
      </form>
    </div>

    <!-- Tahap 2: Ganti Password Baru -->
    <div id="step2" style="display: none;">
      <div class="modal-header">
        <h3>Ganti Password Baru</h3>
        <p>Silakan masukkan password baru Anda.</p>
      </div>
      <form id="changePasswordForm" method="POST" action="forgotpassword-process.php">
        <input type="hidden" name="email" id="verified-email" />
        <input type="hidden" name="old_password" id="verified-old-password" />
        <div class="form-group">
          <input type="password" name="new_password" id="new_password" placeholder="Password Baru" required>
        </div>
        <div class="form-group">
          <input type="password" name="confirm_password" id="confirm_password" placeholder="Konfirmasi Password Baru" required>
        </div>
        <div class="error-message" id="password-match-error" style="display: none;">
          Password dan konfirmasi tidak cocok.
        </div>
        <button type="submit" class="btn-submit">Simpan Password Baru</button>
        <button type="button" class="btn-cancel" id="backToStep1">Kembali</button>
      </form>
    </div>

  </div>
</div>

  <script>
// Popup lupa password
const forgotBtn = document.getElementById("forgotBtn");
const modal = document.getElementById("forgotModal");
const closeModal = document.getElementById("closeModal");

forgotBtn.addEventListener("click", (e) => {
  e.preventDefault();
  modal.classList.add("active");
  // Reset ke tahap 1
  document.getElementById("step1").style.display = "block";
  document.getElementById("step2").style.display = "none";
  document.getElementById("verifyForm").reset();
  document.getElementById("changePasswordForm").reset();
  document.getElementById("verification-error").style.display = "none";
  document.getElementById("password-match-error").style.display = "none";
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

// Tombol Batal (di modal)
document.querySelectorAll("#closeModal2, #closeModal").forEach(btn => {
  btn.addEventListener("click", () => modal.classList.remove("active"));
});

// Form Verifikasi (Tahap 1)
const verifyForm = document.getElementById("verifyForm");
verifyForm.addEventListener("submit", (e) => {
  e.preventDefault();

  const email = document.getElementById("verify-email").value;
  const oldPassword = document.getElementById("verify-old-password").value;

  // Verifikasi password lama via AJAX ke server
  fetch('verify-old-password.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, oldPassword })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // PENTING: Simpan email DAN password lama untuk dikirim ke step 2
      document.getElementById("verified-email").value = email;
      document.getElementById("verified-old-password").value = oldPassword;
      
      // Pindah ke tahap 2
      document.getElementById("step1").style.display = "none";
      document.getElementById("step2").style.display = "block";
      document.getElementById("verification-error").style.display = "none";
    } else {
      document.getElementById("verification-error").style.display = "block";
      document.getElementById("verification-error").textContent = data.message || "Email atau password lama salah.";
    }
  })
  .catch(() => {
    document.getElementById("verification-error").style.display = "block";
    document.getElementById("verification-error").textContent = "Terjadi kesalahan. Silakan coba lagi.";
  });
});

// Tombol Kembali ke Tahap 1
document.getElementById("backToStep1").addEventListener("click", () => {
  document.getElementById("step1").style.display = "block";
  document.getElementById("step2").style.display = "none";
  document.getElementById("verification-error").style.display = "none";
  document.getElementById("password-match-error").style.display = "none";
});

// Validasi Konfirmasi Password (Tahap 2)
const changePasswordForm = document.getElementById("changePasswordForm");
const newPassword = document.getElementById("new_password");
const confirmPass = document.getElementById("confirm_password");
const matchError = document.getElementById("password-match-error");

changePasswordForm.addEventListener("submit", (e) => {
  if (newPassword.value !== confirmPass.value) {
    e.preventDefault();
    matchError.style.display = "block";
  } else {
    matchError.style.display = "none";
    // Form akan submit secara normal ke forgotpassword-process.php
  }
});

confirmPass.addEventListener("input", () => {
  if (newPassword.value === confirmPass.value) {
    matchError.style.display = "none";
  }
});
  </script>
</body>
</html>