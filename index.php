<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>E-Learning Sekolah</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- File CSS -->
  <link rel="stylesheet" href="css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Rubik:wght@400;500&display=swap" rel="stylesheet">

</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg fixed-top bg-white shadow-sm">
  <div class="container-fluid">
    <!-- Logo -->
    <a class="navbar-brand me-auto" href="index.php">
      <img src="assets/logo-elearning.png" alt="Logo elearning" height="50" class="d-inline-block align-top">
    </a>

    <!-- Hamburger (mobile) -->
    <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" 
            data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar" 
            aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Menu utama -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" 
         aria-labelledby="offcanvasNavbarLabel">
      <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasNavbarLabel">Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>

      <div class="offcanvas-body d-flex justify-content-end">
        <!-- Nav pills styled menu -->
        <ul class="nav nav-pills nav-fill custom-nav" id="pillNav" role="tablist">
          <li class="nav-item" role="presentation">
            <a class="nav-link active rounded-5" href="index.php" role="tab">Home</a>
          </li>
          <li class="nav-item" role="presentation">
            <a class="nav-link rounded-5" href="view/tentang.php" role="tab">Tentang</a>
          </li>
        </ul>
        <ul class="nav flex-column d-lg-none w-100 text-start">
          <li class="nav-item">
            <a class="nav-link active" href="index.php">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="view/tentang.php">Tentang</a>
          </li>
        </ul>
      </div>
    </div>
  </div>
</nav>

<!-- Hero Section -->
<section class="hero-section d-flex align-items-center">
  <div class="container mt-5 pt-5">
    <div class="row align-items-center">
      <!-- Teks -->
      <div class="col-md-6">
        <h1 class="hero-title">E-<span>Learning</span></h1>
        <p class="hero-text">
          Selamat datang di E-Learning Sekolah, tempat di mana proses belajar mengajar menjadi lebih mudah 
          dan terhubung secara digital. Akses semua fitur mulai dari materi, tugas, hingga absensi dalam 
          satu platform terpadu. Silakan login untuk memulai pembelajaran dan nikmati pengalaman belajar 
          yang praktis dan menyenangkan.
        </p>

        <!-- Tombol Login selalu di bawah paragraf -->
        <a href="login.php" class="login-button mt-4">Login</a>
      </div>

      <!-- Gambar -->
      <div class="col-md-6 text-center position-relative">
        <div class="circle circle1">
          <img src="assets/smk4gerbang.jpg" alt="SMK" class="img-fluid rounded-circle">
        </div>
        <div class="circle circle2">
          <img src="assets/smk4depan.jpg" alt="Gerbang" class="img-fluid rounded-circle">
        </div>
        <div class="circle circle3">
          <img src="assets/smk4dalam.jpg" alt="Dalam" class="img-fluid rounded-circle">
        </div>
      </div>
    </div>
  </div>
</section>
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
