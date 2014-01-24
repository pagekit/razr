<?php

namespace Razr\Node;

use Razr\Compiler;

class BlockNode extends Node
{
    public function __construct($name, Node $body, $lineno, $tag = null)
    {
        parent::__construct(compact('body'), compact('name'), $lineno, $tag);
    }

    public function compile(Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write(sprintf("public function block_%s(\$context, array \$blocks = array())\n", $this->getAttribute('name')), "{\n")
            ->indent()
        ;

        $compiler
            ->subcompile($this->getNode('body'))
            ->outdent()
            ->write("}\n\n")
        ;
    }
}
