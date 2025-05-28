<?php

namespace PhpDependencyInspector\Exception;

/**
 * Thrown when the composer.json file is missing, unreadable, or contains invalid JSON.
 */
class InvalidComposerJsonException extends \RuntimeException
{
}
