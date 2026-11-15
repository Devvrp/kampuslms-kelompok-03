### Nama : Fariz Daffa Abbiyu Rahmatullah

### NIM  : 10241029

----

### READ Minggu 10

### 1. Saat worker dimatikan dan tugas baru dibuat, apa isi tabel `jobs`?

Karena `QUEUE_CONNECTION=database` dan Listener `SendAssignmentNotification` memakai `ShouldQueue`, Laravel memasukkan pekerjaan pengiriman notifikasi ke tabel `jobs`. Job menunggu sampai worker dijalankan. Pada pemeriksaan database sebelumnya, tabel `jobs` berisi 0 baris karena belum ada job baru yang tertinggal di antrean.

### 2. Bisakah mengenali nama Listener dan ID tugas dari kolom `payload`?

Bisa. Nama Listener dapat dikenali sebagai `App\Listeners\SendAssignmentNotification`. ID tugas tersimpan dalam data Event `AssignmentPublished`, melalui properti `assignmentId`. Payload Laravel menyimpan informasi Listener dan perintah yang akan dijalankan.

### 3. Apa yang terjadi setelah menjalankan `php artisan queue:work`?

Worker mengambil job dari tabel `jobs`, kemudian menjalankan method `handle()` pada `SendAssignmentNotification`. Listener mencari tugas berdasarkan ID, mengambil mahasiswa yang terdaftar pada mata kuliah, lalu mengirim notifikasi. Setelah berhasil, job dihapus dari tabel `jobs`.

### 4. Berapa baris yang muncul di tabel `notifications`? Apakah cocok dengan jumlah mahasiswa?

Pada pemeriksaan database, terdapat 15 baris notifikasi dengan 15 penerima, sedangkan jumlah seluruh mahasiswa adalah 30. Jumlah notifikasi tidak harus sama dengan jumlah seluruh mahasiswa karena Listener hanya mengirim notifikasi kepada mahasiswa yang terdaftar pada mata kuliah tugas tersebut.

### 5. Apakah ada data yang tidak seharusnya tersimpan di kolom `data`?

Contoh data yang diperiksa adalah `{"assignment_id":17,"title":"WEEK 10"}`. Isinya hanya ID tugas dan judul, sehingga tidak ada nilai mahasiswa atau informasi sensitif. Pada `GradeGivenNotification`, data yang disimpan juga hanya `submission_id` dan `title`, bukan nilai angka mahasiswa.

---

## BREAK Minggu 10

### 1. Set `QUEUE_CONNECTION=database`, jangan jalankan worker, lalu buat tugas baru.

**Prediksi:** Notifikasi tidak langsung dikirim karena job masuk ke antrean database dan menunggu worker.

**Hasil:** Pembuatan tugas tetap dapat berhasil, tetapi pekerjaan pengiriman notifikasi tertunda. Job tersimpan di tabel `jobs` sampai worker dijalankan. Tidak muncul error hanya karena worker sedang mati.

### 2. Hapus `implements ShouldQueue` dari Listener, lalu buat tugas untuk 40 mahasiswa.

**Prediksi:** Listener dijalankan langsung ketika Event dipanggil, bukan dimasukkan ke antrean.

**Hasil:** Pengiriman notifikasi berjalan secara sinkron. Proses pembuatan tugas menjadi lebih lama karena aplikasi harus mengirim notifikasi kepada seluruh mahasiswa sebelum menyelesaikan proses. Pada kode saat ini, Listener menggunakan `chunkById(100)` untuk mengambil mahasiswa secara bertahap.

### 3. Ganti `QUEUE_CONNECTION=sync`, lalu ulangi nomor 2.

**Prediksi:** Driver `sync` menjalankan job secara langsung tanpa menunggu worker.

**Hasil:** Jika `ShouldQueue` sudah dihapus seperti nomor 2, Listener tetap berjalan langsung karena tidak lagi menjadi queued listener. Jika `ShouldQueue` dipasang kembali, driver `sync` akan menjalankan Listener saat itu juga. Jadi, driver `sync` tidak membutuhkan proses `queue:work`.

### 4. Buat Notification melempar exception, lalu jalankan worker.

**Prediksi:** Job gagal ketika exception terjadi dan Laravel mencoba menjalankannya kembali sesuai konfigurasi `tries`.

**Hasil:** Kedua Listener memiliki konfigurasi `tries = 3` dan `backoff = 10`. Artinya, Laravel memberi maksimal tiga percobaan dengan jeda 10 detik. Jika semua percobaan gagal, job masuk ke tabel `failed_jobs`. Method `failed()` juga mencatat kegagalan ke log aplikasi.

### 5. Hentikan worker di tengah pemrosesan job.

**Prediksi:** Job yang belum selesai tidak langsung dianggap berhasil.

**Hasil:** Jika worker dihentikan secara paksa ketika job sedang diproses, job dapat diproses ulang setelah masa `retry_after` berakhir. Pada konfigurasi project, `retry_after` untuk database adalah 90 detik. Jika worker dihentikan secara normal setelah job selesai, job sudah dihapus dari antrean.

### 6. Masukkan nilai mahasiswa ke payload notifikasi, lalu buka tabel `notifications` sebagai admin.

**Prediksi:** Nilai mahasiswa dapat dibaca melalui kolom `data` pada database.

**Hasil:** Data nilai yang dimasukkan ke payload notifikasi akan tersimpan di tabel `notifications` dalam bentuk JSON. Admin atau pihak yang memiliki akses database dapat membacanya. Karena itu, informasi nilai pribadi tidak seharusnya dimasukkan ke payload jika tidak diperlukan.

### 7. Panggil endpoint tandai dibaca menggunakan ID notifikasi milik orang lain.

**Prediksi:** Permintaan ditolak karena notifikasi bukan milik pengguna yang sedang login.

**Hasil:** `NotificationController::read()` memeriksa `notifiable_type` dan `notifiable_id` sebelum mengubah status notifikasi. Jika ID tersebut milik pengguna lain, Laravel mengembalikan HTTP 403. Pada Livewire, relasi `notifications()` juga membatasi pencarian hanya pada notifikasi milik mahasiswa yang sedang login.

### 8. Jika memakai Reverb, ganti `PrivateChannel` menjadi `Channel`, lalu langganan channel orang lain.

**Prediksi:** Channel publik dapat diakses tanpa proses otorisasi kepemilikan seperti pada private channel.

**Hasil:** Jika data pribadi dikirim melalui `Channel` publik, pengguna lain dapat berlangganan dan menerima informasi realtime tersebut. `PrivateChannel` memerlukan proses otorisasi sebelum pengguna dapat berlangganan. Project ini tidak menggunakan Laravel Reverb: package `laravel/reverb` tidak ditemukan dan file `routes/channels.php` tidak tersedia, sehingga skenario ini tidak diterapkan pada konfigurasi project saat ini.