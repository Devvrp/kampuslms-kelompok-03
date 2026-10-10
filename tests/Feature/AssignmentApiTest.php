<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssignmentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_assignment_list_filters_status_and_paginates(): void
    {
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $dosen->id,
        ]);

        for ($i = 1; $i <= 16; $i++) {
            Assignment::factory()->create([
                'course_id' => $course->id,
                'created_by' => $dosen->id,
                'status' => 'published',
                'due_at' => now()->addDays($i),
            ]);
        }

        for ($i = 1; $i <= 2; $i++) {
            Assignment::factory()->create([
                'course_id' => $course->id,
                'created_by' => $dosen->id,
                'status' => 'draft',
                'due_at' => now()->addDays(20 + $i),
            ]);
        }

        Sanctum::actingAs($dosen);

        $this->getJson("/api/v1/courses/{$course->id}/assignments?status=draft")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.status', 'draft');

        $this->getJson("/api/v1/courses/{$course->id}/assignments")
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total', 18);

        $this->getJson("/api/v1/courses/{$course->id}/assignments?page=2")
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_dosen_cannot_list_assignments_from_another_dosens_course(): void
    {
        $owner = User::factory()->create([
            'role' => 'dosen',
        ]);

        $otherDosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $owner->id,
        ]);

        Sanctum::actingAs($otherDosen);

        $this->getJson("/api/v1/courses/{$course->id}/assignments")
            ->assertForbidden()
            ->assertJson([
                'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
            ]);
    }

    public function test_mahasiswa_yang_terdaftar_dapat_melihat_daftar_tugas(): void
    {
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $mahasiswa = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $dosen->id,
        ]);

        $mahasiswa->courses()->attach($course->id, [
            'enrolled_at' => now(),
        ]);

        Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $dosen->id,
        ]);

        Sanctum::actingAs($mahasiswa);

        $this->getJson("/api/v1/courses/{$course->id}/assignments")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_mahasiswa_yang_tidak_terdaftar_ditolak_melihat_daftar_tugas(): void
    {
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $mahasiswa = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $dosen->id,
        ]);

        Sanctum::actingAs($mahasiswa);

        $this->getJson("/api/v1/courses/{$course->id}/assignments")
            ->assertForbidden();
    }

    public function test_dosen_pemilik_dapat_membuat_tugas(): void
    {
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $dosen->id,
        ]);

        Sanctum::actingAs($dosen);

        $response = $this->postJson('/api/v1/assignments', [
            'course_id' => $course->id,
            'title' => 'Tugas API Testing',
            'instructions' => 'Menguji pembuatan tugas melalui API.',
            'due_at' => now()->addDays(7)->toDateTimeString(),
            'max_score' => 100,
            'allow_late' => true,
            'status' => 'published',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.course_id', $course->id)
            ->assertJsonPath('data.title', 'Tugas API Testing');

        $this->assertDatabaseHas('assignments', [
            'course_id' => $course->id,
            'created_by' => $dosen->id,
            'title' => 'Tugas API Testing',
        ]);
    }

    public function test_dosen_tidak_dapat_membuat_tugas_di_course_dosen_lain(): void
    {
        $owner = User::factory()->create([
            'role' => 'dosen',
        ]);

        $otherDosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $owner->id,
        ]);

        Sanctum::actingAs($otherDosen);

        $this->postJson('/api/v1/assignments', [
            'course_id' => $course->id,
            'title' => 'Tugas Tidak Sah',
            'instructions' => 'Pengujian akses.',
            'due_at' => now()->addDays(7)->toDateTimeString(),
            'max_score' => 100,
            'allow_late' => true,
            'status' => 'published',
        ])
            ->assertForbidden()
            ->assertJson([
                'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
            ]);
    }

    public function test_dosen_pemilik_dapat_memperbarui_tugas(): void
    {
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $dosen->id,
        ]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $dosen->id,
        ]);

        Sanctum::actingAs($dosen);

        $this->putJson("/api/v1/assignments/{$assignment->id}", [
            'title' => 'Judul Tugas Diperbarui',
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Judul Tugas Diperbarui');

        $this->assertDatabaseHas('assignments', [
            'id' => $assignment->id,
            'title' => 'Judul Tugas Diperbarui',
        ]);
    }

    public function test_dosen_lain_tidak_dapat_memperbarui_tugas(): void
    {
        $owner = User::factory()->create([
            'role' => 'dosen',
        ]);

        $otherDosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $owner->id,
        ]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $owner->id,
        ]);

        Sanctum::actingAs($otherDosen);

        $this->putJson("/api/v1/assignments/{$assignment->id}", [
            'title' => 'Perubahan Tidak Sah',
        ])
            ->assertForbidden();

        $this->assertDatabaseHas('assignments', [
            'id' => $assignment->id,
            'title' => $assignment->title,
        ]);
    }

    public function test_dosen_pemilik_dapat_menghapus_tugas(): void
    {
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $dosen->id,
        ]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $dosen->id,
        ]);

        Sanctum::actingAs($dosen);

        $this->deleteJson("/api/v1/assignments/{$assignment->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('assignments', [
            'id' => $assignment->id,
        ]);
    }

    public function test_dosen_lain_tidak_dapat_menghapus_tugas(): void
    {
        $owner = User::factory()->create([
            'role' => 'dosen',
        ]);

        $otherDosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $owner->id,
        ]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $owner->id,
        ]);

        Sanctum::actingAs($otherDosen);

        $this->deleteJson("/api/v1/assignments/{$assignment->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('assignments', [
            'id' => $assignment->id,
        ]);
    }
}