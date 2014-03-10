<?php

namespace Razr\Node;

use Razr\Environment;

/**
 * @copyright Copyright (c) 2009-2014 by the Twig Team
 */
class NodeTraverser
{
    /**
     * @var Environment
     */
    protected $env;

    /**
     * @var NodeVisitorInterface[]
     */
    protected $visitors = array();

    /**
     * Constructor.
     *
     * @param Environment            $env
     * @param NodeVisitorInterface[] $visitors
     */
    public function __construct(Environment $env, array $visitors = array())
    {
        $this->env = $env;

        foreach ($visitors as $visitor) {
            $this->addVisitor($visitor);
        }
    }

    /**
     * Adds a visitor.
     *
     * @param NodeVisitorInterface $visitor
     */
    public function addVisitor(NodeVisitorInterface $visitor)
    {
        $priority = $visitor->getPriority();

        if (!isset($this->visitors[$priority])) {
            $this->visitors[$priority] = array();
        }

        $this->visitors[$priority][] = $visitor;
    }

    /**
     * Traverses a node and calls the registered visitors.
     *
     * @param  Node $node
     * @return Node|false|null
     */
    public function traverse(Node $node)
    {
        ksort($this->visitors);

        foreach ($this->visitors as $visitors) {
            foreach ($visitors as $visitor) {
                $node = $this->traverseForVisitor($visitor, $node);
            }
        }

        return $node;
    }

    /**
     * Traverses a node with a given visitors.
     *
     * @param  NodeVisitorInterface $visitor
     * @param  Node                 $node
     * @return Node|false|null
     */
    protected function traverseForVisitor(NodeVisitorInterface $visitor, Node $node = null)
    {
        if ($node === null) {
            return null;
        }

        $node = $visitor->enterNode($node, $this->env);

        foreach ($node as $k => $n) {
            if (false !== $n = $this->traverseForVisitor($visitor, $n)) {
                $node->setNode($k, $n);
            } else {
                $node->removeNode($k);
            }
        }

        return $visitor->leaveNode($node, $this->env);
    }
}
