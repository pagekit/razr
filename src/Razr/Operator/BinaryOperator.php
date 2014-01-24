<?php

namespace Razr\Operator;

use Razr\Node\Node;

class BinaryOperator
{
    const LEFT  = 1;
    const RIGHT = 2;

    /**
     * The precedence of the operator.
     *
     * @var int
     */
    protected $precedence;

    /**
     * The associativity of the operator, (LEFT or RIGHT)
     *
     * @var int
     */
    protected $associativity;

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
     * @param int    $associativity
     * @param string $class
     */
    public function __construct($precedence, $associativity, $class)
    {
        $this->precedence = $precedence;
        $this->associativity = $associativity;
        $this->class = $class;
    }

    /**
     * Returns the new node object for this operator.
     *
     * @return Node
     */
    public function getNode(Node $left, Node $right, $lineno)
    {
        $class = $this->class;

        return new $class($left, $right, $lineno);
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

    /**
     * Checks if the operator is left associative.
     *
     * @return bool
     */
    public function isLeftAssociative()
    {
        return $this->associativity === self::LEFT;
    }

    /**
     * Checks if the operator is right associative.
     *
     * @return bool
     */
    public function isRightAssociative()
    {
        return $this->associativity === self::RIGHT;
    }
}
