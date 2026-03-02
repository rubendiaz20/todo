<?php

namespace App\Controller;

use App\Entity\Todo;
use App\Form\CreateTodoFormType;
use App\Form\EditTodoFormType;
use App\Service\TodoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TodoController extends AbstractController
{
    public function __construct(
        private TodoService $todoService
    ) {}

    #[Route('/list', name: 'app_list')]
    public function index(): Response
    {
        $todos = $this->todoService->getByUser($this->getUser());

        return $this->render('todo/listTodo.html.twig', [
            'todos' => $todos,
        ]);
    }

    #[Route('/todo/create', name: 'app_todo_create')]
    public function create(Request $request): Response
    {
        $form = $this->createForm(CreateTodoFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $this->todoService->create(
                $data->getTitle(),
                $data->getDescription(),
                $this->getUser()
            );

            return $this->redirectToRoute('app_list');
        }

        return $this->render('todo/createTodo.html.twig', [
            'createTodoForm' => $form,
        ]);
    }

    #[Route('/todo/edit/{id}', name: 'app_todo_edit')]
    public function edit(Todo $todo, Request $request): Response
    {
        $form = $this->createForm(EditTodoFormType::class, $todo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->todoService->update(
                $todo,
                $todo->getTitle(),
                $todo->getDescription()
            );

            return $this->redirectToRoute('app_list');
        }

        return $this->render('todo/editTodo.html.twig', [
            'editTodoForm' => $form,
            'todo'         => $todo,
        ]);
    }

    #[Route('/todo/toggle/{id}', name: 'app_todo_toggle')]
    public function toggle(Todo $todo): Response
    {
        $this->todoService->toggle($todo);

        return $this->redirectToRoute('app_list');
    }

    #[Route('/todo/delete/{id}', name: 'app_todo_delete')]
    public function delete(Todo $todo): Response
    {
        $this->todoService->delete($todo);

        return $this->redirectToRoute('app_list');
    }
}
