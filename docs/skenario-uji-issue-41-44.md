# Skenario Uji Manual — Revisi Issue #44 & Issue #41

Lembar uji manual (UAT) untuk memverifikasi semua fitur yang berubah pada:

- **Issue #44** — Aliran **operasional** persetujuan berjenjang 4 tahap
  (Kepala Unit → Kepala Seksi → Kanit Kepegawaian → Kasubag Tata Usaha) beserta
  pemisahan `tipe_aliran` operasional/administrasi, aturan skip, scope approver,
  notifikasi, dan PDF.
- **Issue #41** — Aksi admin **"Tangguhkan"** pada pengajuan berstatus `disetujui`
  (pengembalian saldo, status log, notifikasi, PDF tetap historis).

Setiap skenario punya kolom **Hasil ✔** — isi `✔` bila sesuai ekspektasi, atau tulis
temuan pada kolom **Catatan**.

---

## 1. Persiapan

### 1.1 Login
- Buka panel di `/` (login kustom). Field login: **NIP (18 digit)**.
- Password = **NIP yang sama** (untuk semua user hasil seed).
- Setelah login, jika halaman **Lengkapi Profil** muncul, isi semua field wajib lalu
  simpan (hampir semua user seed `is_profile_completed = false`; Super Admin sudah true).
- **Notifikasi** (lonceng kanan atas) polling 30 detik; bisa di-refresh manual.

### 1.2 Environment uji terpisah (data produksi TIDAK disentuh)
Uji manual dijalankan terhadap **database uji** `database/database_uat.sqlite`.
File produksi `database/database.sqlite` tidak pernah diubah.

```bash
# (a) cadangan ekstra DB produksi (opsional, di luar container)
docker cp laravel-cuti-app:/app/database/database.sqlite /tmp/opencode/database.sqlite.bak

# (b) buat DB uji kosong
touch database/database_uat.sqlite

# (c) migrasi + seed HANYA di DB uji (Ulangi bila ingin reset DB uji lagi)
docker exec -e DB_DATABASE=/app/database/database_uat.sqlite laravel-cuti-app php artisan migrate:fresh --seed
```

Jalankan **instance uji** (dipisah dari instance produksi yang berjalan di `:8000`):

```bash
docker run -d --name laravel-cuti-uat --rm \
  -p 8001:8000 \
  -v "$PWD":/app:rw \
  -w /app \
  -e DB_DATABASE=/app/database/database_uat.sqlite \
  -e APP_URL=http://localhost:8001 \
  -e VITE_APP_URL=http://localhost:8001 \
  laravel-cuti:dev \
  php artisan serve --host=0.0.0.0 --port=8000
```

Lalu **worker notifikasi** untuk instance uji (aktivitas koneksi DB sama):

```bash
docker run -d --name laravel-cuti-uat-queue --rm \
  -v "$PWD":/app:rw \
  -w /app \
  -e DB_DATABASE=/app/database/database_uat.sqlite \
  laravel-cuti:dev \
  php artisan queue:work --tries=1 --timeout=0
```

> Jika image memakai custom ENTRYPOINT sehingga perintah `php artisan ...` di atas
> tidak jalan, fallbacknya: hentikan sementara `serve`+`queue` produksi, jalankan
> ulang dengan variabel `DB_DATABASE=...database_uat.sqlite` pada port 8000, dan
> setelah uji selesai kembalikan seperti semula (file produksi tetap utuh).
>
> Akses uji di **http://localhost:8001** (bila memakai fallback, tetap `:8000`).

Verifikasi instance sedang aktif:
```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8001   # harap: 200/302 (bukan 000)
docker exec -e DB_DATABASE=/app/database/database_uat.sqlite laravel-cuti-app \
  php artisan tinker --execute="echo App\\Models\\User::count();"  # harap: 27
```

