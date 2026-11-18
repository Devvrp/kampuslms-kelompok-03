<?php

namespace Tests\Unit;

use App\Rules\MaxPasswordBytes;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MaxPasswordBytesTest extends TestCase
{
    public static function validPasswords(): array
    {
        return [
            'ASCII 72 bytes' => [str_repeat('a', 72)],
            'UTF-8 72 bytes' => [str_repeat('é', 36)],
        ];
    }

    public static function invalidPasswords(): array
    {
        return [
            'ASCII 73 bytes' => [str_repeat('a', 73)],
            'UTF-8 73 bytes' => [str_repeat('é', 36) . 'a'],
        ];
    }

    #[DataProvider('validPasswords')]
    public function test_accepts_password_at_72_byte_limit(string $password): void
    {
        $this->assertPasswordValidation($password, false);
    }

    #[DataProvider('invalidPasswords')]
    public function test_rejects_password_over_72_bytes(string $password): void
    {
        $this->assertPasswordValidation($password, true);
    }

    private function assertPasswordValidation(
        string $password,
        bool $shouldFail
    ): void {
        $errors = [];

        (new MaxPasswordBytes())->validate(
            'password',
            $password,
            function ($message) use (&$errors) {
                $errors[] = $message;
            }
        );

        $this->assertSame($shouldFail, count($errors) > 0);
    }
}