<?php

namespace Razr\Node\Expression\Unary;

use Razr\Compiler;

class NegNode extends UnaryNode
{
    public function operator(Compiler $compiler)
    {
        $compiler->raw('-');
    }
}
