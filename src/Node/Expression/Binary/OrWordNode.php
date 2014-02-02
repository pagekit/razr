<?php

namespace Razr\Node\Expression\Binary;

use Razr\Compiler;

class OrWordNode extends BinaryNode
{
    public function operator(Compiler $compiler)
    {
        return $compiler->raw('or');
    }
}
