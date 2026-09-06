<?php

namespace App\Http\Controllers;

class CourseController extends Controller
{
    public function index()
    {
        $courses = [
            [
                'id' => 1,
                'kode' => 'SI101',
                'nama' => 'Pemrograman Web',
                'sks' => 3,
                'dosen' => 'Budi Santoso',
            ],
            [
                'id' => 2,
                'kode' => 'SI102',
                'nama' => 'Basis Data',
                'sks' => 3,
                'dosen' => 'Andi Wijaya',
            ],
            [
                'id' => 3,
                'kode' => 'SI103',
                'nama' => 'Analisis dan Perancangan Sistem',
                'sks' => 3,
                'dosen' => 'Siti Aminah',
            ],
        ];

        return view('courses.index', compact('courses'));
    }

    public function show($id)
    {
        $courses = [
            [
                'id' => 1,
                'kode' => 'SI101',
                'nama' => 'Pemrograman Web',
                'sks' => 3,
                'dosen' => 'Budi Santoso',
            ],
            [
                'id' => 2,
                'kode' => 'SI102',
                'nama' => 'Basis Data',
                'sks' => 3,
                'dosen' => 'Andi Wijaya',
            ],
            [
                'id' => 3,
                'kode' => 'SI103',
                'nama' => 'Analisis dan Perancangan Sistem',
                'sks' => 3,
                'dosen' => 'Siti Aminah',
            ],
        ];

        $course = collect($courses)->firstWhere('id', (int) $id);

        abort_if(!$course, 404);

        return view('courses.show', compact('course'));
    }
}