<?php

namespace ArtARTs36\ShellCommand\Executors\Ssh;

class FileSystem
{
    protected $ssh;

    protected $source;

    public function __construct(Connection $ssh, $sftp)
    {
        $this->ssh = $ssh;
        $this->source = $sftp;
    }

    public function download(string $remotePath, string $localPath): bool
    {
        return ssh2_scp_recv($this->ssh->getSource(), $remotePath, $localPath);
    }

    public function removeFile(string $path): bool
    {
        return ssh2_sftp_unlink($this->source, $path);
    }
}
