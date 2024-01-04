<?php

namespace Tests\Traits;

use App\Users\PasswordReset\PasswordReset;
use Faker\Factory;

trait MakesPasswordResets
{
    protected function makePasswordReset(?int $userId = null): PasswordReset
    {
        $faker = Factory::create();

        return PasswordReset::make([
            'id' => $faker->randomNumber(),
            'user_id' => $userId ?? $faker->randomNumber(),
            'token' => $faker->uuid(),
            'created_at' => $faker->unixTime(),
        ]);
    }
}
