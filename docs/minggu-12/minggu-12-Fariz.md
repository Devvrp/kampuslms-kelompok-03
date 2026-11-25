### Nama : Fariz Daffa Abbiyu Rahmatullah

### NIM  : 10241029

----

### READ Minggu 12

### 1. Konfigurasi Nginx dan document root

Konfigurasi Nginx terdapat pada file `nginx.conf`.

Baris yang menentukan document root adalah:

```nginx
root /var/www/html/public;
```

Document root mengarah ke folder `public` Laravel, bukan ke akar proyek `/var/www/html`. Hal ini benar karena hanya folder `public` yang boleh diakses langsung melalui browser.

Nginx juga memiliki perlindungan untuk file tersembunyi:

```nginx
location ~ /\.(?!well-known).* {
    return 404;
}
```

Aturan tersebut mencegah akses langsung ke file seperti `.env`.

### 2. Perbandingan `php artisan about` server dan lokal

Perbedaan konfigurasi lokal dan production:

| Konfigurasi | Lokal | Production |
|---|---|---|
| Environment | local | production |
| Debug | true | false |
| Database | SQLite | MySQL 8.4.13 |
| Web server | PHP Artisan Serve | Nginx |
| PHP | PHP lokal | PHP 8.4 |
| Konfigurasi | File `.env` lokal | Railway Variables |
| Asset | Vite development/build | Vite production build |

Production menggunakan `APP_ENV=production` dan `APP_DEBUG=false` agar informasi kesalahan aplikasi tidak ditampilkan kepada pengunjung.

Database production menggunakan MySQL Railway, sedangkan pengembangan lokal menggunakan SQLite.

### 3. Status Supervisor dan jumlah worker

Pada Dockerfile, aplikasi menjalankan PHP-FPM dan Nginx:

```dockerfile
php-fpm -D; exec nginx -g 'daemon off;'
```

Supervisor tidak diinstal atau dijalankan pada container aplikasi utama. Karena itu, jumlah worker Supervisor pada container tersebut adalah 0.

Pemrosesan queue membutuhkan worker tersendiri, misalnya menggunakan:

```bash
php artisan queue:work
```

Worker harus dijalankan sebagai proses terpisah agar notifikasi yang menggunakan database queue dapat diproses.

### 4. Pemeriksaan Laravel log

Konfigurasi log terdapat pada `config/logging.php`.

Default log channel menggunakan:

```php
'default' => env('LOG_CHANNEL', 'stack'),
```

Channel `stack` secara default menggunakan `single`, dengan lokasi:

```text
storage/logs/laravel.log
```

Isi log dapat berupa informasi aplikasi, peringatan, dan error. Jumlah baris terus bertambah sesuai aktivitas aplikasi. Entri terbaru biasanya dapat diperiksa untuk mengetahui apakah ada kegagalan database, session, atau proses aplikasi.

Pada container, log Nginx juga diarahkan ke standard output dan standard error agar dapat dilihat melalui Railway service logs.

### 5. Pemeriksaan HTTPS menggunakan curl

Tiga pemeriksaan yang digunakan:

```bash
curl -I https://kampuslms-kelompok-03-production.up.railway.app
```

Untuk memeriksa respons halaman utama. Respons yang berhasil biasanya berupa HTTP 200.

```bash
curl -I http://kampuslms-kelompok-03-production.up.railway.app
```

Untuk memeriksa akses HTTP. Akses HTTP sebaiknya diarahkan ke HTTPS agar komunikasi berlangsung secara aman.

```bash
curl -I https://kampuslms-kelompok-03-production.up.railway.app/up
```

Untuk memeriksa endpoint health check Laravel. Respons HTTP 200 menunjukkan aplikasi dapat merespons permintaan health check.

### 6. Pengujian aplikasi melalui HP

Aplikasi dapat dibuka melalui browser HP menggunakan alamat production:

https://kampuslms-kelompok-03-production.up.railway.app

Pengujian mobile dilakukan dengan login, membuka dashboard, melihat daftar mata kuliah, membuka tugas, dan mencoba fitur notifikasi.

Tampilan halaman yang menggunakan layout responsif dapat menyesuaikan ukuran layar. Bagian tabel atau daftar dengan banyak kolom perlu diperhatikan agar tidak terpotong pada layar kecil.

---

## BREAK Minggu 12

### 1. Mengubah `APP_DEBUG=true` dan memicu error

**Prediksi:**

Laravel menampilkan halaman exception yang berisi informasi teknis aplikasi.

**Hasil:**

