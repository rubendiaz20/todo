<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Todo;
use App\Form\CreateTodoFormType;
use App\Form\EditTodoFormType;
use App\Repository\ProjectRepository;
use App\Service\TodoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TodoController extends AbstractController
{
    public function __construct(
        private TodoService $todoService,
        private ProjectRepository $projectRepository
    ) {}

    #[Route('/list', name: 'app_list')]
    public function index(): Response
    {
        $todos = $this->todoService->getByUser($this->getUser());

        return $this->render('todo/listTodo.html.twig', [
            'todos' => $todos,
        ]);
    }

    #[Route('/project/{id}', name: 'app_project_show')]
    public function show(Project $project): Response
    {
        return $this->render('todo/listTodo.html.twig', [
            'project' => $project,
            'todos'   => $project->getTodos(),
        ]);
    }

    #[Route('/todo/create', name: 'app_todo_create')]
    public function create(Request $request): Response
    {
        $projectId = $request->query->get('project_id');
        $project = $projectId ? $this->projectRepository->find($projectId) : null;

        $form = $this->createForm(CreateTodoFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $this->todoService->create(
                $data->getTitle(),
                $data->getDescription(),
                $this->getUser(),
                $project
            );

            return $project
                ? $this->redirectToRoute('app_project_show', ['id' => $project->getId()])
                : $this->redirectToRoute('app_list');
        }

        return $this->render('todo/createTodo.html.twig', [
            'createTodoForm' => $form,
            'project'        => $project,
        ]);
    }

    #[Route('/todo/edit/{id}', name: 'app_todo_edit')]
    public function edit(Todo $todo, Request $request): Response
    {
        $project = $todo->getProject();

        $form = $this->createForm(EditTodoFormType::class, $todo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->todoService->update(
                $todo,
                $todo->getTitle(),
                $todo->getDescription()
            );

            return $project
                ? $this->redirectToRoute('app_project_show', ['id' => $project->getId()])
                : $this->redirectToRoute('app_list');
        }

        return $this->render('todo/editTodo.html.twig', [
            'editTodoForm' => $form,
            'todo'         => $todo,
        ]);
    }

    #[Route('/todo/edit-from-project/{id}', name: 'app_todo_edit_from_project', methods: ['POST'])]
    public function editFromProject(Todo $todo, Request $request): Response
    {
        $project = $todo->getProject();

        $form = $this->createForm(EditTodoFormType::class, $todo, [
            'action' => $this->generateUrl('app_todo_edit_from_project', ['id' => $todo->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->todoService->update(
                $todo,
                $todo->getTitle(),
                $todo->getDescription()
            );

            $this->addFlash('success', 'Tarea actualizada correctamente.');
        } else {
            $this->addFlash('error', 'Error al actualizar la tarea.');
        }

        return $this->redirectToRoute('app_project_edit_with_todos', ['id' => $project->getId()]);
    }

    #[Route('/todo/toggle/{id}', name: 'app_todo_toggle')]
    public function toggle(Todo $todo): Response
    {
        $project = $todo->getProject();
        $this->todoService->toggle($todo);

        return $project
            ? $this->redirectToRoute('app_project_show', ['id' => $project->getId()])
            : $this->redirectToRoute('app_list');
    }

    #[Route('/todo/delete/{id}', name: 'app_todo_delete')]
    public function delete(Todo $todo): Response
    {
        $project = $todo->getProject();
        $this->todoService->delete($todo);

        return $project
            ? $this->redirectToRoute('app_project_show', ['id' => $project->getId()])
            : $this->redirectToRoute('app_list');
    }
}
