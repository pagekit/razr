<?php

namespace Razr\Node\Expression;

use Razr\Compiler;

class AssignNameNode extends NameNode
{
    public function compile(Compiler $compiler)
    {
        $compiler
            ->raw('$context[')
            ->string($this->getAttribute('name'))
            ->raw(']')
        ;
    }
}
