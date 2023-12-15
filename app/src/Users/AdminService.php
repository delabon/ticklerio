<?php

namespace App\Users;

use App\Core\Auth;
use App\Exceptions\TicketDoesNotExistException;
use App\Exceptions\UserDoesNotExistException;
use App\Tickets\TicketRepository;
use App\Tickets\TicketStatus;
use InvalidArgumentException;
use LogicException;

readonly class AdminService
{
    public function __construct(
        private UserRepository $userRepository,
        private TicketRepository $ticketRepository,
        private Auth $auth
    ) {
    }

    public function banUser(int $id): User
    {
        if (!$this->auth->getUserId()) {
            throw new LogicException("Cannot ban a user when not logged in.");
        }

        if ($this->auth->getUserType() !== UserType::Admin->value) {
            throw new LogicException("Cannot ban a user using a non-admin account.");
        }

        if ($id < 1) {
            throw new InvalidArgumentException("Cannot ban a user with a non-positive id.");
        }

        $user = $this->userRepository->find($id);

        if (!$user) {
            throw new UserDoesNotExistException("Cannot ban a user that does not exist.");
        }

        if (in_array($user->getType(), [UserType::Deleted->value, UserType::Banned->value])) {
            throw new LogicException("Cannot ban a user that has been {$user->getType()}.");
        }

        $user->setType(UserType::Banned->value);
        $this->userRepository->save($user);

        return $user;
    }

    public function unbanUser(int $id): User
    {
        if (!$this->auth->getUserId()) {
            throw new LogicException("Cannot unban a user when not logged in.");
        }

        if (!$id) {
            throw new LogicException("Cannot unban a user with an id of 0.");
        }

        $user = $this->userRepository->find($id);

        if (!$user) {
            throw new UserDoesNotExistException("Cannot unban a user that does not exist.");
        }

        if ($user->getType() !== UserType::Banned->value) {
            throw new LogicException("Cannot unban a user that is not banned.");
        }

        $admin = $this->userRepository->find($this->auth->getUserId());

        if ($admin->getType() !== UserType::Admin->value) {
            throw new LogicException("Cannot unban a user using a non-admin account.");
        }

        $user->setType(UserType::Member->value);
        $this->userRepository->save($user);

        return $user;
    }

    public function updateTicketStatus(int $id, string $status): void
    {
        if (!$this->auth->getUserId()) {
            throw new LogicException("Cannot update the status of a ticket when not logged in.");
        }

        if ($id < 1) {
            throw new InvalidArgumentException("Cannot update the status of a ticket with a non positive id.");
        }

        if (!in_array($status, TicketStatus::toArray())) {
            throw new InvalidArgumentException("Cannot update the status of a ticket with an invalid status.");
        }

        $admin = $this->userRepository->find($this->auth->getUserId());

        if ($admin->getType() !== UserType::Admin->value) {
            throw new LogicException("Cannot update the status of a ticket using a non-admin account.");
        }

        $ticket = $this->ticketRepository->find($id);

        if (!$ticket) {
            throw new TicketDoesNotExistException("Cannot update the status of a ticket that does not exist.");
        }

        $ticket = $this->ticketRepository->find($id);
        $ticket->setStatus($status);
        $this->ticketRepository->save($ticket);
    }
}
