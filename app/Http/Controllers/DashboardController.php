<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Menampilkan dashboard sesuai role pengguna.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Dashboard khusus dosen.
        if ($user->role === 'dosen') {
            // Menghitung mata kuliah yang diampu dosen.
            $lecturerCourseCount = $user->taughtCourses()->count();

            // Mengambil submission dari mata kuliah milik dosen.
            $lecturerSubmissions = Submission::query()
                ->whereHas('assignment.course', function ($query) use ($user) {
                    $query->where('lecturer_id', $user->id);
                });

            // Menghitung submission yang belum dinilai.
            $pendingGradingCount = (clone $lecturerSubmissions)
                ->whereDoesntHave('grade')
                ->count();

            // Menghitung submission yang sudah dinilai.
            $gradedSubmissionCount = (clone $lecturerSubmissions)
                ->whereHas('grade')
                ->count();

            // Mengambil lima submission terbaru yang belum dinilai.
            $recentPendingSubmissions = (clone $lecturerSubmissions)
                ->whereDoesntHave('grade')
                ->with([
                    'student:id,name',
                    'assignment:id,course_id,title',
                    'assignment.course:id,code,name',
                ])
                ->orderByDesc('submitted_at')
                ->limit(5)
                ->get();

            return view('welcome', compact(
                'lecturerCourseCount',
                'pendingGradingCount',
                'gradedSubmissionCount',
                'recentPendingSubmissions'
            ));
        }

        // Dashboard selain dosen dan mahasiswa.
        if ($user->role !== 'mahasiswa') {
            return view('welcome');
        }

        // Mengambil mata kuliah dan dosen menggunakan eager loading.
        $courses = $user->courses()
            ->select(
                'courses.id',
                'courses.code',
                'courses.name',
                'courses.lecturer_id'
            )
            ->with('lecturer:id,name')
            ->withCount('assignments')
            ->orderBy('courses.code')
            ->get();

        // Mengambil tugas yang dipublikasikan pada mata kuliah mahasiswa.
        $publishedAssignments = Assignment::query()
            ->where('status', 'published')
            ->whereHas('course.students', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            });

        // Menghitung seluruh tugas.
        $totalAssignments = (clone $publishedAssignments)->count();

        // Menghitung tugas yang sudah dikumpulkan.
        $submittedAssignments = (clone $publishedAssignments)
            ->whereHas('submissions', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->count();

        // Menghitung tugas yang belum dikumpulkan.
        $pendingAssignments = max(
            0,
            $totalAssignments - $submittedAssignments
        );

        // Mengambil lima tugas terdekat.
        $upcomingAssignments = (clone $publishedAssignments)
            ->where('due_at', '>=', now())
            ->select([
                'id',
                'course_id',
                'title',
                'due_at',
            ])
            ->with('course:id,name,code')
            ->withExists([
                'submissions as is_submitted' => function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                },
            ])
            ->orderBy('due_at')
            ->limit(5)
            ->get();

        return view('welcome', compact(
            'courses',
            'totalAssignments',
            'submittedAssignments',
            'pendingAssignments',
            'upcomingAssignments'
        ));
    }
}