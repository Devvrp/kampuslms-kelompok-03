<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CourseManagementTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(
        int $lecturerId,
        array $overrides = []
    ): array {
        return array_merge([
            'code' => 'MKTEST001',
            'name' => 'Mata Kuliah Pengujian',
            'description' => 'Deskripsi untuk pengujian.',
            'sks' => 3,
            'lecturer_id' => $lecturerId,
            'status' => 'active',
        ], $overrides);
    }

    public function test_admin_can_create_course_with_active_lecturer(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $lecturer = User::factory()->create([
            'role' => 'dosen',
        ]);

        $this->actingAs($admin)
            ->post(
                route('admin.courses.store'),
                $this->validPayload($lecturer->id)
            )
            ->assertRedirect();

        $this->assertDatabaseHas('courses', [
            'code' => 'MKTEST001',
            'name' => 'Mata Kuliah Pengujian',
            'lecturer_id' => $lecturer->id,
        ]);
    }

    public function test_admin_can_update_course(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $lecturer = User::factory()->create([
            'role' => 'dosen',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
            'name' => 'Nama Lama',
        ]);

        $this->actingAs($admin)
            ->put(
                route('admin.courses.update', $course),
                $this->validPayload($lecturer->id, [
                    'code' => $course->code,
                    'name' => 'Nama Baru',
                ])
            )
            ->assertRedirect(route('admin.courses.show', $course));

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'code' => $course->code,
            'name' => 'Nama Baru',
            'lecturer_id' => $lecturer->id,
        ]);
    }

    public function test_admin_can_delete_course(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $lecturer = User::factory()->create([
            'role' => 'dosen',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.courses.destroy', $course))
            ->assertRedirect(route('admin.courses.index'));

        $this->assertDatabaseMissing('courses', [
            'id' => $course->id,
        ]);
    }

    public function test_non_admin_cannot_create_update_or_delete_courses(): void
    {
        $lecturer = User::factory()->create([
            'role' => 'dosen',
        ]);

        $student = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
        ]);

        $this->actingAs($lecturer)
            ->post(
                route('admin.courses.store'),
                $this->validPayload($lecturer->id)
            )
            ->assertForbidden();

        $this->put(
            route('admin.courses.update', $course),
            $this->validPayload($lecturer->id, [
                'code' => $course->code,
            ])
        )->assertForbidden();

        $this->delete(
            route('admin.courses.destroy', $course)
        )->assertForbidden();

        $this->actingAs($student)
            ->post(
                route('admin.courses.store'),
                $this->validPayload($lecturer->id)
            )
            ->assertForbidden();

        $this->assertDatabaseMissing('courses', [
            'code' => 'MKTEST001',
        ]);

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
        ]);
    }

    public function test_course_creation_rejects_non_dosen_and_deleted_lecturers(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $student = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $this->actingAs($admin)
            ->post(
                route('admin.courses.store'),
                $this->validPayload($student->id)
            )
            ->assertSessionHasErrors('lecturer_id');

        $deletedLecturer = User::factory()->create([
            'role' => 'dosen',
        ]);

        $deletedLecturer->delete();

        $this->post(
            route('admin.courses.store'),
            $this->validPayload($deletedLecturer->id)
        )->assertSessionHasErrors('lecturer_id');

        $this->assertDatabaseMissing('courses', [
            'code' => 'MKTEST001',
        ]);
    }

    public function test_course_update_rejects_non_dosen_and_deleted_lecturers(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $owner = User::factory()->create([
            'role' => 'dosen',
        ]);

        $student = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $owner->id,
            'name' => 'Nama Sebelum Pengujian',
        ]);

        $this->actingAs($admin)
            ->put(
                route('admin.courses.update', $course),
                $this->validPayload($student->id, [
                    'code' => $course->code,
                    'name' => 'Nama Yang Tidak Boleh Tersimpan',
                ])
            )
            ->assertSessionHasErrors('lecturer_id');

        $deletedLecturer = User::factory()->create([
            'role' => 'dosen',
        ]);

        $deletedLecturer->delete();

        $this->put(
            route('admin.courses.update', $course),
            $this->validPayload($deletedLecturer->id, [
                'code' => $course->code,
                'name' => 'Nama Yang Tidak Boleh Tersimpan',
            ])
        )->assertSessionHasErrors('lecturer_id');

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'lecturer_id' => $owner->id,
            'name' => 'Nama Sebelum Pengujian',
        ]);
    }

    public static function descriptionAtByteLimit(): array
    {
        return [
            'ASCII 65535 bytes' => [str_repeat('a', 65535)],
            'UTF-8 65535 bytes' => [str_repeat('é', 32767) . 'a'],
        ];
    }

    #[DataProvider('descriptionAtByteLimit')]
    public function test_course_description_at_byte_limit_is_saved_on_create_and_update(string $description): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturer = User::factory()->create(['role' => 'dosen']);

        $payload = $this->validPayload($lecturer->id, [
            'description' => $description,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.courses.store'), $payload)
            ->assertSessionHasNoErrors();

        $course = Course::where('code', 'MKTEST001')->firstOrFail();

        $this->assertSame($description, $course->description);

        $course->update(['description' => 'Deskripsi sebelum revisi']);

        $this->put(
            route('admin.courses.update', $course),
            $this->validPayload($lecturer->id, [
                'code' => $course->code,
                'description' => $description,
            ])
        )->assertSessionHasNoErrors();

        $this->assertSame($description, $course->fresh()->description);
    }

    public static function oversizedDescription(): array
    {
        return [
            'ASCII 65536 bytes' => [str_repeat('a', 65536)],
            'UTF-8 65536 bytes' => [str_repeat('é', 32768)],
        ];
    }

    #[DataProvider('oversizedDescription')]
    public function test_oversized_course_description_is_rejected_without_writes(string $description): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturer = User::factory()->create(['role' => 'dosen']);

        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
            'description' => 'Deskripsi asli',
        ]);

        $original = $course->fresh()->getAttributes();

        $this->actingAs($admin)
            ->post(
                route('admin.courses.store'),
                $this->validPayload($lecturer->id, [
                    'description' => $description,
                ])
            )
            ->assertSessionHasErrors('description');

        $this->assertDatabaseCount('courses', 1);

        $this->put(
            route('admin.courses.update', $course),
            $this->validPayload($lecturer->id, [
                'code' => $course->code,
                'name' => 'Nama yang tidak boleh tersimpan',
                'description' => $description,
            ])
        )->assertSessionHasErrors('description');

        $this->assertSame($original, $course->fresh()->getAttributes());
    }
}
