<?php

namespace Tests\Unit\Users;

use App\Core\Auth;
use App\Core\Session\ArraySessionHandler;
use App\Core\Session\Session;
use App\Core\Session\SessionHandlerType;
use App\Exceptions\EmailAlreadyExistsException;
use App\Exceptions\UserDoesNotExistException;
use App\Users\User;
use App\Users\UserRepository;
use App\Users\UserSanitizer;
use App\Users\UserService;
use App\Users\UserType;
use App\Users\UserValidator;
use App\Utilities\PasswordUtils;
use InvalidArgumentException;
use LogicException;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use Tests\_data\UserData;
use Tests\Traits\MakesUsers;

class UserServiceTest extends TestCase
{
    use MakesUsers;

    private object $pdoStatementMock;
    private object $pdoMock;
    private UserRepository $userRepository;
    private UserService $userService;
    private ?Session $session;
    private Auth $auth;

    protected function setUp(): void
    {
        parent::setUp();

        $_ENV['APP_DOMAIN'] = 'test.com';
        $this->session = new Session(
            handler: new ArraySessionHandler(),
            handlerType: SessionHandlerType::Array,
            name: 'my_session_name',
            lifeTime: 3600,
            ssl: false,
            useCookies: false,
            httpOnly: false,
            path: '/',
            domain: '.test.com',
            savePath: '/tmp'
        );
        $this->session->start();
        $this->auth = new Auth($this->session);
        $this->pdoStatementMock = $this->createMock(PDOStatement::class);
        $this->pdoMock = $this->createMock(PDO::class);
        $this->userRepository = new UserRepository($this->pdoMock);
        $this->userService = new UserService($this->userRepository, new UserValidator(), new UserSanitizer(), $this->auth);
    }

    protected function tearDown(): void
    {
        $this->session->end();
        $this->session = null;

        parent::tearDown();
    }

    //
    // Create user
    //

