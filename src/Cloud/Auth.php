<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud\DTO\OrganizationId;
use Kiboko\Component\Satellite\Cloud\DTO\WorkspaceId;

final class Auth
{
    private string $pathName;
    private array $configuration;

    public function __construct(?string $pathName = null)
    {
        if (null === $pathName) {
            $this->pathName = getenv('HOME').'/.gyroscops/';
        } else {
            $this->pathName = $pathName;
        }

        if (!file_exists($this->pathName)
            && !mkdir($this->pathName, 0o700, true)
            && !is_dir($this->pathName)
        ) {
            throw new \RuntimeException(sprintf('Directory "%s" can not be created', $this->pathName));
        }

        $content = false;
        if (file_exists($this->pathName.'/auth.json')) {
            $content = file_get_contents($this->pathName.'/auth.json');
        } else {
            touch($this->pathName.'/auth.json');
            chmod($this->pathName.'/auth.json', 0o700);
        }
        if (false === $content) {
            $this->configuration = [];

            return;
        }

        try {
            $this->configuration = json_decode($content, associative: true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $this->configuration = [];
        }
    }

    public function flush(): void
    {
        try {
            $content = json_encode($this->configuration, flags: \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT);
        } catch (\JsonException) {
            throw new \RuntimeException('Could not encode authentication data, aborting.');
        }

        file_put_contents($this->pathName.'/auth.json', $content);
    }

    public function authenticateWithCredentials(
        Api\Client $client,
        Credentials $credentials,
    ): string {
        $data = new Api\Model\Credentials();
        $data->setUsername($credentials->username);
        $data->setPassword($credentials->password);
        if ($credentials->workspace && !$credentials->workspace->isNil()) {
            $data->setWorkspace($credentials->workspace->asString());
        }
        if ($credentials->organization && !$credentials->organization->isNil()) {
            $data->setOrganization($credentials->organization->asString());
        }

        $token = $client->postCredentialsItem($data);
        try {
            \assert($token instanceof Api\Model\Token);
        } catch (\AssertionError) {
            throw new AccessDeniedException('Could not authenticate with the provided credentials.');
        }

        return $token->getToken();
    }

    public function changeOrganization(
        Api\Client $client,
        OrganizationId $organization
    ): string {
        $data = new \stdClass();
        $data->organization = $organization->asString();

        /** @var \stdClass $token */
        $token = $client->putAuthenticationToken($data);

        return $token->token;
    }

    public function changeWorkspace(
        Api\Client $client,
        WorkspaceId $workspace
    ): string {
        $data = new \stdClass();
        $data->workspace = $workspace->asString();

        /** @var \stdClass $token */
        $token = $client->putAuthenticationToken($data);

        return $token->token;
    }

    public function persistOrganization(string $url, OrganizationId $organization): void
    {
        if (!\array_key_exists($url, $this->configuration)) {
            $this->configuration[$url] = [];
        }

        $this->configuration[$url] = array_merge($this->configuration[$url], [
            'organization' => $organization->asString(),
        ]);
    }

    public function persistWorkspace(string $url, WorkspaceId $workspace): void
    {
        if (!\array_key_exists($url, $this->configuration)) {
            $this->configuration[$url] = [];
        }

        $this->configuration[$url] = array_merge($this->configuration[$url], [
            'workspace' => $workspace->asString(),
        ]);
    }

    public function persistCredentials(string $url, Credentials $credentials): void
    {
        if (!\array_key_exists($url, $this->configuration)) {
            $this->configuration[$url] = [];
        }

        $this->configuration[$url] = array_merge($this->configuration[$url], [
            'login' => $credentials->username,
            'password' => $credentials->password,
            'organization' => $credentials->organization?->asString() ?? ($this->configuration[$url]['organization'] ?? null),
            'workspace' => $credentials->workspace?->asString() ?? ($this->configuration[$url]['workspace'] ?? null),
        ]);
    }

    public function persistToken(string $url, string $token): void
    {
        if (!\array_key_exists($url, $this->configuration)) {
            $this->configuration[$url] = [];
        }

        $this->configuration[$url] = array_merge($this->configuration[$url], [
            'token' => $token,
            'date' => (new \DateTimeImmutable())->format(\DateTimeImmutable::RFC3339_EXTENDED),
        ]);
    }

    public function token(string $url): string
    {
        if (!\array_key_exists($url, $this->configuration)
            || !\array_key_exists('token', $this->configuration[$url])
            || !\array_key_exists('date', $this->configuration[$url])
        ) {
            throw new AccessDeniedException('There is no available token to authenticate to the service.');
        }

        //        $date = \DateTimeImmutable::createFromFormat(\DateTimeImmutable::RFC3339_EXTENDED, $this->configuration[$url]['date']);
        //        if ($date <= new \DateTimeImmutable('-1 hour')) {
        //            throw new AccessDeniedException('The stored token has expired, you need a fresh token to authenticate to the service.');
        //        }

        return $this->configuration[$url]['token'];
    }

    public function credentials(string $url): Credentials
    {
        if (!\array_key_exists($url, $this->configuration)
            || !\array_key_exists('login', $this->configuration[$url])
            || !\array_key_exists('password', $this->configuration[$url])
        ) {
            throw new \OutOfBoundsException('There is no available credentials to authenticate to the service.');
        }

        return new Credentials(
            $this->configuration[$url]['login'],
            $this->configuration[$url]['password'],
            organization: \array_key_exists('organization', $this->configuration[$url]) && null !== $this->configuration[$url]['organization'] ?
                new OrganizationId($this->configuration[$url]['organization']) :
                null,
            workspace: \array_key_exists('workspace', $this->configuration[$url]) && null !== $this->configuration[$url]['workspace'] ?
                new WorkspaceId($this->configuration[$url]['workspace']) :
                null,
        );
    }
}
