<?php

namespace Razr\Node\Expression;

use Razr\Compiler;

/**
 * @copyright Copyright (c) 2009-2014 by the Twig Team
 */
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
