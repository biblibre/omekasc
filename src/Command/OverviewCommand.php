<?php

// namespace Omeka\Console\Command;

// use Symfony\Component\Console\Command;
// use Symfony\Component\Console\Input\InputInterface;
// use Symfony\Component\Console\Output\OutputInterface;
// use Symfony\Component\Console\Formatter\OutputFormatterStyle;

// class OverviewCommand extends Command
// {
//     protected static $defaultName = 'overview:command';

//     protected function configure()
//     {
//         $this->setDescription('Overview Command');
//         $this->setHelp('Cette commande permet de recuperer plein des informations sur Omeka S.');
//     }
//     protected function execute(InputInterface $input, OutputInterface $output)
//     {

//         $omekaHelper = $this->getHelper('omeka');
//         $application = $omekaHelper->getApplication();
//         $services = $application->getServiceManager();
//         $modules = $services->get('Omeka\ModuleManager')->getModules();
//         $em = $services->get('Omeka\EntityManager');
//         $logger = $services->get('Omeka\Logger');
//         $authentication = $services->get('Omeka\AuthenticationService');

    //     $this->ListeDesModules();
    //     $this->UserList();
    // }
    // public function ListeDesModules()
    // {
    //     $color = [];
    //     foreach ( $color as$description) {
    //         $outputStyle = new OutputFormatterStyle($color);
    //         $output->getFormatter()->setStyle($color, $outputStyle);
    //         $output->writeln(sprintf('<%s>%s</%s>', $color, $description, $color));
    //     }

    //     $output->writeln('');

    //     foreach ($modules as $module) {
    //         $name = $module->getName();
    //         $moduleStates = [
    //             'active' => ['description' => 'activé', 'color' => 'green'],
    //             'not_active' => ['description' => 'non activé', 'color' => 'yellow'],
    //             'not_installed' => ['description' => 'non installé', 'color' => 'yellow'],
    //             'not_found' => ['description' => 'non trouvé', 'color' => 'red'],
    //             'invalid_module' => ['description' => 'module invalide', 'color' => 'red'],
    //             'invalid_ini' => ['description' => 'module.ini invalide', 'color' => 'red'],
    //             'invalid_omeka_version' => ['description' => 'version de omeka invalide', 'color' => 'red'],
    //             'needs_upgrade' => ['description' => 'mise à niveau nécessaire', 'color' => 'magenta'],
    //         ];
    //         $state = $module->getState();
    //         $stateInfo = $moduleStates[$state];
    //         $description = $stateInfo['description'];
    //         $color = $stateInfo['color'];
    //         $ini = $module->getIni();
    //         $version = $ini['version'];

    //         $outputStyle = new OutputFormatterStyle($color);
    //         $output->getFormatter()->setStyle($state, $outputStyle);

    //         $output->writeln(sprintf('<%s>%s - version(%s)</%s>', $state, $name, $version, $state));
    //     }
    //     return Command::SUCCESS;
    // }
    // public function UserList()
    // {
    //     $api = $services->get('Omeka\ApiManager');
    //     $userId = $input->getArgument('user-id');

    //     $user = $em->find('Omeka\Entity\User', $userId);
    //     if (!$user) {
    //         $logger->err(sprintf('User %d does not exist', $userId));
    //         exit(1);
    //     }
    //     $authentication->getStorage()->write($user);

    //     $response = $api->search('users');
    //     $users = $response->getContent();

    //     $output->writeln("<info>Liste des utilisateurs:</info>");
    //     foreach ($users as $user) {
    //         $userInfo = sprintf(
    //             "%s <%s> %s ",
    //             $user->name(),
    //             $user->email(),
    //             $user->role(),
    //             ($user->isActive() ? : 'inactif')
    //         );
    //         $output->writeln($userInfo);
    //     }

    //     return Command::SUCCESS;

    // }

    // public function VersionOmeka()
    // {
    //     $version = $this->getApplication()->version();
    //     $output->writeln('Version d\'Omeka S : ' . $version);

    //     $monOption = $input->getOption('mon-option');

    //     if ($monOption) {

    //         $moduleManager = $this->getServiceLocator()->get('Omeka\ModuleManager');

    //         $modules = $moduleManager->getModules();

    //         $output->writeln('Modules installés :');

    //         foreach ($modules as $module) {
    //             $output->writeln('Nom : ' . $module->getName());
    //             $output->writeln('Version : ' . $module->getVersion());

    //             $output->writeln('---');
    //         }
    //     }

    //     return AbstractModule::STATUS_SUCCESS;
    // }

//     public function NombreDeContenus()
//     {
//         $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');

//         $contentCount = $entityManager->count('Omeka\Entity\Item');

//         $output->writeln('Nombre de contenus : ' . $contentCount);

//         $contenusParSite = $input->getOption('contenus-par-site');
//         if ($contenusParSite) {
//             $siteService = $this->getServiceLocator()->get('Omeka\ApiManager')->getResource('sites');
//             $sites = $siteService->search([]);

//             $output->writeln('Nombre de contenus par site :');
//             foreach ($sites as $site) {
//                 $siteId = $site->id();
//                 $contentCountBySite = $entityManager->count('Omeka\Entity\Item', ['site' => $siteId]);

//                 $output->writeln('Site ID ' . $siteId . ' : ' . $contentCountBySite . ' contenus');
//             }
//         }

//     }
// }