    public function testCreatesUserSuccessfully(): void
    {
        $userData = UserData::memberOne();

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/INSERT INTO.+?users.+?VALUES.*?\(.*?\?/is'))
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $createdUser = $this->userService->createUser($userData);

        $this->assertSame(1, $createdUser->getId());
        $this->assertSame($userData['email'], $createdUser->getEmail());
        $this->assertSame($userData['first_name'], $createdUser->getFirstName());
        $this->assertSame($userData['last_name'], $createdUser->getLastName());
        $this->assertSame($userData['type'], $createdUser->getType());
        $this->assertSame($userData['created_at'], $createdUser->getCreatedAt());
        $this->assertSame($userData['updated_at'], $createdUser->getUpdatedAt());
        $this->assertTrue(PasswordUtils::isPasswordHashed($createdUser->getPassword()));
    }

    public function testThrowsExceptionWhenAddingUserWithInvalidEmail(): void
    {
        $userData = UserData::memberOne();
        $userData['email'] = 'test';

        $this->expectException(InvalidArgumentException::class);

        $this->userService->createUser($userData);
    }

    public function testThrowsExceptionWhenAddingUserWithInvalidFirstName(): void
    {
        $userData = UserData::memberOne();
        $userData['first_name'] = '';

        $this->expectException(InvalidArgumentException::class);

        $this->userService->createUser($userData);
    }

    public function testThrowsExceptionWhenAddingUserWithInvalidLastName(): void
    {
        $userData = UserData::memberOne();
        $userData['last_name'] = '';

        $this->expectException(InvalidArgumentException::class);

        $this->userService->createUser($userData);
    }

    public function testThrowsExceptionWhenAddingUserWithInvalidPassword(): void
    {
        $userData = UserData::memberOne();
        $userData['password'] = '123';

        $this->expectException(InvalidArgumentException::class);

        $this->userService->createUser($userData);
    }

    public function testThrowsExceptionWhenAddingUserWithInvalidType(): void
    {
        $userData = UserData::memberOne();
        $userData['type'] = 'superfantasticmember';

        $this->expectException(InvalidArgumentException::class);

        $this->userService->createUser($userData);
    }

    public function testThrowsExceptionWhenTryingToCreateUserWithAnEmailThatAlreadyExists(): void
    {
        $userData = UserData::memberOne();
        $userTwoData = UserData::memberTwo();
        $userTwoData['email'] = $userData['email'];

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturnOnConsecutiveCalls(
                true,
                $this->throwException(new LogicException("UNIQUE constraint failed: users.email"))
            );

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $this->userService->createUser($userData);

        $this->expectException(EmailAlreadyExistsException::class);
        $this->expectExceptionMessage("A user with the email '{$userData['email']}' already exists.");

        $this->userService->createUser($userTwoData);
    }

    public function testPasswordShouldBeHashedBeforeAddingUser(): void
    {
        $userData = UserData::memberOne();

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $user = $this->userService->createUser($userData);

        $this->assertNotSame($userData['password'], $user->getPassword());
        $this->assertTrue(PasswordUtils::isPasswordHashed($user->getPassword()));
    }

    public function testSanitizesDataBeforeCreatingAccount(): void
    {
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $userData = UserData::userUnsanitizedData();
        $user = $this->userService->createUser($userData);

        $this->assertSame("John", $user->getFirstName());
        $this->assertSame('Doe Test', $user->getLastName());
        $this->assertSame('svgonload=confirm1@gmail.com', $user->getEmail());
        $this->assertSame(88, $user->getCreatedAt());
        $this->assertSame(111, $user->getUpdatedAt());
    }

    //
    // Update
    //

    public function testUpdatesUserSuccessfully(): void
    {
        $user = $this->makeAndLoginUser();
        $userUpdatedData = UserData::updatedData();
        $userUpdatedData['id'] = 1;

        $this->pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () use ($user) {
                return $user->toArray();
            });

        $this->pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturnCallback(function ($query) {
                if (stripos($query, 'UPDATE') !== false) {
                    $this->assertMatchesRegularExpression('/UPDATE.+?users.+?SET.+?WHERE.+?id = \?/is', $query);
                } else {
                    $this->assertMatchesRegularExpression('/SELECT.+?FROM.+?users.+?WHERE.+?id = \?/is', $query);
                }

                return $this->pdoStatementMock;
            });

        $updatedUser = $this->userService->updateUser($userUpdatedData);

        $this->assertSame(1, $updatedUser->getId());
        $this->assertSame($userUpdatedData['email'], $updatedUser->getEmail());
        $this->assertSame($userUpdatedData['first_name'], $updatedUser->getFirstName());
        $this->assertSame($userUpdatedData['last_name'], $updatedUser->getLastName());
        $this->assertSame($user->getType(), $updatedUser->getType());
        $this->assertSame($user->getCreatedAt(), $updatedUser->getCreatedAt());
        $this->assertGreaterThan($user->getUpdatedAt(), $updatedUser->getUpdatedAt());
        $this->assertTrue(PasswordUtils::isPasswordHashed($updatedUser->getPassword()));
    }

    public function testThrowsExceptionWhenTryingToUpdateUserWhenNotLoggedIn(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot update a user when not logged in.");

        $this->userService->updateUser([]);
    }

