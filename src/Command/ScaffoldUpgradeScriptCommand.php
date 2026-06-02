<?php

namespace Omeka\Console\Command;

use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ScaffoldUpgradeScriptCommand extends Command
{
    protected static $defaultName = 'scaffold:upgrade-script';
    protected array $modulesToAdd = [];
    protected array $modulesToRemove = [];
    protected array $addons;

    protected function configure()
    {
        $this->setDescription('Generates script to upgrade Omeka S, modules and themes');
        $this->addOption('versions-url', '', InputOption::VALUE_REQUIRED, 'URL to a JSON file containing the list of wanted versions for each module');
        $this->addOption('add-module', '', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Name of a module to install');
        $this->addOption('del-module', '', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Name of a module to remove');
        $this->addOption('add-theme', '', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Name of a theme to install');
        $this->addOption('del-theme', '', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Name of a theme to remove');
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        $context = stream_context_create(['http' => ['method' => 'GET', 'header' => 'User-Agent: git/2.0']]);
        $versions_json = file_get_contents($input->getOption('versions-url'), false, $context);
        $versions = json_decode($versions_json, true);

        $moduleNames = array_map(fn($module) => $module['name'], $versions['modules']);
        $question = new Question('Module to remove ? ');
        $question->setAutocompleterValues($moduleNames);

        if (null === $input->getOption('del-module')) {
            $modulesToRemove = [];
            do {
                if ($moduleToRemove = $helper->ask($input, $output, $question)) {
                    $modulesToRemove[] = $moduleToRemove;
                }
            } while ($moduleToRemove);
            $input->setOption('del-module', $modulesToRemove);
        }

        $question = new Question('Module to add ? ');
        $question->setAutocompleterValues($moduleNames);

        if (null === $input->getOption('add-module')) {
            $helper = $this->getHelper('question');
            $modulesToAdd = [];
            do {
                if ($module = $helper->ask($input, $output, $question)) {
                    $modulesToAdd[] = $module;
                }
            } while ($module);
            $input->setOption('add-module', $modulesToAdd);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $omekaHelper = $this->getHelper('omeka');
        $omekaPath = $omekaHelper->getOmekaPath();
        if (!$omekaPath) {
            $io->error('This command should be run inside Omeka directory');

            return Command::FAILURE;
        }

        $modulesToRemove = $input->getOption('del-module');
        $modulesToAdd = $input->getOption('add-module');
        $modulesToUpdate = [];

        $script = <<<'SH'
        #!/bin/sh

        set -e

        OMEKA_PATH=${OMEKA_PATH:-$HOME/www}

        main () {

        SH;

        foreach ($modulesToRemove as $module) {
            $script .= <<<SH
                omekasc module:uninstall "$module"
                remove_folder "$module" modules

            SH;
        }

        $script .= <<<'SH'
            echo "Updating Omeka S to v4.1.1"
            update_git_remote "$OMEKA_PATH" "https://github.com/omeka/omeka-s"
            git -C "$OMEKA_PATH" checkout -b release-4.1
            git -C "$OMEKA_PATH" reset --hard v4.1.1
            git -C "$OMEKA_PATH" branch --set-upstream-to origin/release-4.1
            ( cd "$OMEKA_PATH" && npm install && rm -rf vendor && npx gulp deps )

        SH;

        foreach ($modulesToAdd as ['name' => $name, 'tag' => $tag, 'url' => $url]) {
            $script .= <<<SH
                add_module $name $url $tag

            SH;
        }

        foreach ($modulesToUpdate as ['name' => $name, 'tag' => $tag, 'url' => $url]) {
            $script .= <<<SH
                update_module $module $url $tag

            SH;
        }


        $script .= <<<'SH'
        omekasc db:migrate

        SH;

        foreach ($modulesToAdd as ['name' => $name]) {
            $script .= <<<SH
                omekasc module:install $name

            SH;
        }

        foreach ($modulesToUpdate as ['name' => $name]) {
            $script .= <<<SH
                omekasc module:upgrade $name

            SH;
        }

        $script .= <<<'SH'
        }

        update_git_remote () {
            directory="$1"
            url="$2"

            if [ ! -e "$directory/.git" ]; then
                git -C "$directory" init
            fi

            if git -C "$directory" remote get-url origin >/dev/null 2>/dev/null; then
                git -C "$directory" remote set-url origin "$url"
            else
                git -C "$directory" remote add origin "$url"
            fi
            git -C "$directory" fetch --prune --quiet origin
        }

        add_module () {
            name="$1"
            remote_url="$2"
            tag="$3"

            echo "Cloning module $name ($tag, $remote_url)"

            module_path="$OMEKA_PATH/modules/$name"
            git clone "$remote_url" "$module_path"
            git -C "$module_path" reset --hard "$tag"

            if [ -e "$module_path/composer.json" ]; then
                COMPOSER_DISCARD_CHANGES=true "$OMEKA_PATH/build/composer.phar" --working-dir="$module_path" install --no-dev --no-interaction
            fi
        }

        update_module () {
            name="$1"
            remote_url="$2"
            tag="$3"

            echo "Updating module $name ($tag, $remote_url)"

            module_path="$OMEKA_PATH/modules/$name"
            update_git_remote "$module_path" "$remote_url"
            git -C "$module_path" reset --hard "$tag"

            if [ -e "$module_path/composer.json" ]; then
                COMPOSER_DISCARD_CHANGES=true "$OMEKA_PATH/build/composer.phar" --working-dir="$module_path" install --no-dev --no-interaction
            fi
        }

        update_theme () {
            name="$1"
            remote_url="$2"
            tag="$3"

            echo "Updating theme $name ($tag, $remote_url)"

            theme_path="$OMEKA_PATH/themes/$name"
            update_git_remote "$theme_path" "$remote_url"
            git -C "$theme_path" reset --hard "$tag"
        }

        add_theme () {
            name="$1"
            remote_url="$2"
            tag="$3"

            echo "Cloning theme $name ($tag, $remote_url)"

            theme_path="$OMEKA_PATH/themes/$name"
            git clone "$remote_url" "$theme_path"
            git -C "$theme_path" reset --hard "$tag"
        }

        remove_folder () {
            name="$1"
            type="$2"

            echo "Removing folder $type/$name"

            folder_path="$OMEKA_PATH/$type/$name"
            rm -rf "$folder_path"
        }

        omekasc () {
            OMEKASC="$HOME/.local/bin/omekasc"
            if [ ! -e "$OMEKASC" ]; then
                mkdir -p "$(dirname "$OMEKASC")"
                wget -q -O "$OMEKASC" https://github.com/biblibre/omekasc/releases/download/v0.5.1/omekasc.phar
                chmod +x "$OMEKASC"
            fi
            (cd "$OMEKA_PATH" && $OMEKASC "$@")
        }

        main "$@"

        SH;

        echo $script;

        return Command::SUCCESS;
    }
}
