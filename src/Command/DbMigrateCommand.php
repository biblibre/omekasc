<?php

namespace Omeka\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DbMigrateCommand extends Command
{
    protected static $defaultName = 'db:migrate';

    protected function configure()
    {
        $this->setDescription('Run pending database migrations');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $omekaHelper = $this->getHelper('omeka');
        $application = $omekaHelper->getApplication();

        $services = $application->getServiceManager();
        $logger = $services->get('Omeka\Logger');
        $environment = $services->get('Omeka\Environment');
        $migrationManager = $services->get('Omeka\MigrationManager');
        $status = $services->get('Omeka\Status');
        $settings = $services->get('Omeka\Settings');

        if (!$status->needsMigration()) {
            $output->writeln('No migration needed');
            return Command::SUCCESS;
        }

        if (!$environment->isCompatible()) {
            $output->writeln('Error: There are environment errors that must be resolved before you can update the database.');
            foreach ($environment->getErrorMessages() as $errorMessage) {
                $output->writeln($errorMessage);
            }
            return Command::FAILURE;
        }

        $migrationManager->upgrade();
        $settings->set('version', $status->getVersion());
        $output->writeln('Migration successful');

        return Command::SUCCESS;
    }
}
