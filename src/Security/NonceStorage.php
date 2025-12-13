<?php
namespace App\Security;

use Symfony\Component\HttpFoundation\RequestStack;

class NonceStorage implements NonceStorageInterface
{
    public function __construct(
        private readonly RequestStack $requestStack
    ) {
    }

    public function get(string $key): mixed
    {
        return $this->requestStack->getSession()->get($this->getSessionKey($key));
    }

    public function set(string $key, mixed $value): void
    {
        $this->requestStack->getSession()->set($this->getSessionKey($key), $value);
    }

    public function remove(string $key): mixed
    {
        return $this->requestStack->getSession()->remove($this->getSessionKey($key));
    }

    public function has(string $key): bool
    {
        return $this->requestStack->getSession()->has($this->getSessionKey($key));
    }

    public function valid(string $key, mixed $value, bool $remove = true): bool
    {
        $valid = $this->get($key) === $value;
        if ($remove) {
            $this->remove($key);
        }

        return $valid;
    }

    private function getSessionKey(string $key): string
    {
        return "nonce.{$key}";
    }
}
