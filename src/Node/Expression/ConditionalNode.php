<?php

namespace Razr\Node\Expression;

use Razr\Compiler;

class ConditionalNode extends ExpressionNode
{
    public function __construct(ExpressionNode $expr1, ExpressionNode $expr2, ExpressionNode $expr3, $lineno)
    {
        parent::__construct(compact('expr1', 'expr2', 'expr3'), array(), $lineno);
    }

    public function compile(Compiler $compiler)
    {
        $compiler
            ->raw('((')
            ->subcompile($this->getNode('expr1'))
            ->raw(') ? (')
            ->subcompile($this->getNode('expr2'))
            ->raw(') : (')
            ->subcompile($this->getNode('expr3'))
            ->raw('))')
        ;
    }
}
