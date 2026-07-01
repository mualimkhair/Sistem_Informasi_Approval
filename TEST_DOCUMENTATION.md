# Test Documentation - Sistem Informasi Approval Cuti UPBU

## Overview
Comprehensive test suite untuk Sistem Informasi Approval Cuti (Leave Management System) menggunakan PHPUnit.

## Test Coverage

### 1. Feature Tests

#### PengajuanCutiTest.php
Testing fungsionalitas pengajuan cuti:
- ✅ Pegawai dapat membuat pengajuan cuti
- ✅ Status pengajuan adalah "menunggu_atasan" setelah dibuat
- ✅ Kanit dapat menyetujui pengajuan
- ✅ Kasubag dapat menyetujui pengajuan
- ✅ Status berubah menjadi "menunggu_pejabat" setelah kedua atasan menyetujui
- ✅ Pejabat dapat memberikan persetujuan final
- ✅ Pengajuan dapat ditolak oleh Kanit
- ✅ Pengajuan dapat ditolak oleh Pejabat
- ✅ Saldo cuti dipotong setelah disetujui
- ✅ User hanya dapat melihat pengajuan mereka sendiri

#### AuthenticationTest.php
Testing autentikasi dan otorisasi:
- ✅ Halaman login dapat diakses
- ✅ User dapat login dengan kredensial valid
- ✅ User tidak dapat login dengan kredensial invalid
- ✅ User dapat logout
- ✅ Profile completion required untuk user baru
- ✅ Super admin dapat akses admin panel
- ✅ Pegawai dapat akses admin panel

#### CutiServiceTest.php
Testing service perhitungan cuti:
- ✅ Perhitungan durasi cuti mengecualikan weekend (administrasi)
- ✅ Perhitungan mengecualikan hari libur nasional
- ✅ Perhitungan untuk unit operasional dengan hari libur custom
- ✅ Method invalidDates mengembalikan tanggal yang dikecualikan
- ✅ Perhitungan cuti 1 hari
- ✅ Cuti di weekend untuk administrasi = 0
- ✅ Akurasi perhitungan durasi cuti
- ✅ Cuti bersama dikecualikan dari perhitungan

#### SaldoCutiTest.php
Testing manajemen saldo cuti:
- ✅ User memiliki saldo cuti
- ✅ Struktur saldo cuti benar (N, N-1, N-2)
- ✅ Prioritas pemotongan saldo (N-2 → N-1 → N)
- ✅ Saldo cuti sakit terpisah
- ✅ Saldo cuti melahirkan terpisah
- ✅ Saldo cuti tidak boleh negatif
- ✅ Tracking tahun saldo cuti

#### MasterDataTest.php
Testing data master:
- ✅ Unit Kerja dapat dibuat
- ✅ Unit Kerja memiliki 2 jenis (administrasi/operasional)
- ✅ Kelompok Kerja belongs to Unit Kerja
- ✅ Kelompok Kerja memiliki hari libur custom
- ✅ Hari Libur dapat dibuat
- ✅ Hari Libur memiliki 2 jenis (libur_nasional/cuti_bersama)
- ✅ Tanggal Hari Libur adalah unique
- ✅ Count unit kerja administrasi
- ✅ Count unit kerja operasional

#### RoleAndPermissionTest.php
Testing roles dan permissions:
- ✅ Role super_admin exists
- ✅ Semua role yang diperlukan exists
- ✅ User dapat assigned role pegawai
- ✅ User dapat assigned role kanit
- ✅ User dapat assigned role kasubag
- ✅ User dapat assigned role pejabat_berwenang
- ✅ User dapat memiliki multiple roles
- ✅ User tanpa role adalah guest

#### UserManagementTest.php
Testing manajemen user:
- ✅ NIP user adalah unique
- ✅ NIP adalah 18 digit
- ✅ User memiliki field yang diperlukan
- ✅ User belongs to Unit Kerja
- ✅ Status profile completion
- ✅ User dapat update profile
- ✅ User memiliki relasi saldo cuti
- ✅ User memiliki relasi pengajuan cuti
- ✅ User dapat memiliki signature

#### PdfGenerationTest.php
Testing generate PDF:
- ✅ Route PDF generation exists
- ✅ PDF memiliki content-type yang benar
- ✅ Hanya authenticated user yang dapat generate PDF

#### WorkflowIntegrationTest.php
Testing workflow approval lengkap:
- ✅ Complete approval workflow (Pegawai → Kanit → Kasubag → Pejabat)
- ✅ Rejection oleh Kanit menghentikan workflow
- ✅ Rejection oleh Pejabat setelah atasan approve
- ✅ Request perubahan memungkinkan resubmission
- ✅ Status ditangguhkan
- ✅ Multiple leave requests oleh user yang sama

#### SecurityTest.php
Testing keamanan:
- ✅ Password di-hash
- ✅ Unauthenticated user tidak dapat akses admin panel
- ✅ User tidak dapat akses pengajuan user lain
- ✅ SQL injection prevention
- ✅ XSS prevention
- ✅ CSRF token required
- ✅ Session expires setelah logout
- ✅ NIP harus unique
- ✅ Role-based access control

#### PerformanceTest.php
Testing performa (Non-Functional):
- ✅ Homepage load dalam waktu acceptable
- ✅ Database seeding performance
- ✅ Leave calculation performance
- ✅ Concurrent user sessions
- ✅ Bulk leave request creation

#### ValidationTest.php
Testing validasi:
- ✅ Tanggal mulai harus di masa depan
- ✅ Tanggal selesai harus setelah tanggal mulai
- ✅ Alasan cuti required
- ✅ Alamat selama cuti required
- ✅ Jenis cuti harus valid
- ✅ NIP harus 18 digit
- ✅ Saldo cuti harus mencukupi
- ✅ Validasi field profile user
- ✅ Format nomor telepon
- ✅ Durasi cuti harus positif

### 2. Unit Tests

#### PengajuanCutiModelTest.php
Testing model PengajuanCuti:
- ✅ PengajuanCuti menggunakan ULID
- ✅ Jenis cuti values valid
- ✅ Status values valid
- ✅ Keputusan values valid

## Running Tests

### Run All Tests
```bash
composer test
```

### Run Specific Test Suite
```bash
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

### Run Specific Test File
```bash
php artisan test tests/Feature/PengajuanCutiTest.php
```

### Run with Coverage
```bash
php artisan test --coverage
```

## Test Environment

### Configuration
- Database: SQLite in-memory
- Environment: testing
- Seeding: Automatic before each test

### Test Data
- 5 demo users with different roles
- 29 unit kerjas (13 administrasi, 16 operasional)
- Leave balances seeded for testing

## Requirements Covered

### Kebutuhan Fungsional
1. ✅ Login & Authentication
2. ✅ Role-based Access Control (6 roles)
3. ✅ Pengajuan Cuti (6 jenis cuti)
4. ✅ Multi-level Approval Workflow
5. ✅ Leave Balance Management
6. ✅ Leave Calculation (exclude weekends/holidays)
7. ✅ PDF Generation
8. ✅ Profile Management
9. ✅ Master Data Management

### Kebutuhan Non-Fungsional
1. ✅ Security (Password hashing, SQL injection prevention, XSS prevention)
2. ✅ Performance (Response time, concurrent users)
3. ✅ Data Validation
4. ✅ Database Integrity (Unique constraints, relationships)
5. ✅ Session Management

## Notes

- Semua test menggunakan RefreshDatabase untuk clean state
- Database di-seed sebelum setiap test
- Test coverage mencakup positive dan negative scenarios
- Security tests mencakup common vulnerabilities
- Performance tests memastikan response time acceptable
