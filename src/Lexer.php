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

use Fubhy\Math\Exception\IncorrectParenthesisException;
use Fubhy\Math\Exception\IncorrectExpressionException;
use Fubhy\Math\Exception\UnknownConstantException;
use Fubhy\Math\Exception\UnknownFunctionException;
use Fubhy\Math\Exception\UnknownOperatorException;
use Fubhy\Math\Exception\UnknownTokenException;
use Fubhy\Math\Token\ParenthesisCloseToken;
use Fubhy\Math\Token\ParenthesisOpenToken;
use Fubhy\Math\Token\CommaToken;
use Fubhy\Math\Token\FunctionToken;
use Fubhy\Math\Token\NumberToken;
use Fubhy\Math\Token\Operator\OperatorTokenInterface;
use Fubhy\Math\Token\VariableToken;

/**
 * Lexical analyzer for mathematical expressions.
 *
 * @author Sebastian Siemssen <fubhy@fubhy.com>
 */
class Lexer
{
    /**
     * Static cache of the compiled regular expression.
     *
     * @var string
     */
    protected $compiledRegex;

    /**
     * The list of registered operators.
     *
     * @var array
     */
    protected $operators = [];

    /**
     * The list of registered constants.
     *
     * @var array
     */
    protected $constants = [];

    /**
     * The list of registered functions.
     *
     * @var array
     */
    protected $functions = [];

    /**
     * The list of parsed variables.
     * 
     * @var array
     */
    protected $variables = [];

    /**
     * Registers a function with the lexer.
     *
     * @param string $name
     *     The name of the function.
     * @param callable $function
     *     The callback to be executed.
     * @param int $arguments
     *     The number of arguments required for the function.
     *
     * @return $this
     */
    public function addFunction($name, callable $function, $arguments = 1)
    {
        $this->functions[$name] = [$arguments, $function];
        return $this;
    }

    /**
     * Removes a function from the function registry.
     *
     * @param string $name
     *   The name of the function to remove.
     *
     * @return $this
     */
    public function removeFunction($name)
    {
        unset($this->functions[$name]);
        return $this;
    }

    /**
     * Registers an operator with the lexer.
     *
     * @param string $name
     *     The name of the operator.
     * @param string $operator
     *     The full qualified class name of the operator token.
     *
     * @return $this
     */
    public function addOperator($name, $operator)
    {
        if (!is_subclass_of($operator, 'Fubhy\Math\Token\Operator\OperatorTokenInterface')) {
            throw new \InvalidArgumentException();
        }

        // Clear the static cache when a new operator is added.
        unset($this->compiledRegex);

        $this->operators[$name] = $operator;
        return $this;
    }

    /**
     * Removes an operator from the operator registry.
     *
     * @param string $name
     *   The name of the operator to remove.
     *
     * @return $this
     */
    public function removeOperator($name)
    {
        unset($this->operators[$name]);

        // Clear the static cache when an operator is removed.
        unset($this->compiledRegex);

        return $this;
    }

    /**
     * Registers a constant with the lexer.
     *
     * @param string $name
     *     The name of the constant.
     * @param int $value
     *     The value of the constant.
     *
     * @return $this
     */
    public function addConstant($name, $value)
    {
        $this->constants[$name] = $value;
        return $this;
    }

    /**
     * Removes a constant from the constant registry.
     *
     * @param string $name
     *   The name of the constant to remove.
     *
     * @return $this
     */
    public function removeConstant($name)
    {
        unset($this->operators[$name]);
        return $this;
    }

    /**
     * Generates a token stream from a mathematical expression.
     *
     * @param string $input
     *     The mathematical expression to tokenize.
     *
     * @return array
     *     The generated token stream.
     */
    public function tokenize($input)
    {
        $matches = [];
        $regex = $this->getCompiledTokenRegex();

        if (preg_match_all($regex, $input, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) === FALSE) {
            // There was a failure when evaluating the regular expression.
            throw new \LogicException();
        };

        $types = [
            'number', 'operator', 'function', 'open', 'close', 'comma', 'constant', 'variable',
        ];

        // Traverse over all matches and create the corresponding tokens.
        return array_map(function ($match) use ($types) {
            foreach ($types as $type) {
                if (!empty($match[$type][0])) {
                    return $this->createToken($type, $match[$type][0], $match[$type][1], $match);
                }
            }

            // There was a match outside of one of the token types.
            throw new \LogicException();
        }, $matches);
    }