### 1.3 Verifikasi data via tinker (cheat sheet)
```bash
docker exec -e DB_DATABASE=/app/database/database_uat.sqlite laravel-cuti-app php artisan tinker
```
```php
App\Models\User::where('nip','199109042009121001')->value('nama');        // Adhitya Syah Putra
$p = App\Models\PengajuanCuti::where('user_id', <idPemohon>)->latest()->first();
$p?->status;                                // status terbaru
$p?->only(['tipe_aliran','keputusan_kepala_unit','keputusan_kepala_seksi',
           'keputusan_kanit_kepegawaian','keputusan_kasubag_tu']);        // keputusan tiap tahap
$p?->statusLogs()->orderBy('created_at')->get(['status_from','status_to','changed_by','keterangan']);
$p?->saldoLedger()->orderBy('created_at')->get(['aksi','jumlah','keterangan']);
App\Models\SaldoCutiLedger::where('pengajuan_cuti_id',$p?->id)->orderBy('id')->get(['aksi','jumlah','keterangan']);
$p?->user?->saldoCuti?->only(['saldo_n','saldo_n1','saldo_n2']);          // saldo setelah aksi
```
Kolom ledger: `aksi` = `hold | release | potong | koreksi`.

---

## 2. Matriks Akun

> **Semua password = NIP.** Field login **NIP (18 digit)**. Login di `/`.

### 2.1 Peran khusus
| Peran | Nama | NIP (password) | Keterangan |
|---|---|---|---|
| `super_admin` | Super Admin | `000000000000000000` | Lihat semua; satu-satunya "admin" (role `admin` kosong dari seed) |
| `pejabat_berwenang` (+`pegawai`) | Prasetiyohadi, S.T, S.H, M.H | `197804042002121003` | Penyetuju final aliran administrasi |
| `kanit_kepegawaian` (+`kanit`,`pegawai`) | Asmaul Husna Sabil, SE | `198202092006042001` | Tahap-3 aliran operasional (unit Kepegawaian) |
| `kasubag_tu` (+`kasubag`,`pegawai`) | Hastuty, SE, MM | `197504211999032001` | Tahap-4 aliran operasional; Kasubag aliran administrasi |

### 2.2 Kepala Seksi (kasubag) — tahap-2 aliran operasional, seksi=unit kerja
| Nama | NIP | Seksi (unit operasional di bawahnya) |
|---|---|---|
| Rasud Mohamad, SH | `197109121992031003` | Seksi Kampen dan Pelayanan Darurat (Unit Kargo; Unit Terminal & Pengamanan Kampen; Unit Proteksi; Unit PKP-PK) |
| Muhammad Arief Sagana, SE, MM | `198512222007121001` | Seksi Pelayanan dan Kerjasama (Unit Kerjasama; Unit Informasi; Unit Terminal, Hygiene dan Sanitasi) |
| Winariyanto, SE | `197704271999031004` | Seksi Teknik dan Operasi (Unit Fasilitas Elektronika; Unit AMC; Unit Elektrikal Mekanikal; Unit Bangunan; Unit A2B; Unit Landasan) |
| Hastuty, SE, MM | `197504211999032001` | Kasubag Keuangan dan Tata Usaha (unit administrasi) |

### 2.3 Kepala Unit (kanit) — tahap-1 aliran operasional / penyetuju-1 aliran administrasi
| Nama | NIP | Unit (jenis) |
|---|---|---|
| Adhitya Syah Putra | `199109042009121001` | Kargo (operasional) |
| Fery Sefrian, S.M | `199009192014021004` | Terminal & Pengamanan Kampen (operasional) |
| Moh. Rifan | `198404112009011009` | Proteksi (operasional) |
| Muhammad Nur, S.Sos | `198302072006041004` | PKP-PK (operasional) |
| Haryati Mihari, SE | `198109122009122003` | Kerjasama (operasional) |
| Nurasma, SE | `197410252006042001` | Informasi (operasional) |
| Romi Yosep Sigar | `198610132010121002` | Terminal, Hygiene dan Sanitasi (operasional) |
| A S I S, A.Ma | `196911252002121001` | Fasilitas Elektronika (operasional) |
| Hendra, SE, MM | `198205162006041001` | AMC (operasional) |
| Mochammad Dhamar Tri Saputro, A.Md.T | `198912282014021004` | Elektrikal Mekanikal (operasional) |
| Subhan, ST | `197806102002121003` | Bangunan (operasional) |
| Andi Reza Asyari Iqbal, A.Md | `198706072014021004` | Alat-Alat Besar (A2B) (operasional) |
| Yunus Panto, SH | `198012142007121001` | Landasan (operasional) |
| Musdalifah, ST, MT | `198209222002122001` | Perencanaan dan Program (administrasi) |
| Asmaul Husna Sabil, SE | `198202092006042001` | Kepegawaian (administrasi) |
| Hermawan Susilo, S.Kom | `198210162006041001` | Teknologi dan Humas (administrasi) |
| Muhajir | `197204211997031003` | Keuangan (administrasi) |
| Ni'ma, S.A.P | `197810062002122001` | PPID (administrasi) |
| Supriyadi, SE | `197804042006041002` | Pengevaluasi dan Penyusunan Laporan (administrasi) |
| Umar, S.Kom | `197706112006041001` | SPI (administrasi) |
| Yani Yuliawati, S.Sos, M.M | `197607232006042002` | BMN (administrasi) |

