<?php

namespace Razr\Node\Expression\Binary;

use Razr\Compiler;

class EqualTypeNode extends BinaryNode
{
    public function operator(Compiler $compiler)
    {
        return $compiler->raw('===');
    }
}
