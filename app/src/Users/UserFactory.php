<?php

namespace App\Users;

use Faker\Generator;

class UserFactory
{
    public function __construct(private UserRepository $userRepository, private Generator $faker)
    {
    }

    /**
     * Instantiates Users and persists them to database
     * @param int $howMany
     * @return User[]|array
     */
    public function make(int $howMany): array
    {
        $users = [];
        $types = array_map(function ($item) {
            return $item->value;
        }, UserType::cases());

        for ($i = 0; $i < $howMany; $i++) {
            $user = new User();
            $user->setFirstName($this->faker->firstName());
            $user->setLastName($this->faker->lastName());
            $user->setEmail($this->faker->email());
            $user->setPassword($this->faker->password(minLength: 8, maxLength: 20));
            $user->setType($types[array_rand($types)]);
            $user->setCreatedAt($this->faker->unixTime());
            $user->setUpdatedAt($this->faker->unixTime());
            $users[] = $user;
        }

        return $users;
    }

    /**
     * Instantiates Users and persists them to database
     * @param int $howMany
     * @return User[]|array
     */
    public function create(int $howMany): array
    {
        $users = $this->make($howMany);

        foreach ($users as $user) {
            $this->userRepository->save($user);
        }

        return $users;
    }
}
