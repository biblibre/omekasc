<?php

namespace Omeka\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Yaml\Yaml;
use CzProject\GitPhp\Git;
use CzProject\GitPhp\GitRepository;


class BatchUpdateCommand extends Command
{
    protected static $defaultName = 'batch:update';

    protected function configure()
    {
        $this->setDescription('Modules and themes update from yaml config file');
        $this->addArgument('yaml-file', InputArgument::REQUIRED, 'The path to the YAML file containing module information');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $omekaHelper = $this->getHelper('omeka');
        $application = $omekaHelper->getApplication();
        $yamlFile = $input->getArgument('yaml-file');

        if (!file_exists($yamlFile)) {
            $output->writeln($this->formatMessage("Error: file '%s' does not exist", 'error', $yamlFile));
            return Command::FAILURE;
        }

        $data = Yaml::parseFile($yamlFile);

        // Disable version notification
        $settingInput = [
            'command' => 'settings:set',
            'setting-name' => 'version_notifications',
            'setting-value' => '0'
        ];
        $this->executeCommand('settings:set', $settingInput, $output);

        if (isset($data['modules'])) {
            if (isset($data['modules']['new'])) {
                $newModules = $data['modules']['new'];
                foreach ($newModules as $newModuleData) {
                    $name = $newModuleData['name'];
                    $remoteUrl = $newModuleData['git_remote_url'];
                    $tag = $newModuleData['tag'];

                    if (!$this->addRemote('module', $name, $remoteUrl, $tag, $output)) {
                        continue;
                    };
                }
            }
            if (isset($data['modules']['upgrade'])) {
                $upgradeModules = $data['modules']['upgrade'];
                foreach ($upgradeModules as $upgradeModuleData) {
                    $name = $upgradeModuleData['name'];
                    $remoteUrl = $upgradeModuleData['git_remote_url'];
                    $tag = $upgradeModuleData['tag'];

                    if (!$this->updateRemote('module', $name, $remoteUrl, $tag, $output)) {
                        continue;
                    };
                }
            }
        }

        if (isset($data['themes'])) {
            if (isset($data['themes']['new'])) {
                $newThemes = $data['themes']['new'];
                foreach ($newThemes as $newThemeData) {
                    $name = $newThemeData['name'];
                    $remoteUrl = $newThemeData['git_remote_url'];
                    $tag = $newThemeData['tag'];

                    if (!$this->addRemote('theme', $name, $remoteUrl, $tag, $output)) {
                        continue;
                    };
                }
            }
            if (isset($data['themes']['upgrade'])) {
                $upgradeThemes = $data['themes']['upgrade'];
                foreach ($upgradeThemes as $upgradeThemeData) {
                    $name = $upgradeThemeData['name'];
                    $remoteUrl = $upgradeThemeData['git_remote_url'];
                    $tag = $upgradeThemeData['tag'];

                    if (!$this->updateRemote('theme', $name, $remoteUrl, $tag, $output)) {
                        continue;
                    };
                }
            }
        }

        $this->executeCommand('db:migrate', ['command' => 'db:migrate'], $output);

        if (!empty($data['modules']['new'])) {
            $newModules = $data['modules']['new'];
            foreach ($newModules as $newModuleData) {
                $name = $newModuleData['name'];
                $this->executeCommand('module:install', ['command' => 'module:install', 'module-name' => $name], $output);
            }
        }

        if (!empty($data['modules']['upgrade'])) {
            $upgradeModules = $data['modules']['upgrade'];
            foreach ($upgradeModules as $upgradeModuleData) {
                $name = $upgradeModuleData['name'];
                $this->executeCommand('module:upgrade', ['command' => 'module:upgrade', 'module-name' => $name], $output);
            }
        }

        return Command::SUCCESS;
    }

