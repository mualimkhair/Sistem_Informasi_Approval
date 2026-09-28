# Implementation Tasks

Daftar tugas implementasi dipecah berdasarkan PRD. Setiap task dirancang cukup spesifik agar dapat diselesaikan secara independen oleh AI dalam satu sesi.

---

## TASK-001
### Title
Add `jenis_kelamin` to User Profile
### Related Requirement
REQ-06 — Gender dan Validasi Cuti Melahirkan
### Objective
Menambahkan atribut `jenis_kelamin` ke tabel `users` dan menambahkannya pada tampilan Profil/Filament User Resource.
### Expected Changes
1. Buat migration untuk menambah kolom `jenis_kelamin` (`enum('L', 'P')` atau `string`) di `users`.
2. Update Model `User.php` (`$casts` / konstanta jika perlu).
3. Update Form Filament (misal: EditProfile atau UserResource) untuk menginput Jenis Kelamin.
### Acceptance Criteria
- Kolom `jenis_kelamin` ada di database.
- Admin dan pengguna bisa mengatur jenis kelamin melalui antarmuka Filament.
### Testing
- Validasi Unit/Feature Test untuk memastikan field dapat disimpan.
### Notes
Penting dikerjakan paling awal karena menjadi prasyarat validasi Cuti Melahirkan (TASK-002).

---

## TASK-002
### Title
Gender Validation for "Cuti Melahirkan"
### Related Requirement
REQ-06 — Gender dan Validasi Cuti Melahirkan
### Objective
Mencegah pegawai laki-laki mengambil Cuti Melahirkan dan mencegah Admin memberi saldo Cuti Melahirkan kepada laki-laki.
### Dependencies
TASK-001
### Expected Changes
1. Update `PengajuanCutiForm.php`:
   - Nonaktifkan/sembunyikan opsi `melahirkan` di Select `jenis_cuti` jika user adalah Laki-laki.
   - Tambahkan rule validasi di form submission.
2. Update form Saldo Cuti di Admin Panel untuk tidak menampilkan atau me-reset input saldo melahirkan jika `jenis_kelamin` user adalah Laki-laki.
### Acceptance Criteria
- Pengajuan Cuti Melahirkan gagal/opsi hilang jika gender == L.
### Testing
- Automated test pada Form Submission / Livewire call.

---

## TASK-003
### Title
Restrict Signature Upload to PNG Transparent
### Related Requirement
REQ-05 — Upload Tanda Tangan PNG Transparan
### Objective
Memastikan form upload tanda tangan hanya mengizinkan file berformat `.png`.
### Expected Changes
1. Update form upload di Filament (Profil/UserResource) pada field `signature_path`.
2. Tambahkan modifier `->acceptedFileTypes(['image/png'])` dan `->helperText('Hanya mendukung format PNG dengan background transparan')`.
### Acceptance Criteria
- Upload file JPG ditolak oleh UI Filament dan backend.

---

## TASK-004
### Title
Admin Numbering Flow for BlangkoCuti
### Related Requirement
REQ-04 — Approval Kabandara → Admin → Nomor Surat
### Objective
Menambahkan tahap di mana setelah Kabandara menyetujui, Blangko Cuti membutuhkan penomoran oleh Admin sebelum Surat Izin Cuti resmi diterbitkan.
### Expected Changes
1. Buat migration untuk `blangko_cutis`:
   - tambah `nomor_surat` (string, nullable)
   - tambah `dibuat_oleh_admin_id` (foreign key to users)
   - tambah status enum / indikator baru: misal ubah enum status menjadi `['menunggu', 'menunggu_penomoran', 'selesai', 'ditolak']` (Perhatikan SQLite vs MySQL enum migration issue).
2. Update Model `BlangkoCuti.php` (relasi `dibuatOlehAdmin`).
3. Update Observer atau Action Kabandara Approval: Jika disetujui, ubah status ke `menunggu_penomoran` bukan langsung selesai.
4. Buat Filament Action (di List/View Admin) untuk "Generate Nomor Surat" yang mengisi `nomor_surat` dan `dibuat_oleh_admin_id`, mengubah status ke `selesai`.
### Dependencies
Tidak ada, dapat berjalan paralel.
### Open Questions
- Apakah format penomoran diisi manual atau auto-generate (format khusus)? (Asumsi awal: Input manual teks oleh Admin melalui modal action).

---

## TASK-005
### Title
Render Admin & Signature on PDF
### Related Requirement
REQ-03, REQ-04
### Objective
Menyesuaikan template cetak Surat Izin Cuti agar mencantumkan Nomor Surat, tanda tangan Kabandara secara proper, dan identitas "Dibuat Oleh" (Admin).
### Dependencies
TASK-004
### Expected Changes
1. Update `resources/views/pdf/cetak-surat-izin-cuti.blade.php`.
2. Tampilkan `$blangko->nomor_surat`.
3. Tampilkan footer identitas "Dibuat Oleh: " `$blangko->dibuatOlehAdmin->nama`.
4. Pastikan gambar tanda tangan Kabandara (REQ-03) terender di tempat yang tepat.
### Acceptance Criteria
- PDF Surat Izin Cuti ter-render dengan Nomor Surat dan identitas pembuat.

---

## TASK-006
### Title
Remove NIP from Approval Boxes in BlangkoCuti PDF
### Related Requirement
REQ-07 — Informasi Approval pada Blangko Tanpa NIP
### Objective
Menghilangkan string NIP pada bagian TTD approver di PDF Blangko Cuti.
### Expected Changes
1. Edit `resources/views/pdf/cetak-blangko.blade.php`.
2. Cari dan hapus bagian `$out .= 'NIP. ' . $approver->nip . '<br>';` dan `NIP. {{ $kabandaraNip }}`.
### Acceptance Criteria
- PDF di-generate tanpa mencantumkan "NIP." di bawah nama approver.

---

## TASK-007
### Title
Optimize BlangkoCuti PDF Layout to 1 A4 Page
### Related Requirement
REQ-08 — Layout Blangko Satu Halaman A4
### Objective
Memadatkan tampilan tabel dan meminimalkan margin di PDF agar muat dalam satu halaman A4 tanpa terpotong (page break yang tidak perlu).
### Dependencies
TASK-006
### Expected Changes
1. Edit `resources/views/pdf/cetak-blangko.blade.php`.
2. Atur padding/margin pada `.tall-box` atau ukuran font css.
3. Hapus instruksi `page-break-before: always` jika itu penyebab utamanya, atau sesuaikan dinamika tabel.
### Testing
- Generate PDF dengan data maksimal (termasuk alasan penolakan panjang) dan periksa hasil visualnya.

---

## TASK-008
### Title
Flexible Date Validation for Operasional Workflow
### Related Requirement
REQ-09 — Perubahan workflow untuk pegawai operasional
### Objective
Menghapus restriksi wajib H-X atau tidak boleh backdate pada DatePicker pengajuan cuti khusus untuk tipe aliran operasional.
### Expected Changes
1. Edit `app/Filament/Resources/PengajuanCutis/Schemas/PengajuanCutiForm.php`.
2. Modifikasi atribut `minDate()` pada `tanggal_mulai` dan `tanggal_selesai`. Jika user adalah operasional (atau `$get('kelompok_kerja_id')` tidak null), hilangkan batasan `today()`.
### Open Questions
- Apakah butuh batas maksimal backdate (contoh: max H-30)? (Asumsi awal: dihapus/dibuat sangat longgar misal 30 hari ke belakang, menyesuaikan kebutuhan lapangan).
