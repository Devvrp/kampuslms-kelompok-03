# **Week 3**

**Mata Kuliah: Pemrograman Web**
**Pertemuan: 3**
**Tanggal: 15 September 2026**
**Topik: ERD, Relasi Database, dan Mass Assignment**

---

## **READ**

### **1. Gambar ulang ERD dari spesifikasi di papan/kertas, tanpa melihat dokumen.**

Untuk nomor ini saya lewati terlebih dahulu sesuai dengan instruksi yang diberikan.

### **2. Untuk setiap foreign key, tentukan perilaku `onDelete`-nya dan jelaskan alasannya.**

Berdasarkan struktur migrasi pada project, perilaku setiap foreign key adalah sebagai berikut:

- `courses.lecturer_id`
    - Menggunakan `restrictOnDelete()`.
    - Artinya, data dosen tidak dapat dihapus selama masih terhubung dengan mata kuliah yang diampu. Aturan ini menjaga agar setiap mata kuliah tetap memiliki dosen pengampu yang jelas.

- `materials.course_id`
    - Menggunakan `cascadeOnDelete()`.
    - Ketika suatu mata kuliah dihapus, seluruh materi yang berkaitan dengan mata kuliah tersebut juga akan ikut terhapus karena materi tidak dapat berdiri sendiri tanpa mata kuliah.

- `assignments.course_id`
    - Menggunakan `cascadeOnDelete()`.
    - Jika data mata kuliah dihapus, seluruh tugas yang berada pada mata kuliah tersebut juga ikut dihapus karena tugas merupakan bagian dari mata kuliah.

- `assignments.created_by`
    - Tidak menggunakan `cascadeOnDelete()` maupun `restrictOnDelete()` secara eksplisit.
    - Dalam kondisi ini, database umumnya akan menolak penghapusan user apabila user tersebut masih tercatat sebagai pembuat tugas.

- `submissions.assignment_id`
    - Menggunakan `cascadeOnDelete()`.
    - Jika sebuah tugas dihapus, seluruh submission atau jawaban mahasiswa yang berkaitan dengan tugas tersebut juga ikut terhapus.

- `submissions.user_id`
    - Tidak menggunakan aturan `cascadeOnDelete()` atau `restrictOnDelete()` secara langsung.
    - User umumnya tidak dapat dihapus apabila masih memiliki data submission yang terhubung di dalam database.

- `grades.submission_id`
    - Menggunakan `unique()` dan `cascadeOnDelete()`.
    - `unique()` memastikan bahwa satu submission hanya dapat memiliki satu nilai. Sementara itu, `cascadeOnDelete()` membuat data nilai ikut terhapus apabila submission yang berkaitan dihapus.

- `grades.graded_by`
    - Tidak memiliki aturan penghapusan secara khusus.
    - User yang memberikan nilai biasanya tidak dapat langsung dihapus apabila masih memiliki hubungan dengan data nilai yang tersimpan.

### **3. Kalau seorang dosen dihapus, apa yang terjadi pada mata kuliahnya? Kenapa dirancang begitu?**

Pada `courses.lecturer_id` digunakan `restrictOnDelete()`.

Artinya, data dosen tidak dapat dihapus apabila dosen tersebut masih memiliki mata kuliah yang diampu. Database akan menolak proses penghapusan sampai mata kuliah tersebut dialihkan ke dosen lain atau dihapus terlebih dahulu.

Aturan ini diterapkan agar tidak muncul mata kuliah tanpa dosen pengampu serta menjaga konsistensi hubungan antar data di dalam database.

### **4. Kenapa `grades.submission_id` bersifat unique, bukan sekadar index biasa?**

`grades.submission_id` menggunakan `unique()` karena setiap submission seharusnya hanya memiliki satu nilai.

Jika hanya menggunakan index biasa, nilai `submission_id` yang sama masih dapat disimpan beberapa kali pada tabel `grades`. Hal tersebut dapat menyebabkan satu submission memiliki lebih dari satu nilai.

Dengan menggunakan `unique()`, database membatasi agar setiap `submission_id` hanya dapat muncul satu kali pada tabel `grades`.

---

## **BREAK**

### **1. Hapus `unique(['course_id', 'user_id'])` dari `course_user`, lalu daftarkan mahasiswa yang sama dua kali**

#### **Prediksi**

Saya memperkirakan mahasiswa yang sama dapat didaftarkan lebih dari satu kali pada mata kuliah yang sama karena batasan `unique` telah dihapus.

#### **Hasil yang nyata**

Hasil pengujian sesuai dengan prediksi. Mahasiswa yang sama dapat terdaftar beberapa kali pada mata kuliah yang sama.

#### **Kesimpulan**

`unique(['course_id', 'user_id'])` digunakan untuk memastikan kombinasi mahasiswa dan mata kuliah tidak mengalami duplikasi. Jika aturan tersebut dihilangkan, database akan mengizinkan data mahasiswa yang sama masuk berulang kali pada mata kuliah yang sama.

