<?php

namespace ArtARTs36\ShellCommandSshExecutor\SSH;

class Connection
{
    use ConnectionConstructors;

    protected $source;

    public function __construct($source)
    {
        $this->source = $source;
    }

    public function executeCommand(string $command): ?string
    {
        $stream = ssh2_exec($this->source, $command);
        $stdout = $this->getContentFromStream($stream, SSH2_STREAM_STDIO);

        if ($stdout !== null) {
            return $stdout;
        }

        return $this->getContentFromStream($stream, SSH2_STREAM_STDERR);
    }

    public function close(): bool
    {
        return ssh2_disconnect($this->source);
    }

    public function getFileSystem(): FileSystem
    {
        static $system = null;

        if ($system === null) {
            $system = new FileSystem($this->source, ssh2_sftp($this->source));
        }

        return $system;
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @return resource
     */
    public function getSource()
    {
        return $this->source;
    }

    protected function getContentFromStream($stream, int $id): ?string
    {
        $fetch = ssh2_fetch_stream($stream, $id);

        stream_set_blocking($fetch, true);

        $content = stream_get_contents($fetch);

        return $content === false ? null : $content;
    }
}
