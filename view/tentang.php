<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>E-Learning Sekolah</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- File CSS -->
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/tentang.css">
  <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Rubik:wght@400;500&display=swap" rel="stylesheet">

</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg fixed-top bg-white shadow-sm">
  <div class="container-fluid">
    <!-- Logo -->
    <a class="navbar-brand me-auto" href="index.php">
      <img src="../assets/logo-elearning.png" alt="Logo elearning" height="50" class="d-inline-block align-top">
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
            <a class="nav-link rounded-5" href="../index.php" role="tab">Home</a>
          </li>
          <li class="nav-item" role="presentation">
            <a class="nav-link active rounded-5" href="tentang.php" role="tab">Tentang</a>
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
<section class="hero-section tentang-section d-flex align-items-center">
  <div class="container">
    <div class="row">
      <div class="col-12">
        <h1 class="hero-title">Tentang</h1>
        <p class="tentang-text">
        E-Learning SMK adalah sistem pembelajaran digital yang dirancang untuk mendukung 
        kegiatan belajar mengajar secara daring di lingkungan Sekolah Menengah Kejuruan.

        Melalui platform ini, seluruh proses pembelajaran dapat berlangsung secara fleksibel, efisien, 
        dan terdokumentasi dengan baik.

        Melalui E-Learning, siswa dapat mengakses materi pelajaran, mengerjakan tugas dan ujian secara online, 
        serta berkomunikasi dengan guru kapan pun dan di mana pun.

        Sementara itu, guru dapat mengelola materi, memberikan penilaian, mengawasi perkembangan siswa, 
        dan mempublikasikan pengumuman dengan lebih mudah.
        </p>
      </div>
    </div>
  </div>
</section>
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>