<?php

namespace App\Service;

use App\Entity\Project;
use App\Entity\User;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;

class ProjectService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProjectRepository $projectRepository
    ) {}

    public function getByUser(User $user): array
    {
        return $this->projectRepository->findBy(['owner' => $user]);
    }

    public function create(string $name, ?string $description, User $user): Project
    {
        $project = new Project();
        $project->setName($name);
        $project->setDescription($description);
        $project->setOwner($user);


        $this->em->persist($project);
        $this->em->flush();

        return $project;
    }

    public function update(Project $project, string $name, ?string $description): void
    {
        $project->setName($name);
        $project->setDescription($description);
        $this->em->flush();
    }

    public function delete(Project $project): void
    {
        $this->em->remove($project);
        $this->em->flush();
    }
}
