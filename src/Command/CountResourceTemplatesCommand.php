<?php
namespace Omeka\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CountResourceTemplatesCommand extends Command
{
    protected static $defaultName = 'count:resource_templates';

    protected function configure()
    {
        $this->setDescription('Count Resource_Templates');
        $this->setHelp('this function allows you to list the number of resource models on omekas');
    }
    protected function execute(InputInterface $Input, OutputInterface $Output)
    {
        $omekaHelper = $this->getHelper('omeka');
        $application = $omekaHelper->getApplication();
        $services = $application->getServiceManager();
        $api = $services->get('Omeka\ApiManager');

        $authentication = $services->get('Omeka\AuthenticationService');
        $reponse = $api->search('resource_templates');
        $resource = $reponse->getcontent();
        $resourceCount = count($resource);

        $Output->writeln('Number of resource templates : ' . $resourceCount);
        return Command::SUCCESS;
    }
}