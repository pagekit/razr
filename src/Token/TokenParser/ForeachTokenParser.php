<?php

namespace Razr\Token\TokenParser;

use Razr\Node\Expression\AssignNameNode;
use Razr\Node\ForeachNode;
use Razr\Token\Token;

class ForeachTokenParser extends TokenParser
{
    public function getTag()
    {
        return 'foreach';
    }

    public function getTags()
    {
        return array('endforeach');
    }

    public function parse(Token $token)
    {
        $lineno = $token->getLine();
        $seq = $this->parser->getExpressionParser()->parseExpression();
        $stream = $this->parser->getStream();
        $stream->expect(Token::NAME, 'as');

        $token = $stream->expect(Token::NAME, null, 'Only variables can be assigned to');
        $targets[] = new AssignNameNode($token->getValue(), $token->getLine());

        if ($stream->nextIf(Token::OPERATOR, '=>')) {
            $token = $stream->expect(Token::NAME, null, 'Only variables can be assigned to');
            $targets[] = new AssignNameNode($token->getValue(), $token->getLine());
        }

        $stream->expect(Token::BLOCK_END);
        $body = $this->parser->subparse(array($this, 'decideForeachEnd'), true);
        $stream->expect(Token::BLOCK_END);

        if (count($targets) == 1) {
            array_unshift($targets, new AssignNameNode('_key', $lineno));
        }

        return new ForeachNode($targets[0], $targets[1], $seq, $body, $lineno, $this->getTag());
    }

    public function decideForeachEnd(Token $token)
    {
        return $token->test('endforeach');
    }
}
