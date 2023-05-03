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
                'active' => 'activé',
                'not_active' => 'non activé',
                'not_installed' => 'non installé',
                'not_found' => 'non trouvé',
                'invalid_module' => 'module invalide',
                'invalid_ini' => 'module.ini invalide',
                'invalid_omeka_version' => 'version omeka invalide',
                'needs_upgrade' => 'necessite une mise à niveau',
            ];
            $state = $module->getState();
            $description = $moduleStates[$state];
            $ini = $module->getIni();
            $version = $ini['version'];
            $output->writeln(sprintf("%s - v%s\n %s", $name, $version, $description));
        }
        return Command::SUCCESS;
    }
}
