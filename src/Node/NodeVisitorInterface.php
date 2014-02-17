<?php

namespace Razr\Node;

use Razr\Environment;

interface NodeVisitorInterface
{
    /**
     * Called before child nodes are visited.
     *
     * @param  Node        $node
     * @param  Environment $env
     * @return Node
     */
    public function enterNode(Node $node, Environment $env);

    /**
     * Called after child nodes are visited.
     *
     * @param  Node        $node
     * @param  Environment $env
     * @return Node|false
     */
    public function leaveNode(Node $node, Environment $env);

    /**
     * Returns the visitor priority.
     *
     * @return integer
     */
    public function getPriority();
}
