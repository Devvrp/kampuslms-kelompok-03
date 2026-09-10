# Week 2

**Mata Kuliah: Pemrograman Web**
**Pertemuan: 2**
**Tanggal: 10 September 2026**
**Topik: Route Laravel**

---

## READ

### 1. Baris mana di `routes/web.php` yang menangkapnya?

Route `/tentang` ditangani oleh bagian berikut pada file `routes/web.php`:

```php
Route::get('/tentang', function () {
    return view('tentang');
});
```

Route ini menggunakan method `GET`. Ketika pengguna membuka `/tentang`, Laravel akan menjalankan fungsi tersebut dan kemudian menampilkan view bernama `tentang`.

### 2. Kalau ditangani controller, berkas dan method mana?

Pada kondisi saat ini, route `/tentang` belum menggunakan controller. Prosesnya masih ditulis langsung menggunakan closure di dalam file `routes/web.php`.

Artinya, belum ada file controller maupun method khusus yang digunakan untuk menangani route tersebut.

### 3. View mana yang dikembalikan? Di path apa persisnya?

View yang dipanggil adalah:

```php
return view('tentang');
```

Karena nama view-nya adalah `tentang`, maka Laravel akan mencari file pada:

`resources/views/tentang.blade.php`

### 4. Layout apa yang membungkusnya?

View `tentang.blade.php` tidak menggunakan layout Blade lain sebagai pembungkus. File tersebut masih berdiri sendiri sebagai halaman HTML lengkap dan tidak menggunakan directive seperti `@extends` maupun `@include`.

### 5. Jalankan `php artisan route:list --path=tentang`. Cocok dengan analisis Anda?

Ya, hasilnya sesuai dengan analisis sebelumnya.

Output menunjukkan bahwa route `tentang` menggunakan method `GET|HEAD` dan berasal dari file `routes/web.php`.

Dengan begitu, dapat dipastikan bahwa `/tentang` memang menggunakan method `GET` dan didefinisikan di file routing Laravel.

---

## BREAK

### 1. Ubah `Route::get` menjadi `Route::post` pada route daftar mata kuliah

**Prediksi:** halaman daftar mata kuliah akan mengalami error ketika dibuka melalui browser.

**Hasil:** setelah route diubah menjadi `POST`, route daftar mata kuliah hanya menerima request dengan method tersebut.

```text
POST courses ... courses.index -> CourseController@index
```

Sementara itu, ketika pengguna membuka link melalui browser, request yang dikirim secara default adalah `GET`. Karena method request tidak sesuai dengan route yang tersedia, Laravel menampilkan error `405 Method Not Allowed`.

### 2. Ubah nama view di `return view(...)` menjadi view yang tidak tersedia

**Prediksi:** akan muncul error karena Laravel tidak menemukan view yang dimaksud.

**Hasil:** Laravel menampilkan error `500 | Server Error` karena file view yang dipanggil tidak tersedia di folder `resources/views`.

Jadi, nama yang ditulis pada `return view(...)` harus sesuai dengan file view yang benar-benar ada.

### 3. Hapus `->name('courses.show')`, lalu muat halaman yang memakai `route('courses.show')`

**Prediksi:** akan terjadi error karena route yang dipanggil tidak lagi memiliki nama yang sesuai.

**Hasil:** halaman mengalami error karena `route('courses.show')` mencari route berdasarkan nama. Setelah `->name('courses.show')` dihapus, Laravel tidak lagi menemukan named route tersebut.

Walaupun URL route masih ada, route tersebut tidak bisa dipanggil menggunakan nama `courses.show`.

### 4. Pindahkan route `/courses/{course}` ke atas `/courses/create`, lalu buka `/courses/create`

**Prediksi:** awalnya saya mengira halaman create masih dapat dibuka karena Laravel dapat membedakan route statis dan route berparameter.

**Hasil:** ternyata route `/courses/{course}` lebih dulu menangkap request tersebut. Kata `create` dianggap sebagai nilai dari parameter `course`.

Akibatnya, request diarahkan ke method `show`, bukan ke halaman create. Karena tidak ditemukan data course dengan nilai `create`, Laravel menampilkan error `404 Not Found`.

Dari percobaan ini terlihat bahwa urutan penulisan route memiliki pengaruh terhadap route mana yang akan dijalankan.

### 5. Ganti `{{ $nama }}` menjadi `{!! $nama !!}`, lalu isi `$nama` dengan `<script>alert('XSS')</script>`

**Prediksi:** saya mengira isi `$nama` masih akan dianggap sebagai teks sehingga script tidak dijalankan.

**Hasil:** ternyata `{!! !!}` menampilkan data tanpa proses escaping. Akibatnya, tag `<script>` ikut diproses oleh browser dan alert XSS berhasil muncul.

Percobaan ini menunjukkan bahwa sintaks `{!! !!}` sebaiknya digunakan dengan hati-hati, terutama jika data yang ditampilkan berasal dari input pengguna.

### 6. Hapus `@vite(...)` dari layout

**Prediksi:** saya memperkirakan halaman mungkin tidak dapat ditampilkan dengan normal atau bahkan gagal dibuka.

**Hasil:** halaman tetap berhasil dirender, tetapi file CSS dan JavaScript yang dikelola oleh Vite tidak ikut dimuat.

Akibatnya, tampilan kehilangan styling dan fitur JavaScript tertentu tidak lagi berjalan. Jadi penghapusan `@vite(...)` tidak merusak route maupun proses Blade, tetapi memengaruhi aset frontend.

### 7. Hentikan `npm run dev`, lalu muat ulang halaman

**Prediksi:** halaman akan mengalami error karena Laravel tidak dapat mengambil aset dari Vite.

**Hasil:** halaman Laravel masih dapat dikirim ke browser, tetapi aset dari development server Vite tidak lagi bisa diakses karena server sudah dihentikan.

Akibatnya, tampilan dapat kehilangan CSS atau JavaScript dan browser bisa menampilkan error koneksi terhadap server Vite.

Hal ini berbeda dengan penggunaan `npm run build`, karena aset hasil build sudah disimpan di folder `public/build` dan tidak lagi bergantung pada development server Vite.

### 8. Panggil `route('courses.show')` tanpa mengirim parameter

**Prediksi:** saya mengira Laravel tetap bisa membuat URL, tetapi bagian ID mungkin dibiarkan kosong.

**Hasil:** Laravel langsung menampilkan error `Missing required parameter`.

Hal ini terjadi karena route `courses.show` membutuhkan parameter `course`. Laravel tidak dapat membuat URL sebelum parameter tersebut diberikan.

Contoh pemanggilan yang benar:

```php
route('courses.show', $course['id'])
```

Jadi, jika sebuah route memiliki parameter wajib, parameter tersebut juga harus diberikan ketika route dipanggil.
