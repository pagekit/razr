<?php

namespace Razr\Node;

use Razr\Compiler;

/**
 * @copyright Copyright (c) 2009-2014 by the Twig Team
 */
class SetNode extends Node
{
    public function __construct(Node $names, Node $values, $lineno, $tag = null)
    {
        parent::__construct(compact('names', 'values'), array(), $lineno, $tag);
    }

    public function compile(Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        if (count($this->getNode('names')) == 1) {

            $compiler->subcompile($this->getNode('names'), false);
            $compiler->raw(' = ');
            $compiler->subcompile($this->getNode('values'));

        } else {

            $compiler->write('list(');

            foreach ($this->getNode('names') as $idx => $node) {

                if ($idx) {
                    $compiler->raw(', ');
                }

                $compiler->subcompile($node);
            }

            $compiler->raw(') = array(');

            foreach ($this->getNode('values') as $idx => $value) {

                if ($idx) {
                    $compiler->raw(', ');
                }

                $compiler->subcompile($value);
            }

            $compiler->raw(')');
        }

        $compiler->raw(";\n");
    }
}
