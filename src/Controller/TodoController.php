<?php

namespace App\Controller;

use App\Entity\Todo;
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
    public function index(): Response {

        $todos = $this->todoService->getByUser($this->getUser());

        return $this->render('todo/listTodo.html.twig', [
            'todos' => $todos,
        ]);
    }

    #[Route('/todo/create', name: 'app_todo_create')]
    public function create(Request $request): Response {

        if ($request->isMethod('POST')) {
            $this->todoService->create(
                $request->request->get('title'),
                $request->request->get('description'),
                $this->getUser()
            );

            return $this->redirectToRoute('app_list');
        }

        return $this->render('todo/createTodo.html.twig');
    }

    #[Route('/todo/toggle/{id}', name: 'app_todo_toggle')]
    public function toggle(Todo $todo): Response {

        $this->todoService->toggle($todo);

        return $this->redirectToRoute('app_list');
    }

    #[Route('/todo/delete/{id}', name: 'app_todo_delete')]
    public function delete(Todo $todo): Response {

        $this->todoService->delete($todo);

        return $this->redirectToRoute('app_list');
    }
}
