<?php
namespace Omeka\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CountVocabulariesCommand extends Command
{
    protected static $defaultName = 'count:vocabularies';

    protected function configure()
    {
        $this->setDescription('Count vocabularies');
        $this->setHelp('this function allows you to list the number of vocabulary on omekaS');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $omekaHelper = $this->getHelper('omeka');
        $application = $omekaHelper->getApplication();
        $services = $application->getServiceManager();
        $api = $services->get('Omeka\ApiManager');

        $authentication = $services->get('Omeka\AuthenticationService');
        $reponse = $api->search('vocabularies');
        $vocabularies = $reponse->getcontent();
        $vocabulariesCount = count($vocabularies);

        $output->writeln('Nombre de vocabularies : ' . $vocabulariesCount);
        return Command::SUCCESS;

    }
}