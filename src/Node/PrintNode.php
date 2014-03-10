<?php

namespace Razr\Node;

use Razr\Compiler;
use Razr\Node\Expression\ExpressionNode;

/**
 * @copyright Copyright (c) 2009-2014 by the Twig Team
 */
class PrintNode extends Node implements NodeOutputInterface
{
    public function __construct(ExpressionNode $expr, $lineno, $tag = null)
    {
        parent::__construct(compact('expr'), array(), $lineno, $tag);
    }

    public function compile(Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write('echo ')
            ->subcompile($this->getNode('expr'))
            ->raw(";\n")
        ;
    }
}
