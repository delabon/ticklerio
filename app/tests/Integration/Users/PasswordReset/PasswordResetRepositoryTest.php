<?php

namespace Tests\Integration\Users\PasswordReset;

use App\Users\PasswordReset\PasswordResetRepository;
use Tests\IntegrationTestCase;
use Tests\Traits\CreatesPasswordResets;
use Tests\Traits\CreatesUsers;
use Tests\Traits\MakesPasswordResets;

class PasswordResetRepositoryTest extends IntegrationTestCase
{
    use CreatesPasswordResets;
    use MakesPasswordResets;
    use CreatesUsers;

    private PasswordResetRepository $passwordResetRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->passwordResetRepository = new PasswordResetRepository($this->pdo);
    }

    //
    // Insert
    //

    public function testInsertsSuccessfully(): void
    {
        $user = $this->createUser();
        $passwordReset = $this->makePasswordReset();
        $passwordReset->setId(0);
        $passwordReset->setUserId($user->getId());

        $this->passwordResetRepository->save($passwordReset);

        $this->assertSame(1, $passwordReset->getId());
        $this->assertSame($user->getId(), $passwordReset->getUserId());
    }

    public function testInsertsMultipleEntitiesSuccessfully(): void
    {
        $user = $this->createUser();
        $passwordReset = $this->makePasswordReset();
        $passwordReset->setId(0);
        $passwordReset->setUserId($user->getId());
        $passwordResetTwo = $this->makePasswordReset();
        $passwordResetTwo->setId(0);
        $passwordResetTwo->setUserId($user->getId());

        $this->passwordResetRepository->save($passwordReset);
        $this->passwordResetRepository->save($passwordResetTwo);

        $this->assertSame(1, $passwordReset->getId());
        $this->assertSame(2, $passwordResetTwo->getId());
        $this->assertSame($user->getId(), $passwordReset->getUserId());
        $this->assertSame($user->getId(), $passwordResetTwo->getUserId());
    }

    //
    // Delete
    //

    public function testDeletesSuccessfully(): void
    {
        $user = $this->createUser();
        $passwordReset = $this->createPasswordReset($user->getId());

        $passwordResets = $this->passwordResetRepository->all();
        $this->assertCount(1, $passwordResets);
        $this->assertSame(1, $passwordReset->getId());

        $this->passwordResetRepository->delete($passwordReset->getId());

        $this->assertCount(0, $this->passwordResetRepository->all());
    }
}
