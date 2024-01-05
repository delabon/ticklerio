<?php

namespace Tests\Traits;

use App\Users\PasswordReset\PasswordReset;
use Faker\Factory;

trait CreatesPasswordResets
{
    protected function createPasswordReset(int $userId, ?int $createdAt = null): PasswordReset
    {
        $faker = Factory::create();
        $passwordReset = PasswordReset::make([
            'user_id' => $userId,
            'token' => $faker->uuid(),
            'created_at' => $createdAt ?? strtotime('-1 minute'),
        ]);

        $this->passwordResetRepository->save($passwordReset);

        return $passwordReset;
    }
}
