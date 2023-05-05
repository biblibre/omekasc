<?php

namespace Omeka\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListModulesCommand extends Command
{
    protected static $defaultName = 'list:modules';

    protected function configure()
    {
        $this->setDescription('Liste des Modules');
        $this->setHelp('Cette commande permet de lister tous les modules installés sur Omeka S.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $omekaHelper = $this->getHelper('omeka');
        $application = $omekaHelper->getApplication();
        $services = $application->getServiceManager();
        $modules = $services->get('Omeka\ModuleManager')->getModules();

        foreach ($modules as $module) {
            $name = $module->getName();
            $moduleStates = [
                'active' => ['description' => 'activé', 'color' => 'green'],
                'not_active' =>['description' => 'non activé', 'color' => 'yellow'],
                'not_installed' => ['description' => 'non installé', 'color' => 'yellow'],
                'not_found' => ['description' => 'non trouvé', 'color' => 'red'],
                'invalid_module' => ['description' => 'module invalide', 'color' => 'red'],
                'invalid_ini' => ['description' => 'module.ini invalide', 'color' => 'red'],
                'invalid_omeka_version' => ['description' => 'version de omeka invalide', 'color' => 'red'],
                'needs_upgrade' => ['description' => 'mise à niveau nécessaire', 'color' => 'orange'],
            ];
            $state = $module->getState();
            $stateInfo = $moduleStates[$state];
            $description =  $stateInfo['description'];
            $color =  $stateInfo['color'];
            $ini = $module->getIni();
            $version = $ini['version'];
            $output->writeln(sprintf("%s - v%s\n %s", $name, $version, $description));
        }
        return Command::SUCCESS;
    }
}
