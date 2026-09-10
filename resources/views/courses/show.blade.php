<x-layout title="Detail Mata Kuliah">

    <h1>Detail Mata Kuliah</h1>

    <table border="1">
        <tr>
            <th>Kode</th>
            <td>{{ $course['kode'] }}</td>
        </tr>

        <tr>
            <th>Nama Mata Kuliah</th>
            <td>{{ $course['nama'] }}</td>
        </tr>

        <tr>
            <th>SKS</th>
            <td>{{ $course['sks'] }}</td>
        </tr>

        <tr>
            <th>Dosen</th>
            <td>{{ $course['dosen'] }}</td>
        </tr>
    </table>

    <br>

    <a href="{{ route('courses.index') }}">
        Kembali ke Daftar Mata Kuliah
    </a>

</x-layout>