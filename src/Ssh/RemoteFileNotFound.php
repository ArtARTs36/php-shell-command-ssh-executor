<?php

namespace ArtARTs36\ShellCommand\Executors\Ssh;

use ArtARTs36\FileSystem\Contracts\FileNotFound;
use Throwable;

final class RemoteFileNotFound extends \Exception implements FileNotFound
{
    private $path;

    public function __construct(string $path, $code = 0, Throwable $previous = null)
    {
        $message = "Remote file $path not found!";

        parent::__construct($message, $code, $previous);
    }

    public function getInvalidFilePath(): string
    {
        return $this->path;
    }
}
