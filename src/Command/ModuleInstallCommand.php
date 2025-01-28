<?php

namespace Omeka\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ModuleInstallCommand extends Command
{
    protected static $defaultName = 'module:install';

    protected function configure()
    {
        $this->setDescription('Install a module');
        $this->addArgument('module-name', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $omekaHelper = $this->getHelper('omeka');
        $application = $omekaHelper->getApplication();

        $services = $application->getServiceManager();

        $moduleManager = $services->get('Omeka\ModuleManager');

        $moduleName = $input->getArgument('module-name');
        $module = $moduleManager->getModule($moduleName);
        if (!$module) {
            $output->writeln(sprintf("Error: module '%s' does not exist", $moduleName));
            return Command::FAILURE;
        }

        $moduleIsInstalled = $module->getState() === \Omeka\Module\Manager::STATE_ACTIVE
            || $module->getState() === \Omeka\Module\Manager::STATE_NOT_ACTIVE
            || $module->getState() === \Omeka\Module\Manager::STATE_NEEDS_UPGRADE;

        if ($moduleIsInstalled) {
            $output->writeln(sprintf("Module '%s' is already installed", $moduleName));
            return Command::SUCCESS;
        }

        try {
            $omekaHelper->loginAsAdmin();
            $moduleManager->install($module);
        } catch (\Exception $e) {
            $output->writeln(sprintf("Error: module '%s' was not installed. Reason: %s", $moduleName, $e->getMessage()));
            return Command::FAILURE;
        }

        $output->writeln(sprintf("Module '%s' installed", $moduleName));

        return Command::SUCCESS;
    }
}
