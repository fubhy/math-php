<?php
/**
 * Created by IntelliJ IDEA.
 * User: martino
 * Date: 27/09/17
 * Time: 09:19
 */

namespace Fubhy\Math;

/**
 * Classes implementing this interface can be used in Calculator to resolve variables at runtime.
 *
 * @package Fubhy\Math
 */
interface VariableResolverInterface
{
    /**
     * Return the variable value given the identifier, return null when the variable can not be found.
     *
     * @param $identifier
     * @return int|float|null
     */
    public function resolveVariable($identifier);
}