    /**
     * @dataProvider updateUserInvalidDataProvider
     * @param $data
     * @param $expectedExceptionMessage
     * @return void
     */
    public function testThrowsExceptionWhenTryingToUpdateUserWithInvalidOrUnsanitizedData($data, $expectedExceptionMessage): void
    {
        $user = $this->makeAndLoginUser();

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () use ($user) {
                return $user->toArray();
            });

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->userService->updateUser($data);
    }

    public static function updateUserInvalidDataProvider(): array
    {
        return [
            'Missing email' => [
                [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'password' => 'azerty123456',
                    'type' => UserType::Member->value,
                ],
                "The email address is required.",
            ],
            'Invalid email' => [
                [
                    'email' => '5',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'password' => 'azerty123456',
                    'type' => UserType::Member->value,
                ],
                "Invalid email address.",
            ],
            'Unsanitized email' => [
                [
                    'email' => '¹@².³',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'password' => 'azerty123456',
                    'type' => UserType::Member->value,
                ],
                "Invalid email address.",
            ],
            'Missing first_name' => [
                [
                    'email' => 'test@email.com',
                    'last_name' => 'Doe',
                    'password' => 'azerty123456',
                    'type' => UserType::Member->value,
                ],
                "The first name is required.",
            ],
            'Invalid type of first_name' => [
                [
                    'email' => 'test@email.com',
                    'first_name' => false,
                    'last_name' => 'Doe',
                    'password' => 'azerty123456',
                    'type' => UserType::Member->value,
                ],
                "The first name is of invalid type. It should be a string.",
            ],
            'Empty first_name' => [
                [
                    'email' => 'test@email.com',
                    'first_name' => '',
                    'last_name' => 'Doe',
                    'password' => 'azerty123456',
                    'type' => UserType::Member->value,
                ],
                "The first name cannot be empty.",
            ],
            'Too long first_name' => [
                [
                    'email' => 'test@email.com',
                    'first_name' => str_repeat('a', 51),
                    'last_name' => 'Doe',
                    'password' => 'azerty123456',
                    'type' => UserType::Member->value,
                ],
                "The first name should be equal or less than 50 characters.",
            ],
            'Unsanitized first_name' => [
                [
                    'email' => 'test@email.com',
                    'first_name' => '$~é',
                    'last_name' => 'Doe',
                    'password' => 'azerty123456',
                    'type' => UserType::Member->value,
                ],
                "The first name cannot be empty.",
            ],
            'Missing last_name' => [
                [
                    'email' => 'test@email.com',
                    'first_name' => 'Doe',
                    'password' => 'azerty123456',
                    'type' => UserType::Member->value,
                ],
                "The last name is required.",
            ],
            'Invalid type of last_name' => [
                [
                    'email' => 'test@email.com',
                    'last_name' => false,
                    'first_name' => 'Doe',
                    'password' => 'azerty123456',
                    'type' => UserType::Member->value,
                ],
                "The last name is of invalid type. It should be a string.",
            ],
            'Empty last_name' => [
                [
                    'email' => 'test@email.com',
                    'last_name' => '',
                    'first_name' => 'Doe',
                    'password' => 'azerty123456',
                    'type' => UserType::Member->value,
                ],
                "The last name cannot be empty.",
            ],
            'Too long last_name' => [
                [
                    'email' => 'test@email.com',
                    'last_name' => str_repeat('a', 51),
                    'first_name' => 'Doe',
                    'password' => 'azerty123456',
                    'type' => UserType::Member->value,
                ],
                "The last name should be equal or less than 50 characters.",
            ],
            'Unsanitized last_name' => [
                [
                    'email' => 'test@email.com',
                    'last_name' => '$~é',
                    'first_name' => 'Doe',
                    'password' => 'azerty123456',
                    'type' => UserType::Member->value,
                ],
                "The last name cannot be empty.",
            ],
            'Missing password' => [
                [
                    'email' => 'test@email.com',
                    'first_name' => 'Doe',
                    'last_name' => 'John',
                    'type' => UserType::Member->value,
                ],
                "The password is required",
            ],
            'Invalid type of password' => [
                [
                    'password' => false,
                    'email' => 'test@email.com',
                    'first_name' => 'Doe',
                    'last_name' => 'John',
                    'type' => UserType::Member->value,
                ],
                "The password is of invalid type. It should be a string.",
            ],
            'Short password' => [
                [
                    'password' => 'aaa',
                    'email' => 'test@email.com',
                    'first_name' => 'Doe',
                    'last_name' => 'John',
                    'type' => UserType::Member->value,
                ],
                "The password length should be between 8 and 20 characters.",
            ],
            'Long password' => [
                [
                    'password' => str_repeat('a', 21),
                    'email' => 'test@email.com',
                    'first_name' => 'Doe',
                    'last_name' => 'John',
                    'type' => UserType::Member->value,
                ],
                "The password length should be between 8 and 20 characters.",
            ],
        ];
    }

    public function testThrowsExceptionWhenTryingToUpdateUserWithAnEmailThatAlreadyExists(): void
    {
        $userOne = User::make(UserData::memberOne());
        $userOne->setId(1);
        $userTwo = User::make(UserData::memberTwo());
        $userTwo->setId(2);
        $this->auth->login($userTwo);

        $this->pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturnOnConsecutiveCalls(
                true,
                true,
                $this->throwException(new LogicException("UNIQUE constraint failed: users.email"))
            );

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () use ($userTwo) {
                return $userTwo->toArray();
            });

        $this->pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $userTwo->setEmail($userOne->getEmail());

        $this->expectException(EmailAlreadyExistsException::class);
        $this->expectExceptionMessage("A user with the email '{$userOne->getEmail()}' already exists.");

        $this->userService->updateUser($userTwo->toArray());
    }

    public function testPasswordShouldBeHashedBeforeUpdatingUser(): void
    {
        $updatedPassword = 'azerty123456';
        $userData = UserData::memberOne();
        $userData['id'] = 1;
        $user = User::make($userData);
        $this->auth->login($user);

        $this->pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () use ($user) {
                return $user->toArray();
            });

        $this->pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $user->setPassword($updatedPassword);
        $updatedUser = $this->userService->updateUser($user->toArray());

        $this->assertNotSame($updatedPassword, $updatedUser->getPassword());
        $this->assertTrue(PasswordUtils::isPasswordHashed($updatedUser->getPassword()));
    }

    public function testSanitizesDataBeforeUpdatingAccount(): void
    {
        $userData = UserData::memberOne();
        $userData['id'] = 1;
        $user = User::make($userData);
        $this->auth->login($user);
        $unsanitizedData = UserData::userUnsanitizedData();
        $unsanitizedData['id'] = 1;

        $this->pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () use ($user) {
                return $user->toArray();
            });

        $this->pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $updatedUser = $this->userService->updateUser($unsanitizedData);

        $this->assertSame('John', $updatedUser->getFirstName());
        $this->assertSame('Doe Test', $updatedUser->getLastName());
        $this->assertSame('svgonload=confirm1@gmail.com', $updatedUser->getEmail());
        $this->assertSame($userData['type'], $updatedUser->getType());
        $this->assertSame($userData['created_at'], $updatedUser->getCreatedAt());
        $this->assertGreaterThan($userData['updated_at'], $updatedUser->getUpdatedAt());
    }

    //
    // Soft delete user
    //

    public function testSoftDeletesUserSuccessfully(): void
    {
        $user = User::make(UserData::memberOne());
        $user->setId(1);

        $this->pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () use ($user) {
                return $user->toArray();
            });

        $this->pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturnCallback(function ($query) {
                if (stripos($query, 'UPDATE') !== false) {
                    $this->assertMatchesRegularExpression('/UPDATE.+?users.+?SET.+?WHERE.+?id = \?/is', $query);
                }

                return $this->pdoStatementMock;
            });

        $deletedUser = $this->userService->softDeleteUser($user->getId());

        $this->assertSame($user->getId(), $deletedUser->getId());
        $this->assertSame('deleted-1@' . $_ENV['APP_DOMAIN'], $deletedUser->getEmail());
        $this->assertSame('deleted', $deletedUser->getFirstName());
        $this->assertSame('deleted', $deletedUser->getLastName());
        $this->assertSame(UserType::Deleted->value, $deletedUser->getType());
    }

    public function testThrowsExceptionWhenSoftDeletingUserWithAnIdOfZero(): void
    {
        $user = new User();
        $user->setId(0);

        $this->expectException(LogicException::class);

        $this->userService->softDeleteUser($user->getId());
    }

    public function testThrowsExceptionWhenTryingToSoftDeleteUserThatAlreadySoftDeleted(): void
    {
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                'id' => 1,
                'email' => 'deleted',
                'first_name' => 'deleted',
                'last_name' => 'deleted',
                'type' => UserType::Deleted->value,
            ]);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $user = new User();
        $user->setId(1);
        $user->setType(UserType::Deleted->value);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot delete a user that already has been deleted.");

        $this->userService->softDeleteUser($user->getId());
    }

    public function testThrowsExceptionWhenTryingToSoftDeleteUserThatHasBeenBanned(): void
    {
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () {
                $userData = UserData::memberOne();
                $userData['id'] = 1;
                $userData['type'] = UserType::Banned->value;

                return $userData;
            });

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $user = new User();
        $user->setId(1);
        $user->setType(UserType::Banned->value);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot delete a user that already has been banned.");

        $this->userService->softDeleteUser($user->getId());
    }

    public function testThrowsExceptionWhenSoftDeletingNonExistentUser(): void
    {
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $user = new User();
        $user->setId(999);

        $this->expectException(UserDoesNotExistException::class);
        $this->expectExceptionMessage("Cannot delete a user that does not exist.");

        $this->userService->softDeleteUser($user->getId());
    }
}
