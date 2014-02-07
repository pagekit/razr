<?php

namespace Razr\Node\Expression\Unary;

use Razr\Compiler;
use Razr\Node\Expression\ExpressionNode;
use Razr\Node\Node;

abstract class UnaryNode extends ExpressionNode
{
    public function __construct(Node $node, $lineno)
    {
        parent::__construct(compact('node'), array(), $lineno);
    }

    public function compile(Compiler $compiler)
    {
        $compiler->raw('(');
        $this->operator($compiler);
        $compiler
            ->subcompile($this->getNode('node'))
            ->raw(')')
        ;
    }

    abstract public function operator(Compiler $compiler);
}
