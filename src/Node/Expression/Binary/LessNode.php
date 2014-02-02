<?php

namespace Razr\Node\Expression\Binary;

use Razr\Compiler;

class LessNode extends BinaryNode
{
    public function operator(Compiler $compiler)
    {
        return $compiler->raw('<');
    }
}
