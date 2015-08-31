<?php

/*
 * This file is part of the fubhy/math-php package.
 *
 * (c) Sebastian Siemssen <fubhy@fubhy.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fubhy\Math\Token\Operator;

use Fubhy\Math\Token\TokenInterface;

/**
 * Common interface for all operator tokens.
 *
 * @author Sebastian Siemssen <fubhy@fubhy.com>
 */
interface OperatorTokenInterface extends TokenInterface
{
    /**
     * Right associativity operator.
     */
    const ASSOCIATIVITY_RIGHT = 'RIGHT';

    /**
     * Left associativity operator.
     */
    const ASSOCIATIVITY_LEFT = 'LEFT';

    /**
     * Returns the regular expression pattern for the operator (e.g. \+).
     *
     * @return string
     *      The regular expression pattern.
     */
    public static function getRegexPattern();

    /**
     * Returns the precedence of the operator.
     *
     * http://en.wikipedia.org/wiki/Order_of_operations
     *
     * @return int
     *     The precedence of the operator.
     *
     * @see \Fubhy\Math\Lexer::postfix()
     */
    public function getPrecedence();

    /**
     * Returns the associativity of the operator.
     *
     * http://en.wikipedia.org/wiki/Operator_associativity
     *
     * @return string
     *     The associativity of the operator.
     *
     * @see \Fubhy\Math\Lexer::postfix()
     */
    public function getAssociativity();

    /**
     * Evaluates the operator on the stack.
     *
     * @param array $stack
     *     The stack of tokens in reverse polish (postfix) notation.
     *
     * @return \Fubhy\Math\Token\NumberToken
     *     The result as a NumberToken object.
     */
    public function execute(&$stack);
}
