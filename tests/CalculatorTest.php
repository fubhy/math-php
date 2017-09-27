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
use Fubhy\Math\Exception\UnknownVariableException;
use Fubhy\Math\VariableResolverInterface;

/**
 * @coversDefaultClass \Fubhy\Math\Calculator
 *
 * @author Sebastian Siemssen <fubhy@fubhy.com>
 */
class CalculatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests that mathematical expressions are properly calculated.
     *
     * @param string $expression
     *     A mathematical expression.
     * @param mixed $expected
     *     The expected result.
     * @param array $variables
     *     An optional array of variables.
     *
     * @covers ::calculate
     * @dataProvider calculateProvider
     */
    public function testCalculate($expression, $expected, array $variables = [])
    {
        $actual = (new Calculator())->calculate($expression, $variables);
        $this->assertSame(
          0,
          bccomp($expected, $actual),
          sprintf('Expression "%s" evaluated to %s. Expected %s.', $expression, $actual, $expected)
        );
    }

    /**
     * Test expression calculation with variables resolved through a variable resolver
     *
     * @covers ::calculate
     */
    public function testCalculateWithVariableResolver()
    {

        $expression = '2 + 5 - [VAR1] + [VAR2] * [VAR3]';
        $calc = new Calculator();
        $calc->setVariableResolver(new TestVariableResolver());
        $this->assertEquals(12, $calc->calculate($expression));

        // ::calculate array overrides resolved variables
        $this->assertEquals(10, $calc->calculate($expression, array('VAR1' => 3)));

    }

    /**
     *  Test correct exception is thrown on using a non existing variable on an expression
     *
     *  @covers ::calculate
     */
    public function testCalculateExceptionOnNonExistingVariable()
    {

        $expression = '4 * [VAR5]';

        $calc = new Calculator();
        try {
            $calc->calculate($expression);
            $this->fail('Should throw UnknownVariableException and never reach this code.');
        } catch (UnknownVariableException $e) {
            $this->assertEquals('Could not find value for variable VAR5 at offset 4', $e->getMessage());
        }

        $calc->setVariableResolver(new TestVariableResolver());

        try {
            $calc->calculate($expression);
            $this->fail('Should throw UnknownVariableException and never reach this code.');
        } catch (UnknownVariableException $e) {
            $this->assertEquals('Could not find value for variable VAR5 at offset 4', $e->getMessage());
        }

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
          ['signum(-5)', -1],
          ['sqrt(5)', 2.2360679774],
            // Example expression from Wikipedia.
            // @see http://en.wikipedia.org/wiki/Shunting-yard_algorithm
          ['3 + 4 * 2 / ( 1 - 5 ) ^ 2 ^ 3', 3.0001220703125],
        ];
    }
}

class TestVariableResolver implements VariableResolverInterface
{

    /**
     * @param $identifier
     * @return int|float|null, return null if the variable could not be resolved
     */
    public function resolveVariable($identifier)
    {
        switch ($identifier) {
            case 'VAR1':
                return 1;
                break;
            case 'VAR2':
                return 2;
                break;
            case 'VAR3':
                return 3;
                break;
            default:
                return null;
                break;
        }
    }
}
