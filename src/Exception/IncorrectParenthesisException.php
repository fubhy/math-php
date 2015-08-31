<?php

/*
 * This file is part of the fubhy/math-php package.
 *
 * (c) Sebastian Siemssen <fubhy@fubhy.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fubhy\Math\Exception;

/**
 * Exception thrown when an incorrect parenthesis was detected.
 *
 * Thrown when e.g. an open parenthesis is never closed.
 *
 * @author Sebastian Siemssen <fubhy@fubhy.com>
 */
class IncorrectParenthesisException extends \Exception
{

}
