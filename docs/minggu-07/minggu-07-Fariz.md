### Nama : Fariz Daffa Abbiyu Rahmatullah

### NIM  : 10241029

----

### READ Minggu 7

### 1. Berkas mana yang menangani POST login? Method apa?

POST login ditangani oleh file routes/web.php, kemudian diarahkan ke method store() pada controller AuthenticatedSessionController.

```
Route::post('/login', [
    AuthenticatedSessionController::class,
    'store',
])->name('login.store');
```

Lokasi controller: app/Http/Controllers/Auth/AuthenticatedSessionController.php

Ketika pengguna mengisi form login dan mengirimkannya, Laravel menerima request POST /login, kemudian menjalankan method store() untuk memproses autentikasi pengguna.

### 2. Di baris mana Auth::attempt() atau setaranya dipanggil?

Lokasi: app/Http/Controllers/Auth/AuthenticatedSessionController.php, di dalam method store().

```
if (! Auth::guard('web')->attempt($credentials, $remember)) {
    throw ValidationException::withMessages([
        'email' => 'Email atau kata sandi tidak sesuai.',
    ]);
}
```

Project menggunakan Auth::guard('web')->attempt(), bukan Auth::attempt() secara langsung.

Kode ini memeriksa kecocokan email dan password pengguna dengan data di database.
- Jika berhasil, pengguna dinyatakan login.
- Jika gagal, Laravel menampilkan pesan bahwa email atau kata sandi tidak sesuai.
Variabel $remember digunakan untuk menentukan apakah pengguna ingin tetap diingat oleh aplikasi.
### 3. Temukan session()->regenerate(). Kenapa ia ada di situ?

Lokasi: app/Http/Controllers/Auth/AuthenticatedSessionController.php, baris 41.

```
$request->session()->regenerate();
```

Kode ini memperbarui ID session setelah pengguna berhasil login. Tujuannya untuk mencegah session fixation, yaitu kondisi ketika pihak lain mencoba memanfaatkan ID session yang sudah diketahui sebelum pengguna login. Dengan regenerasi session, ID session lama diganti dengan ID baru setelah autentikasi berhasil.

### 4. Di mana kata sandi di-hash?

Lokasi: app/Models/User.php, pada method casts().

```
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
```

Cast hashed membuat Laravel otomatis melakukan hashing ketika password disimpan melalui model User.

Tujuannya agar password tidak disimpan sebagai teks biasa (plain text) di database. Jadi, apabila database bocor, password pengguna tidak langsung terbaca dalam bentuk aslinya.

-Proses logout

Lokasi: app/Http/Controllers/Auth/AuthenticatedSessionController.php, method destroy().

```
Auth::guard('web')->logout();

$request->session()->invalidate();
$request->session()->regenerateToken();

return redirect()
    ->route('login')
    ->with('status', 'Anda berhasil logout.');
```

Ketika pengguna logout:
1. logout() mengakhiri autentikasi pengguna.
2. invalidate() menghapus data session yang sedang digunakan.
3. regenerateToken() membuat token CSRF baru.
4. Pengguna diarahkan kembali ke halaman login.

Tujuannya agar session lama tidak bisa digunakan kembali setelah logout.

### 5. Buka DevTools → Cookies sebelum dan sesudah login. Bandingkan nilai cookie session.

Lokasi: app/Http/Controllers/Auth/AuthenticatedSessionController.php, method store().

```
$request->session()->regenerate();
```

Setelah login berhasil, Laravel mengganti ID session lama dengan ID session baru. Karena itu, nilai cookie session (biasanya bernama laravel_session) seharusnya berubah. Tujuannya untuk mencegah session fixation, yaitu serangan yang memanfaatkan ID session lama yang sudah diketahui pihak lain.

### 6. Logout, lalu tekan tombol Back. Apa yang terjadi? Kenapa?

Lokasi: app/Http/Controllers/Auth/AuthenticatedSessionController.php, method destroy().

```
Auth::guard('web')->logout();

$request->session()->invalidate();
$request->session()->regenerateToken();
```

Setelah logout, pengguna sudah tidak memiliki status autentikasi dan session sebelumnya sudah dibatalkan. Ketika menekan tombol Back, browser mungkin masih menampilkan halaman sebelumnya dari cache. Namun, jika halaman dimuat ulang atau pengguna mencoba mengakses halaman yang membutuhkan login, middleware auth akan memeriksa autentikasi dan mengarahkan pengguna kembali ke halaman login. Tujuannya agar pengguna yang sudah logout tidak dapat mengakses kembali halaman yang membutuhkan autentikasi.

### BREAK Minggu 7

### 1. Hapus session()->regenerate() dari proses login

Prediksi: ID session tidak diperbarui setelah pengguna berhasil login sehingga risiko session fixation muncul.

Kode yang dihapus:

```
$request->session()->regenerate();
```

Lokasi: app/Http/Controllers/Auth/AuthenticatedSessionController.php, method store().

Hasil: ID session sebelum login tetap digunakan setelah login karena tidak ada regenerasi session secara eksplisit pada controller.

Hal ini membuka risiko session fixation, yaitu penyerang dapat mencoba memanfaatkan ID session yang sudah diketahui sebelum korban login. Regenerasi session setelah login diperlukan agar ID session lama diganti dengan ID baru.

