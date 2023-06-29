<?php
namespace Omeka\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CountItem_SetsCommand extends Command
{
    protected static $defaultName = 'count:item_sets';

    protected function configure()
    {
        $this->setDescription('Count Item_Sets');
        $this->setHelp('this function is used to list the number of collections on omekaS');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $omekaHelper = $this->getHelper('omeka');
        $application = $omekaHelper->getApplication();
        $services = $application->getServiceManager();
        $api = $services->get('Omeka\ApiManager');

        $authentication = $services->get('Omeka\AuthenticationService');
        $response = $api->search('item_sets');
        $item_sets = $response->getContent();
        $itemCount = count($item_sets);

        $output->writeln('Nombre de collection : ' . $itemCount);

        return Command::SUCCESS;
        
    }
}