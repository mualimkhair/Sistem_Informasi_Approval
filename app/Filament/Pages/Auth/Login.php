<?php

namespace App\Filament\Pages\Auth;

use App\Models\TabContext;
use App\Auth\TabLoginResponse;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseAuth;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Component;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

class Login extends BaseAuth
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNipFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ]);
    }

    protected function getNipFormComponent(): Component
    {
        return TextInput::make('nip')
            ->label('NIP (18 digit)')
            ->required()
            ->autocomplete('username')
            ->autofocus();
    }

    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        return [
            'nip' => $data['nip'],
            'password' => $data['password'],
        ];
    }

    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();
        $nip = preg_replace('/\s+/', '', (string) ($data['nip'] ?? ''));
        $rateLimitIdentity = hash('sha256', $nip);
        $accountRateLimitKey = 'login-account:' . $rateLimitIdentity;

        try {
            if (RateLimiter::tooManyAttempts($accountRateLimitKey, 10)) {
                throw new TooManyRequestsException(
                    static::class,
                    'authenticate',
                    request()->ip(),
                    RateLimiter::availableIn($accountRateLimitKey),
                );
            }

            $this->rateLimit(10, 60, 'authenticate:' . $rateLimitIdentity);
            RateLimiter::hit($accountRateLimitKey, 60);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $credentials = $this->getCredentialsFromFormData($data);

        $webGuard = Auth::guard('web');
        $provider = $webGuard->getProvider();

        $user = $provider->retrieveByCredentials($credentials);

        if (!$user || !$provider->validateCredentials($user, $credentials)) {
            $this->fireFailedEvent($webGuard, $user, $credentials);
            $this->throwFailureValidationException();
        }

        if (method_exists($user, 'canAccessPanel')) {
            $panel = Filament::getCurrentOrDefaultPanel();
            if (!$user->canAccessPanel($panel)) {
                $this->fireFailedEvent($webGuard, $user, $credentials);
                $this->throwFailureValidationException();
            }
        }

        $result = TabContext::createForUser(
            $user->id,
            request()->userAgent(),
            (int) config('session.lifetime', 120)
        );

        session()->put('tab_login_token', $result['token']);

        return app(TabLoginResponse::class);
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.nip' => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
    }
}
