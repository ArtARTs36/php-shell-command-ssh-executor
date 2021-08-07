<?php

namespace ArtARTs36\ShellCommandSshExecutor\SSH;

class Connection
{
    use ConnectionConstructors;

    protected $connection;

    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    public function executeCommand(string $command): ?string
    {
        $stream = ssh2_exec($this->connection, $command);
        $stdout = $this->getContentFromStream($stream, SSH2_STREAM_STDIO);

        if ($stdout !== null) {
            return $stdout;
        }

        return $this->getContentFromStream($stream, SSH2_STREAM_STDERR);
    }

    public function close(): bool
    {
        return ssh2_disconnect($this->connection);
    }

    public function downloadFile(string $remotePath, string $localPath): bool
    {
        return ssh2_scp_recv($this->connection, $remotePath, $localPath);
    }

    public function __destruct()
    {
        $this->close();
    }

    protected function getContentFromStream($stream, int $id): ?string
    {
        $fetch = ssh2_fetch_stream($stream, $id);

        stream_set_blocking($fetch, true);

        $content = stream_get_contents($fetch);

        return $content === false ? null : $content;
    }
}
