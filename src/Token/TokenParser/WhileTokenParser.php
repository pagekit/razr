<?php

namespace Razr\Token\TokenParser;

use Razr\Node\WhileNode;
use Razr\Token\Token;

/**
 * @copyright Copyright (c) 2009-2014 by the Twig Team
 */
class WhileTokenParser extends TokenParser
{
    public function getTag()
    {
        return 'while';
    }

    public function getTags()
    {
        return array('endwhile');
    }

    public function parse(Token $token)
    {
        $lineno = $token->getLine();
        $expr = $this->parser->getExpressionParser()->parseExpression();

        $this->parser->getStream()->expect(Token::BLOCK_END);
        $body = $this->parser->subparse(array($this, 'decideWhileEnd'), true);
        $this->parser->getStream()->expect(Token::BLOCK_END);

        return new WhileNode($expr, $body, $lineno, $this->getTag());
    }

    public function decideWhileEnd(Token $token)
    {
        return $token->test('endwhile');
    }
}
