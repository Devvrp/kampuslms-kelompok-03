
# Dokumentasi Performa KampusLMS — Week 11

## 1. Tujuan

Optimisasi dilakukan pada halaman detail mata kuliah dan halaman daftar serta detail tugas untuk mengurangi query berulang, mencegah N+1, dan meningkatkan efisiensi akses database.

## 2. Metode Pengukuran

Pengukuran dilakukan secara lokal menggunakan Laravel DB::listen() sementara melalui AppServiceProvider.

Parameter yang dicatat:
- Jumlah query database.
- Total waktu eksekusi query dalam milidetik.
- Query execution plan menggunakan EXPLAIN QUERY PLAN.

Database pengujian menggunakan SQLite in-memory.

## 3. Hasil Pengukuran Halaman

### Pengukuran awal

| Halaman | Jumlah Query | Total Waktu Query |
|---|---:|---:|
| Detail mata kuliah admin | 5 | 4,58 ms |
| Daftar tugas dosen | 7 | 4,71 ms |
| Daftar tugas mahasiswa | 10 | 3,74 ms |
| Detail tugas dosen | 5 | 4,28 ms |

### Setelah penambahan index

| Halaman | Jumlah Query | Total Waktu Query |
|---|---:|---:|
| Daftar tugas mahasiswa | 10 | 5,13 ms |

Catatan: Pengukuran dilakukan berdasarkan satu kali pengambilan sampel setiap kondisi. Hasil ini belum cukup untuk menyimpulkan peningkatan waktu respons. Penambahan index ditujukan untuk memperbaiki akses pencarian database, bukan mengurangi jumlah query SQL.

## 4. Optimisasi Eloquent

### AssignmentController

Optimisasi dilakukan dengan eager loading menggunakan `with()` untuk memuat relasi yang diperlukan sebelum data digunakan.

Pagination tetap menggunakan 15 data per halaman.

Filter mahasiswa tetap membatasi tugas yang ditampilkan hanya pada tugas dengan status `published`.

### AssignmentPolicy

Relasi yang diperlukan diperiksa menggunakan `loadMissing()` agar relasi tidak dimuat berulang ketika sudah tersedia.

Pemeriksaan hak akses admin, dosen pemilik mata kuliah, dan mahasiswa terdaftar tetap dipertahankan.

### AppServiceProvider

Pencegahan lazy loading diaktifkan pada lingkungan non-produksi menggunakan:

`Model::preventLazyLoading()`

Pengujian seluruh test suite menunjukkan tidak ditemukan pelanggaran lazy loading pada jalur pengujian yang tercakup.

## 5. Optimisasi Index Database

Migration tambahan:

`2026_11_02_124441_add_assignments_course_status_due_index.php`

Index yang ditambahkan:

`assignments_course_status_due_index`

Kolom:
- `course_id`
- `status`
- `due_at`

Index ini dirancang untuk mendukung query:

```sql
SELECT *
FROM assignments
WHERE course_id = ?
AND status = ?
ORDER BY due_at ASC
LIMIT 15 OFFSET 0;
```

Index sebelumnya:

`assignments_course_id_due_at_index`

Tetap dipertahankan untuk mendukung query daftar tugas berdasarkan mata kuliah dan urutan deadline, termasuk daftar tugas dosen.

### Hasil EXPLAIN QUERY PLAN

Sebelum penambahan index gabungan:

`SEARCH assignments USING INDEX assignments_course_id_due_at_index (course_id=?)`

Setelah penambahan index:

`SEARCH assignments USING INDEX assignments_course_status_due_index (course_id=? AND status=?)`

Hasil tersebut menunjukkan SQLite memilih index gabungan untuk query daftar tugas mahasiswa.

## 6. Cache dan Data yang Tidak Di-cache

Halaman detail mata kuliah dan daftar tugas tidak diberi cache dalam lingkup optimisasi ini.

Alasannya:
- Data dan status tugas dapat berubah sewaktu-waktu.
- Informasi yang ditampilkan bergantung pada hak akses pengguna.
- Daftar tugas mahasiswa harus selalu mengikuti status publikasi dan enrollment.
- Cache tambahan memerlukan mekanisme invalidasi agar data tidak menjadi kedaluwarsa.

Dengan demikian, halaman tersebut tetap mengambil data terbaru melalui query database dan menggunakan eager loading serta pagination.

## 7. Hasil Pengujian

Pengujian dilakukan menggunakan:

`php artisan test`

Hasil:

- 337 tests passed.
- 4.179 assertions.
- 0 failed.
- Durasi: 13,99 detik.

Pengujian khusus assignment:
- AssignmentWebTest: 25 passed.
- AssignmentApiTest: 37 passed.

## 8. Kesimpulan

Optimisasi Week 11 pada bagian Course dan Assignment mencakup eager loading, pencegahan lazy loading di lingkungan non-produksi, pagination, serta penambahan index gabungan untuk daftar tugas mahasiswa.

Hasil EXPLAIN membuktikan index baru digunakan oleh SQLite untuk query daftar tugas mahasiswa.

Jumlah query seluruh halaman yang diukur berada di bawah 15 query. Peningkatan waktu respons belum dapat diklaim karena pengukuran masih terbatas pada satu sampel per kondisi.
