<!-- buatTugas.php -->
<link rel="stylesheet" href="CSS/buatTugas.css">

<div class="form-container">
    <h2>Tambah / Buat Tugas</h2>

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

        <!-- Input lain muncul setelah subkelas dipilih -->
        <div id="inputLain" style="display:none;">
            <label for="judul">Judul Tugas</label>
            <input type="text" id="judul" name="judul" placeholder="Masukkan judul tugas">

            <label for="deskripsi">Deskripsi Tugas</label>
            <textarea id="deskripsi" name="deskripsi" placeholder="Tuliskan deskripsi tugas..."></textarea>

            <label for="deadline">Deadline Tugas</label>
            <input type="date" id="deadline" name="deadline">

            <div class="upload-box">
                <label for="file">Upload File</label><br>
                <input type="file" id="file" name="file">
            </div>

            <br>
            <button type="submit" class="save-btn">💾 Simpan</button>
        </div>
    </form>
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

