<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:user',
    description: 'Gestiona los usuarios (list, create, update, delete)',
)]
class UserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::REQUIRED, 'Acción a realizar: list, create, update, delete')
            ->addArgument('id', InputArgument::OPTIONAL, 'ID del usuario (necesario para update y delete)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = $input->getArgument('action');
        $id = $input->getArgument('id');

        return match ($action) {
            'list'   => $this->listUsers($io),
            'create' => $this->createUser($io),
            'update' => $this->updateUser($io, $id),
            'delete' => $this->deleteUser($io, $id),
            default  => $this->unknownAction($io, $action),
        };
    }

    private function listUsers(SymfonyStyle $io): int
    {
        $users = $this->userRepository->findAll();

        if (empty($users)) {
            $io->warning('No hay usuarios en la base de datos.');
            return Command::SUCCESS;
        }

        $rows = array_map(fn(User $u) => [$u->getId(), $u->getUsername()], $users);
        $io->table(['ID', 'Username'], $rows);

        return Command::SUCCESS;
    }

    private function createUser(SymfonyStyle $io): int
    {
        $validation = false;

        while(!$validation){
            $username = $io->ask('Username');
            if(!$username){
                $io->error('Debes proporcionar un username al usuario');
            }else{
                $io->error('Username correcto');
                $validation = true;
            }
        }

        $validation = false;
        while(!$validation){
            $password = $io->askHidden('Password');
            if(!$password){
                $io->error('Debes proporcionar un password al usuario');
            }else{
                $io->error('Password correcto');
                $validation = true;
            }
        }

        $user = new User();
        $user->setUsername($username);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        $io->success(sprintf('Usuario "%s" creado con ID %d.', $username, $user->getId()));

        return Command::SUCCESS;
    }

    private function updateUser(SymfonyStyle $io, ?string $id): int
    {
        if (!$id) {
            $io->error('Debes proporcionar el ID del usuario. Ejemplo: app:user update 3');
            return Command::FAILURE;
        }

        $user = $this->userRepository->find($id);

        if (!$user) {
            $io->error(sprintf('No se encontró ningún usuario con ID %d.', $id));
            return Command::FAILURE;
        }

        $io->section(sprintf('Editando usuario: %s (ID: %d)', $user->getUsername(), $user->getId()));

        $newUsername = $io->ask('Nuevo username (deja vacío para no cambiar)');
        $newPassword = $io->askHidden('Nueva password (deja vacío para no cambiar)');

        if ($newUsername) {
            $user->setUsername($newUsername);
        }

        if ($newPassword) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));
        }

        $this->em->flush();

        $io->success(sprintf('Usuario con ID %d actualizado correctamente.', $id));

        return Command::SUCCESS;
    }

    private function deleteUser(SymfonyStyle $io, ?string $id): int
    {
        if (!$id) {
            $io->error('Debes proporcionar el ID del usuario. Ejemplo: app:user delete 3');
            return Command::FAILURE;
        }

        $user = $this->userRepository->find($id);

        if (!$user) {
            $io->error(sprintf('No se encontró ningún usuario con ID %d.', $id));
            return Command::FAILURE;
        }

        $confirm = $io->confirm(sprintf('¿Seguro que quieres borrar al usuario "%s"?', $user->getUsername()), false);

        if (!$confirm) {
            $io->note('Operación cancelada.');
            return Command::SUCCESS;
        }

        $this->em->remove($user);
        $this->em->flush();

        $io->success(sprintf('Usuario "%s" eliminado correctamente.', $user->getUsername()));

        return Command::SUCCESS;
    }

    private function unknownAction(SymfonyStyle $io, string $action): int
    {
        $io->error(sprintf('Acción "%s" desconocida. Usa: list, create, update, delete.', $action));
        return Command::FAILURE;
    }
}
