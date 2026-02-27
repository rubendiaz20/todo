<?php

namespace App\Command;

use App\Entity\Todo;
use App\Repository\TodoRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:todo',
    description: 'Gestiona las tareas (list, create, update, delete)',
)]
class TodoCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private TodoRepository $todoRepository,
        private UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::REQUIRED, 'Acción a realizar: list, create, update, delete')
            ->addArgument('id', InputArgument::OPTIONAL, 'ID de la tarea (necesario para update y delete)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = $input->getArgument('action');
        $id = $input->getArgument('id');

        return match ($action) {
            'list'   => $this->listTodos($io),
            'create' => $this->createTodo($io),
            'update' => $this->updateTodo($io, $id),
            'delete' => $this->deleteTodo($io, $id),
            default  => $this->unknownAction($io, $action),
        };
    }

    private function listTodos(SymfonyStyle $io): int
    {
        $todos = $this->todoRepository->findAll();

        if (empty($todos)) {
            $io->warning('No hay tareas en la base de datos.');
            return Command::SUCCESS;
        }

        $rows = array_map(fn(Todo $t) => [
            $t->getId(),
            $t->getTitle(),
            $t->getDescription() ?? '-',
            $t->isDone() ? '✅' : '❌',
            $t->getUserTodo()?->getUsername() ?? '-',
            $t->getCreatedAt()->format('d/m/Y H:i'),
        ], $todos);

        $io->table(['ID', 'Título', 'Descripción', 'Hecha', 'Usuario', 'Creada'], $rows);

        return Command::SUCCESS;
    }

    private function createTodo(SymfonyStyle $io): int
    {
        $users = $this->userRepository->findAll();

        if (empty($users)) {
            $io->error('No hay usuarios en la base de datos. Crea uno primero con app:user create.');
            return Command::FAILURE;
        }

        $userChoices = [];
        foreach ($users as $user) {
            $userChoices[$user->getId()] = sprintf('[%d] %s', $user->getId(), $user->getUsername());
        }

        $title       = $io->ask('Título de la tarea');
        $description = $io->ask('Descripción (opcional, deja vacío para omitir)');
        $done        = $io->confirm('¿Está completada?', false);
        $userChoice  = $io->choice('Asignar a usuario', array_values($userChoices));

        $userId = array_search($userChoice, $userChoices);
        $user   = $this->userRepository->find($userId);

        $todo = new Todo();
        $todo->setTitle($title);
        $todo->setDescription($description ?: null);
        $todo->setDone($done);
        $todo->setUserTodo($user);

        $this->em->persist($todo);
        $this->em->flush();

        $io->success(sprintf('Tarea "%s" creada con ID %d.', $title, $todo->getId()));

        return Command::SUCCESS;
    }

    private function updateTodo(SymfonyStyle $io, ?string $id): int
    {
        if (!$id) {
            $io->error('Debes proporcionar el ID de la tarea. Ejemplo: app:todo update 5');
            return Command::FAILURE;
        }

        $todo = $this->todoRepository->find($id);

        if (!$todo) {
            $io->error(sprintf('No se encontró ninguna tarea con ID %d.', $id));
            return Command::FAILURE;
        }

        $io->section(sprintf('Editando tarea: %s (ID: %d)', $todo->getTitle(), $todo->getId()));

        $newTitle       = $io->ask(sprintf('Nuevo título (actual: "%s", deja vacío para no cambiar)', $todo->getTitle()));
        $newDescription = $io->ask(sprintf('Nueva descripción (actual: "%s", deja vacío para no cambiar)', $todo->getDescription() ?? '-'));
        $newDone        = $io->confirm('¿Marcar como completada?', $todo->isDone());

        if ($newTitle) {
            $todo->setTitle($newTitle);
        }

        if ($newDescription) {
            $todo->setDescription($newDescription);
        }

        $todo->setDone($newDone);

        $this->em->flush();

        $io->success(sprintf('Tarea con ID %d actualizada correctamente.', $id));

        return Command::SUCCESS;
    }

    private function deleteTodo(SymfonyStyle $io, ?string $id): int
    {
        if (!$id) {
            $io->error('Debes proporcionar el ID de la tarea. Ejemplo: app:todo delete 5');
            return Command::FAILURE;
        }

        $todo = $this->todoRepository->find($id);

        if (!$todo) {
            $io->error(sprintf('No se encontró ninguna tarea con ID %d.', $id));
            return Command::FAILURE;
        }

        $confirm = $io->confirm(sprintf('¿Seguro que quieres borrar la tarea "%s"?', $todo->getTitle()), false);

        if (!$confirm) {
            $io->note('Operación cancelada.');
            return Command::SUCCESS;
        }

        $this->em->remove($todo);
        $this->em->flush();

        $io->success(sprintf('Tarea "%s" eliminada correctamente.', $todo->getTitle()));

        return Command::SUCCESS;
    }

    private function unknownAction(SymfonyStyle $io, string $action): int
    {
        $io->error(sprintf('Acción "%s" desconocida. Usa: list, create, update, delete.', $action));
        return Command::FAILURE;
    }
}
