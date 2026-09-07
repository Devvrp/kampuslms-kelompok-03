<?php

namespace App\Http\Controllers;

class CourseController extends Controller
{
    public function index()
    {
        $courses = [
            [
                'id' => 1,
                'kode' => 'SI2514023',
                'nama' => 'Perencanaa Strategis Sistem Informasi',
                'sks' => 3,
                'dosen' => 'Bu Yuyun',
            ],
            [
                'id' => 2,
                'kode' => 'SI2514024',
                'nama' => 'Pemrograman Web',
                'sks' => 3,
                'dosen' => 'Pak Aidil',
            ],
            [
                'id' => 3,
                'kode' => 'SI2514025',
                'nama' => 'Kecerdasan Bisnis',
                'sks' => 3,
                'dosen' => 'Pak Dwi',
            ],
        ];

        return view('courses.index', compact('courses'));
    }

    public function show($id)
    {
        $courses = [
            [
                'id' => 1,
                'kode' => 'SI2514023',
                'nama' => 'Perencanaa Strategis Sistem Informasi',
                'sks' => 3,
                'dosen' => 'Bu Yuyun',
            ],
            [
                'id' => 2,
                'kode' => 'SI2514024',
                'nama' => 'Pemrograman Web',
                'sks' => 3,
                'dosen' => 'Pak Aidil',
            ],
            [
                'id' => 3,
                'kode' => 'SI2514025',
                'nama' => 'Kecerdasan Bisnis',
                'sks' => 3,
                'dosen' => 'Pak Dwi',
            ],
        ];

        $course = collect($courses)->firstWhere('id', (int) $id);

        abort_if(!$course, 404);

        return view('courses.show', compact('course'));
    }
}