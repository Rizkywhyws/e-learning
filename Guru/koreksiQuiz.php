<?php
session_start();
include('../config/db.php');

// Cek apakah guru sudah login
if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'guru') {
    if (isset($_GET['action'])) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Anda harus login sebagai guru!']);
        exit;
    }
    echo "<script>alert('Anda harus login sebagai guru!'); window.location='../Auth/login.php';</script>";
    exit;
}

$nipGuru = $_SESSION['nip']; // Ambil NIP dari session

header('Content-Type: application/json');

function jsonResponse($data) {
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

$action = $_GET['action'] ?? null;

if (!$action) {
    // Jika tidak ada action, tampilkan halaman HTML-nya
    header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Koreksi Quiz</title>
<link rel="stylesheet" href="css/koreksiQuiz.css">
</head>
<body>
<div class="container">
    <h2>KOREKSI QUIZ</h2>

    <div class="form-section">
        <label>Mata Pelajaran:</label>
        <select id="mapelSelect">
            <option value="">-- Pilih Mapel --</option>
        </select>

        <label>Kelas:</label>
        <select id="kelasSelect">
            <option value="">-- Pilih Kelas --</option>
        </select>

        <label>Judul Quiz:</label>
        <select id="quizSelect">
            <option value="">-- Pilih Quiz --</option>
        </select>

        <div class="search-container">
            <input type="text" id="siswaSearch" placeholder="Cari nama siswa...">
            <button id="loadBtn">LOAD</button>
        </div>
    </div>

    <div id="quizArea">
        <p id="question"></p>
        <div id="answerBox">-</div>

        <div class="nilai-container">
            <label>Input Nilai:</label>
            <input type="number" id="nilaiInput" min="0" max="100" placeholder="Masukkan nilai (0-100)">
        </div>

        <div class="nav-btns">
            <button id="backBtn">BACK</button>
            <span id="indexLabel">0/0</span>
            <button id="nextBtn">NEXT</button>
        </div>

        <button id="saveBtn" class="save">SIMPAN</button>
    </div>
</div>

<script>
const base = 'koreksiQuiz.php?action=';
let state = { idQuiz:'', NIS:'', idx:0, total:0, idSoal:'' };

async function api(url) {
    const res = await fetch(url);
    return res.json();
}

function alertMsg(msg) { alert(msg); }

document.addEventListener('DOMContentLoaded', async ()=>{
    const mapelSel = document.getElementById('mapelSelect');
    const kelasSel = document.getElementById('kelasSelect');
    const quizSel = document.getElementById('quizSelect');
    const siswaSearch = document.getElementById('siswaSearch');
    const loadBtn = document.getElementById('loadBtn');
    const backBtn = document.getElementById('backBtn');
    const nextBtn = document.getElementById('nextBtn');
    const saveBtn = document.getElementById('saveBtn');
    const qText = document.getElementById('question');
    const aBox = document.getElementById('answerBox');
    const nilaiInput = document.getElementById('nilaiInput');
    const idxLabel = document.getElementById('indexLabel');

    // ambil mapel yang diampu guru ini
    const mapel = await api(base+'get_mapel');
    if(mapel.error) return alertMsg(mapel.error);
    if(mapel.message) return alertMsg(mapel.message);
    mapel.forEach(m=>{
        const o=document.createElement('option');
        o.value=m.kodeMapel; 
        o.textContent=m.namaMapel;
        mapelSel.appendChild(o);
    });

    // ambil kelas yang diajar guru ini
    const kelas = await api(base+'get_kelas');
    if(kelas.error) return alertMsg(kelas.error);
    if(kelas.message) return alertMsg(kelas.message);
    kelas.forEach(k=>{
        const o=document.createElement('option');
        o.value=k; o.textContent=k;
        kelasSel.appendChild(o);
    });

    // load quiz berdasarkan mapel dan kelas
    async function loadQuizOptions(){
        quizSel.innerHTML = '<option value="">-- Pilih Quiz --</option>';
        if(!mapelSel.value || !kelasSel.value) return;
        const data = await api(`base+get_quiz_by_filter&mapel=${mapelSel.value}&kelas=${kelasSel.value}`);
        if(data.error) return alertMsg(data.error);
        if(data.message) return alertMsg(data.message);
        data.forEach(q=>{
            const o=document.createElement('option');
            o.value=q.idQuiz;
            o.textContent=q.judul;
            quizSel.appendChild(o);
        });
    }

    mapelSel.addEventListener('change', loadQuizOptions);
    kelasSel.addEventListener('change', loadQuizOptions);

    let selectedNIS='';

    siswaSearch.addEventListener('change', async ()=>{
        const selectedKelas = kelasSel.value;
        if(!selectedKelas){
            alertMsg('Pilih kelas terlebih dahulu.');
            return;
        }
        const data=await api(base+'search_siswa&term='+siswaSearch.value+'&kelas='+selectedKelas);
        if(data.error) return alertMsg(data.error);
        if(data.message) return alertMsg(data.message);
        if(data.length>0){
            const s = data[0];
            selectedNIS = s.NIS;
            alertMsg(`Siswa ditemukan: ${s.nama} (${s.kelas} - ${s.jurusan})`);
        }
    });

    loadBtn.addEventListener('click', ()=>{
        if(!quizSel.value || !selectedNIS){
            alertMsg('Harap pilih quiz dan siswa terlebih dahulu.');
            return;
        }
        state.idQuiz=quizSel.value;
        state.NIS=selectedNIS;
        state.idx=0;
        loadQuestion();
    });

    backBtn.addEventListener('click', ()=>{
        if(state.idx>0){state.idx--; loadQuestion();}
        else alertMsg('Ini soal pertama.');
    });

    nextBtn.addEventListener('click', ()=>{
        if(state.idx<state.total-1){state.idx++; loadQuestion();}
        else alertMsg('Ini soal terakhir.');
    });

    saveBtn.addEventListener('click', async ()=>{
        if(!state.idSoal){alertMsg('Belum ada soal yang aktif.'); return;}
        const fd=new FormData();
        fd.append('idQuiz',state.idQuiz);
        fd.append('idSoal',state.idSoal);
        fd.append('NIS',state.NIS);
        fd.append('nilai',nilaiInput.value);
        const res=await fetch('koreksiQuiz.php?action=save_nilai',{method:'POST',body:fd});
        const js=await res.json();
        if(js.error) alertMsg(js.error);
        else if(js.warning) alertMsg(js.warning);
        else if(js.success) alertMsg(js.success);
    });

    async function loadQuestion(){
        qText.textContent='Memuat soal...';
        aBox.textContent='';
        nilaiInput.value='';
        const data=await api(`base+load_question&idQuiz=${state.idQuiz}&NIS=${state.NIS}&idx=${state.idx}`);
        if(data.error){alertMsg(data.error); qText.textContent='Error: '+data.error; return;}
        qText.textContent=data.question;
        aBox.textContent=data.jawaban || '(Siswa belum mengisi jawaban)';
        nilaiInput.value=data.nilai;
        state.total=data.total;
        state.idSoal=data.idSoal;
        idxLabel.textContent=(state.idx+1)+'/'+data.total;
    }
});
</script>
</body>
</html>
<?php
exit;
}

// ====================== API HANDLER ======================
switch ($action) {

    // --- Ambil daftar mapel yang diampu guru ini ---
    case 'get_mapel':
        $q = mysqli_query($conn, "
            SELECT DISTINCT m.kodeMapel, m.namaMapel 
            FROM jadwalmapel jm
            JOIN mapel m ON jm.kodeMapel = m.kodeMapel
            WHERE jm.nipGuru = '$nipGuru'
            ORDER BY m.namaMapel ASC
        ");
        if (!$q) jsonResponse(['error' => 'Query mapel gagal: '.mysqli_error($conn)]);
        $data = [];
        while ($r = mysqli_fetch_assoc($q)) $data[] = $r;
        if (!$data) jsonResponse(['message' => 'Anda belum mengampu mata pelajaran.']);
        jsonResponse($data);
        break;

    // --- Ambil daftar kelas yang diajar guru ini ---
    case 'get_kelas':
        $q = mysqli_query($conn, "
            SELECT DISTINCT kelas 
            FROM jadwalmapel 
            WHERE nipGuru = '$nipGuru'
            ORDER BY kelas ASC
        ");
        if (!$q) jsonResponse(['error' => 'Query kelas gagal: '.mysqli_error($conn)]);
        $kelas = [];
        while ($r = mysqli_fetch_assoc($q)) $kelas[] = $r['kelas'];
        if (!$kelas) jsonResponse(['message' => 'Anda belum mengajar di kelas manapun.']);
        jsonResponse($kelas);
        break;

    // --- Ambil quiz sesuai mapel & kelas (milik guru ini) ---
    case 'get_quiz_by_filter':
        $mapel = mysqli_real_escape_string($conn, $_GET['mapel'] ?? '');
        $kelas = mysqli_real_escape_string($conn, $_GET['kelas'] ?? '');
        
        if (!$mapel || !$kelas) jsonResponse(['error'=>'Mapel dan kelas wajib dipilih.']);
        
        // Pastikan quiz yang ditampilkan hanya milik guru yang login
        $q = mysqli_query($conn, "
            SELECT idQuiz, judul 
            FROM quiz 
            WHERE kodeMapel='$mapel' 
            AND kelas='$kelas'
            AND NIP='$nipGuru'
            ORDER BY judul ASC
        ");
        if (!$q) jsonResponse(['error'=>'Query quiz gagal: '.mysqli_error($conn)]);
        $data=[];
        while($r=mysqli_fetch_assoc($q)) $data[]=$r;
        if(!$data) jsonResponse(['message'=>'Belum ada quiz untuk mapel dan kelas ini.']);
        jsonResponse($data);
        break;

    // --- Cari siswa (sesuai kelas yang dipilih) ---
    case 'search_siswa':
        $term = mysqli_real_escape_string($conn, $_GET['term'] ?? '');
        $kelas = mysqli_real_escape_string($conn, $_GET['kelas'] ?? '');
        
        if (!$term) jsonResponse(['message' => 'Masukkan nama siswa terlebih dahulu.']);
        if (!$kelas) jsonResponse(['message' => 'Pilih kelas terlebih dahulu.']);
        
        $q = mysqli_query($conn, "
            SELECT s.NIS, s.nama, s.kelas, s.jurusan, a.email
            FROM datasiswa s
            LEFT JOIN akun a ON s.idAkun = a.idAkun
            WHERE s.nama LIKE '%$term%'
            AND s.kelas = '$kelas'
            ORDER BY s.nama ASC
        ");
        if (!$q) jsonResponse(['error' => 'Query gagal: '.mysqli_error($conn)]);
        $data = [];
        while ($r = mysqli_fetch_assoc($q)) $data[] = $r;
        if (!$data) jsonResponse(['message' => 'Nama siswa tidak ditemukan di kelas ini.']);
        jsonResponse($data);
        break;

    // --- Load soal & jawaban siswa (hanya esai) ---
    case 'load_question':
        $idQuiz = mysqli_real_escape_string($conn, $_GET['idQuiz'] ?? '');
        $NIS = mysqli_real_escape_string($conn, $_GET['NIS'] ?? '');
        $idx = intval($_GET['idx'] ?? 0);

        if (!$idQuiz || !$NIS) jsonResponse(['error' => 'Parameter tidak lengkap.']);

        // Pastikan quiz ini milik guru yang login
        $cekQuiz = mysqli_query($conn, "SELECT * FROM quiz WHERE idQuiz='$idQuiz' AND NIP='$nipGuru'");
        if (mysqli_num_rows($cekQuiz) == 0) jsonResponse(['error' => 'Quiz ini bukan milik Anda.']);

        $soalQ = mysqli_query($conn, "SELECT * FROM soalquiz WHERE idQuiz='$idQuiz' AND type='esai'");
        if (!$soalQ) jsonResponse(['error' => 'Query soal gagal: '.mysqli_error($conn)]);
        $total = mysqli_num_rows($soalQ);
        if ($total == 0) jsonResponse(['error' => 'Tidak ada soal esai pada quiz ini.']);
        mysqli_data_seek($soalQ, $idx);
        $soal = mysqli_fetch_assoc($soalQ);

        $jawQ = mysqli_query($conn, "SELECT jawabanEsai AS jawaban, nilai FROM jawabanquiz WHERE NIS='$NIS' AND idSoal='{$soal['idSoal']}'");
        $jaw = mysqli_fetch_assoc($jawQ);

        jsonResponse([
            'idSoal' => $soal['idSoal'],
            'question' => $soal['pertanyaan'],
            'jawaban' => $jaw['jawaban'] ?? '',
            'nilai' => $jaw['nilai'] ?? '',
            'total' => $total
        ]);
        break;

    // --- Simpan nilai ---
    case 'save_nilai':
        $idQuiz = mysqli_real_escape_string($conn, $_POST['idQuiz'] ?? '');
        $idSoal = mysqli_real_escape_string($conn, $_POST['idSoal'] ?? '');
        $NIS = mysqli_real_escape_string($conn, $_POST['NIS'] ?? '');
        $nilai = mysqli_real_escape_string($conn, $_POST['nilai'] ?? '');

        if (!$idQuiz || !$idSoal || !$NIS) jsonResponse(['error' => 'Data belum lengkap.']);
        if ($nilai === '') jsonResponse(['warning' => 'Nilai belum diisi.']);

        // Pastikan quiz ini milik guru yang login
        $cekQuiz = mysqli_query($conn, "SELECT * FROM quiz WHERE idQuiz='$idQuiz' AND NIP='$nipGuru'");
        if (mysqli_num_rows($cekQuiz) == 0) jsonResponse(['error' => 'Quiz ini bukan milik Anda.']);

        $cek = mysqli_query($conn, "SELECT * FROM jawabanquiz WHERE idSoal='$idSoal' AND NIS='$NIS'");
        if (mysqli_num_rows($cek) == 0)
            jsonResponse(['error' => 'Jawaban siswa tidak ditemukan.']);

        $u = mysqli_query($conn, "UPDATE jawabanquiz SET nilai='$nilai' WHERE idSoal='$idSoal' AND NIS='$NIS'");
        if (!$u) jsonResponse(['error' => 'Gagal menyimpan nilai: '.mysqli_error($conn)]);
        jsonResponse(['success' => 'Nilai berhasil disimpan.']);
        break;

    default:
        jsonResponse(['error' => 'Aksi tidak dikenal atau tidak diberikan.']);
}
?>