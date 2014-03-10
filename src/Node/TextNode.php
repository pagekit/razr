<?php

namespace Razr\Node;

use Razr\Compiler;

/**
 * @copyright Copyright (c) 2009-2014 by the Twig Team
 */
class TextNode extends Node implements NodeOutputInterface
{
    public function __construct($data, $lineno)
    {
        parent::__construct(array(), compact('data'), $lineno);
    }

    public function compile(Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write('echo ')
            ->string($this->getAttribute('data'))
            ->raw(";\n")
        ;
    }
}
