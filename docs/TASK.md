# TASKS — Sistem Informasi Cuti UPBU Mutiara

## Status Task

Dokumen ini mencatat task yang masih perlu dikerjakan, diverifikasi, atau diselesaikan pada Sistem Informasi Cuti UPBU Mutiara.

### Ringkasan

| Task | Deskripsi | Status |
|---|---|---|
| TASK-03 | Tanda tangan elektronik Kabandara | 🟢 SELESAI |
| TASK-06 | Jenis kelamin & pembatasan Cuti Melahirkan | 🔴 Belum dikerjakan |
| TASK-09 | Perubahan untuk Unit Operasional | 🟢 SELESAI |

---

# TASK-03 — Tanda Tangan Elektronik Kabandara

## Status

🟢 **SELESAI**

## Deskripsi

Implementasi penggunaan tanda tangan elektronik Kabandara pada dokumen/alur persetujuan cuti.

Task ini berkaitan dengan penggunaan tanda tangan Kabandara pada proses persetujuan Blangko Cuti dan/atau dokumen yang dihasilkan sistem.

## Tujuan

Memastikan tanda tangan elektronik Kabandara dapat digunakan secara tepat pada dokumen yang membutuhkan persetujuan Kabandara.

## Ruang Lingkup

- Investigasi mekanisme tanda tangan Kabandara yang sudah ada.
- Menentukan sumber tanda tangan Kabandara.
- Memastikan tanda tangan hanya digunakan pada tahap yang sesuai.
- Memastikan tanda tangan muncul pada dokumen setelah persetujuan Kabandara.
- Memastikan tidak terjadi penggunaan tanda tangan pada dokumen sebelum persetujuan.
- Memastikan perubahan tidak mengganggu tanda tangan pegawai maupun Kanit.

## Kriteria Keberhasilan

- [x] Tanda tangan elektronik Kabandara tersedia pada sistem.
- [x] Tanda tangan Kabandara digunakan pada tahap persetujuan yang benar.
- [x] Tanda tangan tidak muncul sebelum Kabandara menyetujui.
- [x] Tanda tangan muncul pada dokumen setelah Kabandara menyetujui.
- [x] Dokumen PDF dapat menampilkan tanda tangan dengan benar.
- [x] Tidak mengganggu workflow persetujuan cuti yang sudah berjalan.

## Catatan

Task ini telah diimplementasikan menggunakan mekanisme snapshot signature.

---

# TASK-06 — Jenis Kelamin & Pembatasan Cuti Melahirkan (SELESAI)

## Status

🔴 **BELUM DIKERJAKAN**

## Deskripsi

Menambahkan informasi jenis kelamin pada profil pegawai dan menggunakan informasi tersebut untuk membatasi penggunaan Cuti Melahirkan.

## Tujuan

Sistem harus dapat membedakan pegawai laki-laki dan perempuan sehingga jenis Cuti Melahirkan hanya dapat digunakan oleh pegawai yang memenuhi ketentuan sistem.

## Kebutuhan

### 1. Jenis Kelamin pada Profil

Tambahkan field:

`jenis_kelamin`

Pilihan:

- Laki-laki
- Perempuan

Field harus tersedia pada proses pengisian/edit profil pegawai.

### 2. Pegawai Laki-laki

Untuk pegawai dengan:

`jenis_kelamin = laki-laki`

maka:

- Cuti Melahirkan tidak boleh ditampilkan sebagai pilihan cuti yang dapat diajukan.
- Pegawai tidak boleh mengajukan Cuti Melahirkan melalui form pengajuan.
- Validasi backend juga harus mencegah pengajuan apabila request dimanipulasi.

### 3. Pegawai Perempuan

Untuk pegawai dengan:

`jenis_kelamin = perempuan`

Cuti Melahirkan tetap dapat digunakan sesuai mekanisme cuti yang tersedia pada sistem.

### 4. Admin

Admin tidak boleh memberikan atau menambahkan saldo Cuti Melahirkan kepada pegawai laki-laki.

Pembatasan harus diterapkan pada proses input/update saldo, bukan hanya pada tampilan form.

## Prinsip Implementasi

- Jangan hanya mengandalkan hidden/disabled field di frontend.
- Validasi harus dilakukan pada backend.
- Jangan mengubah workflow approval yang sudah berjalan.
- Jangan mengubah jenis cuti lain yang tidak berkaitan dengan Cuti Melahirkan.
- Jangan membuat migration baru sebelum investigasi struktur database yang sudah ada.
- Pertahankan kompatibilitas dengan data pegawai yang sudah ada.

