<?php

namespace Razr\Node\Expression\Binary;

use Razr\Compiler;
use Razr\Node\Expression\ExpressionNode;
use Razr\Node\Node;

/**
 * @copyright Copyright (c) 2009-2014 by the Twig Team
 */
abstract class BinaryNode extends ExpressionNode
{
    public function __construct(Node $left, Node $right, $lineno)
    {
        parent::__construct(compact('left', 'right'), array(), $lineno);
    }

    public function compile(Compiler $compiler)
    {
        $compiler
            ->raw('(')
            ->subcompile($this->getNode('left'))
            ->raw(' ')
        ;
        $this->operator($compiler);
        $compiler
            ->raw(' ')
            ->subcompile($this->getNode('right'))
            ->raw(')')
        ;
    }

    abstract public function operator(Compiler $compiler);
}
