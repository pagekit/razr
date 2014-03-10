<?php

namespace Razr\Node\Expression;

use Razr\Compiler;

/**
 * @copyright Copyright (c) 2009-2014 by the Twig Team
 */
class MethodCallNode extends ExpressionNode
{
    public function __construct(ExpressionNode $node, $method, ArrayNode $arguments, $lineno)
    {
        parent::__construct(compact('node', 'arguments'), array('method' => $method, 'safe' => false), $lineno);

        if ($node instanceof NameNode) {
            $node->setAttribute('always_defined', true);
        }
    }

    public function compile(Compiler $compiler)
    {
        $compiler
            ->subcompile($this->getNode('node'))
            ->raw('->')
            ->raw($this->getAttribute('method'))
            ->raw('(')
        ;

        $first = true;

        foreach ($this->getNode('arguments')->getKeyValuePairs() as $pair) {

            if (!$first) {
                $compiler->raw(', ');
            }

            $first = false;

            $compiler->subcompile($pair['value']);
        }

        $compiler->raw(')');
    }
}
