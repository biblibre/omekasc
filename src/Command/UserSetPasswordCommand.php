<?php

namespace Omeka\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UserSetPasswordCommand extends Command
{
    protected static $defaultName = 'user:set-password';

    protected function configure()
    {
        $this->setDescription('Set a new password');
        $this->addArgument('email', InputArgument::REQUIRED);
        $this->addArgument('password', InputArgument::REQUIRED);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        while (null === $input->getArgument('email')) {
            $email = $io->ask("Enter the user's email");
            $input->setArgument('email', $email);
        }

        while (null === $input->getArgument('password')) {
            $password = $io->askHidden("Enter the new password");
            $input->setArgument('password', $password);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        if (null === $email) {
            $io->error('Missing argument: email');
            return Command::FAILURE;
        }

        $password = $input->getArgument('password');
        if (null === $password) {
            $io->error('Missing argument: password');
            return Command::FAILURE;
        }

        $omekaHelper = $this->getHelper('omeka');
        $application = $omekaHelper->getApplication();
        $services = $application->getServiceManager();
        $em = $services->get('Omeka\EntityManager');

        try {
            $user = $em->getRepository('Omeka\Entity\User')->findOneBy(['email' => $email]);
            if ($user === null) {
                throw new \Exception('User does not exist: ' . $email);
            }

            $user->setPassword($password);
            $em->flush();
            $io->success("Password has been changed");
        } catch(\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
