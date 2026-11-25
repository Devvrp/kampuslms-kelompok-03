### Nama : Fariz Daffa Abbiyu Rahmatullah

### NIM  : 10241029

----

### READ Minggu 9

### 1. Temukan berkas fisiknya di sistem berkas. Path lengkapnya apa?

Lokasi kode: `app/Http/Controllers/MaterialController.php`, method `store()`.

```php
$storedPath = $file->store('materials', 'local');
```

File materi disimpan ke folder `materials` menggunakan disk `local`.

Pada `config/filesystems.php`, disk `local` menggunakan lokasi:

```php
'root' => storage_path('app/private'),
```

Jadi, lokasi fisik file adalah:

```text
storage/app/private/materials/<nama-file-yang-dibuat-laravel>
```

Di komputer lokal Windows:

```text
D:\faris\Semester 5\kampuslms-kelompok-03\storage\app\private\materials\<nama-file>
```

Laravel membuat nama file secara otomatis ketika menggunakan `store()`, sehingga nama file fisiknya bisa berbeda dari nama asli file.

### 2. Buka baris database yang bersangkutan. Bandingkan `file_path` dengan lokasi fisiknya.

Lokasi kode: `app/Http/Controllers/MaterialController.php`, method `store()`.

```php
$data['file_path'] = $storedPath;
$data['original_name'] = $file->getClientOriginalName();
```

Kolom `file_path` menyimpan lokasi relatif file, misalnya:

```text
materials/AbCd1234.pdf
```

Sedangkan lokasi fisiknya:

```text
storage/app/private/materials/AbCd1234.pdf
```

Jadi, `file_path` bukan alamat lengkap file di komputer, melainkan lokasi relatif terhadap folder penyimpanan disk `local`.

Nama asli file disimpan secara terpisah pada kolom `original_name`.

### 3. Coba akses berkas itu langsung lewat browser dengan menebak URL-nya. Berhasil atau tidak? Kenapa?

Secara konfigurasi, file materi tidak bisa diakses langsung melalui URL publik seperti `/storage/materials/nama-file.pdf`.

Penyebabnya, file disimpan di disk `local` yang lokasinya berada di `storage/app/private`, bukan di folder publik website.

Untuk mengakses file, pengguna harus melalui method `download()` pada `MaterialController.php`.

```php
Gate::authorize('view', $material);

$disk = Storage::disk('local');

return $disk->download(
    $material->file_path,
    $material->original_name ?: basename($material->file_path)
);
```

Sebelum file diberikan, Laravel memeriksa izin pengguna menggunakan `Gate::authorize()`. Jadi, file hanya dapat diunduh oleh pengguna yang memiliki hak akses.

### 4. Buka `config/filesystems.php`. Identifikasi disk `local` dan `public` — apa persis bedanya?

Lokasi: `config/filesystems.php`.

Disk `local`:

```php
'local' => [
    'driver' => 'local',
    'root' => storage_path('app/private'),
    'serve' => false,
    'throw' => false,
    'report' => false,
],
```

Disk `public`:

```php
'public' => [
    'driver' => 'local',
    'root' => storage_path('app/public'),
    'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
    'visibility' => 'public',
    'throw' => false,
    'report' => false,
],
```

Perbedaannya:

- `local` digunakan untuk menyimpan file privat yang tidak boleh diakses langsung oleh publik.
- `public` digunakan untuk menyimpan file yang memang boleh diakses melalui URL publik.

Materi dan submission mahasiswa menggunakan disk `local` agar aksesnya dapat diperiksa melalui controller dan policy terlebih dahulu.

### 5. Jalankan `php artisan storage:link`. Apa yang dibuatnya? Kenapa materi Anda tidak boleh ada di sana?

Konfigurasi symbolic link terdapat di `config/filesystems.php`:

```php
'links' => [
    public_path('storage') => storage_path('app/public'),
],
```

Perintah:

```bash
php artisan storage:link
```

Membuat symbolic link dari:

```text
public/storage
```

menuju:

```text
storage/app/public
```

Artinya, file di dalam `storage/app/public` dapat diakses melalui URL publik website.

