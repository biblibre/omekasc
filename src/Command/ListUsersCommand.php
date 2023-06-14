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
        $this->setDescription('List users with their information');
        $this->addArgument('globalAdmins');
        $this->setHelp('This command lists all users registered in Omeka S with their information (name, email, role, status).');
    }

   protected function execute(InputInterface $input, OutputInterface $output)
   {

       $omekaHelper = $this->getHelper('omeka');
       $application = $omekaHelper->getApplication();
       $services = $application->getServiceManager();
       $em = $services->get('Omeka\EntityManager');
       $logger = $services->get('Omeka\Logger');
       $authentication = $services->get('Omeka\AuthenticationService');

       $globalAdmins = $em->getRepository('Omeka\Entity\User')->findBy(['role' => 'global_admin']);
      
       if (!$globalAdmins) {
           $logger->err('No user with the global_admin role found');
           exit(1);
       }

       $authentication->getStorage()->write($globalAdmins[0]);
      

       $api = $services->get('Omeka\ApiManager');

       $response = $api->search('users');
       $users = $response->getContent();

       $output->writeln("<info>Users list:</info>");
       foreach ($users as $user) {
           $userInfo = sprintf(
               "%s <%s> %s ",
               $user->name(),
               $user->email(),
               $user->role(),
               ($user->isActive() ? : 'inactif')
           );
           $output->writeln($userInfo);
       }

       return Command::SUCCESS;
   }
}
