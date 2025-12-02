<?php
require_once "../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Create new Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set judul worksheet
$sheet->setTitle('Data Siswa');

// Header kolom
$headers = ['NIS', 'NISN', 'Nama', 'Kelas', 'Jurusan', 'Email', 'Password'];
$sheet->fromArray($headers, NULL, 'A1');

// Styling header
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 12
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '2E7DFF']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];

$sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

// Set lebar kolom
$sheet->getColumnDimension('A')->setWidth(15);
$sheet->getColumnDimension('B')->setWidth(18);
$sheet->getColumnDimension('C')->setWidth(25);
$sheet->getColumnDimension('D')->setWidth(12);
$sheet->getColumnDimension('E')->setWidth(20);
$sheet->getColumnDimension('F')->setWidth(30);
$sheet->getColumnDimension('G')->setWidth(15);

// Contoh data (optional)
$exampleData = [
    ['20230001', '0051234567', 'Budi Santoso', 'X-1', 'Akuntansi', 'budi@example.com', 'password123'],
    ['20230002', '0051234568', 'Siti Aminah', 'X-2', 'Akuntansi', 'siti@example.com', 'password123'],
    ['20230003', '0051234569', 'Ahmad Fauzi', 'XI-1', 'Pemasaran', 'ahmad@example.com', 'password123']
];

$sheet->fromArray($exampleData, NULL, 'A2');

// Styling data
$dataStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'CCCCCC']
        ]
    ]
];

$sheet->getStyle('A2:G4')->applyFromArray($dataStyle);

// Set row height
$sheet->getRowDimension(1)->setRowHeight(25);

// Tambahkan instruksi di sheet kedua
$instructionSheet = $spreadsheet->createSheet();
$instructionSheet->setTitle('Instruksi');

$instructions = [
    ['INSTRUKSI PENGGUNAAN TEMPLATE IMPORT DATA SISWA'],
    [''],
    ['1. Pastikan semua kolom terisi dengan benar sesuai header:'],
    ['   - NIS: Nomor Induk Siswa (angka)'],
    ['   - NISN: Nomor Induk Siswa Nasional (angka)'],
    ['   - Nama: Nama lengkap siswa'],
    ['   - Kelas: Format X-1, X-2, XI-1, XI-2, XII-1, XII-2'],
    ['   - Jurusan: Nama jurusan (contoh: Akuntansi, Pemasaran)'],
    ['   - Email: Email valid untuk login'],
    ['   - Password: Password untuk akun siswa'],
    [''],
    ['2. Hapus baris contoh (baris 2-4) sebelum mengisi data asli'],
    [''],
    ['3. Jangan mengubah nama kolom di baris header (baris 1)'],
    [''],
    ['4. Maksimal ukuran file: 5MB'],
    [''],
    ['5. Format file yang didukung: .xlsx, .xls, .csv'],
    [''],
    ['6. Setelah selesai, simpan file dan upload di halaman Kelola Siswa']
];

$instructionSheet->fromArray($instructions, NULL, 'A1');

// Styling instruksi
$instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$instructionSheet->getColumnDimension('A')->setWidth(70);

// Kembali ke sheet pertama sebagai default
$spreadsheet->setActiveSheetIndex(0);

// Set header untuk download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="template_siswa.xlsx"');
header('Cache-Control: max-age=0');

// Save file
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>