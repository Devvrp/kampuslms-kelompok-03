<?php

namespace Tests\Unit;

use App\Rules\MaxOriginalFileNameLength;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MaxOriginalFileNameLengthTest extends TestCase
{
    public static function validFileNames(): array
    {
        return [
            'ASCII 255 characters' => [str_repeat('a', 251) . '.pdf'],
            'UTF-8 255 characters' => [str_repeat('é', 251) . '.pdf'],
        ];
    }

    public static function invalidFileNames(): array
    {
        return [
            'ASCII 256 characters' => [str_repeat('a', 252) . '.pdf'],
            'UTF-8 256 characters' => [str_repeat('é', 252) . '.pdf'],
        ];
    }

    #[DataProvider('validFileNames')]
    public function test_accepts_file_name_with_maximum_length(string $name): void
    {
        $this->assertFileNameValidation($name, false);
    }

    #[DataProvider('invalidFileNames')]
    public function test_rejects_file_name_exceeding_maximum_length(string $name): void
    {
        $this->assertFileNameValidation($name, true);
    }

    private function assertFileNameValidation(string $name, bool $shouldFail): void
    {
        $path = tempnam(sys_get_temp_dir(), 'upload_');

        try {
            $file = new UploadedFile(
                $path,
                $name,
                'application/pdf',
                UPLOAD_ERR_OK,
                true
            );

            $errors = [];

            (new MaxOriginalFileNameLength())->validate(
                'file',
                $file,
                function ($message) use (&$errors) {
                    $errors[] = $message;
                }
            );

            $this->assertSame($shouldFail, count($errors) > 0);
        } finally {
            unlink($path);
        }
    }
}