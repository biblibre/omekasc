<?php

namespace Omeka\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class ModulesListCommand extends Command
{
    protected static $defaultName = 'modules:list';

    protected function configure()
    {
        $this->setDescription('Modules List');
        $this->setHelp('This command lists all the modules installed on Omeka S.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $omekaHelper = $this->getHelper('omeka');
        $application = $omekaHelper->getApplication();
        $services = $application->getServiceManager();
        $modules = $services->get('Omeka\ModuleManager')->getModules();

        $color = [];

        foreach ( $color as $description) {
            $outputStyle = new OutputFormatterStyle($color);
            $output->getFormatter()->setStyle($color, $outputStyle);
            $output->writeln(sprintf('<%s>%s</%s>', $color, $description, $color));
        }

        $output->writeln('');

        foreach ($modules as $module) {
            $name = $module->getName();
            $moduleStates = [
                'active' => ['description' => 'activé', 'color' => 'green'],
                'not_active' => ['description' => 'not activé', 'color' => 'yellow'],
                'not_installed' => ['description' => 'not installed', 'color' => 'yellow'],
                'not_found' => ['description' => 'not found', 'color' => 'red'],
                'invalid_module' => ['description' => 'invalid module', 'color' => 'red'],
                'invalid_ini' => ['description' => 'invalid module.ini', 'color' => 'red'],
                'invalid_omeka_version' => ['description' => 'invalid omeka version', 'color' => 'red'],
                'needs_upgrade' => ['description' => 'upgrade needed', 'color' => 'magenta'],
            ];
            $state = $module->getState();
            $stateInfo = $moduleStates[$state];
            $description = $stateInfo['description'];
            $color = $stateInfo['color'];
            $ini = $module->getIni();
            $version = $ini['version'];

            $outputStyle = new OutputFormatterStyle($color);
            $output->getFormatter()->setStyle($state, $outputStyle);

            $output->writeln(sprintf('<%s>%s - %s (%s)</>', $state, $name, $state, $version));
        }

        return Command::SUCCESS;
    }
}
