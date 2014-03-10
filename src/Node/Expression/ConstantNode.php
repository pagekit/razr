<?php

namespace Razr\Node\Expression;

use Razr\Compiler;

/**
 * @copyright Copyright (c) 2009-2014 by the Twig Team
 */
class ConstantNode extends ExpressionNode
{
    public function __construct($value, $lineno)
    {
        parent::__construct(array(), compact('value'), $lineno);
    }

    public function compile(Compiler $compiler)
    {
        $compiler->repr($this->getAttribute('value'));
    }
}
