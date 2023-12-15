<?php

namespace App\Users;

use App\Abstracts\Factory;
use App\Utilities\PasswordUtils;

class UserFactory extends Factory
{
    /**
     * Instantiates Users and persists them to database
     * @param array<string, mixed> $attributes
     * @return User[]
     */
    public function make(array $attributes = []): array
    {
        $users = [];
        $types = array_map(function ($item) {
            return $item->value;
        }, UserType::cases());

        for ($i = 0; $i < $this->count; $i++) {
            $user = new User();
            $user->setFirstName($attributes['first_name'] ?? $this->faker->firstName());
            $user->setLastName($attributes['last_name'] ?? $this->faker->lastName());
            $user->setEmail($attributes['email'] ?? $this->faker->email());
            $user->setPassword(PasswordUtils::hashPasswordIfNotHashed($attributes['password'] ?? $this->faker->password(minLength: 8, maxLength: 20)));
            $user->setType($attributes['type'] ?? $types[array_rand($types)]);
            $user->setCreatedAt($attributes['created_at'] ?? $this->faker->unixTime());
            $user->setUpdatedAt($attributes['updated_at'] ?? $this->faker->unixTime());
            $users[] = $user;
        }

        return $users;
    }

    /**
     * Instantiates Users and persists them to database
     * @param array<string, mixed> $attributes
     * @return User[]
     */
    public function create(array $attributes = []): array
    {
        $users = $this->make($attributes);

        foreach ($users as $user) {
            $this->repository->save($user);
        }

        return $users;
    }
}