> Unit Tata Usaha (administrasi) **tanpa** kepala unit → tahap Kanit otomatis `dilewati`.

---

## 3. Alur Persetujuan & Aturan Skip

### Aliran operasional (`tipe_aliran = 'operasional'`, unit berjenis operasional)
```
Pegawai submit → [1 KEPALA UNIT] → [2 KEPALA SEKSI] → [3 KANIT KEPEGAWAIAN] → [4 KASUBAG TU] → Disetujui (saldo dipotong)
```
Status menunggu: `menunggu_kepala_unit` → `menunggu_kepala_seksi` → `menunggu_kanit_kepegawaian` → `menunggu_kasubag_tu` → `disetujui`.

- **Skip tahap-1**: pemohon = Kepala Unit unitnya, atau unit tidak punya kepala unit → `keputusan_kepala_unit = dilewati`.
- **Skip tahap-2**: pemohon = Kepala Seksi seksinya, atau seksi tanpa kepala seksi → `keputusan_kepala_seksi = dilewati`.
- Keputusan per tahap: `disetujui | tidak_disetujui | ditangguhkan | perubahan | dilewati`.

### Aliran administrasi (`tipe_aliran = 'administrasi'`)
```
Pegawai submit → [1 KANIT] → [2 KASUBAG] → [3 PEJABAT BERWENANG] → Disetujui (saldo dipotong)
```
Status menunggu: `menunggu_atasan` (kanit/kasubag) → `menunggu_pejabat` → `disetujui`.

- **Skip tahap-1**: pemohon = kanit unitnya, atau unit tanpa kepala unit → `keputusan_kanit = dilewati`.
- **Skip tahap-1 & 2 (skip-level)**: pemohon berperan `kasubag` → langsung `menunggu_pejabat`, notifikasi "Pengajuan Cuti Baru (Skip-Level)" ke pejabat.

### Perilaku saldo
- Submit → **hold** (saldo tersedia berkurang, tersimpan rapi di ledger `hold`).
- `ditolak_*` atau `perubahan` → **release** (hold dibatalkan).
- Status `ditangguhkan` dari approver → **hold tetap** (tidak di-release).
- Disetujui (tahap terakhir) → **potong** (hold dilepas, saldo benar-benar terpotong).
- Revisi #41: admin menangguhkan `disetujui` → **release saldo potongan** (kembali) tepat 1×, ledger ditulis `release` dari catatan `potong`.

---

## A. Skenario Issue #44 — Aliran Operasional 4 Tahap

### A-01. Pengajuan operasional penuh sampai `disetujui`
**Pelaku:** Adhitya (`199109042009121001`, Unit Kargo) sebagai pemohon; Rasud → Asmaul → Hastuty sebagai approver tahap 2-3-4 (tahap-1 otomatis dilewati karena Adhitya = Kepala Unit Kargo).

