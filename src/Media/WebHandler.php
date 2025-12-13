<?php
namespace App\Media;

use App\Entity\Media;
use App\Media\Adapter\AdapterInterface;

/**
 * Class WebHandler
 */
class WebHandler implements WebHandlerInterface
{
    public function __construct(
        protected AdapterInterface $adapter,
        protected array $cdnRootURLs = []
    ) {
    }

    public function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    public function setAdapter(AdapterInterface $adapter): self
    {
        $this->adapter = $adapter;

        return $this;
    }

    public function getCDNRootURLs(): array
    {
        return $this->cdnRootURLs;
    }

    public function setCDNRootURLs(array $cdnRootURLs): self
    {
        $this->cdnRootURLs = $cdnRootURLs;

        return $this;
    }

    public function write(string $name, string $path, string $localFile): Media
    {
        $this->adapter->write($path, $localFile, [
            'mkdir' => true
        ]);
        $media = new Media();
        $media
            ->setAdapter($this->adapter->getName())
            ->setName($name)
            ->setPath($path);

        return $media;
    }

    public function getWebURL(Media $media): string
    {
        $adapter = $media->getAdapter();
        if (!isset($this->cdnRootURLs[$adapter])) {
            throw new Exception\AdapterNotFoundException(
                "CDN not found for adapter {$adapter}."
            );
        }

        return sprintf('%s/%s', $this->cdnRootURLs[$adapter], $media->getPath());
    }
}
