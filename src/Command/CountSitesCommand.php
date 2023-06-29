<?php
namespace Omeka\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CountSitesCommand extends Command
{
    protected static $defaultName = 'count:sites';

    protected function configure()
    {
        $this->setDescription('Count Sites');
        $this->setHelp('this function allows you to list the number of sites on omekas');
    }
    protected function execute(InputInterface $Input, OutputInterface $Output)
    {
        $omekaHelper = $this->getHelper('omeka');
        $application = $omekaHelper->getApplication();
        $services = $application->getServiceManager();
        $api = $services->get('Omeka\ApiManager');

        $authentication = $services->get('Omeka\AuthenticationService');
        $reponse = $api->search('sites');
        $sites = $reponse->getcontent();
        $sitesCount = count($sites);

        $Output->writeln('Number of sites : ' . $sitesCount);
        return Command::SUCCESS;
    }
}