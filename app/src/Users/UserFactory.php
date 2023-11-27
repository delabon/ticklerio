<?php

namespace App\Users;

use App\Utilities\PasswordUtils;
use Faker\Generator;

class UserFactory
{
    private int $count = 1;

    public function __construct(private UserRepository $userRepository, private Generator $faker)
    {
    }

    public function count(int $howMany): self
    {
        $this->count = $howMany;

        return $this;
    }

    /**
     * Instantiates Users and persists them to database
     * @param array<string, mixed> $attributes
     * @return User[]|array
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
     * @return User[]|array
     */
    public function create(array $attributes = []): array
    {
        $users = $this->make($attributes);

        foreach ($users as $user) {
            $this->userRepository->save($user);
        }

        return $users;
    }
}
