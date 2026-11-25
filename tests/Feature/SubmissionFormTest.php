<?php

namespace Tests\Feature;

use App\Livewire\Mahasiswa\SubmissionForm;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SubmissionFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.default' => 'public']);
        Storage::fake('public');
        Storage::fake('local');
    }

    private function createEnrolledStudentAssignment(
        array $overrides = []
    ): array {
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $mahasiswa = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $dosen->id,
        ]);

        $course->students()->attach($mahasiswa->id, [
            'enrolled_at' => now(),
        ]);

        $assignment = Assignment::factory()->create(array_merge([
            'course_id' => $course->id,
            'created_by' => $dosen->id,
            'status' => 'published',
            'due_at' => now()->addDays(7),
            'allow_late' => false,
        ], $overrides));

        return [$mahasiswa, $assignment];
    }

    public function test_mahasiswa_dapat_mengumpulkan_tugas_pdf(): void
    {
        $disk = 'local';
        Storage::fake($disk);

        [$mahasiswa, $assignment] =
            $this->createEnrolledStudentAssignment();

        Livewire::actingAs($mahasiswa)
            ->test(SubmissionForm::class, [
                'assignment' => $assignment,
            ])
            ->set('file', UploadedFile::fake()->create(
                'tugas.pdf',
                500,
                'application/pdf'
            ))
            ->set('note', 'Tugas sudah selesai.')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect();

        $submission = Submission::where('assignment_id', $assignment->id)
            ->where('user_id', $mahasiswa->id)
            ->firstOrFail();

        $this->assertDatabaseCount('submissions', 1);

        $this->assertSame('tugas.pdf', $submission->original_name);
        $this->assertSame('Tugas sudah selesai.', $submission->note);
        $this->assertFalse($submission->is_late);

        Storage::disk($disk)->assertExists($submission->file_path);
        $this->assertSame([], Storage::disk('public')->allFiles());

        $this->get(route('submissions.download', $submission))
            ->assertOk()->assertDownload('tugas.pdf');
    }

    public function test_mahasiswa_dapat_mengumpulkan_ulang_tugas(): void
    {
        $disk = 'local';
        Storage::fake($disk);

        [$mahasiswa, $assignment] =
            $this->createEnrolledStudentAssignment();

        $oldPath = 'submissions/berkas-lama.pdf';

        Storage::disk($disk)->put($oldPath, 'berkas lama');

        $original = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $mahasiswa->id,
            'file_path' => $oldPath,
            'original_name' => 'berkas-lama.pdf',
            'file_size' => 11,
            'submitted_at' => now()->subHour(),
            'is_late' => false,
        ]);

        Livewire::actingAs($mahasiswa)
            ->test(SubmissionForm::class, [
                'assignment' => $assignment,
            ])
            ->set('file', UploadedFile::fake()->create(
                'tugas-revisi.pdf',
                600,
                'application/pdf'
            ))
            ->set('note', 'Versi revisi.')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect();

        $updated = Submission::where('assignment_id', $assignment->id)
            ->where('user_id', $mahasiswa->id)
            ->firstOrFail();

        $this->assertSame($original->id, $updated->id);
        $this->assertSame('tugas-revisi.pdf', $updated->original_name);
        $this->assertSame('Versi revisi.', $updated->note);

        $this->assertDatabaseCount('submissions', 1);

        Storage::disk($disk)->assertExists($updated->file_path);
        Storage::disk($disk)->assertMissing($oldPath);
        $this->assertSame([], Storage::disk('public')->allFiles());

        $this->get(route('submissions.download', $updated))
            ->assertOk()->assertDownload('tugas-revisi.pdf');
    }

    public function test_berkas_dengan_tipe_tidak_valid_ditolak(): void
    {
        Storage::fake('local');

        [$mahasiswa, $assignment] =
            $this->createEnrolledStudentAssignment();

        Livewire::actingAs($mahasiswa)
            ->test(SubmissionForm::class, [
                'assignment' => $assignment,
            ])
            ->set('file', UploadedFile::fake()->create(
                'script.txt',
                100,
                'text/plain'
            ))
            ->call('submit')
            ->assertHasErrors(['file' => 'mimes']);

        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_berkas_lebih_dari_5_mb_ditolak(): void
    {
        Storage::fake('local');

        [$mahasiswa, $assignment] =
            $this->createEnrolledStudentAssignment();

        Livewire::actingAs($mahasiswa)
            ->test(SubmissionForm::class, [
                'assignment' => $assignment,
            ])
            ->set('file', UploadedFile::fake()->create(
                'tugas-besar.pdf',
                5121,
                'application/pdf'
            ))
            ->call('submit')
            ->assertHasErrors(['file' => 'max']);

        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_pengumpulan_terlambat_ditolak_jika_tidak_diizinkan(): void
    {
        Storage::fake('local');

        [$mahasiswa, $assignment] =
            $this->createEnrolledStudentAssignment([
                'due_at' => now()->subMinute(),
                'allow_late' => false,
            ]);

        Livewire::actingAs($mahasiswa)
            ->test(SubmissionForm::class, [
                'assignment' => $assignment,
            ])
            ->set('file', UploadedFile::fake()->create(
                'tugas-terlambat.pdf',
                500,
                'application/pdf'
            ))
            ->call('submit')
            ->assertHasErrors(['file']);

        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_pengumpulan_terlambat_diterima_jika_diizinkan(): void
    {
        $disk = 'local';
        Storage::fake($disk);

        [$mahasiswa, $assignment] =
            $this->createEnrolledStudentAssignment([
                'due_at' => now()->subMinute(),
                'allow_late' => true,
            ]);

        Livewire::actingAs($mahasiswa)
            ->test(SubmissionForm::class, [
                'assignment' => $assignment,
            ])
            ->set('file', UploadedFile::fake()->create(
                'tugas-terlambat.pdf',
                500,
                'application/pdf'
            ))
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect();

        $submission = Submission::where('assignment_id', $assignment->id)
            ->where('user_id', $mahasiswa->id)
            ->firstOrFail();

        $this->assertTrue($submission->is_late);
        Storage::disk($disk)->assertExists($submission->file_path);
    }

    public function test_component_rechecks_enrollment_after_access_is_revoked(): void
    {
        [$mahasiswa, $assignment] =
            $this->createEnrolledStudentAssignment();

        $component = Livewire::actingAs($mahasiswa)
            ->test(SubmissionForm::class, [
                'assignment' => $assignment,
            ]);

        // Mahasiswa dikeluarkan dari mata kuliah.
        $assignment->course->students()->detach($mahasiswa->id);

        // Render ulang harus ditolak.
        $component
            ->set('note', 'Percobaan setelah dikeluarkan.')
            ->assertStatus(403);

        $this->assertDatabaseCount('submissions', 0);
    }

    public static function oversizedSubmissionNotes(): array
    {
        return [
            'ASCII 65536 bytes' => [str_repeat('a', 65536)],
            'UTF-8 65536 bytes' => [str_repeat('é', 32768)],
        ];
    }

    #[DataProvider('oversizedSubmissionNotes')]
    public function test_livewire_rejects_oversized_submission_note(string $note): void
    {
        [$mahasiswa, $assignment] =
            $this->createEnrolledStudentAssignment();

        Livewire::actingAs($mahasiswa)
            ->test(SubmissionForm::class, [
                'assignment' => $assignment,
            ])
            ->set('file', UploadedFile::fake()->create(
                'tugas.pdf',
                500,
                'application/pdf'
            ))
            ->set('note', $note)
            ->call('submit')
            ->assertHasErrors(['note']);

        $this->assertDatabaseCount('submissions', 0);
    }

    #[DataProvider('oversizedSubmissionNotes')]
    public function test_web_controller_rejects_oversized_submission_note(string $note): void
    {
        [$mahasiswa, $assignment] =
            $this->createEnrolledStudentAssignment();

        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.assignments.submissions.store', $assignment), [
                'file' => UploadedFile::fake()->create(
                    'tugas.pdf',
                    500,
                    'application/pdf'
                ),
                'note' => $note,
            ])
            ->assertSessionHasErrors('note');

        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_livewire_accepts_submission_note_at_byte_limit(): void
    {
        [$mahasiswa, $assignment] =
            $this->createEnrolledStudentAssignment();

        $note = str_repeat('é', 32767) . 'a';

        Livewire::actingAs($mahasiswa)
            ->test(SubmissionForm::class, [
                'assignment' => $assignment,
            ])
            ->set('file', UploadedFile::fake()->create(
                'tugas.pdf',
                500,
                'application/pdf'
            ))
            ->set('note', $note)
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect();

        $submission = Submission::firstOrFail();

        $this->assertSame($note, $submission->note);
    }

    public function test_web_controller_accepts_submission_note_at_byte_limit(): void
    {
        [$mahasiswa, $assignment] =
            $this->createEnrolledStudentAssignment();

        $note = str_repeat('é', 32767) . 'a';

        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.assignments.submissions.store', $assignment), [
                'file' => UploadedFile::fake()->create(
                    'tugas.pdf',
                    500,
                    'application/pdf'
                ),
                'note' => $note,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            $note,
            Submission::firstOrFail()->note
        );
    }

    public function test_livewire_rejects_submission_file_name_over_255_characters(): void
    {
        [$mahasiswa, $assignment] =
            $this->createEnrolledStudentAssignment();

        Livewire::actingAs($mahasiswa)
            ->test(SubmissionForm::class, [
                'assignment' => $assignment,
            ])
            ->set('file', $this->livewirePdfWithOriginalName(
                str_repeat('a', 252) . '.pdf'
            ))
            ->set('note', 'Pengujian nama file panjang.')
            ->call('submit')
            ->assertHasErrors(['file']);

        $this->assertDatabaseCount('submissions', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_web_controller_rejects_submission_file_name_over_255_characters(): void
    {
        [$mahasiswa, $assignment] =
            $this->createEnrolledStudentAssignment();

        $this->actingAs($mahasiswa)
            ->post(route('mahasiswa.assignments.submissions.store', $assignment), [
                'file' => $this->pdfWithOriginalName(
                    str_repeat('a', 252) . '.pdf'
                ),
                'note' => 'Pengujian nama file panjang.',
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('submissions', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    private function pdfWithOriginalName(string $originalName): UploadedFile
    {
        $fake = UploadedFile::fake()->create(
            'short.pdf',
            1,
            'application/pdf'
        );

        return new class(
            $fake->getPathname(),
            $originalName
        ) extends UploadedFile {
            public string $name;

            public function __construct(string $path, string $originalName)
            {
                parent::__construct(
                    $path,
                    $originalName,
                    'application/pdf',
                    UPLOAD_ERR_OK,
                    true
                );

                $this->name = $originalName;
            }

            public function getMimeType(): ?string
            {
                return 'application/pdf';
            }

            public function getSize(): int
            {
                return 1024;
            }
        };
    }

    private function livewirePdfWithOriginalName(string $originalName): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'livewire-upload-');

        file_put_contents($path, str_repeat('x', 1024));

        return new class($path, $originalName) extends UploadedFile {
            public string $name;

            public function __construct(string $path, string $originalName)
            {
                parent::__construct(
                    $path,
                    $originalName,
                    'application/pdf',
                    UPLOAD_ERR_OK,
                    true
                );

                $this->name = $originalName;
            }

            public function getMimeType(): ?string
            {
                return 'application/pdf';
            }
        };
    }
}
