<?php

namespace Omeka\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

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

        $legend = [
            'green' => 'activé',
            'yellow' => 'non activé / non installé',
            'red' => 'non trouvé / module invalide / module.ini invalide / version de omeka invalide',
            'magenta' => 'mise à niveau nécessaire',

        ];
        $output->writeln('Légende :');
        foreach ($legend as $color => $description) {
            $outputStyle = new OutputFormatterStyle($color);
            $output->getFormatter()->setStyle($color, $outputStyle);
            $output->writeln(sprintf('<%s>%s</%s>', $color, $description, $color));
        }

        $output->writeln('');

        foreach ($modules as $module) {
            $name = $module->getName();
            $moduleStates = [
                'active' => ['description' => 'activé', 'color' => 'green'],
                'not_active' => ['description' => 'non activé', 'color' => 'yellow'],
                'not_installed' => ['description' => 'non installé', 'color' => 'yellow'],
                'not_found' => ['description' => 'non trouvé', 'color' => 'red'],
                'invalid_module' => ['description' => 'module invalide', 'color' => 'red'],
                'invalid_ini' => ['description' => 'module.ini invalide', 'color' => 'red'],
                'invalid_omeka_version' => ['description' => 'version de omeka invalide', 'color' => 'red'],
                'needs_upgrade' => ['description' => 'mise à niveau nécessaire', 'color' => 'magenta'],
            ];
            $state = $module->getState();
            $stateInfo = $moduleStates[$state];
            $description = $stateInfo['description'];
            $color = $stateInfo['color'];
            $ini = $module->getIni();
            $version = $ini['version'];

            $outputStyle = new OutputFormatterStyle($color);
            $output->getFormatter()->setStyle($state, $outputStyle);

            $output->writeln(sprintf('<%s>%s - version(%s)</%s>', $state, $name, $version, $state));
        }

        return Command::SUCCESS;
    }
}