### 2. Hapus Gate::authorize() dari method update, tetapi biarkan @can di Blade

Prediksi: tombol update tidak terlihat bagi pengguna yang tidak memiliki izin, tetapi request langsung ke URL masih berpotensi dijalankan.

Kode yang dihapus:

```
Gate::authorize('update', $course);
```

Hasil: tombol update tetap tersembunyi karena Blade masih menggunakan @can, tetapi pengguna dapat mencoba mengirim request langsung melalui URL atau curl.

Ini menunjukkan bahwa @can hanya mengatur tampilan tombol, bukan melindungi proses di server. Pemeriksaan izin tetap harus dilakukan di controller atau middleware agar pengguna tanpa hak tidak bisa mengubah data. request masih dapat ditolak oleh middleware atau pemeriksaan otorisasi lain yang belum dihapus.

### 3. Sebagai dosen A, kirim PUT ke mata kuliah milik dosen B melalui curl

Prediksi: request ditolak dengan status 403 Forbidden karena dosen A tidak memiliki hak untuk mengubah mata kuliah dosen B.

Hasil: Laravel menolak perubahan dan data mata kuliah milik dosen B tetap tidak berubah.

Pengujian ini memastikan Policy atau pemeriksaan otorisasi benar-benar berjalan di server. Pengguna yang sudah login belum tentu memiliki hak untuk mengubah semua data.

### 4. Sebagai mahasiswa, buka submission milik mahasiswa lain (IDOR)

Prediksi: request ditolak dengan status 403 Forbidden.

Hasil: mahasiswa tidak dapat melihat submission milik mahasiswa lain meskipun mengetahui ID submission tersebut.

IDOR (Insecure Direct Object Reference) terjadi ketika aplikasi hanya mengandalkan ID data dari URL tanpa memeriksa apakah pengguna berhak mengakses data tersebut. Karena itu, aplikasi harus memeriksa kepemilikan submission sebelum menampilkan datanya.

### 5. Ubah index menjadi Course::paginate() polos, lalu login sebagai mahasiswa

Kode perubahan:

```
Course::paginate();
```

Prediksi: mahasiswa dapat melihat daftar seluruh mata kuliah karena query tidak lagi membatasi data berdasarkan hak akses atau enrollment mahasiswa.

Hasil: daftar mata kuliah yang tampil lebih banyak daripada mata kuliah yang seharusnya dapat diakses mahasiswa.

Ini merupakan kebocoran data pada tingkat daftar karena query tidak menerapkan pembatasan yang sesuai. Solusinya adalah menggunakan query yang memfilter mata kuliah berdasarkan hak akses mahasiswa.

### 6. Kirim role=admin melalui form edit profil

Prediksi: pengguna mencoba mengubah role miliknya menjadi admin dengan menambahkan parameter role=admin pada request.

Kode pada app/Models/User.php:

```
protected $fillable = [
    'name',
    'email',
    'password',
];
```

Hasil: berdasarkan konfigurasi $fillable saat ini, kolom role tidak dapat diubah melalui mass assignment biasa. Laravel akan mengabaikan atribut yang tidak diizinkan atau menampilkan exception jika pemeriksaan mass assignment yang ketat diaktifkan.

$fillable berfungsi membatasi atribut model yang boleh diisi secara mass assignment. Ini mencegah pengguna mengubah data penting seperti role melalui request form biasa.

### 7. Hapus cast hashed, buat user baru, lalu periksa kolom password di database

Kode yang dihapus dari app/Models/User.php:

```
'password' => 'hashed',
```

Prediksi: jika proses pembuatan user mengirim password biasa tanpa melakukan hashing secara manual, password dapat tersimpan sebagai teks biasa di database.

Hasil: tanpa cast hashed dan tanpa proses hashing lain, kolom password menyimpan nilai asli yang dimasukkan pengguna.

Cast hashed membuat Laravel melakukan hashing otomatis ketika password disimpan melalui model User. Tujuannya agar password tidak tersimpan dalam bentuk plain text. hasil ini bergantung pada proses pembuatan user. Jika controller tetap menggunakan Hash::make(), password masih akan di-hash meskipun cast dihapus.

### 8. Login, salin cookie session, lalu tempel di browser lain

Prediksi: browser lain berpotensi menggunakan session pengguna yang sama selama cookie tersebut masih valid.

Hasil: apabila cookie session yang disalin masih valid dan server menerima session tersebut, browser kedua dapat masuk sebagai pengguna yang sama.

Hal ini menunjukkan bahwa cookie session harus dilindungi karena siapa pun yang memiliki cookie session valid berpotensi mengambil alih sesi pengguna.

Dua konfigurasi penting:

SESSION_HTTP_ONLY=true: mencegah JavaScript membaca cookie session secara langsung.

SESSION_SECURE_COOKIE=true: memastikan cookie hanya dikirim melalui koneksi HTTPS.

HttpOnly membantu mengurangi risiko pencurian cookie melalui JavaScript, sedangkan Secure melindungi pengiriman cookie melalui koneksi yang tidak terenkripsi. Namun, keduanya tidak mencegah seseorang menggunakan cookie yang sudah berhasil dicuri dan disalin secara manual.