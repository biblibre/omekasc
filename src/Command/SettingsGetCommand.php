<?php

namespace Omeka\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class SettingsGetCommand extends Command
{
    protected static $defaultName = 'settings:get';

    protected function configure()
    {
        $this->setDescription('List Omeka S settings');
        $this->addArgument('setting-name', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $omekaHelper = $this->getHelper('omeka');
        $application = $omekaHelper->getApplication();

        $services = $application->getServiceManager();

        $settings = $services->get('Omeka\Settings');

        $settingName = $input->getArgument('setting-name');
        $value = $settings->get($settingName);
        if (!isset($value)) {
            $output->writeln(sprintf("Error: setting '%s' does not exist", $settingName));
            return Command::FAILURE;
        }

        $output->writeln($value);

        return Command::SUCCESS;
    }
}
