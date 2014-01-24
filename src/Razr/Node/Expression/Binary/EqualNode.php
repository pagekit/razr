<?php

namespace Razr\Node\Expression\Binary;

use Razr\Compiler;

class EqualNode extends BinaryNode
{
    public function operator(Compiler $compiler)
    {
        return $compiler->raw('==');
    }
}
