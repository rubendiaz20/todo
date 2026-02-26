<?php

namespace App\Service;

use App\Entity\Todo;
use App\Entity\User;
use App\Repository\TodoRepository;
use Doctrine\ORM\EntityManagerInterface;

class TodoService
{
    public function __construct(
        private EntityManagerInterface $em,
        private TodoRepository $todoRepository
    ) {}

    public function getByUser(User $user): array{
        return $this->todoRepository->findBy(['userTodo' => $user]);
    }

    public function create(string $title, string $description, User $user): Todo{
        $todo = new Todo();
        $todo->setTitle($title);
        $todo->setDescription($description);
        $todo->setDone(false);
        $todo->setUserTodo($user);

        $this->em->persist($todo);
        $this->em->flush();

        return $todo;
    }

    public function toggle(Todo $todo): void
    {
        $todo->setDone(!$todo->isDone());
        $this->em->flush();
    }

    public function delete(Todo $todo): void
    {
        $this->em->remove($todo);
        $this->em->flush();
    }
}
