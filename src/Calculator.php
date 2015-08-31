<?php

/*
 * This file is part of the fubhy/math-php package.
 *
 * (c) Sebastian Siemssen <fubhy@fubhy.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fubhy\Math;

use Fubhy\Math\Exception\IncorrectExpressionException;
use Fubhy\Math\Exception\UnknownVariableException;
use Fubhy\Math\Token\FunctionToken;
use Fubhy\Math\Token\NumberToken;
use Fubhy\Math\Token\Operator\OperatorTokenInterface;
use Fubhy\Math\Token\VariableToken;

/**
 * Parser for mathematical expressions.
 *
 * @author Sebastian Siemssen <fubhy@fubhy.com>
 */
class Calculator
{
    /**
     * Static cache of token streams in reverse polish (postfix) notation.
     *
     * @var array
     */
    protected $tokenCache = [];

    /**
     * Constructs a new Calculator object.
     *
     * @param array $constants
     * @param array $functions
     * @param array $operators
     */
    public function __construct(array $constants = null, array $functions = null, array $operators = null)
    {
        $this->lexer = new Lexer();

        $constants = $constants ?: static::getDefaultConstants();
        $functions = $functions ?: static::getDefaultFunctions();
        $operators = $operators ?: static::getDefaultOperators();

        foreach ($constants as $constant) {
            $this->lexer->addConstant($constant[0], $constant[1]);
        }

        foreach ($functions as $function) {
            $this->lexer->addFunction($function[0], $function[1], $function[2]);
        }

        foreach ($operators as $operator) {
            $this->lexer->addOperator($operator[0], $operator[1]);
        }
    }

    /**
     * Calculates the result of a mathematical expression.
     *
     * @param string $expression
     *     The mathematical expression.
     * @param array $variables
     *     A list of numerical values keyed by their variable names.
     *
     * @return mixed
     *     The result of the mathematical expression.
     *
     * @throws \Fubhy\Math\Exception\IncorrectExpressionException
     * @throws \Fubhy\Math\Exception\UnknownVariableException
     */
    public function calculate($expression, $variables)
    {
        $hash = md5($expression);
        if (!isset($this->tokenCache[$hash])) {
            $this->tokenCache[$hash] = $this->lexer->postfix($this->lexer->tokenize($expression));
        }

        $stack = [];
        $tokens = $this->tokenCache[$hash];
        foreach ($tokens as $token) {
            if ($token instanceof NumberToken) {
                array_push($stack, $token);
            }
            elseif ($token instanceof VariableToken) {
                $identifier = $token->getValue();
                if (!isset($variables[$identifier])) {
                    throw new UnknownVariableException($token->getOffset(), $identifier);
                }
                array_push($stack, new NumberToken($token->getOffset(), $variables[$identifier]));
            }
            elseif ($token instanceof OperatorTokenInterface || $token instanceof FunctionToken) {
                array_push($stack, $token->execute($stack));
            }
        }

        $result = array_pop($stack);
        if (!empty($stack)) {
            throw new IncorrectExpressionException();
        }

        return $result->getValue();
    }

    /**
     * Returns the default list of operators.
     *
     * @return array
     *   The default list of operators.
     */
    public static function getDefaultOperators() {
        return [
            ['plus', 'Fubhy\Math\Token\Operator\PlusToken'],
            ['minus',  'Fubhy\Math\Token\Operator\MinusToken'],
            ['multiply', 'Fubhy\Math\Token\Operator\MultiplyToken'],
            ['division', 'Fubhy\Math\Token\Operator\DivisionToken'],
            ['modulus', 'Fubhy\Math\Token\Operator\ModulusToken'],
            ['power', 'Fubhy\Math\Token\Operator\PowerToken'],
        ];
    }

    /**
     * Returns the default list of functions.
     *
     * @return array
     *   The default list of functions.
     */
    public static function getDefaultFunctions() {
        return [
            ['abs', 'abs', 1],
            ['acos', 'acos', 1],
            ['acosh', 'acosh', 1],
            ['asin', 'asin', 1],
            ['asinh', 'asinh', 1],
            ['atan2', 'atan2', 2],
            ['atan', 'atan', 1],
            ['atanh', 'atanh', 1],
            ['ceil', 'ceil', 1],
            ['cos', 'cos', 1],
            ['cosh', 'cosh', 1],
            ['deg2rad', 'deg2rad', 1],
            ['exp', 'exp', 1],
            ['floor', 'floor', 1],
            ['hypot', 'hypot', 2],
            ['log10', 'log10', 1],
            ['log', 'log', 2],
            ['max', 'max', 2],
            ['min', 'min', 2],
            ['pow', 'pow', 2],
            ['rad2deg', 'rad2deg', 1],
            ['rand', 'rand', 2],
            ['round', 'round', 1],
            ['sin', 'sin', 1],
            ['sinh', 'sinh', 1],
            ['sqrt', 'sqrt', 1],
            ['tan', 'tan', 1],
            ['tanh', 'tanh', 1],
        ];
    }

    /**
     * Returns the default list of constants.
     *
     * @return array
     *   The default list of constants.
     */
    public static function getDefaultConstants() {
        return [
            ['pi', pi()],
            ['e',  exp(1)],
        ];
    }
}
