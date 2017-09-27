<?php

/*
 * This file is part of the fubhy/math-php package.
 *
 * (c) Sebastian Siemssen <fubhy@fubhy.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fubhy\Math\Tests\Unit;

use Fubhy\Math\Lexer;
use Fubhy\Math\Token\CommaToken;
use Fubhy\Math\Token\FunctionToken;
use Fubhy\Math\Token\NumberToken;
use Fubhy\Math\Token\Operator\DivisionToken;
use Fubhy\Math\Token\Operator\MinusToken;
use Fubhy\Math\Token\Operator\MultiplyToken;
use Fubhy\Math\Token\Operator\PlusToken;
use Fubhy\Math\Token\Operator\PowerToken;
use Fubhy\Math\Token\ParenthesisCloseToken;
use Fubhy\Math\Token\ParenthesisOpenToken;
use Fubhy\Math\Token\VariableToken;

/**
 * @coversDefaultClass \Fubhy\Math\Lexer
 *
 * @author Sebastian Siemssen <fubhy@fubhy.com>
 */
class LexerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * The lexer.
     *
     * @var \Fubhy\Math\Lexer.
     */
    protected $lexer;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->lexer = new Lexer();
        $this->lexer
            ->addOperator('plus', 'Fubhy\Math\Token\Operator\PlusToken')
            ->addOperator('minus', 'Fubhy\Math\Token\Operator\MinusToken')
            ->addOperator('multiply', 'Fubhy\Math\Token\Operator\MultiplyToken')
            ->addOperator('division', 'Fubhy\Math\Token\Operator\DivisionToken')
            ->addOperator('modulus', 'Fubhy\Math\Token\Operator\ModulusToken')
            ->addOperator('power', 'Fubhy\Math\Token\Operator\PowerToken');

        $this->lexer
            ->addFunction('abs', 'abs', 1)
            ->addFunction('atan2', 'atan2', 2);

        $this->lexer
            ->addConstant('pi', pi());
    }

    /**
     * Tests that mathematical expressions are properly tokenized.
     *
     * @param string $expression
     *     A mathematical expression.
     * @param \Fubhy\Math\Token\TokenInterface[] $tokens
     *     The list of matched tokens.
     *
     * @covers ::tokenize
     * @dataProvider tokenizeProvider
     */
    public function testTokenize($expression, $tokens)
    {
        $this->assertArraySubset($tokens, $this->lexer->tokenize($expression));
    }

    /**
     * Tests that the token stream is properly translated into postfix.
     *
     * @param \Fubhy\Math\Token\TokenInterface[] $tokens
     *     The list of tokens in infix notation.
     * @param \Fubhy\Math\Token\TokenInterface[] $postfix
     *     The list of tokens in postfix notation.
     *
     * @covers ::postfix
     * @dataProvider postfixProvider
     */
    public function testPostfix($tokens, $postfix)
    {
        $this->assertArraySubset($postfix, $this->lexer->postfix($tokens));
    }

    /**
     * Tests that variables identifiers can be correctly retrieved from Lexer after tokenization.
     *
     * @covers ::getVariables
     */
    public function testVariables()
    {
        $expression = '2 + 3 + [VAR1] + [VAR2] * [VAR1]';
        $this->lexer->tokenize($expression);

        $this->assertEquals(array('VAR1', 'VAR2'), $this->lexer->getVariables());

    }

    /**
     * Data provider for the testTokenize() test case.
     */
    public function tokenizeProvider()
    {
        return [
            ['3 + 2', [
                new NumberToken(0, 3),
                new PlusToken(2, '+'),
                new NumberToken(4, 2),
            ]],
            ['7/6', [
                new NumberToken(0, 7),
                new DivisionToken(1, '/'),
                new NumberToken(2, 6),
            ]],
            ['3^5 * 5 * $pi', [
                new NumberToken(0, 3),
                new PowerToken(1, '^'),
                new NumberToken(2, 5),
                new MultiplyToken(4, '*'),
                new NumberToken(6, 5),
                new MultiplyToken(8, '*'),
                new NumberToken(10, pi()),
            ]],
            ['(3^2) * -2 + [foo]', [
                new ParenthesisOpenToken(0, '('),
                new NumberToken(1, 3),
                new PowerToken(2, '^'),
                new NumberToken(3, 2),
                new ParenthesisCloseToken(4, ')'),
                new MultiplyToken(6, '*'),
                new NumberToken(8, -2),
                new PlusToken(11, '+'),
                new VariableToken(13, 'foo'),
            ]],
            ['abs(-5)', [
                new FunctionToken(0, [1, 'abs']),
                new ParenthesisOpenToken(3, '('),
                new NumberToken(4, -5),
                new ParenthesisCloseToken(6, ')'),
            ]],
            ['atan2(4, -3)', [
                new FunctionToken(0, [2, 'atan2']),
                new ParenthesisOpenToken(5, '('),
                new NumberToken(6, 4),
                new CommaToken(7, ','),
                new NumberToken(9, -3),
                new ParenthesisCloseToken(11, ')'),
            ]],
            // Example expression from Wikipedia.
            // http://en.wikipedia.org/wiki/Shunting-yard_algorithm
            ['3 + 4 * 2 / ( 1 - 5 ) ^ 2 ^ 3', [
                new NumberToken(0, 3),
                new PlusToken(2, '+'),
                new NumberToken(4, 4),
                new MultiplyToken(6, '*'),
                new NumberToken(8, 2),
                new DivisionToken(10, '/'),
                new ParenthesisOpenToken(12, '('),
                new NumberToken(14, 1),
                new MinusToken(16, '-'),
                new NumberToken(18, 5),
                new ParenthesisCloseToken(20, ')'),
                new PowerToken(22, '^'),
                new NumberToken(24, 2),
                new PowerToken(26, '^'),
                new NumberToken(28, 3),
            ]],
        ];
    }

    /**
     * Data provider for the testPostfix() test case.
     */
    public function postfixProvider()
    {
        return [[[
            new NumberToken(0, 3),
            new PlusToken(2, '+'),
            new NumberToken(4, 2),
        ], [
            new NumberToken(0, 3),
            new NumberToken(4, 2),
            new PlusToken(2, '+'),
        ]], [[
            new ParenthesisOpenToken(0, '('),
            new NumberToken(1, 3),
            new PowerToken(2, '^'),
            new NumberToken(3, 2),
            new ParenthesisCloseToken(4, ')'),
            new MultiplyToken(6, '*'),
            new NumberToken(8, -2),
        ], [
            new NumberToken(1, 3),
            new NumberToken(3, 2),
            new PowerToken(2, '^'),
            new NumberToken(8, -2),
            new MultiplyToken(6, '*'),
        ]],
            // Example expression from Wikipedia.
            // http://en.wikipedia.org/wiki/Shunting-yard_algorithm
        [[
            new NumberToken(0, 3),
            new PlusToken(2, '+'),
            new NumberToken(4, 4),
            new MultiplyToken(6, '*'),
            new NumberToken(8, 2),
            new DivisionToken(10, '/'),
            new ParenthesisOpenToken(12, '('),
            new NumberToken(14, 1),
            new MinusToken(16, '-'),
            new NumberToken(18, 5),
            new ParenthesisCloseToken(20, ')'),
            new PowerToken(22, '^'),
            new NumberToken(24, 2),
            new PowerToken(26, '^'),
            new NumberToken(28, 3),
        ], [
            new NumberToken(0, 3),
            new NumberToken(4, 4),
            new NumberToken(8, 2),
            new MultiplyToken(6, '*'),
            new NumberToken(14, 1),
            new NumberToken(18, 5),
            new MinusToken(16, '-'),
            new NumberToken(24, 2),
            new NumberToken(28, 3),
            new PowerToken(26, '^'),
            new PowerToken(22, '^'),
            new DivisionToken(10, '/'),
            new PlusToken(2, '+'),
        ]]];
    }
}
