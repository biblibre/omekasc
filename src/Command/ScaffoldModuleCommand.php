<?php

namespace Omeka\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ScaffoldModuleCommand extends Command
{
    protected static $defaultName = 'scaffold:module';

    protected function configure()
    {
        $this->setDescription('Generates files for a new module');
        $this->addOption('name', '', InputOption::VALUE_REQUIRED, 'The name of the module');
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io = new SymfonyStyle($input, $output);

        $name = $input->getOption('name');
        if (null === $name) {
            $name = $io->ask('Name of the new module', $name, function ($name) {
                return $this->validateName($name);
            });
            $input->setOption('name', $name);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $name = $this->validateName($input->getOption('name'));
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $omekaHelper = $this->getHelper('omeka');
        $omekaPath = $omekaHelper->getOmekaPath();
        if (!$omekaPath) {
            $io->error('This command should be run inside Omeka directory');

            return Command::FAILURE;
        }

        $modulePath = "$omekaPath/modules/$name";
        if (file_exists($modulePath)) {
            $io->error("Path '$modulePath' already exists");

            return Command::FAILURE;
        }

        if (!mkdir($modulePath)) {
            $io->error("Cannot create directory '$modulePath'");

            return Command::FAILURE;
        }

        $configPath = "$modulePath/config";
        if (!mkdir($configPath)) {
            $io->error("Cannot create directory '$configPath'");

            return Command::FAILURE;
        }

        $moduleClassPath = "$modulePath/Module.php";
        if (false === file_put_contents($moduleClassPath, $this->getModuleCode($name))) {
            $io->error("Cannot write to $moduleClassPath");

            return Command::FAILURE;
        }

        $moduleConfigPath = "$configPath/module.config.php";
        if (false === file_put_contents($moduleConfigPath, $this->getModuleConfig($name))) {
            $io->error("Cannot write to $moduleConfigPath");

            return Command::FAILURE;
        }

        $moduleIniPath = "$configPath/module.ini";
        if (false === file_put_contents($moduleIniPath, $this->getModuleIni($name))) {
            $io->error("Cannot write to $moduleIniPath");

            return Command::FAILURE;
        }

        $io->success("Module $name created in '$modulePath'");

        return Command::SUCCESS;
    }

    protected function validateName(?string $name)
    {
        $name = trim($name);
        if (empty($name)) {
            throw new \Exception('Name cannot be empty');
        }

        if (!preg_match('/^[A-Z][0-9A-Za-z]*$/', $name)) {
            throw new \Exception('Name must contain only alphanumeric characters, and must start with a capital letter');
        }

        return $name;
    }

    protected function getModuleCode(string $moduleName): string
    {
        $majorVersion = $this->getHelper('omeka')->getOmekaMajorVersion();
        $framework = $majorVersion < 3 ? 'Zend' : 'Laminas';

        return <<<EOF
<?php

namespace $moduleName;

use Omeka\Module\AbstractModule;
use $framework\EventManager\SharedEventManagerInterface;
use $framework\Mvc\Controller\AbstractController;
use $framework\Mvc\MvcEvent;
use $framework\ServiceManager\ServiceLocatorInterface;
use $framework\View\Renderer\PhpRenderer;

class Module extends AbstractModule
{
    public function onBootstrap(MvcEvent \$event)
    {
        parent::onBootstrap(\$event);
    }

    public function install(ServiceLocatorInterface \$serviceLocator)
    {
    }

    public function uninstall(ServiceLocatorInterface \$serviceLocator)
    {
    }

    public function getConfigForm(PhpRenderer \$renderer)
    {
    }

    public function handleConfigForm(AbstractController \$controller)
    {
    }

    public function attachListeners(SharedEventManagerInterface \$sharedEventManager)
    {
    }

    public function getConfig()
    {
        return require __DIR__ . '/config/module.config.php';
    }
}
EOF;
    }

    protected function getModuleConfig(string $moduleName): string
    {
        return <<<EOF
<?php

namespace $moduleName;

return [
];
EOF;
    }

    protected function getModuleIni(string $moduleName): string
    {
        $majorVersion = $this->getHelper('omeka')->getOmekaMajorVersion();

        return <<<EOF
[info]
name         = "$moduleName"
version      = "0.1.0"
author       = ""
configurable = false
description  = ""
module_link  = "https://github.com/USER/omeka-s-module-$moduleName"
author_link  = ""
omeka_version_constraint = "^$majorVersion.0.0"
EOF;
    }
}
