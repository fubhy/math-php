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

use Fubhy\Math\Calculator;

/**
 * @coversDefaultClass \Fubhy\Math\Calculator
 *
 * @author Sebastian Siemssen <fubhy@fubhy.com>
 */
class CalculatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * The parser.
     *
     * @var \Fubhy\Math\Calculator.
     */
    protected $calculator;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

    }

    /**
     * Tests that mathematical expressions are properly calculated.
     *
     * @param string $expression
     *     A mathematical expression.
     *
     * @param mixed $expected
     *     The expected result.
     *
     * @param array $variables
     *     An optional array of variables.
     *
     * @covers ::calculate
     * @dataProvider calculateProvider
     */
    public function testCalculate($expression, $expected, array $variables = [])
    {
        $actual = (new Calculator())->calculate($expression, $variables);
        $this->assertSame($expected, $actual, sprintf('Expression "%s" evaluated to %s. Expected %s.', $expression, $actual, $expected));
    }

    /**
     * Data provider for the testCalculate() test case.
     */
    public function calculateProvider()
    {
        return [
            ['3 + 2', 5],
            ['7/6', 1.1666666666667],
            ['3^5 * 5 * $pi', 3817.0350741116],
            ['(3^2) * -2 + [foo]', -13, ['foo' => 5]],
            ['abs(-5)', 5],
            ['atan2(4, -3)', 2.2142974355882],
            // Example expression from Wikipedia.
            // @see http://en.wikipedia.org/wiki/Shunting-yard_algorithm
            ['3 + 4 * 2 / ( 1 - 5 ) ^ 2 ^ 3', 3.0001220703125],
        ];
    }
}
