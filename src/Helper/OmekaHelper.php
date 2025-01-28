<?php

namespace Omeka\Console\Helper;

use Symfony\Component\Console\Helper\Helper;

class OmekaHelper extends Helper
{
    protected $application;
    protected $omekaPath;
    protected $omekaVersion;

    public function getName(): string
    {
        return 'omeka';
    }

    public function getOmekaPath()
    {
        if (!isset($this->omekaPath)) {
            $this->omekaPath = $this->findOmekaPath();
        }

        return $this->omekaPath;
    }

    public function getOmekaVersion()
    {
        if (!isset($this->omekaVersion)) {
            $omekaPath = $this->getOmekaPath();
            require_once $omekaPath . '/vendor/autoload.php';
            require_once $omekaPath . '/application/Module.php';
            $this->omekaVersion = \Omeka\Module::VERSION;
        }

        return $this->omekaVersion;
    }

    public function getOmekaMajorVersion()
    {
        $version = $this->getOmekaVersion();

        return preg_filter('/^(\d+).*/', '\1', $version);
    }

    public function getApplication()
    {
        if (!isset($this->application)) {
            $omekaPath = $this->getOmekaPath();
            require "$omekaPath/bootstrap.php";
            $this->application = \Omeka\Mvc\Application::init(require $omekaPath . '/application/config/application.config.php');
        }

        return $this->application;
    }

    public function loginAsAdmin()
    {
        $application = $this->getApplication();
        $services = $application->getServiceManager();
        $em = $services->get('Omeka\EntityManager');
        $userRepository = $em->getRepository('Omeka\Entity\User');
        $admins = $userRepository->findBy(['role' => \Omeka\Permissions\Acl::ROLE_GLOBAL_ADMIN], ['id' => 'asc'], 1);
        if (empty($admins)) {
            throw new \Exception('No global admin found. Cannot log in');
        }

        $admin = reset($admins);
        $authentication = $services->get('Omeka\AuthenticationService');
        $authentication->getStorage()->write($admin);
    }

    protected function findOmekaPath()
    {
        $dir = getcwd();
        if (false === $dir) {
            throw new \RuntimeException('Cannot get the current working directory');
        }

        $olddir = null;
        while ($dir !== $olddir) {
            $composerJsonPath = $dir . '/composer.json';
            if (is_file($composerJsonPath) && is_readable($composerJsonPath)) {
                $composerJson = file_get_contents($composerJsonPath);
                if (false !== $composerJson) {
                    $composerData = json_decode($composerJson, true);
                    if (null !== $composerData && isset($composerData['name']) && $composerData['name'] === 'omeka/omeka-s') {
                        return $dir;
                    }
                }
            }

            $olddir = $dir;
            $dir = dirname($dir);
        }
    }

}
