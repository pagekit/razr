<?php

namespace Razr\Node;

use Razr\Compiler;
use Razr\Node\Expression\ExpressionNode;

class WhileNode extends Node
{
    public function __construct(ExpressionNode $expr, Node $body, $lineno, $tag = null)
    {
        parent::__construct(compact('expr', 'body'), array(), $lineno, $tag);
    }

    public function compile(Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write('while (')
            ->subcompile($this->getNode('expr'))
            ->raw(") {\n")
            ->indent()
            ->subcompile($this->getNode('body'))
            ->outdent()
            ->write("}\n");
        ;
    }
}
