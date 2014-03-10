<?php

namespace Razr\Node\Expression\Unary;

use Razr\Compiler;

/**
 * @copyright Copyright (c) 2009-2014 by the Twig Team
 */
class PosNode extends UnaryNode
{
    public function operator(Compiler $compiler)
    {
        $compiler->raw('+');
    }
}