    /**
     * Reorganizes a list of tokens into reverse polish (postfix) notation.
     *
     * Uses an implementation of the Shunting-yard algorithm.
     *
     * http://en.wikipedia.org/wiki/Shunting-yard_algorithm
     *
     * @param \Fubhy\Math\Token\TokenInterface[] $tokens
     *     The tokens to be reorganized into reverse polish (postfix) notation.
     *
     * @return \Fubhy\Math\Token\TokenInterface[]
     *     The given tokens in reverse polish (postfix) notation.
     *
     * @throws \Fubhy\Math\Exception\IncorrectParenthesisException
     * @throws \Fubhy\Math\Exception\IncorrectExpressionException
     */
    public function postfix($tokens)
    {
        $output = [];
        $stack = [];

        foreach ($tokens as $token) {
            if ($token instanceof NumberToken || $token instanceof VariableToken) {
                $output[] = $token;
            }
            elseif ($token instanceof FunctionToken) {
                array_push($stack, $token);
            }
            elseif ($token instanceof ParenthesisOpenToken) {
                array_push($stack, $token);
            }
            elseif ($token instanceof CommaToken) {
                while (($current = array_pop($stack)) && (!$current instanceof ParenthesisOpenToken)) {
                    $output[] = $current;

                    if (empty($stack)) {
                        throw new IncorrectExpressionException();
                    }
                }
            }
            elseif ($token instanceof ParenthesisCloseToken) {
                while (($current = array_pop($stack)) && !($current instanceof ParenthesisOpenToken)) {
                    $output[] = $current;
                }

                if (!empty($stack) && ($stack[count($stack) - 1] instanceof FunctionToken)) {
                    $output[] = array_pop($stack);
                }
            }
            elseif ($token instanceof OperatorTokenInterface) {
                while (!empty($stack)) {
                    $last = end($stack);
                    if (!($last instanceof OperatorTokenInterface)) {
                        break;
                    }

                    $associativity = $token->getAssociativity();
                    $precedence = $token->getPrecedence();
                    $last_precedence = $last->getPrecedence();
                    if (!(
                        ($associativity === OperatorTokenInterface::ASSOCIATIVITY_LEFT && $precedence <= $last_precedence) ||
                        ($associativity === OperatorTokenInterface::ASSOCIATIVITY_RIGHT && $precedence < $last_precedence)
                    )) {
                        break;
                    }

                    $output[] = array_pop($stack);
                }

                array_push($stack, $token);
            }
        }

        while (!empty($stack)) {
            $token = array_pop($stack);
            if ($token instanceof ParenthesisOpenToken || $token instanceof ParenthesisCloseToken) {
                throw new IncorrectParenthesisException();
            }

            $output[] = $token;
        }

        return $output;
    }

    /**
     * Creates a token object of the given type.
     *
     * @param string $type
     *     The type of the token.
     * @param string $value
     *     The matched string.
     * @param int $offset
     *     The offset of the matched string.
     * @param $match
     *     The full match as returned by preg_match_all().
     *
     * @return \Fubhy\Math\Token\TokenInterface
     *     The created token object.
     *
     * @throws \Fubhy\Math\Exception\UnknownConstantException
     * @throws \Fubhy\Math\Exception\UnknownFunctionException
     * @throws \Fubhy\Math\Exception\UnknownOperatorException
     * @throws \Fubhy\Math\Exception\UnknownTokenException
     */
    protected function createToken($type, $value, $offset, $match)
    {
        switch ($type) {
            case 'number':
                return new NumberToken($offset, $value);

            case 'open':
                return new ParenthesisOpenToken($offset, $value);

            case 'close':
                return new ParenthesisCloseToken($offset, $value);

            case 'comma':
                return new CommaToken($offset, $value);

            case 'operator':
                foreach ($this->operators as $id => $operator) {
                    if (!empty($match["op_$id"][0])) {
                        return new $operator($offset, $value);
                    }
                }
                throw new UnknownOperatorException($offset, $value);

            case 'function':
                if (isset($this->functions[$value])) {
                    return new FunctionToken($offset, $this->functions[$value]);
                }
                throw new UnknownFunctionException($offset, $value);

            case 'constant':
                $constant = substr($value, 1);
                if (isset($this->constants[$constant])) {
                    return new NumberToken($offset, $this->constants[$constant]);
                }
                throw new UnknownConstantException($offset, $constant);

            case 'variable':
                $variable = substr($value, 1, -1);
                if (!in_array($variable, $this->variables)) {
                    $this->variables[] = $variable;
                }
                return new VariableToken($offset, $variable);
        }

        throw new UnknownTokenException($offset, $value);
    }

    /**
     * Builds a concatenated regular expression for all available operators.
     *
     * @return string
     *     The regular expression for matching all available operators.
     */
    protected function getOperatorRegex()
    {
        $operators = [];
        foreach ($this->operators as $id => $operator) {
            $pattern = call_user_func([$operator, 'getRegexPattern']);
            $operators[] = "(?P<op_$id>{$pattern})";
        }
        return implode('|', $operators);
    }

    /**
     * Compiles the regular expressions of all token types.
     *
     * @return string
     *     The compiled regular expression.
     */
    protected function getCompiledTokenRegex()
    {
        if (isset($this->compiledRegex)) {
            return $this->compiledRegex;
        }

        $regex = [
            sprintf('(?P<number>%s)', '\-?\d+\.?\d*(E-?\d+)?'),
            sprintf('(?P<function>%s)', '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*'),
            sprintf('(?P<constant>%s)', '\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*'),
            sprintf('(?P<variable>%s)', '\[[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\]'),
            sprintf('(?P<open>%s)', '\('),
            sprintf('(?P<close>%s)', '\)'),
            sprintf('(?P<comma>%s)', '\,'),
            sprintf('(?P<operator>%s)', $this->getOperatorRegex()),
        ];

        $regex = implode('|', $regex);
        return $this->compiledRegex = "/$regex/i";
    }

    /**
     * Return the parsed variables identifiers.
     * 
     * @return array
     */
    public function getVariables()
    {
        return $this->variables;
    }

}
