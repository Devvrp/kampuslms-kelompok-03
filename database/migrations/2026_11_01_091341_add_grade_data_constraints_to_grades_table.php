<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite tidak mendukung ALTER TABLE ADD CONSTRAINT.
        // Constraint tetap diterapkan pada MySQL/MariaDB.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("
            ALTER TABLE grades
            ADD CONSTRAINT grades_score_range_chk
            CHECK (score >= 0 AND score <= 100)
        ");

        DB::statement("
            ALTER TABLE grades
            ADD CONSTRAINT grades_feedback_length_chk
            CHECK (
                feedback IS NULL
                OR CHAR_LENGTH(feedback) <= 5000
            )
        ");
    }

    public function down(): void
    {
        // Tidak ada constraint yang ditambahkan pada SQLite.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("
            ALTER TABLE grades
            DROP CHECK grades_feedback_length_chk
        ");

        DB::statement("
            ALTER TABLE grades
            DROP CHECK grades_score_range_chk
        ");
    }
};