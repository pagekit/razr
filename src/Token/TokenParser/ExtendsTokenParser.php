<?php

namespace Razr\Token\TokenParser;

use Razr\Exception\SyntaxErrorException;
use Razr\Token\Token;

/**
 * @copyright Copyright (c) 2009-2014 by the Twig Team
 */
class ExtendsTokenParser extends TokenParser
{
    public function getTag()
    {
        return 'extends';
    }

    public function parse(Token $token)
    {
        if (!$this->parser->isMainScope()) {
            throw new SyntaxErrorException('Cannot extend from a block', $token->getLine(), $this->parser->getFilename());
        }

        if (null !== $this->parser->getParent()) {
            throw new SyntaxErrorException('Multiple extends tags are forbidden', $token->getLine(), $this->parser->getFilename());
        }

        $this->parser->setParent($this->parser->getExpressionParser()->parseExpression());
        $this->parser->getStream()->expect(Token::BLOCK_END);
    }
}
