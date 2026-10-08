<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Exceptions;

use RuntimeException;

/**
 * Base class for the help center's own exceptions, so callers can catch them all.
 */
abstract class HelpCenterException extends RuntimeException {}
