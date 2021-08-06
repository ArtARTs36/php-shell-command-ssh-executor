<?php

namespace ArtARTs36\ShellCommandSshExecutor;

use ArtARTs36\ShellCommand\Interfaces\ShellCommandExecutor;
use ArtARTs36\ShellCommand\Interfaces\ShellCommandInterface;
use ArtARTs36\ShellCommand\Result\CommandResult;
use ArtARTs36\ShellCommandSshExecutor\SSH\Connection;

class SshCommandExecutor implements ShellCommandExecutor
{
    protected $connection;

    protected $closeConnectionAfterCommandExecute;

    public function __construct(
        Connection $connection,
        bool $closeConnectionAfterCommandExecute = true
    ) {
        $this->connection = $connection;
        $this->closeConnectionAfterCommandExecute = $closeConnectionAfterCommandExecute;
    }

    public function execute(ShellCommandInterface $command): CommandResult
    {
        $result = $this->connection->executeCommand($command);

        if ($this->closeConnectionAfterCommandExecute) {
            $this->connection->close();
        }

        return new CommandResult($command, $result, new \DateTime());
    }
}
