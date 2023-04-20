<?php

namespace Omeka\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;




class SettingsSetCommand extends Command
{
    protected static $defaultName = 'settings:set';

    protected function configure()
    {
        $this->setDescription('Define Omeka S settings');
        $this->addArgument('setting-name', InputArgument::OPTIONAL);
        $this->addArgument('setting-value', InputArgument::OPTIONAL);
    }
    
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        
        $settingName = $input->getArgument('setting-name');
        if(null === $settingName){
            $settingName = $io->ask('What setting do you want to change');
            $input->setArgument('setting-name', $settingName);
        }
        $settingValue = $input->getArgument('setting-value');
        if(null === $settingValue){
            $settingValue = $io->ask('New Value');
            $input->setArgument('setting-value', $settingValue);
        }
        
    }
        protected function execute(InputInterface $input, OutputInterface $output){
               $omekaHelper = $this->getHelper('omeka');
               $application = $omekaHelper->getApplication();
               $services = $application->getServiceManager();
               $settings = $services->get('Omeka\Settings');
               $settingName = $input->getArgument('setting-name');
               $settingValue = $input->getArgument('setting-value');
               $settings->set($settingName, $settingValue);
              
                
                    return Command::SUCCESS;
      
     }
}