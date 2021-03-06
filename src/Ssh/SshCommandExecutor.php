<?php

namespace ArtARTs36\ShellCommand\Executors\Ssh;

use ArtARTs36\ShellCommand\Interfaces\CommandBuilder;
use ArtARTs36\ShellCommand\Interfaces\ShellCommandExecutor;
use ArtARTs36\ShellCommand\Interfaces\ShellCommandInterface;
use ArtARTs36\ShellCommand\Result\CommandResult;
use ArtARTs36\ShellCommand\ShellCommander;
use ArtARTs36\Str\Str;

class SshCommandExecutor implements ShellCommandExecutor
{
    protected $connection = null;

    protected $closeConnectionAfterCommandExecute;

    protected $builder;

    public function __construct(
        ?Connection $connection = null,
        bool $closeConnectionAfterCommandExecute = true,
        ?CommandBuilder $builder = null
    ) {
        $this->connection = $connection;
        $this->closeConnectionAfterCommandExecute = $closeConnectionAfterCommandExecute;
        $this->builder = $builder ?? new ShellCommander();
    }

    public function useConnection(Connection $connection): self
    {
        $this->connection = $connection;

        return $this;
    }

    public function buildFileSystem(): FileSystem
    {
        return FileSystem::connect($this->connection, $this->builder, $this);
    }

    public function execute(ShellCommandInterface $command): CommandResult
    {
        $prepareCommand = clone $command;
        $prepareCommand->joinAnyway(
            $this->builder->make('echo')
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