## Tahapan Pengerjaan

### Fase 1 — Investigasi

- [ ] Identifikasi model User/Pegawai.
- [ ] Identifikasi struktur data profil pegawai.
- [ ] Identifikasi form Edit Profile/Lengkapi Profil.
- [ ] Identifikasi model Kategori Cuti.
- [ ] Identifikasi proses pengajuan cuti.
- [ ] Identifikasi proses Admin menambahkan saldo cuti.
- [ ] Identifikasi bagaimana Cuti Melahirkan dibedakan dari jenis cuti lain.
- [ ] Tentukan titik validasi yang paling tepat.

### Fase 2 — Implementasi

- [ ] Tambahkan field jenis kelamin sesuai struktur project.
- [ ] Tambahkan field pada form profil.
- [ ] Tambahkan validasi jenis kelamin.
- [ ] Batasi Cuti Melahirkan pada pegawai laki-laki.
- [ ] Tambahkan validasi backend pengajuan.
- [ ] Batasi Admin agar tidak dapat memberikan saldo Cuti Melahirkan kepada pegawai laki-laki.

### Fase 3 — Testing

#### Pegawai Laki-laki

- [ ] Jenis kelamin tersimpan sebagai laki-laki.
- [ ] Cuti Melahirkan tidak tersedia pada pilihan pengajuan.
- [ ] Manipulasi request langsung tetap ditolak.
- [ ] Admin tidak dapat menambahkan saldo Cuti Melahirkan.

#### Pegawai Perempuan

- [ ] Jenis kelamin tersimpan sebagai perempuan.
- [ ] Cuti Melahirkan tersedia sesuai aturan sistem.
- [ ] Pengajuan dapat mengikuti workflow normal.

#### Regression

- [ ] Jenis cuti lain tetap dapat digunakan.
- [ ] Workflow approval tidak berubah.
- [ ] Tidak ada error pada profil.
- [ ] Test suite yang relevan tetap berhasil.

## Kriteria Keberhasilan

Task dianggap selesai apabila:

- [ ] Jenis kelamin tersedia pada profil pegawai.
- [ ] Data jenis kelamin tersimpan dengan benar.
- [ ] Pegawai laki-laki tidak dapat menggunakan Cuti Melahirkan.
- [ ] Backend menolak pengajuan Cuti Melahirkan oleh pegawai laki-laki.
- [ ] Admin tidak dapat memberikan saldo Cuti Melahirkan kepada pegawai laki-laki.
- [ ] Pegawai perempuan tetap dapat menggunakan Cuti Melahirkan.
- [ ] Jenis cuti lainnya tidak terdampak.

---

# TASK-09 — Verifikasi Unit Operasional

## Status

🟢 **SELESAI**

## Deskripsi

Memastikan mekanisme pengajuan dan persetujuan cuti untuk pegawai Unit Operasional sesuai dengan karakteristik cuti operasional yang tidak menentu.

Implementasi terkait Unit Operasional sebelumnya pernah dikerjakan, tetapi fitur tersebut belum dilakukan demonstrasi/pembuktian secara menyeluruh.

## Tujuan

Tujuan awal bukan langsung memperbaiki, tetapi:

Cari apakah sudah ada implementasi khusus Unit Operasional.
Cari apakah sudah ada test terkait Unit Operasional.
Cari aturan/alur yang sekarang berlaku.

Bandingkan dengan kebutuhan:

“Perubahan untuk unit operasional karena cutinya tidak menentu.”

Kalau belum ada implementasi → buat implementasinya.
Kalau sudah ada → lakukan demo/test untuk memastikan sesuai kebutuhan.
Kalau sudah ada tetapi belum sesuai → perbaiki.

Dengan begitu kita tidak mengasumsikan sesuatu yang belum kita buktikan.

## Kondisi Saat Ini

Belum diketahui apakah implementasi khusus Unit Operasional sudah tersedia di project.

Task ini harus dimulai dengan investigasi terhadap codebase untuk mengetahui:

- apakah sudah ada implementasi khusus Unit Operasional;
- apakah sudah ada test terkait Unit Operasional;
- bagaimana workflow Unit Operasional saat ini;
- apakah terdapat perbedaan dengan pegawai non-operasional;
- apakah kebutuhan "cutinya tidak menentu" sudah didukung oleh sistem.