| # | Langkah | Ekspektasi | Hasil ✔ | Catatan |
|---|---|---|---|---|
| 1 | Login **Adhitya** → menu **Pengajuan Cuti → Buat**; isi Kelompok Kerja (wajib), Jenis Cuti **Tahunan 3 hari**, tanggal, alasan, alamat; **Simpan** | Status **Menunggu Kepala Unit**; widget Saldo **N berkurang 3** (hold); ledger `hold`; notifikasi ke RASUD (tahap-2, tahap-1 dilewati) | | |
| 2 | Login **Rasud** → menu **Persetujuan Cuti** | Pengajuan **muncul**; ada tombol keputusan **Disetujui / Tidak Disetujui / Perubahan / Ditangguhkan** + kolom alasan | | |
| 3 | **Rasud**: pilih **Disetujui** | Status → **Menunggu Kanit Kepegawaian**; notifikasi ke **Asmaul**; `keputusan_kepala_seksi = disetujui` | | |
| 4 | **Asmaul**: lihat counter widget **Menunggu Keputusan** → **Disetujui** | Status → **Menunggu Kasubag TU**; notifikasi ke **Hastuty**; `keputusan_kanit_kepegawaian = disetujui` | | |
| 5 | **Hastuty**: **Disetujui** | Status → **Disetujui**; ledger: `hold`+`release`+`potong`; saldo N terpotong (setelah keluar dari hold) | | |
| 6 | Login **Super Admin** → menu **Pengajuan Cuti** → buka rekord | Status log lengkap `menunggu_kepala_unit → … → disetujui`; badge Disetujui; PDF tersedia | | |

### A-02. Skip tahap-1 & tahap-2 (pemohon = KU dan/atau = KS)
**Pelaku:** Adhitya (KU) dan Rasud (KS).

| # | Langkah | Ekspektasi | Hasil ✔ | Catatan |
|---|---|---|---|---|
| 1 | Login **Adhitya** → ajukan pengajuan baru | Tahap-1 **dilewati** (`keputusan_kepala_unit = dilewati`), langsung **Menunggu Kepala Seksi** (Rasud) | | |
| 2 | Login **Rasud** → ajukan pengajuan baru (ia Kepala Seksi, unit kerja dari seksi yg sama mis. profil unit-nya seksi Kampen) | Tahap-2 **dilewati**; setelah tahap-1 disetujui KU-nya, status lompat ke **Menunggu Kanit Kepegawaian** | | |

### A-03. Penolakan / koreksi di setiap tahap + perilaku saldo
**Pelaku:** pemohon pegawai operasional bukan-approver, mis. **Romi Yosep Sigar** (`198610132010121002`, Unit Terminal Hygiene & Sanitasi).

> Unit THS di bawah Seksi Yanker → tahap: KU = Romi sendiri? Tidak — Kepala Unit THS adalah Romi, sehingga tahap-1 ikut dilewati. Untuk menguji tahap-1, gunakan pemohon dari unit yang kepala unit-nya orang lain (mis. pegawai lain). Atau cukup verifikasi tahap-1 pada A-01/A-02.

| # | Skenario | Aktor | Ekspektasi | Hasil ✔ | Catatan |
|---|---|---|---|---|---|
| 1 | Tolak di tahap-2 | **Rasud** pilih **Tidak Disetujui** + alasan | Status `ditolak_kepala_seksi`; saldo **release**; notifikasi + status log ke pemohon | | |
| 2 | Tolak di tahap-3 | **Asmaul** pilih **Tidak Disetujui** | Status `ditolak_kanit_kepegawaian`; saldo **release** | | |
| 3 | Tolak di tahap-4 | **Hastuty** pilih **Tidak Disetujui** | Status `ditolak_kasubag_tu`; saldo **release** | | |
| 4 | **Ditangguhkan** oleh approver (bukan admin) | Approver tahap manapun pilih **Ditangguhkan** + alasan | Status `ditangguhkan`; **saldo tetap hold** (TIDAK release) | | |
| 5 | Merubah data | Approver pilih **Perubahan** + alasan | Status `perubahan`; saldo **release**; pemohon dapat notifikasi dengan alasan | | |

### A-04. Permintaan perubahan → resubmit pemohon → lanjut tahap benar
**Pelaku:** pemohon + approver.

| # | Langkah | Ekspektasi | Hasil ✔ | Catatan |
|---|---|---|---|---|
| 1 | (lanjut A-03 no.5) Login **pemohon** → status `perubahan` | Tombol **Edit** tersedia | | |
| 2 | Ubah tanggal (mis. tambah 1 hari) → **Simpan** | Status kembali ke **tahap terbuka pertama** (yang belum `dilewati`); notifikasi approver terkait; **TIDAK ada ledger `koreksi` ganda** | | |
| 3 | Approver menyetujui kembali | Status berjalan sampai `disetujui`; saldo **potong** | | |

