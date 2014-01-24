<?php

namespace Razr\Node\Expression\Binary;

use Razr\Compiler;

class NotEqualNode extends BinaryNode
{
    public function operator(Compiler $compiler)
    {
        return $compiler->raw('!=');
    }
}
