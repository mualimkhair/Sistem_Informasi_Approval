# Current Handover

## Project
Sistem Informasi Approval Cuti

## Current Branch
*(Branch saat ini tempat sesi AI dijalankan)*

## Current Objective
Persiapan dokumentasi AI Workflow telah selesai. Belum ada implementasi fitur (Issue 3-9) yang dikerjakan. Objektif selanjutnya adalah memulai implementasi fitur berdasarkan urutan task yang ada di `TASKS.md`.

## Scope
Fokus pada implementasi fitur (REQ-03 hingga REQ-09):
- Validasi Gender dan Cuti Melahirkan
- Tanda Tangan Kabandara dan Validasi format PNG
- Alur Penomoran Surat oleh Admin
- Perbaikan layout PDF cetak blangko dan penghapusan NIP
- Penyesuaian batas tanggal pengajuan Cuti Operasional

## Out of Scope
Issue berikut **jangan** disentuh atau dikerjakan, karena berada pada scope dan branch lain:
1. Perbaikan penulisan saldo N, N-1, N-2 pada hasil cetak.
2. Penambahan kolom TTD baru untuk Kanit.

## Repository State
- Audit repository telah dilakukan.
- File-file dokumentasi AI Workflow sudah diisi (`ai-context.md`, `index.md`, `PRD.md`, `TASKS.md`, `HANDOVER.md`).
- Kode aplikasi utama **belum diubah sama sekali**.

## Completed
- Fase Setup Dokumentasi AI Workflow.

## In Progress
- Tidak ada. (Sesi implementasi belum dimulai)

## Not Started
- TASK-001: Add `jenis_kelamin` to User Profile
- TASK-002: Gender Validation for "Cuti Melahirkan"
- TASK-003: Restrict Signature Upload to PNG Transparent
- TASK-004: Admin Numbering Flow for BlangkoCuti
- TASK-005: Render Admin & Signature on PDF
- TASK-006: Remove NIP from Approval Boxes in BlangkoCuti PDF
- TASK-007: Optimize BlangkoCuti PDF Layout to 1 A4 Page
- TASK-008: Flexible Date Validation for Operasional Workflow

## Known Dependencies
- Potensi minor conflict pada `resources/views/pdf/cetak-blangko.blade.php` di masa mendatang jika di-*merge* dengan pengerjaan Out of Scope (1) dan (2). Oleh karena itu, pengubahan layout PDF (TASK-006 & TASK-007) harus dilakukan seminimal dan se-spesifik mungkin pada target area.

## Known Risks
- Mengubah alur persetujuan (TASK-004) dari Kabandara -> Selesai menjadi Kabandara -> Admin dapat merusak query yang mendasarkan pada status final "disetujui"/"selesai". Status enum SQLite mungkin bermasalah saat migration jika tidak hati-hati, karena SQLite butuh workarounds untuk memodifikasi kolom table.

## Open Questions
- TASK-004: Apakah form input `nomor_surat` oleh Admin berupa teks bebas (manual) atau butuh auto-generation?
- TASK-008: Berapa batas maksimal hari *backdate* yang diperbolehkan untuk pegawai operasional? Ataukah benar-benar bebas tanpa batas?
- REQ-03: Apakah sekadar menampilkan image signature (`signature_path`) pada PDF dianggap sudah memenuhi "Tanda tangan elektronik", atau butuh mekanisme sertifikat digital / log terpisah?

## Recommended Next Task
**TASK-001: Add `jenis_kelamin` to User Profile**
*(Ini adalah pondasi yang aman dan tidak bergantung pada hal lain, untuk kemudian dilanjutkan ke validasi cuti melahirkan).*

## Testing Status
Belum ada testing baru yang dibuat atau dijalankan terkait fitur di atas. Status implementasi adalah 0%.
