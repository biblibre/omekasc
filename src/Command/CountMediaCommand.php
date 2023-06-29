<?php
namespace Omeka\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CountMediaCommand extends Command
{
    protected static $defaultName = 'count:media';

    protected function configure()
    {
        $this->setDescription('Count Media');
        $this->setHelp('this function allows you to list the number of media on omekaS');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $omekaHelper = $this->getHelper('omeka');
        $application = $omekaHelper->getApplication();
        $services = $application->getServiceManager();
        $api = $services->get('Omeka\ApiManager');

        $authentication = $services->get('Omeka\AuthenticationService');
        $reponse = $api->search('media');
        $media = $reponse->getcontent();
        $mediaCount = count($media);

        $output->writeln('Nombre de media : ' . $mediaCount);
        return Command::SUCCESS;

    }
}