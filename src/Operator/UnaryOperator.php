<?php

namespace Razr\Operator;

use Razr\Node\Node;

class UnaryOperator
{
    /**
     * The precedence of the operator.
     *
     * @var int
     */
    protected $precedence;

    /**
     * The class of the node object for this operator.
     *
     * @var string
     */
    protected $class;

    /**
     * Constructor.
     *
     * @param int    $precedence
     * @param string $class
     */
    public function __construct($precedence, $class)
    {
        $this->precedence = $precedence;
        $this->class = $class;
    }

    /**
     * Returns the new node object for this operator.
     *
     * @return Node
     */
    public function getNode(Node $node, $lineno)
    {
        $class = $this->class;

        return new $class($node, $lineno);
    }

    /**
     * Returns the precedence of the operator.
     *
     * @return int
     */
    public function getPrecedence()
    {
        return $this->precedence;
    }
}
