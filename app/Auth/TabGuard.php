<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Auth;

class TabGuard implements Guard
{
    protected ?Authenticatable $user = null;
    protected string $name = 'tab';
    protected UserProvider $provider;

    public function __construct(UserProvider $provider)
    {
        $this->provider = $provider;
    }

    public function user(): ?Authenticatable
    {
        return $this->user;
    }

    public function check(): bool
    {
        return !is_null($this->user);
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function hasUser(): bool
    {
        return $this->check();
    }

    public function id(): ?string
    {
        return $this->user ? (string) $this->user->getAuthIdentifier() : null;
    }

    public function setUser(Authenticatable $user): void
    {
        $this->user = $user;
    }

    public function validate(array $credentials = []): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        if (!$user) {
            return false;
        }

        return $this->provider->validateCredentials($user, $credentials);
    }

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        return $this->provider->retrieveByCredentials($credentials);
    }

    public function getProvider(): UserProvider
    {
        return $this->provider;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
