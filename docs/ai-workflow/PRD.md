# Product Requirements Document

## 1. Background
Sistem Informasi Approval Cuti terus mengalami perkembangan untuk menyesuaikan dengan kebutuhan operasional dan administrasi di unit kerja. Terdapat kebutuhan untuk melengkapi fitur approval cuti dengan dukungan penomoran surat otomatis, penyempurnaan dokumen cetak, perbaikan UX/aturan bisnis terkait gender dan pengajuan cuti bagi pegawai lapangan (operasional).

## 2. Problem Statement
- Blangko Cuti dan Surat Izin Cuti belum memiliki kolom penomoran surat yang tersentralisasi via Admin setelah persetujuan akhir.
- Tata letak (layout) dokumen cetak Blangko Cuti seringkali melebihi satu halaman A4 yang boros kertas dan kurang rapi.
- Pada dokumen cetak, informasi NIP approver dan Kabandara masih ditampilkan di bawah tanda tangan, yang tidak sesuai dengan standar terbaru.
- Aturan cuti belum mengakomodasi validasi gender (contoh: laki-laki tidak seharusnya mendapatkan/menggunakan cuti melahirkan).
- Cuti operasional belum memiliki fleksibilitas tanggal yang memadai, padahal sifat operasional di lapangan seringkali membutuhkan pengajuan yang mendadak.
- Gambar tanda tangan (signature) Kabandara harus spesifik diformat transparan (PNG) untuk hasil cetakan yang baik, namun saat ini tidak ada validasi teknis.

## 3. Goals
- Mengintegrasikan peran Admin dalam menerbitkan Nomor Surat Cuti pasca-approval Kabandara.
- Menyempurnakan cetakan PDF agar pas 1 halaman A4 dan menghilangkan NIP approver pada area persetujuan.
- Menegakkan validasi bisnis (Gender vs Cuti Melahirkan).
- Menyediakan validasi unggah TTD PNG Transparan.
- Memperbaiki waktu validasi pengajuan cuti alur operasional.

## 4. Scope
Fitur ini mencakup penyesuaian pada:
- **(REQ-03)** Tanda tangan elektronik Kabandara.
- **(REQ-04)** Approval Kabandara → Admin untuk nomor surat; hasil cetak menunjukkan siapa "dibuat oleh".
- **(REQ-05)** Validasi upload foto/TTD hanya menerima format PNG transparan.
- **(REQ-06)** Penambahan kolom Jenis Kelamin dan validasi pencegahan cuti melahirkan untuk pegawai laki-laki.
- **(REQ-07)** Penghapusan tampilan NIP dari kolom persetujuan pada cetakan PDF Blangko Cuti.
- **(REQ-08)** Optimasi layout CSS cetak PDF blangko cuti menjadi 1 halaman A4.
- **(REQ-09)** Perubahan/penyesuaian validasi tanggal pada workflow pengajuan cuti pegawai operasional.

## 5. Out of Scope
Pekerjaan berikut secara tegas **dikecualikan** dari fase/PRD ini, karena berstatus dikerjakan oleh developer lain di branch terpisah:
- **(1)** Perbaikan penulisan saldo N, N-1, N-2 pada hasil cetak.
- **(2)** Penambahan kolom TTD baru untuk Kanit.
*(Jika terdapat benturan (conflict) terkait perbaikan cetak PDF atau field di database, akan dikelola melalui proses merge nantinya).*

## 6. Existing System
- **BlangkoCuti:** Saat ini menggunakan `status` (menunggu, disetujui, ditolak) oleh `kabandara_id`. Belum ada flow ke Admin.
- **Cetak PDF:** Menggunakan DOMPDF, memuat NIP pada setiap kotak tanda tangan (`cetak-blangko.blade.php`).
- **Profile:** Belum menyimpan informasi jenis kelamin (`jenis_kelamin`), dan unggahan tanda tangan hanya mengandalkan field `signature_path` generik.
- **Pengajuan Cuti:** Form (`PengajuanCutiForm.php`) menggunakan `minDate(today())` secara umum tanpa mempertimbangkan fleksibilitas workflow operasional yang kadang membutuhkan pengajuan cuti secara restrospektif.

## 7. Requirements

