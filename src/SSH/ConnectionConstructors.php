<?php

namespace ArtARTs36\ShellCommandSshExecutor\SSH;

trait ConnectionConstructors
{
    public static function withUsername(string $host, string $user, int $port = 22): self
    {
        return static::create($host, $port, function ($connection) use ($user) {
            ssh2_auth_none($connection, $user);
        });
    }

    public static function withPassword(string $host, string $user, string $password, int $port = 22): self
    {
        return static::create($host, $port, function ($connection) use ($user, $password) {
            ssh2_auth_password($connection, $user, $password);
        });
    }

    public static function withPublicKey(
        string $host,
        string $user,
        string $publicKeyPath,
        string $privateKeyPath,
        ?string $passphrase = null,
        int $port = 22
    ): self {
        return static::create(
            $host,
            $port,
            function ($connection) use ($user, $publicKeyPath, $privateKeyPath, $passphrase) {
                ssh2_auth_pubkey_file($connection, $user, $publicKeyPath, $privateKeyPath, $passphrase);
            }
        );
    }

    protected static function create(string $host, int $port, \Closure $applyAuthentication): self
    {
        $connection = ssh2_connect($host, $port);

        if ($connection === false) {
            throw new \RuntimeException('Invalid credentials');
        }

        $applyAuthentication($connection);

        return new static($connection);
    }
}
