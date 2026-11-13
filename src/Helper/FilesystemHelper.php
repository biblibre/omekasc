<?php

namespace Omeka\Console\Helper;

use Symfony\Component\Console\Helper\Helper;

class FilesystemHelper extends Helper
{
    public function getName(): string
    {
        return 'fs';
    }

    /**
     * Move a file or directory to another location
     *
     * Because $from and $to can be on different filesystems, rename() cannot be used.
     * Instead, this copies $from to $to recursively, and then deletes $from recursively.
     *
     * If the copy fails then $from is not deleted.
     *
     * A partial copy may still exists after a copy failure. Use rrm($from) to clean up. For instance:
     *
     *     try {
     *         $fs->move($from, $to);
     *     } catch (\Throwable $e) {
     *         if (file_exists($to))
     *             $fs->rrm($to);
     *         }
     *         ...
     *     }
     *
     * @param string $from Absolute path of file or directory to move
     * @param string $to Absolute path of destination. It must not exist
     *
     * @throws \Exception if any filesystem operation fails
     */
    public function move(string $from, string $to): void
    {
        $this->rcopy($from, $to);
        $this->rrm($from);
    }

    /**
     * Copy file or directory recursively
     *
     * @param string $from Absolute path of file or directory to copy
     * @param string $to Absolute path of destination. It must not exist
     *
     * @throws \Exception if any filesystem operation fails
     */
    public function rcopy(string $from, string $to): void
    {
        if (file_exists($to) || is_link($to)) {
            throw new \Exception(sprintf("Destination '%s' already exists", $to));
        }

        if (is_link($from)) {
            $linkTarget = readlink($from);
            if ($linkTarget === false) {
                throw new \Exception(sprintf("Failed to readlink '%s'", $from));
            }

            if (symlink($linkTarget, $to) === false) {
                throw new \Exception(sprintf("Failed to create symlink '%s' -> '%s'", $to, $linkTarget));
            }

            return;
        }

        if (!is_dir($from)) {
            if (copy($from, $to) === false) {
                throw new \Exception(sprintf("Failed to copy '%s' to '%s'", $from, $to));
            }
            $permissions = fileperms($from);
            if ($permissions === false) {
                throw new \Exception(sprintf("Failed to get permissions of file '%s'", $from));
            }
            if (chmod($to, $permissions) === false) {
                throw new \Exception(sprintf("Failed to set permissions of file '%s'", $to));
            }
            return;
        }

        if (!is_dir($to)) {
            if (mkdir($to, recursive: true) === false) {
                throw new \Exception(sprintf("Failed to create directory '%s'", $to));
            }
            $permissions = fileperms($from);
            if ($permissions === false) {
                throw new \Exception(sprintf("Failed to get permissions of directory '%s'", $from));
            }
            if (chmod($to, $permissions) === false) {
                throw new \Exception(sprintf("Failed to set permissions of directory '%s'", $to));
            }
        }

        $files = scandir($from);
        if ($files === false) {
            throw new \Exception(sprintf("Failed to scan directory '%s'", $from));
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $fromFilepath = $from . '/' . $file;
            $toFilepath = $to . '/' . $file;
            $this->rcopy($fromFilepath, $toFilepath);
        }
    }

    /**
     * Remove a file or a directory recursively
     *
     * @param string $path Absolute path of file or directory to remove
     *
     * @throws \Exception if any filesystem operation fails
     */
    public function rrm(string $path): void
    {
        if (is_link($path) || !is_dir($path)) {
            if (unlink($path) === false) {
                throw new \Exception(sprintf("Failed to unlink '%s'", $path));
            }
            return;
        }

        $files = scandir($path);
        if ($files === false) {
            throw new \Exception(sprintf("Failed to scan directory '%s'", $path));
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $this->rrm($path . '/' . $file);
        }

        if (rmdir($path) === false) {
            throw new \Exception(sprintf("Failed to remove directory '%s'", $path));
        }
    }
}
