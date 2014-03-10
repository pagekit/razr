<?php

namespace Razr\Node\Expression;

use Razr\Compiler;

/**
 * @copyright Copyright (c) 2009-2014 by the Twig Team
 */
class BlockReferenceNode extends ExpressionNode
{
    public function __construct(Node $name, $asString = false, $lineno, $tag = null)
    {
        parent::__construct(compact('name'), array('as_string' => $asString, 'output' => false), $lineno, $tag);
    }

    public function compile(Compiler $compiler)
    {
        if ($this->getAttribute('as_string')) {
            $compiler->raw('(string) ');
        }

        if ($this->getAttribute('output')) {
            $compiler
                ->addDebugInfo($this)
                ->write("\$this->displayBlock(")
                ->subcompile($this->getNode('name'))
                ->raw(", \$context, \$blocks);\n")
            ;
        } else {
            $compiler
                ->raw("\$this->renderBlock(")
                ->subcompile($this->getNode('name'))
                ->raw(", \$context, \$blocks)")
            ;
        }
    }
}
