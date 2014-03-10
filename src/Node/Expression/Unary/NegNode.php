<?php

namespace Razr\Node\Expression\Unary;

use Razr\Compiler;

/**
 * @copyright Copyright (c) 2009-2014 by the Twig Team
 */
class NegNode extends UnaryNode
{
    public function operator(Compiler $compiler)
    {
        $compiler->raw('-');
    }
}
