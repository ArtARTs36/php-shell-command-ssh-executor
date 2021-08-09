<?php

namespace ArtARTs36\ShellCommand\Executors\Ssh;

use ArtARTs36\ShellCommand\Interfaces\ShellCommandExecutor;
use ArtARTs36\ShellCommand\Interfaces\ShellCommandInterface;
use ArtARTs36\ShellCommand\Result\CommandResult;
use ArtARTs36\ShellCommand\ShellCommand;
use ArtARTs36\Str\Str;

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
        $prepareCommand = clone $command;
        $prepareCommand->joinAnyway(
            ShellCommand::make('echo')
                ->addCutOption('en')
                ->addArgument('"|exit-code:$?|"', false)
        );

        [$out, $err] = $this->connection->executeCommand($prepareCommand);

        $result = Str::make($out)->trim();

        $code = $this->getCodeFromResult($result);

        if ($this->closeConnectionAfterCommandExecute) {
            $this->connection->close();
        }

        return new CommandResult($command, $result->deleteLastLine(), new \DateTime(), Str::make($err), $code);
    }

    protected function getCodeFromResult(Str $result): int
    {
        $code = $result->getLastLine()->match('#\|exit-code:(\d+)\|#');

        if (! $code->isDigit()) {
            throw new \RuntimeException('Process exit-code not found!');
        }

        return $code->toInteger();
    }
}
