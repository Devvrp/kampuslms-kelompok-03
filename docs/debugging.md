# Dokumentasi Debugging KampusLMS

## Bug 1: Kebocoran Informasi Akun pada Fitur Reset Password

**Kontributor:** Fariz (Autentikasi dan Manajemen Mata Kuliah)

### 1. Gejala

Di fitur reset password, aplikasi memberikan pesan kesalahan yang berbeda ketika email tidak terdaftar dan ketika email terdaftar tapi token reset tidak valid.

Perbedaan tersebut ngebuat seseorang bisa tahu apakah sebuah alamat email terdaftar di KampusLMS lewat respons reset password.

### 2. Proses Menemukan Penyebab

Pemeriksaan dilakukan dengan membaca:

`app/Http/Controllers/Auth/NewPasswordController.php`

Ditemukan bahwa aplikasi menggunakan pesan berbeda berdasarkan status dari Laravel Password Broker:

```php
Password::INVALID_USER =>
    'Email tidak valid atau tidak ditemukan.',

Password::INVALID_TOKEN =>
    'Token reset tidak valid atau sudah kedaluwarsa.',
```

Akibatnya, respons untuk dua kondisi tersebut dapat dibandingkan untuk mengetahui keberadaan akun.

Sebelum perbaikan, dibuat regression test:

`test_password_reset_error_does_not_reveal_account_existence`

Test tersebut mengirim permintaan reset dengan token tidak valid menggunakan dua kondisi:
1. Email milik pengguna yang terdaftar.
2. Email yang tidak terdaftar.

Test gagal karena pesan kesalahan yang dikembalikan aplikasi berbeda.

### 3. Perbaikan

Pada `NewPasswordController.php`, pemetaan pesan kesalahan diperbaiki agar `INVALID_USER` dan `INVALID_TOKEN` menggunakan pesan yang sama:

```php
$message = match ($status) {
    Password::INVALID_USER, Password::INVALID_TOKEN =>
        'Tautan reset password tidak valid atau sudah kedaluwarsa.',
    Password::RESET_THROTTLED =>
        'Permintaan terlalu sering. Silakan coba lagi nanti.',
    default =>
        'Password gagal diubah. Silakan coba kembali.',
};
```

Dengan perubahan ini, aplikasi tidak lagi membedakan keberadaan akun melalui pesan kesalahan reset password.

### 4. Pengujian Pencegahan Regresi

Test baru ditambahkan pada:

`tests/Feature/PasswordResetTest.php`

Nama test:

`test_password_reset_error_does_not_reveal_account_existence`

Test memastikan bahwa email terdaftar dan tidak terdaftar dengan token tidak valid menghasilkan pesan kesalahan yang sama.

Hasil pengujian setelah perbaikan:

```text
php artisan test --filter=test_password_reset_error_does_not_reveal_account_existence

PASS
```

Seluruh test pada `PasswordResetTest.php` juga dijalankan kembali:

```text
php artisan test tests/Feature/PasswordResetTest.php

Tests: 7 passed
```

Dengan demikian, test baru berhasil dan enam test reset password lainnya tetap lulus.

### 5. Pelajaran

Pesan kesalahan pada fitur autentikasi dan pemulihan akun harus dirancang agar tidak memberikan informasi yang bisa digunakan untuk mengidentifikasi akun pengguna.

Pesan generik perlu diterapkan secara konsisten pada kondisi email tidak terdaftar maupun token reset yang tidak valid. Regression test digunakan untuk memastikan perbedaan pesan tersebut tidak muncul kembali.