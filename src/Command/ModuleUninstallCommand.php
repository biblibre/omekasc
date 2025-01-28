<?php

namespace Omeka\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ModuleUninstallCommand extends Command
{
    protected static $defaultName = 'module:uninstall';

    protected function configure()
    {
        $this->setDescription('Uninstall a module');
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

        if ($module->getState() === \Omeka\Module\Manager::STATE_NOT_INSTALLED) {
            $output->writeln(sprintf("Module '%s' is already not installed", $moduleName));
            return Command::SUCCESS;
        }

        try {
            $omekaHelper->loginAsAdmin();
            $moduleManager->uninstall($module);
        } catch (\Exception $e) {
            $output->writeln(sprintf("Error: module '%s' was not uninstalled. Reason: %s", $moduleName, $e->getMessage()));
            return Command::FAILURE;
        }

        $output->writeln(sprintf("Module '%s' uninstalled", $moduleName));

        return Command::SUCCESS;
    }
}