### A-05. PDF operasional — Bagian VII "PERSETUJUAN BERJENJANG"
**Pelaku:** siapa saja yang bisa lihat pengajuan (pemohon/approver/admin), gunakan rekord `disetujui` dari A-01.

| # | Cek | Ekspektasi | Hasil ✔ | Catatan |
|---|---|---|---|---|
| 1 | Klik **Cetak PDF** | PDF legal, **1 halaman**, judul **"VII. PERSETUJUAN BERJENJANG"** | | |
| 2 | Isi kotak | 4 kotak: **KEPALA UNIT, KEPALA SEKSI, KANIT KEPEGAWAIAN, KASUBAG TU**; baris atas kotak berlabel rangkap **DISETUJUI** dan baris bawah **DITANGGUHKAN / TIDAK DISETUJUI** | | |
| 3 | Nama/bagian | **KU & KS**: nama, NIP, pangkat/golongan, tanpa tanda tangan (kosong); **KanitKep & KasubagTU**: nama + **tanda tangan** bila diunggah di profil | | |
| 4 | Tahap dilewati | Kotak untuk tahap yang `dilewati` tampil kosong (tipe "dilewati", bukan "disetujui") | | |
| 5 | Checklist Bagian II | Simbol centang yang benar (**✓**) | | |

### A-06. Scope/layer persetujuan (siapa melihat apa)
| # | Login sebagai | Cek | Ekspektasi | Hasil ✔ | Catatan |
|---|---|---|---|---|---|
| 1 | **KU** (Adhitya) | menu Persetujuan Cuti | Hanya pengajuan unit Kargo | | |
| 2 | **KS** (Rasud) | menu Persetujuan Cuti | Hanya pengajuan unit di Seksi Kampen (Kargo, T&PK, Proteksi, PKP-PK) | | |
| 3 | **KanitKep** (Asmaul) | menu Persetujuan Cuti | Hanya status `menunggu_kanit_kepegawaian` | | |
| 4 | **KasubagTU** (Hastuty) | menu Persetujuan Cuti | Hanya status `menunggu_kasubag_tu` | | |
| 5 | **Super Admin** | Pengajuan Cuti | Semua unit/jenis | | |
| 6 | **Pegawai biasa** | dashboard | Widget **Menunggu Keputusan** = 0 / tidak ada; tidak melihat menu Persetujuan | | |

### A-07. Regresi aliran administrasi (fitur lama tetap jalan & PDF lama)
**Pelaku:** **Muhajir** (`197204211997031003`, Unit Keuangan) sebagai pemohon (ia kanit, jadi tahap-1 dilewati); **Hastuty** (kasubag); **Prasetiyohadi** (pejabat).

| # | Langkah | Ekspektasi | Hasil ✔ | Catatan |
|---|---|---|---|---|
| 1 | Login **Muhajir** → ajukan (Unit Keuangan, jenis administrasi) | Status **Menunggu Atasan**; **tidak ada** field Kelompok Kerja; `keputusan_kanit = dilewati` (pemohon=kanit); notif ke kasubag | | |
| 2 | **Hastuty**: Persetujuan → **Disetujui** | Status → **Menunggu Pejabat**; notif ke **Prasetiyohadi** | | |
| 3 | **Prasetiyohadi**: **Disetujui** | Status → **Disetujui**; saldo **potong** | | |
| 4 | Cetak PDF rekord administrasi | Judul **"VII. PERTIMBANGAN ATASAN LANGSUNG"** + **"VIII. KEPUTUSAN PEJABAT"** (BUKAN "PERSETUJUAN BERJENJANG"); nama pejabat + ttd pada Bagian VIII | | |
| 5 | **Kasubag skip-level**: login **Hastuty**, ajukan (ia kasubag) | Langsung **Menunggu Pejabat**; notifikasi "Pengajuan Cuti Baru (Skip-Level)" ke pejabat; kasubag tidak perlu menyetujui sendiri | | |

---

## B. Skenario Issue #41 — Admin Menangguhkan Pengajuan `disetujui`

