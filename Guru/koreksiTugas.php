<!-- buatTugas.php -->
<link rel="stylesheet" href="CSS/koreksiTugas.css">

<div class="form-container">
    <h2>Koreksi Tugas Siswa</h2>

    <form action="" method="POST" enctype="multipart/form-data">

        <!-- Dropdown Mata Pelajaran -->
        <label for="mapel">Mata Pelajaran</label>
        <select name="mapel" id="mapel">
            <option value="">-- Pilih Mata Pelajaran --</option>
            <option value="Matematika">Matematika</option>
            <option value="Bahasa Indonesia">Bahasa Indonesia</option>
            <option value="Bahasa Inggris">Bahasa Inggris</option>
            <option value="Seni Budaya">Seni Budaya</option>
        </select>

        <!-- Dropdown Kelas (muncul setelah mapel dipilih) -->
        <div id="kelasContainer" style="display:none;">
            <label for="kelas">Kelas</label>
            <select name="kelas" id="kelas">
                <option value="">-- Pilih Kelas --</option>
                <option value="X">X</option>
                <option value="XI">XI</option>
                <option value="XII">XII</option>
            </select>
        </div>
        
        <!-- Dropdown Sub Kelas (muncul setelah kelas dipilih) -->
        <div id="subkelasContainer" style="display:none;">
            <label for="subkelas">Sub Kelas</label>
            <select name="subkelas" id="subkelas">
                <option value="">-- Pilih Sub Kelas --</option>
            </select>
        </div>
    </form>
</div>


    <!-- Input lain muncul setelah subkelas dipilih -->
        <div id="inputLain" style="display:none;">
            


            <!-- disini harusnya muncul tabel yang isinya ada nama + kerjaannya siswa. jadi guru
             tinggal buka filenya untuk ngoreksi trus kasih nilai lgsng dalam tabel ini -->


            <div class="wide-section">
                <!-- INFORMASI TUGAS -->
                <div class="tugas-info">
                    <div class="tugas-header">
                        <div class="judul-container">
                            <label for="judulTugas">Judul Tugas:</label>
                            <select id="judulTugas" name="judulTugas">
                                <option value="">-- Pilih Tugas --</option>
                                <option value="Dasar Perhitungan">Dasar Perhitungan</option>
                                <option value="Rumus Dasar">Rumus Dasar</option>
                                <option value="Fungsi Linear">Fungsi Linear</option>
                            </select>
                        </div>

                        <div class="search-container">
                            <i class="fa fa-search"></i>
                            <input type="text" id="searchInput" placeholder="Search...">
                        </div>
                    </div>

                    <div class="tugas-deskripsi">
                        <p><strong>Deskripsi:</strong> 
                            <span id="deskripsiTugas">Silakan kerjakan latihan dasar sesuai materi yang telah diberikan.</span>
                        </p>
                        <p><strong>Tanggal:</strong> <span id="tanggalTugas">05 Oktober 2025</span></p>
                    </div>
                </div>

                <!-- TABEL KOREKSI -->
                <div class="table-container">
                    <table class="koreksi-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Pengumpulan</th>
                                <th>Status</th>
                                <th>File</th>
                                <th>Nilai</th>
                            </tr>
                        </thead>
                        <tbody id="tabelData">
                            <tr>
                                <td>1</td>
                                <td>Andi Pratama</td>
                                <td>05 Okt 2025</td>
                                <td>Terkumpul</td>
                                <td><a href="#" class="file-link">Lihat File</a></td>
                                <td><input type="number" class="nilai-input" min="0" max="100"></td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Bella Rahma</td>
                                <td>Belum Ada</td>
                                <td>Belum Mengumpulkan</td>
                                <td>-</td>
                                <td><input type="number" class="nilai-input" min="0" max="100" disabled></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- klo udah selesai ngoreksi -->
                <button type="submit" class="save-btn">💾 Simpan</button>
            </div>  
        </div>



<script>
    const mapel = document.getElementById("mapel");
    const kelasContainer = document.getElementById("kelasContainer");
    const kelas = document.getElementById("kelas");
    const subkelasContainer = document.getElementById("subkelasContainer");
    const subkelas = document.getElementById("subkelas");
    const inputLain = document.getElementById("inputLain");

    // Data subkelas bisa nanti diambil dari database juga
    const dataSubkelas = {
        "X": ["X-1", "X-2", "X-3"],
        "XI": ["XI-1", "XI-2", "XI-3"],
        "XII": ["XII-1", "XII-2"]
    };

    // Saat mapel dipilih
    mapel.addEventListener("change", () => {
        if (mapel.value !== "") {
            kelasContainer.style.display = "block";
        } else {
            kelasContainer.style.display = "none";
            subkelasContainer.style.display = "none";
            inputLain.style.display = "none";
        }
    });

    // Saat kelas dipilih
    kelas.addEventListener("change", () => {
        const kelasVal = kelas.value;
        if (kelasVal !== "") {
            subkelasContainer.style.display = "block";

            // Isi subkelas berdasarkan pilihan kelas
            subkelas.innerHTML = '<option value="">-- Pilih Sub Kelas --</option>';
            dataSubkelas[kelasVal].forEach(sub => {
                const opt = document.createElement("option");
                opt.value = sub;
                opt.textContent = sub;
                subkelas.appendChild(opt);
            });
        } else {
            subkelasContainer.style.display = "none";
            inputLain.style.display = "none";
        }
    });

    // Saat subkelas dipilih
    subkelas.addEventListener("change", () => {
        if (subkelas.value !== "") {
            inputLain.style.display = "block";
        } else {
            inputLain.style.display = "none";
        }
    });
</script>

