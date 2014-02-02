<?php

namespace Razr\Node\Expression;

use Razr\Compiler;

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
