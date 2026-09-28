# AI Development Workflow

Dokumentasi ini adalah pusat referensi untuk pengembangan berbantu AI pada repositori ini. Workflow ini dirancang untuk memastikan AI dan developer memiliki konteks yang jelas, objektif yang terukur, dan batasan pekerjaan yang eksplisit pada setiap sesi.

## Peta Dokumentasi

| Dokumen | Fungsi |
|---|---|
| **`AGENTS.md`** | *[Opsional]* Menyimpan aturan utama dan batasan yang harus diikuti AI saat berinteraksi di repository ini. |
| **`ai-context.md`** | Berisi konteks teknis sistem yang relatif stabil (arsitektur, pola desain, alur bisnis inti). Membantu AI baru memahami sistem tanpa perlu membaca seluruh kode. |
| **`PRD.md`** | Product Requirements Document. Berisi definisi masalah, tujuan, dan rincian fitur/persyaratan yang harus dipenuhi untuk suatu *issue* atau inisiatif pengembangan. |
| **`TASKS.md`** | Rincian PRD yang dipecah menjadi unit tugas teknis (tasks) kecil dan terukur. AI akan mengimplementasikan satu tugas per sesi berdasarkan dokumen ini. |
| **`HANDOVER.md`** | Dokumen serah terima status pengembangan antar sesi AI. Berfungsi untuk me-*resume* pekerjaan tanpa kehilangan konteks status saat membuka chat AI baru. |
| **`skenario-uji-integrasi.md`** | *(Jika ada)* Dokumentasi User Acceptance Testing (UAT) manual maupun otomatis yang sudah disepakati untuk menjamin fungsionalitas. |

---

## Workflow Pengembangan Berbasis AI

Berikut adalah alur standar yang harus diikuti saat memulai atau melanjutkan pengembangan fitur:

1. **Requirements Review** 
   Developer dan AI meninjau permintaan fitur atau bug (misalnya dari GitHub Issues).
2. **PRD & Context Update** 
   AI menyusun `PRD.md` dan memperbarui `ai-context.md` jika ada perubahan struktural pada pemahaman sistem.
3. **Task Breakdown** 
   AI memecah PRD menjadi daftar tugas implementasi teknis di `TASKS.md`.
4. **Fresh AI Chat + Handover** 
   Developer membuka sesi chat AI baru (fresh chat) dan mengumpankan isi `HANDOVER.md` (dan dokumen relevan lainnya) agar AI langsung memahami status terkini.
5. **Implementation** 
   AI mengimplementasikan **satu** task kecil sesuai daftar di `TASKS.md`.
6. **Automated Test** 
   AI menulis dan menjalankan pengujian otomatis (PHPUnit/Pest) untuk memvalidasi implementasi.
7. **Manual UAT** 
   Developer melakukan pengujian fungsional manual berbasis UI/dokumentasi UAT.
8. **Handover Update** 
   AI memperbarui `HANDOVER.md` (menandai task selesai, mencatat isu) sebelum sesi berakhir.
9. **Next Task** 
   Ulangi dari tahap (4) atau (5) untuk mengerjakan task berikutnya.
