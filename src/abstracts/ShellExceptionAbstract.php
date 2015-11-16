<?php

namespace PavelEkt\Wrappers\Shell\Abstracts;

abstract class ShellExceptionAbstract extends \Exception
{
    const SHELL_NO_GROUP_ERRORS = -1;

    protected $title;
    protected $cause;
    protected $group;

    public function __construct($title, $cause, $group = self::SHELL_NO_GROUP_ERRORS, $code = 0)
    {
        parent::__construct($title . PHP_EOL . $cause, $code, $this);
        $this->title = $title;
        $this->cause = $cause;
        $this->group = $group;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getCause()
    {
        return $this->cause;
    }

    public function getGroup()
    {
        return $this->group;
    }
}