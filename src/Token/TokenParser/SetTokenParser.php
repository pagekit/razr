<?php

namespace Razr\Token\TokenParser;

use Razr\Node\SetNode;
use Razr\Token\Token;

/**
 * @copyright Copyright (c) 2009-2014 by the Twig Team
 */
class SetTokenParser extends TokenParser
{
    public function getTag()
    {
        return 'set';
    }

    public function parse(Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        extract($this->parser->getExpressionParser()->parseAssignmentExpression());

        $stream->expect(Token::BLOCK_END);

        return new SetNode($names, $values, $lineno, $this->getTag());
    }
}
