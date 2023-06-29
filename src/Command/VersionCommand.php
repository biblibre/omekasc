<?php

namespace Omeka\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VersionCommand extends Command
{
    protected static $defaultName = 'version:command';

    protected function configure()
    {
        $this->setDescription('Version Command');
        $this->setHelp('this command allows you to list the versions on omeka S');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $omekaHelper = $this->getHelper('omeka');
        $application = $omekaHelper->getApplication();
        $services = $application->getServiceManager();
        $api = $services->get('Omeka\ApiManager');
        
        
        $version = $application->getVersion();

        $output->writeln(sprintf("Version d'Omeka : (%s)", $version));

        return Command::SUCCESS;
    }
}
