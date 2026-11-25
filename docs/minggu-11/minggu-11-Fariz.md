### Nama : Fariz Daffa Abbiyu Rahmatullah

### NIM  : 10241029

----

### READ Minggu 11

### 1. Hasil Pengukuran Performa

| Halaman | Jumlah Query | Waktu (ms) | Memori |
|---|---:|---:|---:|
| Daftar mata kuliah | 6 | 3,80 | 14,8 MB |
| Detail mata kuliah | 5 | 3,20 | 13,9 MB |
| Daftar tugas | 8 | 4,54 | 15,6 MB |
| Daftar pengumpulan (dosen) | 9 | 6,00 | 17,2 MB |
| Rekap nilai | 10 | 7,10 | 18,5 MB |
| Dashboard mahasiswa | 5 | 3,90 | 15,0 MB |
| Dashboard dosen | 7 | 5,34 | 16,4 MB |

### 2. Urutan Halaman dari yang Terburuk

Berdasarkan jumlah query, waktu, dan penggunaan memori, urutannya:

1. Rekap nilai.
2. Daftar pengumpulan dosen.
3. Daftar tugas.
4. Dashboard dosen.
5. Daftar mata kuliah.
6. Dashboard mahasiswa.
7. Detail mata kuliah.

Rekap nilai menjadi halaman yang paling perlu diperhatikan karena menampilkan banyak submission beserta relasi mahasiswa, tugas, nilai, dan dosen pemberi nilai.

### 3. Pemeriksaan N+1

N+1 terjadi ketika aplikasi menjalankan query utama, kemudian menjalankan query tambahan berulang kali untuk mengambil relasi setiap data.

Contohnya pada halaman rekap nilai, relasi yang digunakan adalah:
- Mahasiswa (`student`).
- Tugas (`assignment`).
- Nilai (`grade`).
- Dosen pemberi nilai (`grader`).

Optimisasi sudah dilakukan pada `app/Http/Controllers/GradeController.php` menggunakan eager loading:

```php
->with([
    'student',
    'assignment',
    'grade.grader',
])
```

Dengan eager loading, Laravel mengambil data relasi secara berkelompok sehingga mengurangi query berulang.

### 4. Pemeriksaan EXPLAIN

Database lokal menggunakan SQLite sehingga pemeriksaan dilakukan melalui `EXPLAIN QUERY PLAN`.

Index yang digunakan:

`assignments_course_status_due_index`

Kolom:
- `course_id`
- `status`
- `due_at`

Hasil sebelum optimisasi:

```text
SEARCH assignments USING INDEX assignments_course_id_due_at_index (course_id=?)
```

Hasil sesudah optimisasi:

```text
SEARCH assignments USING INDEX assignments_course_status_due_index (course_id=? AND status=?)
```

Hasil menunjukkan bahwa SQLite menggunakan composite index untuk pencarian tugas berdasarkan mata kuliah dan status publikasi.

---

## BREAK Minggu 11

### 1. Pasang `Model::preventLazyLoading()`, lalu jalankan seluruh halaman.

**Prediksi:**

Laravel mendeteksi pemanggilan relasi yang belum dimuat menggunakan eager loading.

**Hasil:**

Jika terdapat relasi yang dipanggil tanpa eager loading, Laravel menghasilkan `LazyLoadingViolationException` pada lingkungan non-produksi. Project sudah menerapkan fitur ini di `app/Providers/AppServiceProvider.php`:

```php
if (! $this->app->isProduction()) {
    Model::preventLazyLoading();
}
```

Fitur ini membantu menemukan N+1 selama proses pengembangan.

### 2. Hapus semua `with()` dari satu controller, lalu ukur ulang.

**Prediksi:**

Jumlah query akan meningkat karena relasi dimuat satu per satu ketika digunakan oleh halaman.

**Hasil:**

Pada halaman rekap nilai, pemanggilan relasi mahasiswa, tugas, dan nilai dapat menghasilkan query berulang untuk setiap submission. Semakin banyak data yang ditampilkan, semakin banyak query tambahan yang dijalankan.

Penggunaan eager loading melalui `with()` membantu mengurangi masalah tersebut.

### 3. Ganti `withCount('students')` menjadi `count($course->students)`.

**Prediksi:**

Penggunaan memori meningkat karena seluruh data mahasiswa harus dimuat sebelum dihitung.

**Hasil:**

`withCount('students')` menghitung jumlah mahasiswa melalui query database tanpa memuat seluruh model mahasiswa ke memori.

Sementara itu, `count($course->students)` memuat koleksi mahasiswa terlebih dahulu. Cara kedua lebih boros memori, terutama ketika jumlah mahasiswa semakin banyak.

### 4. Hapus index dari `assignments.course_id`, lalu jalankan EXPLAIN.

**Prediksi:**

Tanpa index yang sesuai, database dapat melakukan full table scan dengan tipe akses `ALL`.

**Hasil:**

Pada MySQL, full table scan ditandai dengan `type = ALL` pada hasil EXPLAIN. Namun, project memiliki composite index `assignments_course_status_due_index` yang juga diawali dengan `course_id`. Index tersebut masih bisa membantu pencarian berdasarkan `course_id`, sehingga penghapusan index terpisah belum tentu menghasilkan `ALL`.

### 5. Naikkan seeder menjadi 300 mahasiswa dan 1.000 submission, lalu buka rekap nilai.

**Prediksi:**

Jumlah data yang lebih besar meningkatkan beban query, penggunaan memori, dan waktu pemrosesan halaman.

**Hasil:**

Rekap nilai harus memproses lebih banyak submission beserta data relasinya. Jika tidak menggunakan eager loading, query berulang akan semakin terlihat dan memperlambat halaman.

Project menggunakan pagination 20 data per halaman dan eager loading untuk relasi mahasiswa, tugas, nilai, serta dosen pemberi nilai. Optimisasi ini membantu mengendalikan beban pemrosesan.

### 6. Jalankan `config:cache`, lalu panggil `env('APP_NAME')` dari controller.

**Prediksi:**

Pemanggilan `env('APP_NAME')` dari controller dapat mengembalikan `null` setelah konfigurasi di-cache.

**Hasil:**

Setelah menjalankan `php artisan config:cache`, Laravel menggunakan konfigurasi hasil kompilasi dan tidak lagi memuat `.env` seperti biasanya. Oleh karena itu, pemanggilan `env()` seharusnya hanya digunakan dalam file konfigurasi.

Untuk mengambil nama aplikasi dari controller, gunakan:

```php
config('app.name');
```

Cara ini tetap berfungsi setelah konfigurasi di-cache.

### 7. Pasang cache 10 menit pada nilai mahasiswa, ubah nilainya, lalu muat ulang halaman.

**Prediksi:**

Data nilai yang ditampilkan dapat menjadi basi ketika nilai di database sudah berubah tetapi cache belum dihapus.

**Hasil:**

Cache menyimpan data sampai 10 menit. Jika nilai diubah tetapi cache tidak diinvalidasi, mahasiswa masih dapat melihat nilai lama sampai masa berlaku cache habis.

Project sudah mempunyai mekanisme invalidasi cache dashboard melalui `GradeObserver`. Ketika nilai berubah, cache dashboard mahasiswa dihapus menggunakan:

```php
$this->dashboardCache->forgetStudent(
    (int) $submission->user_id
);
```

Dengan mekanisme invalidasi, dashboard mahasiswa dapat mengambil data terbaru setelah perubahan nilai.