Materi dan submission tidak boleh disimpan di lokasi tersebut karena orang yang mengetahui URL file berpotensi mengaksesnya tanpa melalui pemeriksaan login dan policy.

Karena itu, KampusLMS menggunakan disk `local` untuk menyimpan file yang bersifat privat.

## BREAK Minggu 9

### 1. Pindahkan berkas submission ke `storage/app/public`, akses URL-nya tanpa login

Prediksi: file submission dapat diakses langsung oleh siapa saja melalui URL publik.

Hasil: jika file dipindahkan ke `storage/app/public` dan symbolic link `public/storage` sudah dibuat, file dapat diakses melalui URL `/storage/...` tanpa login.

Hal ini menyebabkan file tugas mahasiswa berpotensi terbuka untuk publik. Orang yang mengetahui URL file dapat mengunduhnya tanpa melewati pemeriksaan autentikasi dan policy.

Lokasi konfigurasi: `config/filesystems.php`, bagian disk `public` dan `links`.

### 2. Hapus `Gate::authorize()` dari endpoint download, akses submission orang lain

Kode yang dihapus:

```php
Gate::authorize('download', $submission);
```

Lokasi: `app/Http/Controllers/SubmissionController.php`, method `download()`.

Prediksi: pengguna yang sudah login dapat mencoba mengunduh submission milik mahasiswa lain dengan mengganti ID submission pada URL.

Hasil: jika tidak ada pemeriksaan otorisasi lain, pengguna yang sudah login dapat mengunduh file submission lain selama ID file valid. Ini merupakan IDOR (*Insecure Direct Object Reference*), karena aplikasi tidak lagi memastikan apakah pengguna berhak mengakses submission tersebut.

Pemeriksaan `Gate::authorize()` diperlukan untuk memastikan hanya pengguna yang memiliki izin yang dapat mengunduh file.

### 3. Simpan berkas dengan `storeAs()` memakai nama asli, lalu dua akun mahasiswa berbeda mengunggah `tugas.pdf` dengan isi berbeda ke tugas yang sama

Contoh kode yang dicoba:

```php
$file->storeAs(
    'submissions',
    $file->getClientOriginalName(),
    'local'
);
```

Prediksi: kedua mahasiswa akan menyimpan file dengan nama dan lokasi yang sama.

Hasil: file mahasiswa kedua dapat menimpa file mahasiswa pertama karena keduanya menggunakan path yang sama, yaitu `submissions/tugas.pdf`.

Pada kode asli, aplikasi menggunakan:

```php
$newFilePath = $file->store('submissions', $disk);
```

`store()` menghasilkan nama file unik secara otomatis. Tujuannya agar file mahasiswa berbeda tidak saling menimpa.

### 4. Unggah berkas PHP yang diganti namanya menjadi `.pdf` dengan validasi `extensions`, lalu dengan `mimes`

Lokasi validasi: `app/Http/Controllers/SubmissionController.php`, method `store()`.

```php
'file' => [
    'required',
    'file',
    'mimes:pdf,doc,docx',
    'max:5120',
],
```

Prediksi: validasi `extensions` dan `mimes` memberikan hasil berbeda karena keduanya memeriksa hal yang berbeda.

Hasil:

- `extensions` memeriksa ekstensi yang tertulis pada nama file.
- `mimes` memeriksa jenis MIME berdasarkan isi file.

Berkas PHP yang hanya diganti namanya menjadi `.pdf` dapat lolos pemeriksaan ekstensi, tetapi akan ditolak oleh validasi `mimes` apabila isi file tidak sesuai dengan format PDF.

Saat ini, controller submission menggunakan `mimes:pdf,doc,docx`, bukan `extensions`.

### 5. Ubah `max:5120` menjadi `max:5`, unggah berkas 1 MB

Lokasi: `app/Http/Controllers/SubmissionController.php`, method `store()`.

Kode saat ini:

```php
'max:5120',
```

Prediksi: file berukuran 1 MB akan ditolak ketika batas diubah menjadi `max:5`.

Hasil: file 1 MB melebihi batas 5 KB sehingga validasi gagal.

