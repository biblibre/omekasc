<?php

namespace Omeka\Console;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Omeka\Console\Style\OmekaStyle;

class Application extends \Symfony\Component\Console\Application
{
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $io = (new OmekaStyle($input, $output))->getErrorStyle();

        $path = $input->getParameterOption(['--working-dir', '-C'], default: null, onlyParams: true);
        if ($path !== null) {
            if (!is_dir($path)) {
                $io->error(sprintf("Path \"%s\" does not exists or is not a directory.", $path));
                return 1;
            }
            if (chdir($path) === false) {
                $io->error(sprintf("Failed to set current directory to \"%s\".", $path));
                return 1;
            }
        }

        return parent::doRun($input, $output);
    }

    protected function getDefaultInputDefinition()
    {
        $inputDefinition = parent::getDefaultInputDefinition();
        $inputDefinition->addOption(
            new InputOption(
                '--working-dir',
                '-C',
                InputOption::VALUE_REQUIRED,
                'Run as if omekasc was started in <WORKING-DIR>',
            )
        );

        return $inputDefinition;
    }
}
