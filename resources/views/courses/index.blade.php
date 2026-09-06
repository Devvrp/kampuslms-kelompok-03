<x-layout title="Daftar Mata Kuliah">

    <h1>Daftar Mata Kuliah</h1>

    <table border="1">
        <tr>
            <th>Kode</th>
            <th>Nama Mata Kuliah</th>
            <th>SKS</th>
            <th>Dosen</th>
            <th>Detail</th>
        </tr>

        @foreach ($courses as $course)
            <tr>
                <td>{{ $course['kode'] }}</td>
                <td>{{ $course['nama'] }}</td>
                <td>{{ $course['sks'] }}</td>
                <td>{{ $course['dosen'] }}</td>
                <td>
                    <a href="{{ route('courses.show', $course['id']) }}">
                        Lihat Detail
                    </a>
                </td>
            </tr>
        @endforeach
    </table>

</x-layout>