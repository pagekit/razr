<?php

namespace Razr\Node\Expression;

use Razr\Compiler;
use Razr\Node\Node;
use Razr\SimpleFilter;

/**
 * @copyright Copyright (c) 2009-2014 by the Twig Team
 */
class FilterNode extends CallNode
{
    public function __construct(Node $node, ConstantNode $filter, Node $arguments, $lineno, $tag = null)
    {
        parent::__construct(compact('node', 'filter', 'arguments'), array(), $lineno, $tag);
    }

    public function compile(Compiler $compiler)
    {
        $name   = $this->getNode('filter')->getAttribute('value');
        $filter = $compiler->getEnvironment()->getFilter($name);

        $this->setAttribute('name', $name);
        $this->setAttribute('type', 'filter');
        $this->setAttribute('thing', $filter);
        $this->setAttribute('needs_environment', $filter->needsEnvironment());
        $this->setAttribute('needs_context', $filter->needsContext());
        $this->setAttribute('arguments', $filter->getArguments());

        if ($filter instanceof SimpleFilter) {
            $this->setAttribute('callable', $filter->getCallable());
        }

        $this->compileCallable($compiler);
    }
}
