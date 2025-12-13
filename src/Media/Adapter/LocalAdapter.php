<?php
namespace App\Media\Adapter;

/**
 * Adapter which writes files to the local file system.
 */
class LocalAdapter implements AdapterInterface
{
    protected string $savePath;

    public function __construct(string $savePath)
    {
        $this->setSavePath($savePath);
    }

    public function setSavePath(string $savePath): self
    {
        $this->savePath = $savePath;

        return $this;
    }

    public function getSavePath(): string
    {
        return $this->savePath;
    }

    public function getName(): string
    {
        return 'local';
    }

    public function write(string $path, string $localFile, array $options = []): bool
    {
        $options = [
            'mkdir'     => false,
            'overwrite' => false,
            ...$options
        ];

        if (!is_readable($localFile)) {
            throw new Exception\FileNotFoundException(
                "File {$localFile} does not exist or is not readable."
            );
        }

        if ($this->exists($path)) {
            if ($options['overwrite']) {
                $this->remove($path);
            } else {
                throw new Exception\FileExistsException(
                    "File {$path} already exists."
                );
            }
        }

        $writePath = $this->getWritePath($path);
        $directory = pathinfo($writePath, PATHINFO_DIRNAME);
        if (!is_writable($directory)) {
            if ($options['mkdir']) {
                if (!@mkdir($directory)) {
                    throw new Exception\WriteException(
                        "Unable to create directory {$directory}."
                    );
                }
            } else {
                throw new Exception\FileNotFoundException(
                    "Directory {$directory} does not exist or is not writable."
                );
            }
        }

        return copy($localFile, $this->getWritePath($path));
    }

    public function exists(string $path): bool
    {
        return is_readable($this->getWritePath($path));
    }

    public function remove(string $path): bool
    {
        $writePath = $this->getWritePath($path);
        if (!is_readable($writePath)) {
            throw new Exception\FileNotFoundException(
                "File {$writePath} does not exist or is not readable."
            );
        }

        return unlink($writePath);
    }

    private function getWritePath(string $path): string
    {
        return sprintf('%s/%s', $this->savePath, trim($path, '/\\'));
    }
}
