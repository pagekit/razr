<?php

namespace Razr\Node\Expression\Binary;

use Razr\Compiler;

/**
 * @copyright Copyright (c) 2009-2014 by the Twig Team
 */
class EqualTypeNode extends BinaryNode
{
    public function operator(Compiler $compiler)
    {
        return $compiler->raw('===');
    }
}
