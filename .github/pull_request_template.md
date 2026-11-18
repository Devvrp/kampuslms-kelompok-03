<!-- Lengkapi bagian yang relevan. Hapus petunjuk dalam tanda komentar sebelum mengirim Pull Request. Jika suatu checklist tidak relevan, beri keterangan N/A pada deskripsi PR. -->

## Apa yang diubah

<!-- Jelaskan perubahan utama dalam 2–5 poin. Fokus pada perilaku atau hasil yang berubah, bukan hanya daftar file. -->
-
-

## Kenapa

<!-- Jelaskan masalah/kebutuhan yang melatarbelakangi perubahan. Sertakan nomor issue atau tugas jika tersedia. -->


## Jenis perubahan

- [ ] Fitur baru
- [ ] Perbaikan bug
- [ ] Validasi atau keamanan
- [ ] Performa atau refactor
- [ ] Dokumentasi
- [ ] Konfigurasi, deployment, atau CI

## Rincian implementasi

<!-- Sebutkan komponen, halaman, endpoint, model, migration, job/notification, atau konfigurasi yang terdampak. Jelaskan perilaku penting sebelum dan sesudah perubahan. -->
-


## Cara menguji

**Lingkungan:** <!-- lokal/testing/staging; jangan menuliskan credential atau secret -->

**Role/akun pengujian:** <!-- admin/dosen/mahasiswa; gunakan akun uji, jangan tuliskan password -->

**Prasyarat/data awal:** <!-- data atau kondisi yang diperlukan sebelum pengujian -->

**Langkah pengujian:**
1.
2.
3.

**Hasil yang diharapkan:**


**Hasil aktual:**


## Bukti pengujian

<!-- Cantumkan perintah yang dijalankan dan ringkasan hasilnya. Untuk perubahan UI, sertakan screenshot sebelum/sesudah bila relevan. -->

- Perintah test:
- Hasil test:
- CI:
- Bukti tampilan/log (jika relevan):

## Dampak dan risiko

<!-- Isi bagian yang relevan. Jelaskan dampak ke data, user role, API, file, queue, atau perilaku lama. -->

- **Database/migration:** <!-- Tidak ada / jelaskan perubahan dan langkah rollback -->
- **Role dan otorisasi:** <!-- Role yang terdampak -->
- **API atau integrasi:** <!-- Endpoint/kontrak yang berubah -->
- **File dan storage:** <!-- Upload, download, lokasi penyimpanan, akses -->
- **Queue/notifikasi:** <!-- Job, listener, worker, atau konfigurasi -->
- **Kompatibilitas/risiko:** <!-- Potensi efek samping atau langkah pemulihan -->

## Daftar periksa

- [ ] Validasi ada di server-side
- [ ] Otorisasi diperiksa (`Gate::authorize()` dipanggil di controller)
- [ ] Tidak ada N+1 (diperiksa dengan Debugbar)
- [ ] Ada feature test untuk jalur sukses dan jalur ditolak
- [ ] Tidak ada `dd()`, `dump()`, atau `console.log` tertinggal
- [ ] `.env` tidak tersentuh
- [ ] Tidak ada credential, token, atau secret yang ikut di-commit.
- [ ] `.env.example` diperbarui bila ada variabel environment baru yang memang diperlukan.
- [ ] Tidak ada file upload, log, cache, atau artefak debug yang tidak seharusnya di-commit.
- [ ] Migration aman dijalankan dan langkah rollback dipahami jika ada perubahan schema.
- [ ] Dokumentasi diperbarui bila cara penggunaan, API, atau konfigurasi berubah.
- [ ] `git diff --check` tidak menunjukkan masalah whitespace.
- [ ] GitHub Actions/CI sudah diperiksa dan hasilnya lulus.

## Bagian yang dibantu AI

<!-- Jika tidak menggunakan AI, tulis "Tidak menggunakan AI". Jika menggunakan AI, jelaskan bagian yang dibantu dan perubahan yang dilakukan terhadap hasil awal. Pastikan hasil akhirnya sudah dipahami dan diuji. -->

- **Bagian yang dibantu:**
- **Perubahan/review yang dilakukan sendiri:**
- **Cara memverifikasi hasil:**

## Catatan tambahan untuk reviewer

<!-- Tulis hal yang perlu diperhatikan reviewer, keputusan teknis, keterbatasan yang diketahui, atau bagian yang membutuhkan perhatian khusus. -->
