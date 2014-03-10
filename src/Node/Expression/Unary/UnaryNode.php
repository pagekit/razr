<?php

namespace Razr\Node\Expression\Unary;

use Razr\Compiler;
use Razr\Node\Expression\ExpressionNode;
use Razr\Node\Node;

/**
 * @copyright Copyright (c) 2009-2014 by the Twig Team
 */
abstract class UnaryNode extends ExpressionNode
{
    public function __construct(Node $node, $lineno)
    {
        parent::__construct(compact('node'), array(), $lineno);
    }

    public function compile(Compiler $compiler)
    {
        $compiler->raw('(');
        $this->operator($compiler);
        $compiler
            ->subcompile($this->getNode('node'))
            ->raw(')')
        ;
    }

    abstract public function operator(Compiler $compiler);
}
