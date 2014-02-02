<?php

namespace Razr\Node\Expression\Binary;

use Razr\Compiler;
use Razr\Node\Node;
use Razr\Node\Expression\ExpressionNode;

abstract class BinaryNode extends ExpressionNode
{
    public function __construct(Node $left, Node $right, $lineno)
    {
        parent::__construct(compact('left', 'right'), array(), $lineno);
    }

    public function compile(Compiler $compiler)
    {
        $compiler
            ->raw('(')
            ->subcompile($this->getNode('left'))
            ->raw(' ')
        ;
        $this->operator($compiler);
        $compiler
            ->raw(' ')
            ->subcompile($this->getNode('right'))
            ->raw(')')
        ;
    }

    abstract public function operator(Compiler $compiler);
}
