<?php

namespace Tests\Integration\Users;

use App\Exceptions\UserDoesNotExistException;
use App\Users\User;
use App\Users\UserRepository;
use Tests\_data\UserData;
use Tests\IntegrationTestCase;

use function Symfony\Component\String\u;

class UserRepositoryTest extends IntegrationTestCase
{
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = new UserRepository($this->pdo);
    }

    //
    // Create user
    //

    public function testAddsUserSuccessfully(): void
    {
        $now = time();
        $userData = UserData::memberOne();
        $userData['created_at'] = $now;
        $userData['updated_at'] = $now;
        $user = User::make($userData);

        $this->userRepository->save($user);

        $this->assertSame(1, $user->getId());
        $this->assertCount(1, $this->userRepository->all());
        $this->assertSame($userData['email'], $user->getEmail());
        $this->assertSame($userData['first_name'], $user->getFirstName());
        $this->assertSame($userData['last_name'], $user->getLastName());
        $this->assertSame($userData['password'], $user->getPassword());
        $this->assertSame($userData['type'], $user->getType());
        $this->assertSame($userData['created_at'], $user->getCreatedAt());
        $this->assertSame($userData['updated_at'], $user->getUpdatedAt());
        $this->assertSame($now, $user->getCreatedAt());
        $this->assertSame($now, $user->getUpdatedAt());
    }

    public function testAddsMultipleUsersSuccessfully(): void
    {
        $userOneData = UserData::memberOne();
        $userTwoData = UserData::memberTwo();
        $user = User::make($userOneData);
        $user2 = User::make($userTwoData);

        $this->userRepository->save($user);
        $this->userRepository->save($user2);

        $this->assertSame(1, $user->getId());
        $this->assertSame(2, $user2->getId());
        $this->assertCount(2, $this->userRepository->all());
    }

    //
    // Update user
    //

    public function testUpdatesUserSuccessfully(): void
    {
        $userData = UserData::memberOne();
        $user = User::make($userData);
        $this->userRepository->save($user);

        $userUpdatedData = UserData::updatedData();
        $user = User::make($userUpdatedData, $user);
        $this->userRepository->save($user);

        $users = $this->userRepository->all();
        $this->assertCount(1, $users);
        $this->assertSame(1, $user->getId());
        $this->assertEquals($user, $users[0]);
    }

    public function testThrowsExceptionWhenTryingToUpdateNonExistentUser(): void
    {
        $user = new User();
        $user->setId(5555);
        $user->setEmail('test@test.com');

        $this->expectException(UserDoesNotExistException::class);

        $this->userRepository->save($user);
    }

    //
    // Find user
    //

    public function testFindsUserByIdSuccessfully(): void
    {
        $userData = UserData::memberOne();
        $user = User::make($userData);
        $this->userRepository->save($user);

        $userFound = $this->userRepository->find($user->getId());

        $this->assertSame(1, $userFound->getId());
        $this->assertEquals($user, $userFound);
        $this->assertInstanceOf(User::class, $userFound);
    }

    public function testFindsNonExistentUserShouldFail(): void
    {
        $userFound = $this->userRepository->find(99999);

        $this->assertNull($userFound);
    }

    /**
     * @dataProvider validUserDataProvider
     * @param array $findData
     * @return void
     */
    public function testFindsUserByKeyAndValueSuccessfully(array $findData): void
    {
        $user = User::make(UserData::memberOne());
        $this->userRepository->save($user);

        $usersFound = $this->userRepository->findBy($findData['key'], $findData['value']);
        $method = 'get' . u($findData['key'])->camel()->toString();

        $this->assertCount(1, $usersFound);
        $this->assertSame(1, $usersFound[0]->getId());
        $this->assertSame($findData['value'], $usersFound[0]->$method());
        $this->assertInstanceOf(User::class, $usersFound[0]);
    }

    /**
     * @dataProvider validUserDataProvider
     * @param array $findData
     * @return void
     */
    public function testReturnsEmptyArrayWhenFindingUserWithNonExistentData(array $findData): void
    {
        $usersFound = $this->userRepository->findBy($findData['key'], $findData['value']);

        $this->assertCount(0, $usersFound);
    }

    public static function validUserDataProvider(): array
    {
        $userData = UserData::memberOne();

        return [
            'Find by email' => [
                [
                    'key' => 'email',
                    'value' => $userData['email'],
                ]
            ],
            'Find by first_name' => [
                [
                    'key' => 'first_name',
                    'value' => $userData['first_name'],
                ]
            ],
            'Find by last_name' => [
                [
                    'key' => 'last_name',
                    'value' => $userData['last_name'],
                ]
            ],
            'Find by type' => [
                [
                    'key' => 'type',
                    'value' => $userData['type'],
                ]
            ],
        ];
    }

    public function testFindsAllUsers(): void
    {
        $userOne = User::make(UserData::memberOne());
        $this->userRepository->save($userOne);
        $userTwo = User::make(UserData::memberTwo());
        $this->userRepository->save($userTwo);

        $usersFound = $this->userRepository->all();

        $this->assertCount(2, $usersFound);
        $this->assertSame(1, $usersFound[0]->getId());
        $this->assertSame(2, $usersFound[1]->getId());
        $this->assertInstanceOf(User::class, $usersFound[0]);
        $this->assertInstanceOf(User::class, $usersFound[1]);
    }

    public function testFindsAllWithNoUsersInTableShouldReturnEmptyArray(): void
    {
        $this->assertCount(0, $this->userRepository->all());
    }

    //
    // Delete
    //

    public function testDeletesUserSuccessfully(): void
    {
        $user = User::make(UserData::memberOne());
        $this->userRepository->save($user);

        $this->assertCount(1, $this->userRepository->all());

        $this->userRepository->delete($user->getId());

        $this->assertNull($this->userRepository->find($user->getId()));
    }
}
