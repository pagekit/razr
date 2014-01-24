<?php

namespace Razr\Node\Expression;

use Razr\Compiler;
use Razr\SimpleFunction;
use Razr\Node\Node;

class FunctionNode extends CallNode
{
    public function __construct($name, Node $arguments, $lineno)
    {
        parent::__construct(compact('arguments'), compact('name'), $lineno);
    }

    public function compile(Compiler $compiler)
    {
        $name = $this->getAttribute('name');
        $function = $compiler->getEnvironment()->getFunction($name);

        $this->setAttribute('name', $name);
        $this->setAttribute('type', 'function');
        $this->setAttribute('thing', $function);
        $this->setAttribute('needs_environment', $function->needsEnvironment());
        $this->setAttribute('needs_context', $function->needsContext());
        $this->setAttribute('arguments', $function->getArguments());

        if ($function instanceof SimpleFunction) {
            $this->setAttribute('callable', $function->getCallable());
        }

        $this->compileCallable($compiler);
    }
}