Jangan mengasumsikan implementasi sudah ada atau belum ada sebelum investigasi dilakukan.

Jika implementasi belum ada → buat implementasi sesuai kebutuhan.

Jika implementasi sudah ada dan sesuai → lakukan testing/demo dan dokumentasikan hasilnya.

Jika implementasi sudah ada tetapi belum sesuai → tentukan root cause dan lakukan perbaikan yang diperlukan.

## Tahap 1 — Investigasi Read-Only

Sebelum melakukan perubahan kode:

- [ ] Identifikasi implementasi Unit Operasional yang sudah ada.
- [ ] Identifikasi role/unit yang dianggap sebagai Unit Operasional.
- [ ] Identifikasi perbedaan workflow Unit Operasional dengan pegawai biasa.
- [ ] Identifikasi form pengajuan yang digunakan.
- [ ] Identifikasi approval flow yang digunakan.
- [x] Identifikasi aturan tanggal/periode cuti Unit Operasional.
- [x] Identifikasi test yang sudah tersedia.
- [ ] Jangan mengubah kode pada tahap investigasi.

## Tahap 2 — Pembuktian Fitur

Buat akun/test case untuk pegawai Unit Operasional.

### Skenario Dasar

- [x] Login sebagai pegawai Unit Operasional (via Test).
- [x] Buka form pengajuan cuti (via Test).
- [x] Pastikan sistem mengenali pegawai sebagai Unit Operasional.
- [x] Ajukan cuti dengan kondisi yang sesuai.
- [x] Pastikan pengajuan tersimpan.
- [x] Pastikan pengajuan masuk ke tahap approval yang benar.
- [x] Lakukan approval sesuai alur.
- [x] Pastikan status berubah dengan benar.

### Skenario Operasional

- [x] Uji kondisi tanggal cuti yang tidak menentu (OperasionalLamaCutiTest).
- [x] Uji pengajuan dengan periode yang berbeda (Shift A & Shift B Test).
- [x] Pastikan sistem tidak memaksakan pola cuti pegawai biasa apabila memang berbeda.
- [x] Pastikan saldo/validasi cuti tetap konsisten dengan aturan sistem.

### Skenario Regression

- [x] Pegawai non-operasional tetap menggunakan workflow normal.
- [x] Approval Kanit/Kasubag/Kabandara tidak terganggu.
- [x] Blangko Cuti tetap dapat dibuat.
- [x] Proses Admin tetap berjalan.
- [x] Tidak terjadi perubahan pada workflow cuti yang sudah dinyatakan selesai.

## Jika Fitur Belum Sesuai

Jika hasil demo menunjukkan implementasi belum memenuhi kebutuhan Unit Operasional:

1. Dokumentasikan hasil aktual.
2. Tentukan root cause.
3. Buat perubahan sekecil mungkin.
4. Tambahkan/ubah test yang relevan.
5. Jalankan test.
6. Lakukan demo ulang.

Jangan melakukan refactor besar apabila masalah dapat diselesaikan dengan perubahan terbatas.

## Kriteria Keberhasilan

Task dianggap selesai apabila:

- [x] Implementasi Unit Operasional telah dipahami.
- [x] Skenario utama berhasil didemonstrasikan.
- [x] Pengajuan Unit Operasional berjalan sesuai kebutuhan.
- [x] Approval berjalan sesuai alur.
- [x] Kondisi cuti yang tidak menentu dapat ditangani sesuai aturan sistem.
- [x] Pegawai non-operasional tidak terdampak.
- [x] Hasil telah diverifikasi melalui testing/manual demo.

---

# Urutan Prioritas

Urutan pengerjaan yang disepakati:

1. **TASK-09 — Verifikasi Unit Operasional**
2. **TASK-06 — Jenis Kelamin & Pembatasan Cuti Melahirkan (SELESAI)**
3. **TASK-03 — Tanda Tangan Elektronik Kabandara**

## Catatan

Task lain yang sudah selesai tidak perlu dikerjakan ulang kecuali ditemukan regression atau masalah pada saat testing.

Task yang sudah selesai:

- Saldo N, N-1, N-2.
- Kolom TTD Kanit.
- Workflow Kabandara → Admin → Nomor Surat → Surat Izin Cuti.
- Upload tanda tangan pegawai PNG transparan.
- Approval Blangko tanpa NIP.
- Layout Blangko Cuti satu halaman.