### REQ-03 — Tanda tangan elektronik Kabandara
- **Current Behavior:** Sistem mencetak gambar dari `signature_path` Kabandara pada dokumen PDF jika Kabandara menyetujui.
- **Desired Behavior:** Memastikan tanda tangan Kabandara dapat terekam dan ditampilkan dengan baik secara elektronik sebagai bentuk persetujuan resmi. *(Open Question: Apakah ini hanya merujuk pada validasi REQ-05 & display, atau butuh log otentikasi TTD baru?)*
- **Acceptance Criteria:** Tanda tangan elektronik (berupa image dari profil) tampil di Surat dan Blangko setelah approval.

### REQ-04 — Approval Kabandara → Admin → Nomor Surat → Surat
- **Current Behavior:** Begitu Kabandara `disetujui`, status selesai, dan pegawai bisa mengunduh Blangko/Surat Cuti tanpa Nomor Surat dan nama Pembuat Surat.
- **Desired Behavior:** Setelah disetujui Kabandara, Blangko Cuti harus masuk ke status menunggu penomoran oleh Admin (contoh status: `menunggu_penomoran`). Admin perlu memasukkan `nomor_surat`. Hasil cetak Surat Izin Cuti juga mencantumkan `dibuat_oleh_admin_id`.
- **Dependencies:** Membutuhkan penambahan kolom `nomor_surat`, `dibuat_oleh_admin_id`, dan ekstensi Enum status di `blangko_cutis`.

### REQ-05 — Upload Tanda Tangan PNG Transparan
- **Current Behavior:** Form upload `signature_path` belum memiliki restriksi tipe file spesifik ke PNG.
- **Desired Behavior:** Hanya file PNG yang diizinkan untuk diunggah sebagai tanda tangan di form User Profile. Sistem memberikan petunjuk visual (help text) bahwa background harus transparan.
- **Acceptance Criteria:** Percobaan upload file JPG/PDF pada field tanda tangan akan ditolak oleh validasi form Filament.

### REQ-06 — Gender dan Validasi Cuti Melahirkan
- **Current Behavior:** Tidak ada identitas kelamin, sehingga pegawai laki-laki bisa mengajukan "Cuti Melahirkan" dan admin bisa menambah saldo tersebut.
- **Desired Behavior:** Menambahkan field `jenis_kelamin` (Laki-laki/Perempuan) di profil pengguna. Pada `PengajuanCutiForm`, opsi/validasi menolak cuti melahirkan untuk Laki-laki. Di Form Saldo, Admin tidak bisa memberi input > 0 untuk saldo melahirkan laki-laki.
- **Acceptance Criteria:** Error validasi muncul jika laki-laki mengajukan Cuti Melahirkan.

### REQ-07 — Informasi Approval pada Blangko Tanpa NIP
- **Current Behavior:** View `cetak-blangko.blade.php` memiliki loop/helper yang mencetak `NIP. {nip_number}` pada tiap kotak tanda tangan approver (Kanit, Kasubag, Kabandara, dsb).
- **Desired Behavior:** Teks NIP dan isiannya dihilangkan dari tampilan kotak persetujuan pada PDF khusus untuk lembar Blangko Cuti.
- **Acceptance Criteria:** File cetak blangko tidak lagi mengandung kata "NIP." di blok "KEPUTUSAN PEJABAT YANG BERWENANG".

### REQ-08 — Layout Blangko Satu Halaman A4
- **Current Behavior:** PDF Blangko Cuti terpotong dan menghasilkan halaman kosong/ekstra karena layout/tabel yang melebar atau panjang (`page-break-before: always`).
- **Desired Behavior:** Penyesuaian margin DOMPDF, ukuran font, dan tinggi cell (`tall-box`) agar muat tepat 1 lembar A4 secara vertikal/portrait.
- **Dependencies:** Berpotensi memiliki *conflict* kecil dengan Out-of-Scope (1) dan (2) karena mereka juga memodifikasi view PDF yang sama.

### REQ-09 — Workflow Pegawai Operasional
- **Current Behavior:** Komponen `DatePicker` di form membatasi `minDate(today())` yang memaksa pengajuan tidak bisa mundur, menyulitkan pegawai operasional yang waktunya tidak terprediksi.
- **Desired Behavior:** Untuk pegawai dengan Unit Kerja `jenis == 'operasional'`, validasi tanggal mulai/selesai cuti diberi kelonggaran (misal bisa *backdate* beberapa hari, atau menghilangkan restriksi `minDate`).
- **Open Questions:** Berapa batas maksimal hari *backdate* untuk cuti operasional? Perlu klarifikasi dari bisnis. Sementara akan dibuka pembatasan khususnya untuk operasional.