Ketika debug aktif, halaman error dapat menampilkan stack trace, lokasi file, baris kode, informasi request, serta detail query database. Informasi ini berbahaya jika dapat dilihat pengguna karena dapat membantu penyerang memahami struktur aplikasi.

Pada production, `APP_DEBUG` harus tetap `false`.

### 2. Mengubah document root Nginx ke akar proyek dan menghapus blok perlindungan file tersembunyi.

**Prediksi:**

File konfigurasi dan file internal project dapat diakses melalui browser.

**Hasil:**

Jika document root diarahkan ke `/var/www/html`, bukan `/var/www/html/public`, file seperti `.env`, `composer.json`, dan file internal lainnya berpotensi dapat diunduh.

Akibatnya, informasi database, konfigurasi aplikasi, dan rahasia lainnya bisa bocor. Document root harus selalu diarahkan ke folder `public`, serta aturan perlindungan file tersembunyi harus dipertahankan.

### 3. Menjalankan `config:cache` ketika `env()` masih digunakan di controller.

**Prediksi:**

Pemanggilan `env()` dari controller dapat mengembalikan `null` setelah konfigurasi di-cache.

**Hasil:**

Setelah menjalankan:

```bash
php artisan config:cache
```

Laravel menggunakan konfigurasi yang sudah dikompilasi. Pemanggilan `env()` seharusnya hanya dilakukan di dalam file konfigurasi.

Contoh penggunaan yang benar di controller:

```php
config('app.name');
```

Cara tersebut tetap dapat mengambil nilai konfigurasi setelah cache diaktifkan.

### 4. Menghentikan Supervisor, lalu membuat tugas baru.

**Prediksi:**

Notifikasi tidak langsung dikirim karena worker berhenti memproses antrean.

**Hasil:**

Ketika tugas baru diterbitkan, Event `AssignmentPublished` memicu Listener `SendAssignmentNotification`. Karena Listener menggunakan `ShouldQueue` dan koneksi queue menggunakan database, pekerjaan menunggu di tabel `jobs`.

Notifikasi belum masuk ke tabel `notifications` sampai worker kembali berjalan.

### 5. Melakukan deployment kode baru tanpa `queue:restart`.

**Prediksi:**

Worker yang masih berjalan dapat menggunakan kode lama.

**Hasil:**

Worker Laravel merupakan proses jangka panjang dan menyimpan kode aplikasi yang sudah dimuat ketika proses dimulai. Jika kode baru di-deploy tanpa me-restart worker, proses tersebut dapat terus menggunakan implementasi lama.

Perintah untuk meminta worker memuat ulang kode:

```bash
php artisan queue:restart
```

Setelah itu, worker akan menyelesaikan job yang sedang berjalan dan berhenti secara normal. Process manager kemudian menjalankan worker kembali.

### 6. Mengubah izin folder `storage` menjadi 555.

**Prediksi:**

Laravel tidak dapat menulis file ke folder yang tidak memiliki izin menulis.

**Hasil:**

Folder `storage` digunakan untuk menyimpan log, cache, session, dan file aplikasi. Jika izinnya diubah menjadi 555, proses web server tidak dapat menulis data baru.

Akibatnya, operasi yang membutuhkan penulisan file dapat menghasilkan error 500. Jika log juga tidak dapat ditulis, informasi kesalahan sulit ditemukan dari file log.

Izin folder harus diberikan kepada pengguna proses aplikasi, misalnya `www-data`.

### 7. Mengatur `upload_max_filesize=1M`, kemudian mengunggah file 3 MB.

**Prediksi:**

PHP menolak upload karena ukuran file melebihi batas konfigurasi server.

**Hasil:**

Walaupun validasi Laravel mengizinkan ukuran sampai 5 MB melalui `max:5120`, PHP tetap membatasi ukuran upload menjadi 1 MB.

Karena file berukuran 3 MB, file ditolak sebelum berhasil diproses oleh validasi Laravel.

Konfigurasi PHP harus disesuaikan dengan batas upload aplikasi, termasuk `upload_max_filesize` dan `post_max_size`.

### 8. Mengakses situs melalui HTTP saat `SESSION_SECURE_COOKIE=true`.

**Prediksi:**

Cookie session tidak dikirim melalui koneksi HTTP yang tidak terenkripsi.

**Hasil:**

Cookie dengan atribut `Secure` hanya dikirim browser melalui koneksi HTTPS. Jika aplikasi diakses melalui HTTP, browser tidak mengirim cookie session tersebut.

Akibatnya, session login tidak dapat dipertahankan melalui HTTP. Pengguna dapat diarahkan kembali ke halaman login.

Karena itu, `SESSION_SECURE_COOKIE=true` harus digunakan bersama HTTPS pada production.