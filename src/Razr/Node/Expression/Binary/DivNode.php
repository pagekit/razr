<?php

namespace Razr\Node\Expression\Binary;

use Razr\Compiler;

class DivNode extends BinaryNode
{
    public function operator(Compiler $compiler)
    {
        return $compiler->raw('/');
    }
}
