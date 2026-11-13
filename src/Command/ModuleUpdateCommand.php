<?php

namespace Omeka\Console\Command;

use Composer\Semver\Semver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Omeka\Console\Style\OmekaStyle;

class ModuleUpdateCommand extends Command
{
    protected static $defaultName = 'module:update';

    protected function configure()
    {
        $this->setDescription('Update a module');

        $this->addArgument('module-name', InputArgument::REQUIRED);
        $this->addArgument('target-version', InputArgument::REQUIRED);

        $this->addOption('--git-remote-url', null, InputOption::VALUE_REQUIRED, 'URL of remote git repository. Guessed from local repository if not set');
        $this->addOption('--git-tag', null, InputOption::VALUE_REQUIRED, 'Git tag to use. Default: "v<target-version>"');
        $this->addOption('--no-upgrade', null, InputOption::VALUE_NONE, 'Do not run the module upgrade process after update');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $omekaHelper = $this->getHelper('omeka');
        $fs = $this->getHelper('fs');
        $moduleName = $input->getArgument('module-name');
        $targetVersion = $input->getArgument('target-version');
        $gitRemoteUrl = $input->getOption('git-remote-url');
        $gitTag = $input->getOption('git-tag') ?? "v$targetVersion";
        $noUpgrade = $input->getOption('no-upgrade');

        $io = (new OmekaStyle($input, $output))->getErrorStyle();

        $omekaPath = $omekaHelper->getOmekaPath();
        if ($omekaPath === null) {
            $io->error('This command should be run from inside an Omeka S directory');
            return Command::FAILURE;
        }

        if (!preg_match('/^[A-Za-z0-9_]+$/', $moduleName)) {
            $io->error(sprintf('Invalid module name: %s', $moduleName));
            return Command::FAILURE;
        }

        $modulesPath = $omekaPath . '/modules';
        $modulePath = $modulesPath . '/' . $moduleName;

        // Verify that $modulePath is an Omeka S module
        try {
            $this->verifyModule($modulePath);
        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        // Get module version from config/module.ini
        try {
            $iniVersion = $this->getIniValue($modulePath, 'version');
        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        // Make sure this is an update and not a downgrade
        if (version_compare($iniVersion, $targetVersion) > 0) {
            $io->error(sprintf("Installed version (%s) is greater than target version (%s).", $iniVersion, $targetVersion));
            return Command::FAILURE;
        }
        if (version_compare($iniVersion, $targetVersion) === 0) {
            $io->success("Module is already up-to-date.");
            return Command::SUCCESS;
        }

        // --git-remote-url was not given, try to guess it from existing git repository
        if (!$gitRemoteUrl) {
            try {
                $gitRemoteUrl = $this->guessRemoteUrl($modulePath);
            } catch (\Throwable $e) {
                $io->error($e->getMessage());
                return Command::FAILURE;
            }
        }

        // Clone the module into a temporary location
        if ($io->isVerbose()) {
            $io->info(sprintf('Cloning git repository from %s (tag: %s)', $gitRemoteUrl, $gitTag));
        }
        try {
            $tempClonePath = $this->cloneRemoteRepository($moduleName, $gitRemoteUrl, $gitTag);
        } catch (\Throwable $e) {
            $io->error(sprintf('Failed to clone git repository: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        // Check that the version in cloned repository's config/module.ini match $targetVersion
        try {
            $this->verifyModule($tempClonePath);
            $tempIniVersion = $this->getIniValue($tempClonePath, 'version');
        } catch (\Throwable $e) {
            $fs->rrm($tempClonePath);
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        if (version_compare($tempIniVersion, $targetVersion) !== 0) {
            $fs->rrm($tempClonePath);
            $io->error(sprintf("Version of cloned repository (%s) does not match target version (%s).", $tempIniVersion, $targetVersion));
            return Command::FAILURE;
        }

        // Check that the new version is compatible with the installed Omeka S version
        try {
            $omekaVersionConstraint = $this->getIniValue($tempClonePath, 'omeka_version_constraint');
        } catch (\Throwable $e) {
            // No constraint means it is compatible with all versions
            $omekaVersionConstraint = null;
        }

        if ($omekaVersionConstraint) {
            // getOmekaVersion loads Omeka's autoloader, which makes Semver available
            $omekaVersion = $omekaHelper->getOmekaVersion();
            if (!Semver::satisfies($omekaVersion, $omekaVersionConstraint)) {
                $fs->rrm($tempClonePath);
                $io->error(sprintf("Omeka S version (%s) does not satisfy cloned repository's omeka_version_constraint (%s).", $omekaVersion, $omekaVersionConstraint));
                return Command::FAILURE;
            }
        }

        // Archive the existing module
        $dataDir = getenv('XDG_DATA_HOME') ?: sprintf('%s/.local/share', getenv('HOME'));
        $archiveDir = "$dataDir/omekasc/archive";
        $archivePath = sprintf(
            '%s/module-%s-%s-%s',
            $archiveDir,
            $moduleName,
            date('YmdHis'),
            sodium_bin2base64(random_bytes(3), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING)
        );

        if (!is_dir($archiveDir)) {
            if (false === mkdir($archiveDir, 0777, true)) {
                $io->error(sprintf("Failed to create archive directory %s", $archiveDir));
                $fs->rrm($tempClonePath);
                return Command::FAILURE;
            }
        }

        try {
            $fs->move($modulePath, $archivePath);
        } catch (\Throwable $e) {
            $fs->rrm($tempClonePath);
            $io->error(sprintf("Failed to move %s to %s", $modulePath, $archivePath));
            return Command::FAILURE;
        }

        try {
            $fs->move($tempClonePath, $modulePath);
        } catch (\Throwable $e) {
            $fs->rrm($tempClonePath);
            $io->error(sprintf("Failed to move %s to %s. Restoring archive %s.", $tempClonePath, $modulePath, $archivePath));
            try {
                if (file_exists($modulePath)) {
                    $fs->rrm($modulePath);
                }
                $fs->move($archivePath, $modulePath);
            } catch (\Throwable $e) {
                $io->error(sprintf("Failed to restore archive %s. MANUAL INTERVENTION IS REQUIRED.", $archivePath));
                return Command::FAILURE;
            }

            return Command::FAILURE;
        }

        if ($io->isVerbose()) {
            $io->info(sprintf("Module archived in %s", $archivePath));
        }

        // Install dependencies
        if (file_exists("$modulePath/composer.json")) {
            $composerPath = $omekaPath . '/build/composer.phar';
            try {
                $this->shellExec(['php', $composerPath, 'install', '--no-interaction', '--quiet', '--no-dev', '--working-dir', $modulePath]);
                if ($io->isVerbose()) {
                    $io->info('Composer dependencies installed');
                }
            } catch (\Throwable $e) {
                $io->error(sprintf("Failed to install composer dependencies: %s. MANUAL INTERVENTION IS REQUIRED.", $e->getMessage()));
                return Command::FAILURE;
            }
        }

        if ($noUpgrade) {
            $io->success(sprintf('Module %s updated to %s (not upgraded)', $moduleName, $targetVersion));
            return Command::SUCCESS;
        }

        $application = $omekaHelper->getApplication();
        $services = $application->getServiceManager();
        $moduleManager = $services->get('Omeka\ModuleManager');

        $module = $moduleManager->getModule($moduleName);
        if (!$module) {
            $io->error("Module does not exist in module manager");
            return Command::FAILURE;
        }

        if ($module->getState() === \Omeka\Module\Manager::STATE_NOT_INSTALLED) {
            if ($io->isVerbose()) {
                $io->info("Upgrade process skipped because module is not installed");
            }
            $io->success(sprintf('Module %s updated to %s', $moduleName, $targetVersion));
            return Command::SUCCESS;
        }

        if ($module->getState() === \Omeka\Module\Manager::STATE_ACTIVE || $module->getState() === \Omeka\Module\Manager::STATE_NOT_ACTIVE) {
            $io->warning(sprintf("Module state after update is '%s' but it should be '%s'", $module->getState(), \Omeka\Module\Manager::STATE_NEEDS_UPGRADE));
            $io->success(sprintf('Module %s updated to %s', $moduleName, $targetVersion));
            return Command::SUCCESS;
        }

        try {
            $omekaHelper->loginAsAdmin();
            $moduleManager->upgrade($module);
        } catch (\Throwable $e) {
            $io->error(sprintf("Module '%s' was not upgraded: %s", $moduleName, $e->getMessage()));
            return Command::FAILURE;
        }

        $io->success(sprintf('Module %s updated to %s', $moduleName, $targetVersion));

        return Command::SUCCESS;
    }

    protected function shellExec(array $argv): array
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

    protected function getIniValue(string $modulePath, string $key)
    {
        $iniFilepath = $modulePath . '/config/module.ini';
        $config = parse_ini_file($iniFilepath, process_sections: true);
        if ($config === false) {
            throw new \Exception(sprintf("Failed to parse INI file '%s'", $iniFilepath));
        }

        $value = $config['info'][$key] ?? null;
        if ($value === null) {
            throw new \Exception(sprintf("Cannot find '%s' in INI file '%s'", $key, $iniFilepath));
        }

        return $value;
    }

    protected function cloneRemoteRepository(string $moduleName, string $gitRemoteUrl, string $gitTag): string
    {
        $tempClonePath = null;
        try {
            [$tempClonePath] = $this->shellExec(['mktemp', '-d', '-p', sys_get_temp_dir(), sprintf('omekasc-%s.XXXXXX', $moduleName)]);
            chmod($tempClonePath, 0755);
            $this->shellExec(['git', 'clone', '--quiet', $gitRemoteUrl, $tempClonePath]);
            $this->shellExec(['git', '-C', $tempClonePath, 'reset', '--hard', $gitTag, '--']);
        } catch (\Throwable $e) {
            if ($tempClonePath !== null) {
                $fs = $this->getHelper('fs');
                $fs->rrm($tempClonePath);
            }

            throw $e;
        }

        return $tempClonePath;
    }

    protected function guessRemoteUrl(string $modulePath): string
    {
        if (!file_exists($modulePath . '/.git')) {
            throw new \Exception(sprintf("Cannot guess remote URL because path %s is not a git repository", $modulePath));
        }

        $remotes = $this->shellExec(['git', '-C', $modulePath, 'remote']);
        if (!$remotes) {
            throw new \Exception("Cannot guess remote URL because repository has no remotes");
        }

        if (in_array('origin', $remotes)) {
            $remote = 'origin';
        } else {
            $remote = $remotes[0];
        }
        [$gitRemoteUrl] = $this->shellExec(['git', '-C', $modulePath, 'remote', 'get-url', $remote]);
        if (!$gitRemoteUrl) {
            throw new \Exception(sprintf("Cannot guess remote URL because remote %s has no URL", $remote));
        }

        return $gitRemoteUrl;
    }

    protected function verifyModule(string $modulePath): void
    {
        if (!is_dir($modulePath)) {
            throw new \Exception(sprintf("Path '%s' does not exist or is not a directory", $modulePath));
        }

        // Verify that $modulePath is an Omeka S module
        foreach (['config/module.ini', 'Module.php'] as $file) {
            $filepath = $modulePath . '/' . $file;
            if (!file_exists($filepath)) {
                throw new \Exception(sprintf("Path '%s' is not a valid Omeka S module ('%s' missing)", $modulePath, $file));
            }
        }
    }
}
