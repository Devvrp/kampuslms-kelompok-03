<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <title>{{ $title ?? 'KampusLMS' }}</title>
</head>

<body>
<h1>XSS Demo</h1>

@php
    $nama = "<script>alert('XSS berhasil!')</script>";
@endphp

{{ $nama }}
    <nav>
        <a href="{{ route('dashboard') }}">Dashboard</a> |
        <a href="{{ route('courses.index') }}">Mata Kuliah</a> |
        <a href="{{ route('tentang') }}">Tentang</a>
    </nav>

    <hr>

    <main>
        {{ $slot }}
    </main>

</body>
</html>