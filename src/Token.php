<?php

namespace Razr;

class Token
{
    protected $type;
    protected $value;
    protected $line;

    /**
     * Constructor.
     *
     * @param int    $type
     * @param string $value
     * @param int    $line
     */
    public function __construct($type, $value, $line)
    {
        $this->type  = $type;
        $this->value = $value;
        $this->line  = $line;
    }

    /**
     * Gets the line.
     *
     * @return int
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * Gets the token type.
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Gets the token value.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Tests the token for a type and/or a value.
     *
     * @param  array|integer     $type
     * @param  array|string|null $value
     * @return bool
     */
    public function test($type, $value = null)
    {
        if ($value === null && !is_int($type)) {
            $value = $type;
            $type  = $this->type;
        }

        return ($this->type === $type) && ($value === null || (is_array($value) && in_array($this->value, $value)) || $this->value == $value);
    }

    /**
     * Returns a string with the token details.
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf('%s (%s)', self::getName($this->type), $this->value);
    }

    /**
     * Returns the token name.
     *
     * @param  int    $type
     * @return string
     */
    public static function getName($type)
    {
        if ($type == T_PUNCTUATION) {
            $type = 'T_PUNCTUATION';
        } else {
            $type = token_name($type);
        }

        return $type;
    }
}
