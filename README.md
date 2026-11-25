# KampusLMS — Kelompok 03

KampusLMS adalah aplikasi **Learning Management System (LMS)** berbasis web untuk mendukung pengelolaan kegiatan pembelajaran di lingkungan kampus. Aplikasi menyediakan fitur sesuai peran admin, dosen, dan mahasiswa.

**Aplikasi:** [KampusLMS — Production](https://kampuslms-kelompok-03-production.up.railway.app/)

## Daftar isi

- [Fitur utama](#fitur-utama)
- [Teknologi](#teknologi)
- [Menjalankan aplikasi secara lokal](#menjalankan-aplikasi-secara-lokal)
- [Menjalankan worker notifikasi](#menjalankan-worker-notifikasi)
- [Menjalankan pengujian](#menjalankan-pengujian)
- [Gambaran API](#gambaran-api)
- [Alur kontribusi dan Git](#alur-kontribusi-dan-git)
- [Anggota kelompok](#anggota-kelompok)

## Fitur utama

### Admin

- Mengelola data pengguna.
- Mengelola mata kuliah.
- Mengelola materi dan tugas pada mata kuliah.
- Mengelola pendaftaran mahasiswa pada mata kuliah.

### Dosen

- Melihat mata kuliah yang dikelola.
- Mengelola materi dan tugas mata kuliah.
- Melihat submission mahasiswa.
- Memberikan nilai dan umpan balik serta melihat rekap nilai mata kuliah.
- Mengelola template umpan balik.
- Mengelola pendaftaran mahasiswa pada mata kuliah sesuai hak akses.

### Mahasiswa

- Melihat mata kuliah yang diikuti.
- Melihat materi dan tugas yang tersedia.
- Mengumpulkan dan mengirim ulang submission tugas sesuai ketentuan tugas.
- Melihat nilai pribadi.
- Melihat notifikasi.

### Fitur umum dan API

- Autentikasi, logout, dan alur reset kata sandi.
- Pengunduhan materi dan berkas submission melalui route aplikasi yang memerlukan autentikasi.
- Notifikasi tugas dan hasil penilaian yang diproses menggunakan database queue.
- REST API versi 1 dengan autentikasi token Laravel Sanctum untuk fitur mata kuliah, materi, tugas, submission, penilaian, rekap nilai, dan notifikasi.

Akses setiap fitur tetap mengikuti role dan pemeriksaan otorisasi yang diterapkan pada aplikasi.

## Teknologi

| Bagian | Teknologi |
|---|---|
| Backend | PHP 8.3+ dan Laravel 12 |
| Database | MySQL |
| Interaksi antarmuka | Livewire 3 |
| API authentication | Laravel Sanctum |
| Dependency backend | Composer |
| Frontend assets | Node.js, NPM, dan Vite |
| Deployment | Railway |

## Menjalankan aplikasi secara lokal

### Prasyarat

Pastikan perangkat sudah memiliki:

- PHP 8.3 atau versi yang kompatibel dengan dependency proyek.
- Composer.
- MySQL.
- Node.js dan NPM.

### 1. Clone repository

```bash
git clone https://github.com/Devvrp/kampuslms-kelompok-03.git
cd kampuslms-kelompok-03
```

### 2. Install dependency PHP

```bash
composer install
```

### 3. Siapkan file environment

Di Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Di macOS/Linux:

```bash
cp .env.example .env
```

Buka file `.env`, lalu sesuaikan konfigurasi aplikasi dan database lokal. Contoh konfigurasi MySQL:

```dotenv
APP_NAME="KampusLMS"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kampuslms
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
```

Buat database `kampuslms` pada MySQL atau gunakan nama database lain yang sesuai dengan konfigurasi `.env`.

> Jangan mengunggah `.env` ke Git. File `.env.example` digunakan sebagai contoh konfigurasi dan boleh disimpan di repository.

### 4. Generate application key

```bash
php artisan key:generate
```

### 5. Jalankan migration

```bash
php artisan migrate
```

### 6. Install dan build frontend assets

```bash
npm install
npm run build
```

### 7. Jalankan aplikasi

```bash
php artisan serve
```

Buka aplikasi melalui:

```text
http://127.0.0.1:8000
```

### Data contoh untuk pengembangan lokal (opsional)

Jika membutuhkan data contoh, jalankan seeder pada **database lokal atau database khusus pengembangan**:

```bash
php artisan db:seed
```

Seeder membuat akun dan data demo untuk membantu pengujian fitur. Jangan menjalankan seeder demo pada database production karena data yang dibuat merupakan data contoh dengan kredensial bawaan pengembangan.

## Menjalankan worker notifikasi

Notifikasi tugas baru dan hasil penilaian diproses melalui event, listener yang menggunakan queue, dan database notification. Pastikan konfigurasi berikut tersedia di `.env`:

```dotenv
QUEUE_CONNECTION=database
```

Jalankan worker pada terminal terpisah dan biarkan tetap aktif selama aplikasi digunakan:

```bash
php artisan queue:work database --sleep=3 --tries=3 --backoff=10
```

Jika worker tidak berjalan, pekerjaan notifikasi dapat menunggu di tabel `jobs`. Untuk memeriksa pekerjaan yang gagal:

```bash
php artisan queue:failed
```

Untuk mencoba kembali pekerjaan yang gagal, gunakan UUID job yang ditampilkan oleh perintah sebelumnya:

```bash
php artisan queue:retry <uuid-job>
```

Setelah perubahan kode, worker yang berjalan manual dapat diminta untuk berhenti setelah menyelesaikan pekerjaan saat ini:

```bash
php artisan queue:restart
```

Jalankan kembali perintah `queue:work` setelah worker berhenti. Detail error dapat diperiksa pada `storage/logs/laravel.log`.

## Menjalankan pengujian

Jalankan seluruh test dengan:

```bash
php artisan test
```

Pastikan konfigurasi database pengujian terpisah dari database pengembangan atau production. Jangan mengarahkan test ke database yang berisi data penting.

## Gambaran API

API menggunakan prefix `/api/v1`. Endpoint login dapat diakses tanpa token; endpoint lainnya berada di dalam middleware autentikasi Sanctum dan rate limit.

| Method | Endpoint | Keterangan |
|---|---|---|
| `POST` | `/api/v1/auth/login` | Login dan memperoleh token |
| `POST` | `/api/v1/auth/logout` | Logout |
| `GET` | `/api/v1/me` | Informasi pengguna yang sedang login |
| `GET` | `/api/v1/courses` | Daftar mata kuliah |
| `GET` | `/api/v1/courses/{course}` | Detail mata kuliah |
| `GET` | `/api/v1/courses/{course}/materials` | Materi mata kuliah |
| `GET` | `/api/v1/courses/{course}/assignments` | Tugas mata kuliah |
| `POST` | `/api/v1/assignments` | Membuat tugas |
| `PUT/PATCH` | `/api/v1/assignments/{assignment}` | Memperbarui tugas |
| `DELETE` | `/api/v1/assignments/{assignment}` | Menghapus tugas |
| `GET` | `/api/v1/assignments/{assignment}/submissions` | Daftar submission tugas |
| `POST` | `/api/v1/assignments/{assignment}/submissions` | Mengirim submission |
| `PUT` | `/api/v1/submissions/{submission}/grade` | Memberikan atau memperbarui nilai |
| `GET` | `/api/v1/courses/{course}/grades` | Rekap nilai mata kuliah |
| `GET` | `/api/v1/my/grades` | Nilai mahasiswa yang login |
| `GET` | `/api/v1/notifications` | Daftar notifikasi |
| `POST` | `/api/v1/notifications/{notification}/read` | Menandai notifikasi telah dibaca |

Ketersediaan operasi mengikuti role, kepemilikan data, dan otorisasi pada masing-masing endpoint.

## Alur kontribusi dan Git

Pengembangan dilakukan melalui branch fitur/perbaikan dan Pull Request menuju `dev`. Branch `main` digunakan untuk kode yang sudah melalui peninjauan dan pemeriksaan CI.

### 1. Ambil pembaruan branch `dev`

```bash
git fetch origin
git switch dev
git pull --ff-only origin dev
```

### 2. Buat branch pekerjaan

Gunakan nama branch yang menjelaskan cakupan pekerjaan, misalnya:

```bash
git switch -c feat/nama-fitur
```

Untuk perbaikan bug, gunakan awalan `fix/`; untuk dokumentasi, gunakan `docs/`.

### 3. Periksa dan stage perubahan

```bash
git status --short
git add -p
git diff --cached
git diff --cached --check
```

`git add -p` membantu memilih perubahan yang memang akan dimasukkan ke commit. Hindari `git add .` tanpa memeriksa daftar perubahan terlebih dahulu.

### 4. Jalankan pengujian dan commit

```bash
php artisan test
git commit -m "feat: jelaskan perubahan"
```

Gunakan Conventional Commits agar tujuan commit mudah dibaca:

| Prefix | Kegunaan | Contoh |
|---|---|---|
| `feat` | Menambahkan fitur | `feat: add course search` |
| `fix` | Memperbaiki bug | `fix: validate submission upload` |
| `docs` | Memperbarui dokumentasi | `docs: update project readme` |
| `style` | Perubahan format/tampilan tanpa mengubah perilaku | `style: tidy course page` |
| `refactor` | Merapikan kode tanpa mengubah perilaku yang diharapkan | `refactor: simplify course service` |
| `test` | Menambah atau memperbaiki pengujian | `test: cover submission authorization` |
| `perf` | Meningkatkan performa | `perf: optimize dashboard queries` |
| `chore` | Pekerjaan pemeliharaan | `chore: update project tooling` |
| `ci` | Perubahan pipeline CI | `ci: update test matrix` |
| `build` | Perubahan dependency/proses build | `build: update frontend dependencies` |

### 5. Push branch dan buat Pull Request

```bash
git push -u origin feat/nama-fitur
```

Buat Pull Request dengan:

- **Base:** `dev`
- **Compare:** branch pekerjaan yang baru di-push.

Isi bagian ringkasan, alasan perubahan, langkah pengujian, dampak, dan checklist pada template Pull Request. Satu branch dan satu Pull Request sebaiknya berfokus pada satu pekerjaan yang jelas.

### Sebelum membuat Pull Request

- Periksa kembali `git status` dan isi staging area.
- Pastikan `.env`, kredensial, file upload lokal, dan artefak debug tidak ikut ter-commit.
- Pastikan validasi dilakukan di server-side dan otorisasi diperiksa.
- Jalankan test yang relevan dan periksa hasil CI.
- Sertakan pembaruan dokumentasi apabila perubahan memengaruhi cara penggunaan atau konfigurasi aplikasi.

## Anggota kelompok

| No. | Nama | NIM | Tanggung jawab utama |
|---:|---|---|---|
| 1 | Devin Raditya Pratama | 10241021 | Ketua kelompok, koordinasi pengerjaan, pengumpulan dan penyerahan tugas, integrasi kode, pemeriksaan kualitas, serta backup pekerjaan yang belum selesai. |
| 2 | Fariz Daffa Abbiyu Rahmatullah | 10241029 | Pengelolaan mata kuliah, termasuk CRUD, pencarian, filter, dan paginasi. |
| 3 | Fabyo Nathanael Suoth | 10241027 | Pengelolaan materi pembelajaran dan tugas, termasuk CRUD dan validasi data. |
| 4 | Eagan Ferdian | 10241025 | Pengelolaan nilai dan rekap nilai serta pengujian fitur terkait. |
| 5 | Dewi Bulan Purnama | 10241023 | Pengelolaan pengguna, termasuk CRUD pengguna, pencarian, dan filter. |
