<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_private_files_for_all_submissions(): void
    {
        Storage::fake('local');

        $this->seed();

        // Memastikan role akun demo tersimpan dengan benar.
        $this->assertDatabaseCount('users', 34);

        $this->assertSame(
            1,
            User::where('role', 'admin')->count()
        );

        $this->assertSame(
            3,
            User::where('role', 'dosen')->count()
        );

        $this->assertSame(
            30,
            User::where('role', 'mahasiswa')->count()
        );

        $this->assertDatabaseHas('users', [
            'email' => 'admin@kampuslms.test',
            'role' => 'admin',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'dosen@kampuslms.test',
            'role' => 'dosen',
            'nim_nip' => '198001001',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'mahasiswa@kampuslms.test',
            'role' => 'mahasiswa',
            'nim_nip' => '202500001',
        ]);

        $this->assertSame(
            5,
            Assignment::where('status', 'draft')->count()
        );

        $this->assertSame(
            10,
            Assignment::where('status', 'published')->count()
        );

        $this->assertSame(
            150,
            Submission::count()
        );

        $draftSubmissions = Submission::query()
            ->whereHas('assignment', function ($query) {
                $query->where('status', 'draft');
            })
            ->count();

        $this->assertSame(0, $draftSubmissions);

        $this->assertSame(
            90,
            Grade::count()
        );

        foreach (Submission::all() as $submission) {
            Storage::disk('local')->assertExists(
                $submission->file_path
            );

            $content = Storage::disk('local')->get(
                $submission->file_path
            );

            $this->assertStringStartsWith(
                '%PDF-1.4',
                $content
            );

            $this->assertSame(
                strlen($content),
                (int) $submission->file_size
            );
        }
    }
}
