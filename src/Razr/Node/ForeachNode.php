<?php

namespace Razr\Node;

use Razr\Compiler;
use Razr\Node\Expression\AssignNameNode;
use Razr\Node\Expression\ExpressionNode;

class ForeachNode extends Node
{
    public function __construct(AssignNameNode $key_target, AssignNameNode $value_target, ExpressionNode $seq, Node $body, $lineno, $tag = null)
    {
        parent::__construct(compact('key_target', 'value_target', 'seq', 'body'), array(), $lineno, $tag);
    }

    public function compile(Compiler $compiler)
    {
        $compiler
            ->write("foreach (\$this->env->getExtension('core')->ensureTraversable(")
            ->subcompile($this->getNode('seq'))
            ->raw(") as ")
            ->subcompile($this->getNode('key_target'))
            ->raw(" => ")
            ->subcompile($this->getNode('value_target'))
            ->raw(") {\n")
            ->indent()
            ->subcompile($this->getNode('body'))
            ->outdent()
            ->write("}\n")
        ;
    }
}
