<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher
    ) {}

    public function register(string $username, string $password): User
    {
        $user = new User();
        $user->setUsername($username);
        $user->setPassword(
            $this->hasher->hashPassword($user, $password)
        );

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
