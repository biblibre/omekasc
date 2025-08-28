<?php

namespace Omeka\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpgradeCommand extends Command
{
    protected static $defaultName = 'upgrade';

    protected function configure()
    {
        $this->setDescription('Omeka S upgrade');
        $this->addArgument('tag', InputArgument::REQUIRED, 'Targeted Omeka S tag');
        $this->addOption('branch', 'b', InputOption::VALUE_REQUIRED, 'Targeted Omeka S branch');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $omekaHelper = $this->getHelper('omeka');
        $omekaPath = $omekaHelper->getOmekaPath();
        $tag = $input->getArgument('tag');
        $branch = $input->getOption('branch');

        if (!$omekaPath) {
            $output->writeln($this->formatMessage("Not in Omeka S directory", 'error'));
            return COMMAND::FAILURE;
        }

        chdir($omekaPath);

        $output->writeln($this->formatMessage("Omeka S will be upgraded on %s", 'info', $tag));
        $omekasRepo = $this->updateGitRemote($omekaHelper->getOmekaPath(), "https://github.com/omeka/omeka-s", $output);
        if (!$omekasRepo) {
            return COMMAND::FAILURE;
        }
        $tags = $this->executeShell(['git', 'tag']);

        if ($branch) {
            $remoteBranches = $this->executeShell(['git', 'branch', '-r', "--format=%(refname:short)"]);
            if (in_array("origin/$branch", $remoteBranches)) {
                $this->executeShell(['git', 'switch', '-C', $branch, "origin/$branch"]);
                if (in_array($tag, $tags)) {
                    $output->writeln($this->formatMessage("Omeka S will be now reset on %s tag", 'info', $tag));
                    $this->executeShell(['git', 'reset', '--hard', $tag]);
                } else {
                    $output->writeln($this->formatMessage("%s tag does not exist in remote", 'error', $tag));
                    return COMMAND::FAILURE;
                }
                $this->executeShell(['git', 'branch', "--set-upstream-to=origin/$branch", $branch]);
            } else {
                $output->writeln($this->formatMessage("%s branch does not exist in origin", 'error', $branch));
                return COMMAND::FAILURE;
            }
        } else {
            if (in_array($tag, $tags)) {
                $output->writeln($this->formatMessage("Omeka S will be now reset on %s tag", 'info', $tag));
                $this->executeShell(['git', 'reset', '--hard', $tag]);
            } else {
                $output->writeln($this->formatMessage("%s tag does not exist in remote", 'error', $tag));
                return COMMAND::FAILURE;
            }
        }

        try {
            $this->installOmekasDependencies($output);
        } catch (\Exception $e) {
            $output->writeln($this->formatMessage("An error occurred: %s", 'error', $e->getMessage()));
            return COMMAND::FAILURE;
        }

        $completeVersion = isset($branch) ? "$branch ($tag)" : $tag;
        $output->writeln($this->formatMessage("Omeka S successfully upgraded to %s", 'info', $completeVersion));

        return Command::SUCCESS;
    }

    private function updateGitRemote($directory, $url, OutputInterface $output)
    {
        if (!is_dir("$directory/.git")) {
            $output->writeln($this->formatMessage("Not a git repository", 'warning'));
            return false;
        }

        $repoStatus = $this->executeShell(['git', 'status', '--porcelain']);
        if ($repoStatus) {
            $statusInfo = implode("\n", $repoStatus);
            $output->writeln($this->formatMessage("Repository has uncommitted changes:\n%s", 'warning', $statusInfo));
            return false;
        }

        $this->executeShell(['git', 'remote', 'remove', 'origin']);
        $this->executeShell(['git', 'remote', 'add', 'origin', $url]);
        $this->executeShell(['git', 'fetch', 'origin', '--quiet']);

        return true;
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

    private function installOmekasDependencies(OutputInterface $output)
    {
        $outputInstall = [];
        $returnVarInstall = 0;
        exec('npm install', $outputInstall, $returnVarInstall);

        if ($returnVarInstall === 0) {
            $output->writeln($this->formatMessage("Command npm install succeeded.", 'info'));
        } else {
            throw new \Exception("Error during npm install: " . implode("\n", $outputInstall));
        }

        $outputGulp = [];
        $returnVarGulp = 0;
        exec('npx gulp deps', $outputGulp, $returnVarGulp);

        if ($returnVarGulp === 0) {
            $output->writeln($this->formatMessage("Command npx gulp deps succeeded.", 'info'));
        } else {
            throw new \Exception("Error during npx gulp deps: " . implode("\n", $outputGulp));
        }
    }

    private function executeShell(array $argv): array
    {
        $command = implode(' ', array_map('escapeshellarg', $argv));

        $output = null;
        $result_code = null;
        exec($command, $output, $result_code);

        if ($result_code !== 0) {
            throw new \Exception(sprintf('Command failed: %s', $command));
        }

        return $output;
    }
}
