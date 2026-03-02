<?php

namespace App\Controller;

use App\Entity\Project;
use App\Form\CreateProjectFormType;
use App\Form\EditProjectFormType;
use App\Service\ProjectService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProjectController extends AbstractController
{
    public function __construct(
        private ProjectService $projectService
    ) {}

    #[Route('/projects', name: 'app_project_list')]
    public function index(): Response
    {
        $projects = $this->projectService->getByUser($this->getUser());

        return $this->render('project/listProject.html.twig', [
            'projects' => $projects,
        ]);
    }

    #[Route('/project/create', name: 'app_project_create')]
    public function create(Request $request): Response
    {
        $form = $this->createForm(CreateProjectFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $this->projectService->create(
                $data->getName(),
                $data->getDescription(),
                $data->getColor(),
                $this->getUser()
            );

            return $this->redirectToRoute('app_project_list');
        }

        return $this->render('project/createProject.html.twig', [
            'createProjectForm' => $form,
        ]);
    }

    #[Route('/project/{id}', name: 'app_project_show')]
    public function show(Project $project): Response
    {
        return $this->render('project/showProject.html.twig', [
            'project' => $project,
            'todos'   => $project->getTodos(),
        ]);
    }

    #[Route('/project/{id}/edit', name: 'app_project_edit')]
    public function edit(Project $project, Request $request): Response
    {
        $form = $this->createForm(EditProjectFormType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->projectService->update(
                $project,
                $project->getName(),
                $project->getDescription(),
                $project->getColor()
            );

            return $this->redirectToRoute('app_project_list');
        }

        return $this->render('project/editProject.html.twig', [
            'editProjectForm' => $form,
            'project'         => $project,
        ]);
    }

    #[Route('/project/{id}/delete', name: 'app_project_delete')]
    public function delete(Project $project): Response
    {
        $this->projectService->delete($project);

        return $this->redirectToRoute('app_project_list');
    }
}