---

### **2. Tambahkan `role` ke `$fillable` model `User`, lalu kirim request dengan `role=admin` melalui form yang tidak memiliki field role**

#### **Prediksi**

Saya memperkirakan nilai `role=admin` tetap dapat tersimpan karena atribut `role` telah dimasukkan ke dalam `$fillable`.

#### **Hasil yang nyata**

Hasil pengujian sesuai dengan prediksi. User baru dapat memiliki `role=admin` walaupun form tidak menyediakan field untuk memasukkan role.

Hal tersebut dapat terjadi karena isi request dapat dimanipulasi, dan model tetap menerima nilai `role` selama atribut tersebut diperbolehkan melalui `$fillable`.

#### **Kesimpulan**

Keamanan data tidak cukup hanya mengandalkan field yang ditampilkan pada form. Pengaturan `$fillable` juga harus diperhatikan agar atribut penting seperti `role` tidak dapat diisi atau diubah secara sembarangan melalui request.

---

### **3. Ganti seluruh `$fillable` dengan `protected $guarded = [];`, lalu ulangi nomor 2**

#### **Prediksi**

Saya memperkirakan nilai `role=admin` tetap dapat masuk karena `protected $guarded = [];` membuat seluruh atribut diperbolehkan dalam proses mass assignment.

#### **Hasil yang nyata**

Hasil pengujian sesuai dengan prediksi. Setelah menggunakan `protected $guarded = [];`, semua atribut dapat dimasukkan melalui mass assignment, termasuk atribut `role`.

#### **Kesimpulan**

Penggunaan `protected $guarded = [];` menyebabkan seluruh atribut model terbuka terhadap mass assignment. Kondisi ini dapat menimbulkan risiko keamanan apabila data dari request tidak divalidasi dan dibatasi dengan baik, khususnya untuk atribut penting seperti role atau hak akses pengguna.

---

### **4. Kosongkan isi `down()` di satu migrasi, lalu jalankan `php artisan migrate:refresh`**

#### **Prediksi**

Saya memperkirakan proses `migrate:refresh` akan mengalami masalah karena Laravel tidak memiliki instruksi untuk membatalkan perubahan yang dibuat oleh migrasi tersebut.

#### **Hasil yang nyata**

Hasil pengujian sesuai dengan prediksi. Proses rollback tidak dapat berjalan dengan semestinya karena fungsi `down()` tidak berisi perintah untuk mengembalikan perubahan.

#### **Kesimpulan**

Fungsi `down()` berperan untuk membatalkan perubahan yang sebelumnya dibuat oleh fungsi `up()`. Jika `down()` dikosongkan, migrasi tidak dapat di-rollback dengan benar dan dapat menimbulkan masalah saat menjalankan `php artisan migrate:refresh` maupun ketika melakukan testing.

---

### **5. Ubah `restrictOnDelete` pada `lecturer_id` menjadi `cascadeOnDelete`, lalu hapus satu dosen**

#### **Prediksi**

Saya memperkirakan jika dosen dihapus, seluruh mata kuliah yang diampu oleh dosen tersebut juga akan ikut terhapus.

#### **Hasil yang nyata**

Hasil pengujian sesuai dengan prediksi. Setelah `lecturer_id` menggunakan `cascadeOnDelete()`, penghapusan data dosen menyebabkan mata kuliah yang terhubung dengan dosen tersebut ikut terhapus.

Data lain yang memiliki hubungan dengan mata kuliah tersebut juga berpotensi ikut terhapus apabila relasinya menggunakan `cascadeOnDelete()`.

#### **Kesimpulan**

`cascadeOnDelete()` dapat mempermudah proses penghapusan data yang memiliki hubungan langsung. Namun, penggunaannya harus dipertimbangkan dengan hati-hati karena penghapusan satu data dapat menyebabkan data-data lain yang saling berelasi ikut terhapus.

---

## **Ringkasan**

Berdasarkan pembelajaran pada minggu ini, saya memahami bahwa:

- `onDelete` digunakan untuk menentukan perlakuan terhadap data yang berelasi ketika suatu data dihapus.
- `unique()` berfungsi untuk mencegah terjadinya duplikasi pada data yang seharusnya hanya muncul satu kali.
- `$fillable` dan `$guarded` digunakan untuk mengatur atribut model yang dapat diproses melalui mass assignment.
- Fungsi `down()` diperlukan agar perubahan migrasi dapat dibatalkan atau dilakukan rollback.
- `cascadeOnDelete()` perlu digunakan secara hati-hati karena dapat menyebabkan beberapa data yang saling berhubungan ikut terhapus sekaligus.

Secara keseluruhan, perancangan database dan model pada Laravel tidak hanya mengatur hubungan antar data, tetapi juga berperan penting dalam menjaga keamanan, integritas, dan konsistensi data pada aplikasi.