    private function addRemote($type, $name, $remoteUrl, $tag, OutputInterface $output)
    {
        $output->writeln(sprintf("Cloning %s %s (%s %s)", $type, $name, $remoteUrl, $tag));

        $git = new Git();
        $componentPath = OMEKA_PATH . "/{$type}s/$name";

        try {
            $repo = $git->cloneRepository($remoteUrl, $componentPath);
        } catch (\Exception $e) {
            $output->writeln($this->formatMessage("Error cloning %s '%s': %s", 'error', $type, $name, $e->getMessage()));
            return false;
        }

        if ($tag) {
            try {
                $repo->execute('reset', '--hard', $tag);
            } catch (\Exception $e) {
                $output->writeln($this->formatMessage("Error resetting %s '%s' to tag '%s': %s", 'error', $type, $name, $tag, $e->getMessage()));
                return false;
            }
        }

        if ($type == 'module' && file_exists("$componentPath/composer.json")) {
            putenv("COMPOSER_DISCARD_CHANGES=true");
            exec(OMEKA_PATH . "/build/composer.phar --working-dir=\"$componentPath\" install --no-dev --no-interaction", $composerOutput, $composerReturnVar);
            if ($composerReturnVar !== 0) {
                $output->writeln($this->formatMessage("Error installing dependencies for %s '%s': %s", 'error', $type, $name, implode("\n", $composerOutput)));
                return false;
            }
        }

        $output->writeln($this->formatMessage("%s '%s' cloned and set up successfully.", 'success', ucfirst($type), $name));
        return true;
    }

    private function updateRemote($type, $name, $remoteUrl, $tag, OutputInterface $output)
    {
        $output->writeln(sprintf("Updating %s %s (%s, %s)", $type, $name, $tag, $remoteUrl));

        $componentPath = OMEKA_PATH . "/{$type}s/$name";

        $repo = $this->updateGitRemote($componentPath, $remoteUrl, $output);

        if ($repo) {
            if ($tag) {
                try {
                    $repo->execute('reset', '--hard', $tag);
                } catch (\Exception $e) {
                    $output->writeln($this->formatMessage("Error resetting %s '%s' to tag '%s': %s", 'error', $type, $name, $tag, $e->getMessage()));
                    return false;
                }
            }

            if ($type == 'module' && file_exists("$componentPath/composer.json")) {
                putenv("COMPOSER_DISCARD_CHANGES=true");
                exec(OMEKA_PATH . "/build/composer.phar --working-dir=\"$componentPath\" install --no-dev --no-interaction", $composerOutput, $composerReturnVar);
                if ($composerReturnVar !== 0) {
                    $output->writeln($this->formatMessage("Error installing dependencies for %s '%s': %s", 'warning', $type, $name, implode("\n", $composerOutput)));
                    return false;
                }
            }

            $output->writeln($this->formatMessage("%s '%s' updated successfully.", 'success', ucfirst($type), $name));
            return true;
        } else {
            $output->writeln($this->formatMessage("%s '%s' updated failed.", 'error', ucfirst($type), $name));
        }
    }

    private function updateGitRemote($directory, $url, OutputInterface $output)
    {
        if (!is_dir("$directory/.git")) {
            $git = new Git();
            $repo = $git->init($directory);
        } else {
            $repo = new GitRepository($directory);
        }

        if ($repo->hasChanges()) {
            $status = $repo->execute('status');
            $statusInfo = implode("\n", $status);
            $output->writeln($this->formatMessage("Repository has uncommitted changes:\n%s", 'warning', $statusInfo));

            return false;
        }

        $remotes = $repo->execute('remote');
        if (in_array('origin', $remotes)) {
            $repo->setRemoteUrl('origin', $url);
        } else {
            $repo->addRemote('origin', $url);
        }

        $repo->execute('remote', 'update', 'origin', '--prune');

        return $repo;
    }

    private function formatMessage(string $message, string $level, ...$args): string
    {
        $colors = [
            'success' => '<fg=green>',
            'error' => '<fg=red>',
            'warning' => '<fg=yellow>',
            'info' => '<fg=blue>',
            'reset' => '</>',
        ];

        $formattedMessage = sprintf($message, ...$args);

        return $colors[$level] . $formattedMessage . $colors['reset'];
    }

    private function executeCommand($name, $arguments, OutputInterface $output)
    {
        $commandInput = new ArrayInput($arguments);
        $bufferedOutput = new BufferedOutput();
        $command = $this->getApplication()->find($name);

        $statusCode = $command->run($commandInput, $bufferedOutput);
        $colorStyle = 'success';
        if ($statusCode !== Command::SUCCESS) {
            $colorStyle = 'error';
        }

        $capturedOutput = $bufferedOutput->fetch();

        return $output->writeln($this->formatMessage(trim($capturedOutput), $colorStyle));
    }
}
