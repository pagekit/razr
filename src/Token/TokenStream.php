<?php

namespace Razr\Token;

use Razr\Exception\SyntaxErrorException;

/**
 * @copyright Copyright (c) 2009-2014 by the Twig Team
 */
class TokenStream
{
    protected $tokens;
    protected $filename;
    protected $current = 0;

    public function __construct(array $tokens, $filename = null)
    {
        $this->tokens   = $tokens;
        $this->filename = $filename;
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function getCurrent()
    {
        return $this->tokens[$this->current];
    }

    public function injectTokens(array $tokens)
    {
        $this->tokens = array_merge(array_slice($this->tokens, 0, $this->current), $tokens, array_slice($this->tokens, $this->current));
    }

    public function next()
    {
        if (!isset($this->tokens[++$this->current])) {
            throw new SyntaxErrorException('Unexpected end of template', $this->tokens[$this->current - 1]->getLine(), $this->filename);
        }

        return $this->tokens[$this->current - 1];
    }

    public function nextIf($primary, $secondary = null)
    {
        if ($this->tokens[$this->current]->test($primary, $secondary)) {
            return $this->next();
        }
    }

    public function expect($type, $value = null, $message = null)
    {
        $token = $this->tokens[$this->current];

        if (!$token->test($type, $value)) {
            throw new SyntaxErrorException(sprintf('%sUnexpected token "%s" of value "%s" ("%s" expected%s) in file %s on line %s',
                $message ? $message.'. ' : '',
                Token::typeToEnglish($token->getType()),
                $token->getValue(),
                Token::typeToEnglish($type),
                $value ? sprintf(' with value "%s"', $value) : '',
                $this->filename,
                $token->getLine()
            ));
        }

        $this->next();

        return $token;
    }

    public function look($number = 1)
    {
        if (!isset($this->tokens[$this->current + $number])) {
            throw new SyntaxErrorException('Unexpected end of template', $this->tokens[$this->current + $number - 1]->getLine(), $this->filename);
        }

        return $this->tokens[$this->current + $number];
    }

    public function test($primary, $secondary = null)
    {
        return $this->tokens[$this->current]->test($primary, $secondary);
    }

    public function isEOF()
    {
        return $this->tokens[$this->current]->getType() === Token::EOF;
    }

    public function __toString()
    {
        return implode("\n", $this->tokens);
    }
}