> Prasyarat: siapkan minimal 1 pengajuan operasional + 1 administrasi berstatus **Disetujui**
> (selesaikan A-01 dan A-07). Jika uji di DB produksi (bukan DB uji), bisa juga pakai
> rekord lama: pengajuan `01m1tc57npgp895ejm4awfae6y` (Adhitya, operasional, `disetujui`).

### B-01. Super Admin menangguhkan (operasional)
| # | Langkah | Ekspektasi | Hasil ✔ | Catatan |
|---|---|---|---|---|
| 1 | Login **Super Admin** → Pengajuan Cuti → baris status **Disetujui** | Tombol **Tangguhkan** tampil (hanya pada status Disetujui) | | |
| 2 | Klik **Tangguhkan** | Modal konfirmasi + kolom **Alasan (opsional)** | | |
| 3 | Isi alasan → **Konfirmasi** | Status → **Ditangguhkan**; **saldo dikembalikan** (ledger: `hold` → `release(potong)` → `potong` → `release`; saldo N kembali penuh); notifikasi ke pemohon | | |
| 4 | Buka detail rekord | Status log `disetujui → ditangguhkan`, changed_by = Super Admin, keterangan = alasan penangguhan | | |
| 5 | Cetak **PDF** | **Tetap valid**: Bagian VII masih menampilkan 4 keputusan persetujuan (historis, tdk hilang) | | |
| 6 | Cek lagi baris yang sama | Tombol **Tangguhkan sudah tidak ada** (status bukan `disetujui` lagi) → tidak bisa dobel | | |

### B-02. Super Admin menangguhkan (administrasi)
| # | Langkah | Ekspektasi | Hasil ✔ | Catatan |
|---|---|---|---|---|
| 1 | Login **Super Admin** → rekord administrasi `disetujui` → **Tangguhkan** (+ alasan) | Status `ditangguhkan`; saldo **kembali**; PDF Bagian VIII tetap menampilkan keputusan pejabat | | |

### B-03. Role lain TIDAK boleh menangguhkan
| # | Login sebagai | Cek | Ekspektasi | Hasil ✔ | Catatan |
|---|---|---|---|---|---|
| 1 | Pemohon / KS / KanitKep / KasubagTU / Pejabat | baris status Disetujui | Tombol **Tangguhkan tidak tampil** (hanya Super Admin/Admin) | | |
| 2 | (uji batas) Coba akses layanan via tinker dari user non-admin | `CutiService::tangguhkanPengajuan(...)` | Ditolak dengan exception "hanya Admin/Super Admin" dan status `disetujui` | | |

### B-04. Resubmit setelah penangguhan admin → re-approval
| # | Langkah | Ekspektasi | Hasil ✔ | Catatan |
|---|---|---|---|---|
| 1 | Login **pemohon** (setelah B-01) | Status `ditangguhkan`; tombol **Edit** aktif (keputusan tetap tersimpan sebagai "disetujui" historis) | | |
| 2 | Ubah tanggal → **Simpan** | Status kembali ke **tahap terbuka pertama** (`dilewati` tetap dilewati); notifikasi approver terkait; **tidak ada `koreksi` ganda** | | |
| 3 | Approver menyetujui sampai tahap akhir | Status → **Disetujui**; ledger `potong` baru; saldo **terpotong lagi** | | |

### B-05. Konsistensi saldo multi-pengajuan
| # | Langkah | Ekspektasi | Hasil ✔ | Catatan |
|---|---|---|---|---|
| 1 | Setelah ditangguhkan (B-01), pemohon ajukan pengajuan lain | Saldo penuh tersedia untuk pengajuan baru (bukti `release` sukses) | | |
| 2 | Setelah re-approval (B-04), ajukan pengajuan lain | Saldo berkurang sesuai potongan terbaru (bukti `potong` benar) | | |

### B-06. Status logo kehadiran di widget riwayat
| # | Langkah | Ekspektasi | Hasil ✔ | Catatan |
|---|---|---|---|---|
| 1 | Dashboard pemohon → widget Riwayat | Rekord ditampilkan dengan badge **Ditangguhkan** setelah aksi B-01, dan **Disetujui** kembali setelah B-04 | | |

