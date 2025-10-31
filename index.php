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
      <a class="navbar-brand me-auto" href="index.php">
        <img src="assets/logo-elearning.png" alt="Logo elearning" height="50">
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" 
              data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar">
        <div class="offcanvas-header">
          <h5 class="offcanvas-title">Menu</h5>
          <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>

        <div class="offcanvas-body d-flex justify-content-end">
          <ul class="nav nav-pills nav-fill custom-nav">
            <li class="nav-item">
              <a class="nav-link active rounded-5" href="index.php" data-animate-link="left">Home</a>
            </li>
            <li class="nav-item">
              <a class="nav-link rounded-5" href="view/tentang.php" data-animate-link="right">Tentang</a>
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
        <div class="col-md-6">
          <h1 class="hero-title">E-<span>Learning</span></h1>
          <p class="hero-text">
            Selamat datang di E-Learning Sekolah, tempat di mana proses belajar mengajar menjadi lebih mudah 
            dan terhubung secara digital. Akses semua fitur mulai dari materi, tugas, hingga absensi dalam 
            satu platform terpadu.Silahkan login untuk memulai pembelajaran dan nikmati pengalaman belajar
            yang praktis dan menyenangkan
          </p>
          <a href="/elearning-app/Auth/login.php" class="login-button mt-4" data-animate-link="fade">Login</a>
        </div>
        <div class="col-md-6 text-center position-relative">
          <div class="circle circle1">
            <img src="assets/smk4gerbang.jpg" class="img-fluid rounded-circle" alt="SMK">
          </div>
          <div class="circle circle2">
            <img src="assets/smk4depan.jpg" class="img-fluid rounded-circle" alt="Gerbang">
          </div>
          <div class="circle circle3">
            <img src="assets/smk4dalam.jpg" class="img-fluid rounded-circle" alt="Dalam">
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Script Transisi -->
  <script>
  const links = document.querySelectorAll('[data-animate-link]');

  links.forEach(link => {
    link.addEventListener('click', e => {
      e.preventDefault();
      const target = link.getAttribute('href');
      const type = link.getAttribute('data-animate-link');

      let animation;

      // Tentukan animasi keluar
      if (type === 'right') {
        animation = document.body.animate([
          { transform: 'translateX(0)', opacity: 1 },
          { transform: 'translateX(120px)', opacity: 0 }
        ], { duration: 600, easing: 'ease' });
        localStorage.setItem('entryAnim', 'left');
      } else if (type === 'left') {
        animation = document.body.animate([
          { transform: 'translateX(0)', opacity: 1 },
          { transform: 'translateX(-120px)', opacity: 0 }
        ], { duration: 600, easing: 'ease' });
        localStorage.setItem('entryAnim', 'right');
      } else {
        animation = document.body.animate([
          { opacity: 1, transform: 'scale(1)' },
          { opacity: 0, transform: 'scale(0.97)' }
        ], { duration: 600, easing: 'ease' });
        localStorage.setItem('entryAnim', 'fade');
      }

      animation.onfinish = () => {
        window.location.href = target;
      };
    });
  });

  // Animasi masuk saat halaman dimuat
  window.addEventListener('DOMContentLoaded', () => {
    const entryAnim = localStorage.getItem('entryAnim');
    
    if (entryAnim === 'left') {
      document.body.animate([
        { transform: 'translateX(-120px)', opacity: 0 },
        { transform: 'translateX(0)', opacity: 1 }
      ], { duration: 600, easing: 'ease', fill: 'forwards' });
    } else if (entryAnim === 'right') {
      document.body.animate([
        { transform: 'translateX(120px)', opacity: 0 },
        { transform: 'translateX(0)', opacity: 1 }
      ], { duration: 600, easing: 'ease', fill: 'forwards' });
    } else if (entryAnim === 'fade') {
      document.body.animate([
        { opacity: 0 },
        { opacity: 1 }
      ], { duration: 600, easing: 'ease', fill: 'forwards' });
    }

    if (entryAnim) {
      setTimeout(() => {
        localStorage.removeItem('entryAnim');
      }, 700);
    }
  });
</script>
</body>
</html>