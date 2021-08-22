<?php

namespace ArtARTs36\ShellCommand\Executors\Ssh;

use ArtARTs36\ShellCommand\Interfaces\CommandBuilder;
use ArtARTs36\ShellCommand\Interfaces\ShellCommandExecutor;

class FileSystem implements \ArtARTs36\FileSystem\Contracts\FileSystem
{
    protected $ssh;

    protected $source;

    protected $builder;

    protected $executor;

    public function __construct(
        Connection $ssh,
        $sftp,
        CommandBuilder $builder,
        ShellCommandExecutor $executor
    ) {
        $this->ssh = $ssh;
        $this->source = $sftp;
        $this->builder = $builder;
        $this->executor = $executor;
    }

    public static function connect(Connection $ssh, CommandBuilder $builder, ShellCommandExecutor $executor): self
    {
        return new static($ssh, ssh2_sftp($ssh->getSource()), $builder, $executor);
    }

    public function download(string $remotePath, string $localPath): bool
    {
        return ssh2_scp_recv($this->ssh->getSource(), $remotePath, $localPath);
    }

    public function removeFile(string $path): bool
    {
        return ssh2_sftp_unlink($this->source, $path);
    }

    public function createDir(string $path, int $permissions = 0755): bool
    {
        return ssh2_sftp_mkdir($this->source, $path, $permissions, true);
    }

    public function getAbsolutePath(string $path): string
    {
        return ssh2_sftp_realpath($this->source, $path);
    }

    public function exists(string $path): bool
    {
        set_error_handler(function () {
        });

        $state = ssh2_sftp_stat($this->source, $path) !== false;

        restore_error_handler();

        return $state;
    }

    public function getLastUpdateDate(string $path): \DateTimeInterface
    {
        $stat = $this->getStat($path);

        return (new \DateTime())->setTimestamp($stat['mtime']);
    }

    public function getStat(string $path): array
    {
        set_error_handler(function () use ($path) {
            throw new RemoteFileNotFound($path);
        });

        $stat = ssh2_sftp_stat($this->source, $path);

        restore_error_handler();

        return $stat;
    }

    /**
     * @return array<string>
     */
    public function getFromDirectory(string $path): array
    {
        return array_map('trim', $this
            ->builder
            ->make('ls')
            ->addCutOption('A')
            ->addArgument($path)
            ->executeOrFail($this->executor)
            ->getResult()
            ->lines());
    }

    public function removeDir(string $path): bool
    {
        return $this
            ->builder
            ->make()
            ->addArgument('rm')
            ->addCutOption('rf')
            ->addArgument($path)
            ->executeOrFail($this->executor)
            ->isOk();
    }

    public function downPath(string $path): string
    {
        return dirname($path);
    }

    public function createFile(string $path, string $content): bool
    {
        return $this
            ->builder
            ->make()
            ->addArgument('echo')
            ->addArgument($content)
            ->setOutputFlow($path)
            ->executeOrFail($this->executor)
            ->isOk();
    }

    public function getFileContent(string $path): string
    {
        $this->getStat($path);

        return $this
            ->builder
            ->make('cat')
            ->addArgument($path)
            ->executeOrFail($this->executor)
            ->getResult()
            ->trim();
    }

    public function getTmpDir(): string
    {
        return $this
            ->builder
            ->make()
            ->addArgument('echo')
            ->addArgument('${TMPDIR:-/tmp}', false)
            ->executeOrFail($this->executor)
            ->getResult()
            ->trim();
    }
}
