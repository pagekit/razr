<?php

namespace Razr;

use Razr\Exception\SyntaxErrorException;

class TokenStream
{
    protected $tokens;
    protected $current = 0;
    protected $peek = 0;

    /**
     * Constructor.
     *
     * @param array $tokens
     */
    public function __construct(array $tokens)
    {
        $line = 0;

        if (!defined('T_PUNCTUATION')) {
            define('T_PUNCTUATION', -1);
        }

        foreach ($tokens as $token) {
            if (is_array($token)) {
                $this->tokens[] = new Token($token[0], $token[1], $line = $token[2]);
            } elseif (is_string($token)) {
                $this->tokens[] = new Token(T_PUNCTUATION, $token, $line);
            }
        }
    }

    /**
     * Get a token.
     *
     * @param  int $number
     * @return Token|null
     */
    public function get($number = 0)
    {
        if (isset($this->tokens[$this->current + $number])) {
            return $this->tokens[$this->current + $number];
        }
    }

    /**
     * Next token.
     *
     * @return Token|null
     */
    public function next()
    {
        $this->peek = 0;
        if (isset($this->tokens[$this->current])) {
            return $this->tokens[$this->current++];
        }
    }

    /**
     * Previous token.
     *
     * @return Token|null
     */
    public function prev()
    {
        return $this->tokens[$this->current--];
    }

    /**
     * Gets next token if condition is true.
     *
     * @param  array|integer     $type
     * @param  array|string|null $value
     * @return Token|null
     */
    public function nextIf($type, $value = null)
    {
        if ($this->test($type, $value)) {
            return $this->next();
        }
    }

    /**
     * Tests the current token for a condition.
     *
     * @param  array|integer     $type
     * @param  array|string|null $value
     * @return Token|null
     */
    public function test($type, $value = null)
    {
        return $this->tokens[$this->current]->test($type, $value);
    }

    /**
     * Tests the current token for a condition or throws an exception otherwise.
     *
     * @param  array|integer     $type
     * @param  array|string|null $value
     * @param  string|null       $message
     * @throws SyntaxErrorException
     * @return Token|null
     */
    public function expect($type, $value = null, $message = null)
    {
        $token = $this->tokens[$this->current];

        if (!$token->test($type, $value)) {
            throw new SyntaxErrorException(sprintf('%sUnexpected token "%s" of value "%s" ("%s" expected%s) on line %s', $message ? "$message. " : "", $token, $token->getValue(), Token::getName($type), $value ? sprintf(' with value "%s"', $value) : '', $token->getLine()));
        }

        return $token;
    }

    /**
     * Resets the peek pointer to 0.
     */
    public function resetPeek()
    {
        $this->peek = 0;
    }

    /**
     * Moves the peek token forward.
     *
     * @return Token|null
     */
    public function peek()
    {
        if (isset($this->tokens[$this->current + ++$this->peek])) {
            return $this->tokens[$this->current + $this->peek];
        } else {
            return null;
        }
    }

    /**
     * Peeks until a token with the given type is found.
     *
     * @param  array|integer     $type
     * @param  array|string|null $value
     * @return Token|null
     */
    public function peekUntil($type, $value = null)
    {
        while($token = $this->peek() and !$token->test($type, $value)) {
            $token = null;
        }
        return $token;
    }

    /**
     * Peeks at the next token, returns it and immediately resets the peek.
     *
     * @return Token|null
     */
    public function glimpse()
    {
        $peek = $this->peek();
        $this->peek = 0;
        return $peek;
    }

    /**
     * Returns a string with the token stream details.
     *
     * @return string
     */
    public function __toString()
    {
        return implode("\n", $this->tokens);
    }
}