Pada validasi Laravel untuk file, satuan `max` adalah kilobyte.

- `max:5` berarti maksimal 5 KB.
- `max:5120` berarti maksimal 5.120 KB atau sekitar 5 MB.

### 6. Set `upload_max_filesize=1M` di php.ini, unggah berkas 3 MB dengan validasi `max:5120`

Prediksi: file 3 MB akan terhalang oleh batas upload PHP meskipun validasi Laravel mengizinkan file sampai sekitar 5 MB.

Hasil: batas PHP sebesar 1 MB lebih kecil daripada ukuran file 3 MB. Akibatnya, proses upload dapat ditolak sebelum validasi Laravel berjalan.

Ini menunjukkan bahwa batas upload PHP dan batas validasi Laravel harus disesuaikan. Validasi Laravel tidak dapat mengatasi file yang sudah ditolak oleh PHP.

Pada Dockerfile KampusLMS, batas upload PHP diatur sebagai berikut:

```dockerfile
echo 'upload_max_filesize=10M';
echo 'post_max_size=12M';
```

Dengan demikian, batas upload PHP lebih besar daripada batas validasi submission sebesar 5 MB.

### 7. Hapus `updateOrCreate`, ganti `create`, kumpulkan tugas dua kali

Lokasi: `app/Services/AssignmentSubmissionService.php`.

Kode asli:

```php
$submission = $assignment->submissions()->updateOrCreate(
    [
        'user_id' => $student->id,
    ],
    [
        'file_path' => $newFilePath,
        'original_name' => $file->getClientOriginalName(),
        'file_size' => $file->getSize(),
        'note' => $note,
        'submitted_at' => $submittedAt,
        'is_late' => $isLate,
    ]
);
```

Prediksi: jika `updateOrCreate()` diganti dengan `create()`, pengumpulan kedua akan mencoba membuat record submission baru, bukan memperbarui record sebelumnya.

Hasil: database menolak record kedua karena terdapat unique constraint pada kombinasi `assignment_id` dan `user_id`.

Lokasi migration: `database/migrations/2026_09_13_065958_create_submissions_table.php`.

```php
$table->unique(['assignment_id', 'user_id']);
```

Unique constraint memastikan satu mahasiswa hanya memiliki satu record submission untuk satu tugas.

Pada kode asli, `updateOrCreate()` memungkinkan mahasiswa mengumpulkan ulang tugas dengan memperbarui record sebelumnya.

Jika penyimpanan record gagal, service juga menghapus file baru yang sudah terlanjur diunggah:

```php
catch (Throwable $exception) {
    Storage::disk($disk)->delete($newFilePath);

    throw $exception;
}
```

Tujuannya agar tidak terjadi duplikasi record dan file yatim akibat proses penyimpanan yang gagal.

### 8. Hapus satu submission dari database melalui SQL langsung, cek folder storage

Lokasi: `app/Models/Submission.php`.

```php
use App\Models\Concerns\DeletesStoredFiles;

class Submission extends Model
{
    use HasFactory;
    use DeletesStoredFiles;
}
```

Lokasi mekanisme penghapusan: `app/Models/Concerns/DeletesStoredFiles.php`.

```php
static::deleting(function (Model $model): void {
    $paths = $model->storedFilePathsForDeletion();
});

static::deleted(function (Model $model): void {
    DB::afterCommit(function () use ($paths): void {
        Storage::disk('local')->delete($paths);
    });
});
```

Prediksi: jika record submission dihapus langsung melalui SQL, file fisiknya akan tertinggal di folder storage.

Hasil: penghapusan langsung melalui SQL tidak menjalankan event Eloquent `deleting` dan `deleted`. Akibatnya, mekanisme penghapusan file pada trait `DeletesStoredFiles` tidak dijalankan.

File tetap ada di `storage/app/private/submissions`, tetapi record database yang menunjuk ke file tersebut sudah tidak ada.

Kondisi ini disebut file yatim (*orphaned file*).

Pada penghapusan normal melalui Eloquent, trait tersebut mengambil lokasi file dan menghapusnya setelah transaksi database berhasil di-commit. Tujuannya agar file dan record database tetap konsisten.