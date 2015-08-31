<?php

/*
 * This file is part of the fubhy/math-php package.
 *
 * (c) Sebastian Siemssen <fubhy@fubhy.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fubhy\Math\Token;

/**
 * Token class for functions.
 *
 * @author Sebastian Siemssen <fubhy@fubhy.com>
 */
class FunctionToken extends BaseToken
{
    /**
     * Evaluates the function on the stack.
     *
     * @param array $stack
     *     The stack of tokens in reverse polish (postfix) notation.
     *
     * @return \Fubhy\Math\Token\NumberToken
     *     The result as a NumberToken object.
     */
    public function execute(&$stack)
    {
        $arguments = [];
        list($count, $function) = $this->value;
        for ($i = 0; $i < $count; $i++) {
            array_push($arguments, array_pop($stack)->getValue());
        }
        $result = call_user_func_array($function, array_reverse($arguments));
        return new NumberToken($this->getOffset(), $result);
    }
}
