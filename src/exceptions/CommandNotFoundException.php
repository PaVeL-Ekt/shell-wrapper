<?php

namespace PavelEkt\Wrappers\Shell\Exceptions;

class CommandNotFoundException extends \Exception
{
    const EXCEPTION_CODE = 1201;

    /**
     * Standard exception constructor
     * @param PavelEkt\Wrappers\Shell\Components\ShellCommand|PavelEkt\Wrappers\Shell\Components\ShellInteractiveCommand $command
     * @param \Exception $previous [optional] The previous exception used for the exception chaining.
     */
    public function __construct($command, $previous = null)
    {
        parent::__construct('Command not found', self::EXCEPTION_CODE, $previous);
    }
}
