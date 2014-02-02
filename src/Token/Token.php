<?php

namespace Razr\Token;

use Razr\Exception\LogicException;

class Token
{
    const EOF         = 0;
    const TEXT        = 1;
    const BLOCK_START = 2;
    const BLOCK_END   = 3;
    const VAR_START   = 4;
    const VAR_END     = 5;
    const NAME        = 6;
    const NUMBER      = 7;
    const STRING      = 8;
    const OPERATOR    = 9;
    const PUNCTUATION = 10;

    protected $type;
    protected $value;
    protected $lineno;

    /**
     * Constructor.
     *
     * @param integer $type   The type of the token
     * @param string  $value  The token value
     * @param integer $lineno The line position in the source
     */
    public function __construct($type, $value, $lineno)
    {
        $this->type   = $type;
        $this->value  = $value;
        $this->lineno = $lineno;
    }

    /**
     * Tests the current token for a type and/or a value.
     *
     * Parameters may be:
     * * just type
     * * type and value (or array of possible values)
     * * just value (or array of possible values) (NAME is used as type)
     *
     * @param array|integer     $type   The type to test
     * @param array|string|null $values The token value
     *
     * @return Boolean
     */
    public function test($type, $values = null)
    {
        if (null === $values && !is_int($type)) {
            $values = $type;
            $type   = self::NAME;
        }

        return ($this->type === $type) && (null === $values || (is_array($values) && in_array($this->value, $values)) || $this->value == $values);
    }

    /**
     * Gets the line.
     *
     * @return integer The source line
     */
    public function getLine()
    {
        return $this->lineno;
    }

    /**
     * Gets the token type.
     *
     * @return integer The token type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Gets the token value.
     *
     * @return string The token value
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Returns the constant representation (internal) of a given type.
     *
     * @param integer $type  The type as an integer
     * @param Boolean $short Whether to return a short representation or not
     * @param integer $line  The code line
     *
     * @return string The string representation
     */
    public static function typeToString($type, $short = false, $line = -1)
    {
        $types = array(
            self::EOF         => 'EOF',
            self::TEXT        => 'TEXT',
            self::BLOCK_START => 'BLOCK_START',
            self::BLOCK_END   => 'BLOCK_END',
            self::VAR_START   => 'VAR_START',
            self::VAR_END     => 'VAR_END',
            self::NAME        => 'NAME',
            self::NUMBER      => 'NUMBER',
            self::STRING      => 'STRING',
            self::OPERATOR    => 'OPERATOR',
            self::PUNCTUATION => 'PUNCTUATION',
        );

        if (!array_key_exists($type, $types)) {
            throw new LogicException(sprintf('Token of type "%s" does not exist.', $type));
        }

        return $short ? $types[$type] : 'Token::'.$types[$type];
    }

    /**
     * Returns the english representation of a given type.
     *
     * @param integer $type The type as an integer
     *
     * @return string The string representation
     */
    public static function typeToEnglish($type)
    {
        $types = array(
            self::EOF         => 'end of template',
            self::TEXT        => 'text',
            self::BLOCK_START => 'begin of block statement',
            self::BLOCK_END   => 'end of block statement',
            self::VAR_START   => 'begin of print statement',
            self::VAR_END     => 'end of print statement',
            self::NAME        => 'name',
            self::NUMBER      => 'number',
            self::STRING      => 'string',
            self::OPERATOR    => 'operator',
            self::PUNCTUATION => 'punctuation'
        );

        if (!array_key_exists($type, $types)) {
            throw new LogicException(sprintf('Token of type "%s" does not exist.', $type));
        }

        return $types[$type];
    }

    /**
     * Returns a string representation of the token.
     *
     * @return string A string representation of the token
     */
    public function __toString()
    {
        return sprintf('%s(%s)', self::typeToString($this->type, true, $this->lineno), $this->value);
    }
}
