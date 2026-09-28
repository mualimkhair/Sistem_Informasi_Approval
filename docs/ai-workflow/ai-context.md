# AI Context: Sistem Informasi Approval Cuti

## 1. Project Overview
Aplikasi manajemen pengajuan cuti berbasis web (Laravel) dengan dua jenis alur persetujuan:
1. **Administrasi (Legacy/Standard):** Alur standar melalui Atasan Langsung -> Pejabat.
2. **Operasional (4-Stage):** Alur khusus untuk pegawai lapangan (operasional) melalui Kepala Unit -> Kepala Seksi -> Kanit Kepegawaian -> Kasubag TU.
Aplikasi ini melayani pencatatan saldo cuti, persetujuan berjenjang, dan pencetakan Blangko Cuti serta Surat Izin Cuti.

## 2. Technology Stack
- **Framework:** Laravel 11.x
- **Admin Panel:** FilamentPHP 3.x
- **Database:** MySQL / SQLite (for testing)
- **PDF Generation:** Barryvdh DOMPDF (HTML to PDF)

## 3. Architecture & Core Domain
Aplikasi menggunakan pola Observer untuk manajemen state (status) pengajuan cuti.
Domain utama:
- `User`: Pegawai, Pejabat, Admin, dll. Memiliki relasi ke `UnitKerja`, `Seksi`.
- `SaldoCuti`: Catatan sisa cuti N, N-1, N-2, Besar, Sakit, Melahirkan, Alasan Penting.
- `PengajuanCuti`: Dokumen transaksi cuti utama yang memegang status. Memiliki atribut `tipe_aliran` ('administrasi' / 'operasional').
- `BlangkoCuti`: Dokumen final setelah `PengajuanCuti` disetujui secara manajerial, yang menunggu keputusan final (tanda tangan) dari Kabandara (Kepala Bandara).

## 4. Workflows

### 4.1 Leave Workflow (Administrasi)
1. Pegawai (atau proxy) membuat pengajuan (`status: menunggu_atasan`).
2. Atasan (Kanit/Kasubag) menyetujui.
3. Pejabat berwenang menyetujui -> Status menjadi `disetujui`.

### 4.2 Leave Workflow (Operasional)
1. Pegawai operasional membuat pengajuan (`status: menunggu_kepala_unit`).
2. Persetujuan 4 Tahap Berjenjang:
   - Kepala Unit
   - Kepala Seksi
   - Kanit Kepegawaian
   - Kasubag TU -> Status akhir manajerial `disetujui`.

### 4.3 BlangkoCuti Workflow (Kabandara)
1. Setelah `PengajuanCuti` berstatus `disetujui` (dari manajerial), sistem (via Observer) meng-generate `BlangkoCuti` dengan status `menunggu`.
2. Kabandara melihat daftar Blangko Cuti yang menunggu.
3. Kabandara dapat Menyetujui atau Menolak.
4. **Current State:** Setelah disetujui, dokumen PDF mengambil relasi `kabandara` untuk nama dan `signature_path`. Belum ada tahapan administratif khusus untuk penomoran surat sebelum dicetak.

## 5. User Roles and Authorization
Sistem otorisasi menggunakan Spatie Permission (`HasRoles`).
- `super_admin`, `admin`: Memiliki akses penuh.
- `kabandara`: Akses khusus menyetujui Blangko Cuti final.
- `pegawai` (default): Hanya melihat data sendiri. Akses dashboard diatur berdasarkan User->roles.

## 6. PDF Architecture
- PDF di-generate menggunakan template Blade HTML (contoh: `resources/views/pdf/cetak-blangko.blade.php`, `cetak-surat-izin-cuti.blade.php`).
- Snapshot: Aplikasi mengambil salinan (snapshot) data nama, pangkat, dan NIP approver ke database pada saat approval, untuk menjaga integritas dokumen PDF apabila data pegawai berubah di kemudian hari. Terdapat fallback ke tabel User jika snapshot kosong.

## 7. Testing
- Testing menggunakan Pest/PHPUnit.
- Pengujian menggunakan trait `RefreshDatabase` atau sejenisnya.
- Sebagian file terkait `Feature` testing dapat ditemukan di folder `tests/Feature/`.

## 8. Development Constraints
- **Do Not Modify Existing Behavior Arbitrarily:** Pastikan tidak merusak alur Administrasi saat memodifikasi alur Operasional, dan sebaliknya.
- **Snapshot Integrity:** Jika mengubah struktur data yang dicetak ke PDF, pastikan data historis (snapshot) tidak rusak atau minimal memiliki fallback yang aman.
- **Filament Approach:** Segala UI form dan table wajib menggunakan komponen Filament secara native (Forms/Tables).
