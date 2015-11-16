<?php

namespace PavelEkt\Wrappers\Shell\Interfaces;

use \PavelEkt\Wrappers\Shell;

interface ListenerObjectInterface
{
    public function shellListener(Shell $wrapper, $object);
}