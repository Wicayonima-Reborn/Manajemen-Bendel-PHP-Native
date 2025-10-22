<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
require 'vendor/autoload.php';
require 'koneksi.php';

// query
$sql = "SELECT 
  b.no_bendel,
  b.tgl_terima,
  kp.nama_kantor AS kantor_penerima,
  t.tipe_transaksi,
  t.nomor_mulai,
  t.nomor_sampai,
  t.nama_penyetor,
  kk.nama_kantor AS kantor_pengirim
FROM bendel b
JOIN transaksi t ON b.id = t.id_bendel
JOIN kantor kp ON b.id_kantor_penerima = kp.id
JOIN kantor kk ON t.id_kantor_pengirim = kk.id;
";

$result = $conn->query($sql);
// Buat spreadsheet baru
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// header
$headers = [
    'A1' => 'No Bendel',
    'B1' => 'Tanggal Terima',
    'C1' => 'Kantor Penerima',
    'D1' => 'Tipe Transaksi',
    'E1' => 'Nomor Mulai',
    'F1' => 'Nomor Sampai',
    'G1' => 'Nama Penyetor',
    'H1' => 'Kantor Pengirim'
];
foreach ($headers as $cell => $value) {
    $sheet->setCellValue($cell, $value);
}
// tebal header
$sheet->getStyle('A1:H1')->getFont()->setBold(true);
// isi data
$row = 2;
while ($r = $result->fetch_assoc()) {
    $sheet->setCellValue('A' . $row, $r['no_bendel']);
    $sheet->setCellValue('B' . $row, $r['tgl_terima']);
    $sheet->setCellValue('C' . $row, $r['kantor_penerima']);
    $sheet->setCellValue('D' . $row, $r['tipe_transaksi']);
    $sheet->setCellValue('E' . $row, $r['nomor_mulai']);
    $sheet->setCellValue('F' . $row, $r['nomor_sampai']);
    $sheet->setCellValue('G' . $row, $r['nama_penyetor']);
    $sheet->setCellValue('H' . $row, $r['kantor_pengirim']);
    $row++;
}
// Auto-sizekolom
foreach (range('A', 'H') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}
$filename = 'laporan_bendel_' . date('Y-m-d') . '.xlsx';

// Output ke browser auto ke download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>