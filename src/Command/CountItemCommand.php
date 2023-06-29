<?php

namespace Omeka\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CountItemCommand extends Command
{
    protected static $defaultName = 'count:item';

    protected function configure()
    {
        $this->setDescription('Count Item');
        $this->setHelp('allows you to list content on omeka S');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {   

        $omekaHelper = $this->getHelper('omeka');
        $application = $omekaHelper->getApplication();
        $services = $application->getServiceManager();
        $api = $services->get('Omeka\ApiManager');

        $authentication = $services->get('Omeka\AuthenticationService');
        $response = $api->search('items');
        $items = $response->getContent();
        $contentCount = count($items);

        $output->writeln('Nombre de contenus : ' . $contentCount);

        return Command::SUCCESS;

    }
}
