# **REVISI SISTEM PENGAJUAN CUTI** 

**1.** Penambahan Fitur Perubahan Status Menjadi Penangguhan Cuti oleh Admin Menambahkan fitur yang memungkinkan Admin mengubah status pengajuan cuti yang telah disetujui menjadi Ditangguhkan. Fitur ini digunakan apabila terdapat pegawai yang pengajuan cutinya telah memperoleh persetujuan, tetapi pelaksanaan cutinya perlu ditunda berdasarkan kondisi atau kebutuhan kedinasan. Perubahan status dilakukan oleh Admin dari status Disetujui menjadi Ditangguhkan, dengan tetap menyimpan riwayat perubahan status dan informasi pengajuan cuti. 

## **2. Penyimpanan Data Historis untuk Cetak Dokumen PDF** 

Menyesuaikan mekanisme pencetakan dokumen PDF agar tidak mengambil data secara real-time melalui relasi tabel, tetapi menggunakan snapshot data pada saat pengajuan dan persetujuan dilakukan. 

Contoh: apabila pada saat pengajuan cuti seorang pegawai disetujui oleh Kanit/Kasubag A, kemudian data pejabat tersebut berubah menjadi Kanit/Kasubag B setelah pengajuan disetujui, maka dokumen PDF yang dicetak harus tetap menampilkan Kanit/Kasubag A sebagai pejabat yang memberikan persetujuan pada saat pengajuan tersebut diproses. Dengan demikian, dokumen yang dihasilkan tetap merepresentasikan kondisi historis pengajuan dan persetujuan pada saat kejadian. 

## **3. Pemisahan Tombol Rollover Saldo Cuti** 

Memindahkan tombol Rollover Saldo dari setiap record data pegawai dan menyediakan tombol atau menu Rollover Saldo secara terpisah. Mekanisme ini bertujuan agar proses rollover dapat dilakukan secara terpusat dan tidak melekat pada masing-masing record pegawai. 

## **4. Penerapan Soft Delete pada Data Seksi** 

Mengubah mekanisme penghapusan data pada Seksi menjadi soft delete, sebagaimana telah diterapkan pada data Unit Kerja. Data yang dihapus tidak dihilangkan secara permanen dari database, tetapi ditandai sebagai tidak aktif sehingga riwayat dan relasi data tetap dapat dipertahankan serta dapat digunakan kembali apabila diperlukan. 

## **5. Penyesuaian Alur Pengajuan Cuti Pegawai Operasional Menjadi Empat Tahap** 

Menyesuaikan alur pengajuan cuti bagi pegawai operasional menjadi empat tahap persetujuan, dengan mekanisme sebagai berikut: 

- **Tahap 1 Kepala Unit:** Pengajuan cuti dari pegawai operasional diteruskan kepada Kepala Unit sesuai dengan unit kerja tempat pegawai tersebut ditempatkan untuk dilakukan pemeriksaan dan persetujuan. 

- **Tahap 2 Kepala Seksi:** Setelah mendapatkan persetujuan dari Kepala Unit, pengajuan diteruskan kepada Kepala Seksi terkait untuk dilakukan pemeriksaan dan persetujuan. 

- **Tahap 3 Kanit Kepegawaian:** Setelah memperoleh persetujuan dari pihak operasional terkait, pengajuan diteruskan kepada Kanit Kepegawaian untuk dilakukan proses administrasi dan persetujuan. 

- **Tahap 4 Kasubag:** Setelah diproses oleh Kanit Kepegawaian, pengajuan diteruskan kepada Kasubag untuk dilakukan persetujuan akhir pada alur pengajuan cuti. 

Dalam mekanisme ini, Kepala Unit dan Kepala Seksi hanya memiliki kewenangan untuk memberikan approval, sedangkan pengisian dan pembubuhan tanda tangan pada dokumen cuti dilakukan oleh Kanit Kepegawaian dan Kasubag sesuai dengan kewenangan masing-masing. 

## **6. Penyesuaian Alur Dokumen Cuti dan Persetujuan Kabandara** 

Menyesuaikan alur pengajuan cuti yang telah disetujui agar tidak meneruskan pengajuan cuti utama kepada Kabandara. Proses pengajuan cuti cukup diselesaikan sampai dengan Kasubag. 

Untuk Kabandara, dokumen yang diteruskan hanya berupa surat blangko untuk mendapatkan persetujuan. Setelah blangko tersebut disetujui oleh Kabandara, sistem akan mengembalikan hasil pengajuan kepada pegawai dalam bentuk dokumen PDF, yang terdiri atas: 

- Surat Izin Cuti; 

- Blangko Cuti yang telah disetujui Kabandara. 

Dengan demikian, Kabandara tidak melakukan approval terhadap pengajuan cuti utama, melainkan hanya memberikan persetujuan terhadap dokumen blangko. Pengajuan cuti utama tetap berakhir pada proses persetujuan Kasubag, sedangkan hasil akhir berupa surat izin dan blangko yang telah disetujui kemudian dikirimkan kembali kepada pegawai. 

