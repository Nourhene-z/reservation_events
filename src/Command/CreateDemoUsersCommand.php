<?php

namespace App\Command;

use App\Entity\Admin;
use App\Entity\User;
use App\Repository\AdminRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-demo-users', description: 'Create default admin/user accounts if missing')]
class CreateDemoUsersCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly AdminRepository $adminRepository,
        private readonly UserRepository $userRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $created = false;

        $admin = $this->adminRepository->findOneBy(['username' => 'admin']);
        if (!$admin) {
            $admin = new Admin();
            $admin->setUsername('admin');
            $admin->setPasswordHash($this->passwordHasher->hashPassword($admin, 'admin1234'));
            $this->entityManager->persist($admin);
            $created = true;
            $output->writeln('Admin created: admin / admin1234');
        }

        $user = $this->userRepository->findOneBy(['username' => 'user']);
        if (!$user) {
            $user = new User();
            $user->setUsername('user');
            $user->setPasswordHash($this->passwordHasher->hashPassword($user, 'user1234'));
            $this->entityManager->persist($user);
            $created = true;
            $output->writeln('User created: user / user1234');
        }

        if ($created) {
            $this->entityManager->flush();
            return Command::SUCCESS;
        }

        $output->writeln('Demo users already exist.');

        return Command::SUCCESS;
    }
}
