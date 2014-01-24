<?php

namespace Razr\Node\Expression\Unary;

use Razr\Compiler;

class PosNode extends UnaryNode
{
    public function operator(Compiler $compiler)
    {
        $compiler->raw('+');
    }
}
