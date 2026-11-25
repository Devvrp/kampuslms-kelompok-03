<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EnrollmentMaterialSubmissionFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Menonaktifkan pemeriksaan CSRF hanya untuk feature test ini.
        // Pengujian tetap menjalankan autentikasi, role, dan authorization.
        $this->withoutMiddleware(ValidateCsrfToken::class);

        // Menggunakan penyimpanan palsu untuk pengujian file.
        Storage::fake('local');
    }

    // Membuat dosen dan mata kuliah untuk kebutuhan pengujian.
    private function createCourseWithLecturer(): array
    {
        $lecturer = User::factory()->create([
            'role' => 'dosen',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
        ]);

        return [$lecturer, $course];
    }

    // Membuat tugas yang sudah dipublikasikan.
    private function createPublishedAssignment(
        Course $course,
        User $lecturer,
        array $overrides = []
    ): Assignment {
        return Assignment::factory()->create(array_merge([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
            'status' => 'published',
            'due_at' => now()->addDay(),
            'allow_late' => false,
        ], $overrides));
    }

    // TEST 1:
    // Mahasiswa hanya dapat melihat mata kuliah yang diikutinya.
    public function test_student_only_sees_courses_they_are_enrolled_in(): void
    {
        [$lecturer, $enrolledCourse] =
            $this->createCourseWithLecturer();

        $otherCourse = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
            'code' => 'MK-PRIVATE',
            'name' => 'Mata Kuliah Tidak Diikuti',
        ]);

        $student = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $student->courses()->attach($enrolledCourse->id, [
            'enrolled_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('mahasiswa.courses.index'))
            ->assertOk()
            ->assertSeeText($enrolledCourse->name)
            ->assertDontSeeText($otherCourse->name)
            ->assertDontSeeText('MK-PRIVATE');
    }

    // TEST 2:
    // Dosen pengampu berhasil mengunggah materi.
    public function test_course_owner_can_upload_material_successfully(): void
    {
        [$lecturer, $course] =
            $this->createCourseWithLecturer();

        $response = $this->actingAs($lecturer)->post(
            route('dosen.courses.materials.store', $course),
            [
                'title' => 'Materi Pengujian',
                'description' => 'Materi untuk feature test.',
                'type' => 'file',
                'file' => UploadedFile::fake()->create(
                    'materi.pdf',
                    300,
                    'application/pdf'
                ),
            ]
        );

        $response->assertRedirect(
            route('dosen.courses.materials.index', $course)
        );

        $material = Material::query()->sole();

        $this->assertSame($course->id, $material->course_id);
        $this->assertSame($lecturer->id, $material->uploaded_by);

        Storage::disk('local')->assertExists(
            $material->file_path
        );
    }

    // TEST 3:
    // Dosen lain tidak dapat mengunggah materi pada mata kuliah tersebut.
    public function test_other_lecturer_is_rejected_when_uploading_material(): void
    {
        [, $course] = $this->createCourseWithLecturer();

        $otherLecturer = User::factory()->create([
            'role' => 'dosen',
        ]);

        $this->actingAs($otherLecturer)
            ->post(
                route('dosen.courses.materials.store', $course),
                [
                    'title' => 'Materi Tidak Sah',
                    'description' => 'Percobaan dosen lain.',
                    'type' => 'file',
                    'file' => UploadedFile::fake()->create(
                        'materi.pdf',
                        300,
                        'application/pdf'
                    ),
                ]
            )
            ->assertForbidden();

        $this->assertDatabaseCount('materials', 0);

        $this->assertSame(
            [],
            Storage::disk('local')->allFiles()
        );
    }

    // TEST 4:
    // Mahasiswa yang terdaftar berhasil mengumpulkan tugas.
    public function test_enrolled_student_can_submit_assignment_successfully(): void
    {
        [$lecturer, $course] =
            $this->createCourseWithLecturer();

        $student = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $course->students()->attach($student->id, [
            'enrolled_at' => now(),
        ]);

        $assignment = $this->createPublishedAssignment(
            $course,
            $lecturer
        );

        $this->actingAs($student)
            ->post(
                route(
                    'mahasiswa.assignments.submissions.store',
                    $assignment
                ),
                [
                    'file' => UploadedFile::fake()->create(
                        'tugas.pdf',
                        400,
                        'application/pdf'
                    ),
                    'note' => 'Pengumpulan tugas pengujian.',
                ]
            )
            ->assertRedirect();

        $submission = Submission::query()->sole();

        $this->assertSame($student->id, $submission->user_id);
        $this->assertSame(
            $assignment->id,
            $submission->assignment_id
        );
        $this->assertFalse($submission->is_late);

        Storage::disk('local')->assertExists(
            $submission->file_path
        );
    }

    // TEST 5:
    // Pengumpulan setelah deadline ditolak jika allow_late false.
    public function test_submission_after_deadline_is_rejected_when_late_submission_is_disabled(): void
    {
        [$lecturer, $course] =
            $this->createCourseWithLecturer();

        $student = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $course->students()->attach($student->id, [
            'enrolled_at' => now(),
        ]);

        $assignment = $this->createPublishedAssignment(
            $course,
            $lecturer,
            [
                'due_at' => now()->subMinute(),
                'allow_late' => false,
            ]
        );

        $this->actingAs($student)
            ->post(
                route(
                    'mahasiswa.assignments.submissions.store',
                    $assignment
                ),
                [
                    'file' => UploadedFile::fake()->create(
                        'terlambat.pdf',
                        400,
                        'application/pdf'
                    ),
                ]
            )
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('submissions', 0);

        $this->assertSame(
            [],
            Storage::disk('local')->allFiles()
        );
    }

    // TEST 6:
    // Mahasiswa yang tidak terdaftar ditolak ketika mengumpulkan tugas.
    public function test_unenrolled_student_is_rejected_when_submitting_assignment(): void
    {
        [$lecturer, $course] =
            $this->createCourseWithLecturer();

        $student = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $assignment = $this->createPublishedAssignment(
            $course,
            $lecturer
        );

        $this->actingAs($student)
            ->post(
                route(
                    'mahasiswa.assignments.submissions.store',
                    $assignment
                ),
                [
                    'file' => UploadedFile::fake()->create(
                        'tugas.pdf',
                        400,
                        'application/pdf'
                    ),
                ]
            )
            ->assertForbidden();

        $this->assertDatabaseCount('submissions', 0);

        $this->assertSame(
            [],
            Storage::disk('local')->allFiles()
        );
    }

    // TEST 7:
    // Validasi format file materi dan tugas yang tidak sesuai.
    public function test_invalid_material_and_submission_uploads_are_rejected_without_storing_files(): void
    {
        [$lecturer, $course] =
            $this->createCourseWithLecturer();

        // Menguji unggahan materi dengan format TXT.
        $this->actingAs($lecturer)
            ->post(
                route('dosen.courses.materials.store', $course),
                [
                    'title' => 'Materi Tidak Valid',
                    'description' => 'Format berkas tidak diizinkan.',
                    'type' => 'file',
                    'file' => UploadedFile::fake()->create(
                        'materi.txt',
                        100,
                        'text/plain'
                    ),
                ]
            )
            ->assertSessionHasErrors('file');

        $student = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $course->students()->attach($student->id, [
            'enrolled_at' => now(),
        ]);

        $assignment = $this->createPublishedAssignment(
            $course,
            $lecturer
        );

        // Menguji pengumpulan tugas dengan format TXT.
        $this->actingAs($student)
            ->post(
                route(
                    'mahasiswa.assignments.submissions.store',
                    $assignment
                ),
                [
                    'file' => UploadedFile::fake()->create(
                        'tugas.txt',
                        100,
                        'text/plain'
                    ),
                ]
            )
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('materials', 0);
        $this->assertDatabaseCount('submissions', 0);

        // Memastikan tidak ada file yang tersimpan.
        $this->assertSame(
            [],
            Storage::disk('local')->allFiles()
        );
    }
}
