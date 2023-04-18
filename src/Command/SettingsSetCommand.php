<?php

namespace Omeka\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\AsCommand;




class SettingsSetCommand extends Command
{
    protected static $defaultName = 'settings:set';

    protected function configure()
    {
        $this->setDescription('Define Omeka S settings');
        $this->addOption('setting-name','', InputOption::VALUE_REQUIRED);
        $this->addOption('setting-value','', InputOption::VALUE_REQUIRED);
    }
    
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        
        $settingName = $input->getOption('setting-name');
        if(null === $settingName){
            $settingName = $io->ask('What setting do you want to change', $settingName, function($settingName){
                return($settingName);
            });
            $input->setOption('setting-name', $settingName);
        }
        
    }
        protected function execute(InputInterface $input, OutputInterface $output){
               $omekaHelper = $this->getHelper('omeka');
               $application = $omekaHelper->getApplication();
               $services = $application->getServiceManager();
               $settings = $services->get('Omeka\Settings');
               $settingName = $input->getOption('setting-name');
               $value = $settings->get($settingName);

               $io = new SymfonyStyle($input, $output);
              
                $newValue = $io->ask("$value is the new value:",null, function($newValue) use($settings, $settingName){
                    $settings->set($settingName,$newValue);
                    return Command::SUCCESS;
                   
                });
               
              return Command::SUCCESS;
      
     }
}