---

## C. Uji Lintas Fitur (semua peran sekali lewat)

### C-01. Login & kelengkapan profil
| # | Role | Ekspektasi | Hasil ✔ | Catatan |
|---|---|---|---|---|
| 1 | Super Admin | Tidak diminta Lengkapi Profil; langsung dashboard | | |
| 2 | Semua user lain (pegawai/kanit/kasubag/pejabat/kanit_kep/kasubag_tu) | Setelah login teralih ke **Lengkapi Profil**; setelah lengkap masuk dashboard | | |

### C-02. Dashboard & widget per role
| # | Role | Ekspektasi | Hasil ✔ | Catatan |
|---|---|---|---|---|
| 1 | Super Admin | Statistik + Saldo Cuti (miliknya) + Menunggu Keputusan (0) + Pegawai Sedang Cuti + Riwayat | | |
| 2 | Approver | **Menunggu Keputusan** menampilkan hitungan sesuai scope; **Pegawai Sedang Cuti** per unit/seksi | | |
| 3 | Pegawai biasa | Saldo Cuti + Riwayat pengajuan sendiri | | |

### C-03. Menu & otorisasi
| # | Role | Ekspektasi | Hasil ✔ | Catatan |
|---|---|---|---|---|
| 1 | Super Admin | Semua menu: Pengajuan Cuti, Persetujuan Cuti, User, Unit, Seksi, Kelompok Kerja, Hari Libur, **Export Excel** | | |
| 2 | Approver | Menu **Persetujuan Cuti** (+ halaman profil); tidak bisa membuka Pengajuan Cuti (semua user) | | |
| 3 | Pegawai | Menu **Pengajuan Cuti** (membuat & list milik sendiri); akses langsung `role` non-pegawai harus diblokir (backend guard) | | |
| 4 | Super Admin | **Export Excel** berhasil diunduh | | |

### C-04. Policy edit / hapus
| # | Skenario | Ekspektasi | Hasil ✔ | Catatan |
|---|---|---|---|---|
| 1 | Edit saat status `perubahan` / `ditangguhkan` oleh pemohon | Diizinkan (resubmit) | | |
| 2 | Edit saat status `disetujui`/`ditolak_*` oleh pemohon | Diblokir | | |
| 3 | Hapus pengajuan `disetujui` oleh pemohon/approver | Diblokir; hanya admin yang bisa soft-delete | | |
| 4 | Hapus oleh admin saat status lain | Soft-delete; saldo **release**; status log `dihapus` (audit log tercatat) | | |

---

## 4. Matriks Hasil Ringkas (rekap per user yang diuji)

| NIP | Nama | A-01 | A-02 | A-03 | A-04 | A-05 | A-06 | A-07 | B-01 | B-02 | B-03 | B-04 | B-05 | B-06 | C-01 | C-02 | C-03 | C-04 |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| 000000000000000000 | Super Admin | | | | | | | | | | | | | | | | | |
| 199109042009121001 | Adhitya | | | | | | | | | | | | | | | | | |
| 197109121992031003 | Rasud Mohamad | | | | | | | | | | | | | | | | | |
| 198202092006042001 | Asmaul Husna Sabil | | | | | | | | | | | | | | | | | |
| 197504211999032001 | Hastuty | | | | | | | | | | | | | | | | | |
| 197804042002121003 | Prasetiyohadi | | | | | | | | | | | | | | | | | |
| 197204211997031003 | Muhajir | | | | | | | | | | | | | | | | | |
| 198610132010121002 | Romi Yosep Sigar | | | | | | | | | | | | | | | | | |

---

## 5. Setelah UAT Selesai

1. Hentikan instance uji:
   ```bash
   docker rm -f laravel-cuti-uat laravel-cuti-uat-queue
   ```
2. Hapus DB uji bila tidak diperlukan lagi:
   ```bash
   rm -f database/database_uat.sqlite
   ```
3. (Opsional) Rangkum temuan pada kolom Catatan; tim bisa menindaklanjuti bila ada
   ketidaksesuaian dengan implementasi (`CutiService::tangguhkanPengajuan()`,
   `PengajuanCutiObserver`, `PersetujuanCutisTable`, `PengajuanCutisTable`).