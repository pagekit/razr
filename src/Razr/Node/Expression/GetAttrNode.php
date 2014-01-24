<?php

namespace Razr\Node\Expression;

use Razr\Compiler;
use Razr\Template;

class GetAttrNode extends ExpressionNode
{
    public function __construct(ExpressionNode $node, ExpressionNode $attribute, ArrayNode $arguments, $type, $lineno)
    {
        parent::__construct(compact('node', 'attribute', 'arguments'), array('type' => $type, 'is_defined_test' => false, 'ignore_strict_check' => false), $lineno);
    }

    public function compile(Compiler $compiler)
    {
        $compiler->raw('$this->getAttribute(');

        if ($this->getAttribute('ignore_strict_check')) {
            $this->getNode('node')->setAttribute('ignore_strict_check', true);
        }

        $compiler->subcompile($this->getNode('node'));

        $compiler->raw(', ')->subcompile($this->getNode('attribute'));

        if (count($this->getNode('arguments')) || Template::ANY_CALL !== $this->getAttribute('type') || $this->getAttribute('is_defined_test') || $this->getAttribute('ignore_strict_check')) {
            $compiler->raw(', ')->subcompile($this->getNode('arguments'));

            if (Template::ANY_CALL !== $this->getAttribute('type') || $this->getAttribute('is_defined_test') || $this->getAttribute('ignore_strict_check')) {
                $compiler->raw(', ')->repr($this->getAttribute('type'));
            }

            if ($this->getAttribute('is_defined_test') || $this->getAttribute('ignore_strict_check')) {
                $compiler->raw(', '.($this->getAttribute('is_defined_test') ? 'true' : 'false'));
            }

            if ($this->getAttribute('ignore_strict_check')) {
                $compiler->raw(', '.($this->getAttribute('ignore_strict_check') ? 'true' : 'false'));
            }
        }

        $compiler->raw(')');
    }
}
