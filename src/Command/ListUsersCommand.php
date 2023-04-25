<?php

namespace Omeka\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class ListUsersCommand extends Command
{
    protected static $defaultName = 'user:list';

    protected function configure()
    {
        $this->setDescription('Liste les utilisateurs avec leurs informations');
        $this->addArgument('user-id', InputArgument::REQUIRED);
        $this->setHelp('Cette commande permet de lister tous les utilisateurs enregistrés dans Omeka S avec leurs informations (nom, email, rôle, statut).');
    }

   protected function execute(InputInterface $input, OutputInterface $output)
   {
       $omekaHelper = $this->getHelper('omeka');
       $application = $omekaHelper->getApplication();
       $services = $application->getServiceManager();
       $em = $services->get('Omeka\EntityManager');
       $logger = $services->get('Omeka\Logger');
       $authentication = $services->get('Omeka\AuthenticationService');

       $api = $services->get('Omeka\ApiManager');
       $userId = $input->getArgument('user-id');

       $user = $em->find('Omeka\Entity\User', $userId);
       if (!$user) {
           $logger->err(sprintf('User %d does not exist', $userId));
           exit(1);
       }
       $authentication->getStorage()->write($user);

       $response = $api->search('users');
       $users = $response->getContent();

       $output->writeln("<info>Liste des utilisateurs:</info>");
       foreach ($users as $user) {
           $output->writeln("- Nom: " . $user->name());
           $output->writeln("  Email: " . $user->email());
           $output->writeln("  Rôle: " . $user->role());
           $output->writeln("  Statut: " . ($user->isActive() ? 'actif' : 'inactif') . "\n");
       }

       return Command::SUCCESS;
   }
}
