<?php

namespace App\Services\DeveloperTokenService;

use App\Services\DeveloperTokenService\Dto\DeveloperTokenDto;
use App\Services\DeveloperTokenService\Exceptions\DeveloperTokenCreationFailedException;
use App\Services\DeveloperTokenService\Exceptions\MissingDeveloperTokenConfigurationException;
use App\Services\DeveloperTokenService\Models\DeveloperToken;
use DomainException;
use Illuminate\Support\Carbon;

readonly class DeveloperTokenService
{
    public function __construct(
        private ?string $configurationToken,
        private int     $defaultExpirationTime,
    )
    {
    }

    /**
     * @throws MissingDeveloperTokenConfigurationException
     * @throws DeveloperTokenCreationFailedException
     */
    public static function fromDto(
        DeveloperTokenDto $dto,
    ): DeveloperToken
    {
        return app(self::class)->getFirstOrCreate(
            $dto->renew,
            $dto->expiresAt,
        );
    }

    /**
     * @throws MissingDeveloperTokenConfigurationException
     * @throws DeveloperTokenCreationFailedException
     */
    public function getFirstOrCreate(
        bool    $renew = false,
        ?Carbon $expiresAt = null,
    ): DeveloperToken
    {
        if ($this->configurationToken) {
            return DeveloperToken::make([
                'token' => $this->configurationToken,
            ]);
        }

        $expiresAt = $expiresAt ?? Carbon::now()->addSeconds($this->defaultExpirationTime);

        if ($renew) {
            return DeveloperToken::create([
                'token' => $this->generate($expiresAt),
                'expires_at' => $expiresAt,
            ]);
        }

        return DeveloperToken::firstOrCreate([], [
            'token' => $this->generate($expiresAt),
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * @throws DeveloperTokenCreationFailedException
     * @throws MissingDeveloperTokenConfigurationException
     */
    public function generate(Carbon $expiresAt): string
    {
        $private_key = $this->getAppleAuthKey();
        $team_id = config('musickit.apple.team_id');
        $key_id = config('musickit.apple.key_id');

        if (!$private_key) {
            throw new MissingDeveloperTokenConfigurationException('Unable to generate developer token : no APPLE_AUTH_KEY_FILE or APPLE_AUTH_KEY found');
        }
        if (!$team_id) {
            throw new MissingDeveloperTokenConfigurationException('Unable to generate developer token : no APPLE_TEAM_ID found');
        }
        if (!$key_id) {
            throw new MissingDeveloperTokenConfigurationException('Unable to generate developer token : no APPLE_KEY_ID found');
        }

        try {
            return JWT::make(
                $private_key,
                $key_id,
                $team_id,
                $expiresAt,
            );
        } catch (DomainException $exception) {
            throw new DeveloperTokenCreationFailedException(sprintf('Unable to generate developer token : %s', $exception->getMessage()));
        }
    }

    private function getAppleAuthKeyPath(): string
    {
        return config_path(sprintf('keys/%s', config('musickit.apple.auth_key.path')));
    }

    private function getAppleAuthKey(): ?string
    {

        if ($authKey = config('musickit.apple.auth_key.value')) {
            return $authKey;
        }

        $path = $this->getAppleAuthKeyPath();
        if ($path && is_file($path)) {
            return file_get_contents($path);
        }

        return null;
    }

}
