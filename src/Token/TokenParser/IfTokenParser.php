<?php

namespace Razr\Token\TokenParser;

use Razr\Exception\SyntaxErrorException;
use Razr\Node\IfNode;
use Razr\Node\Node;
use Razr\Token\Token;

class IfTokenParser extends TokenParser
{
    public function getTag()
    {
        return 'if';
    }

    public function getTags()
    {
        return array('elseif', 'else', 'endif');
    }

    public function parse(Token $token)
    {
        $lineno = $token->getLine();
        $expr = $this->parser->getExpressionParser()->parseExpression();
        $stream = $this->parser->getStream();
        $stream->expect(Token::BLOCK_END);
        $body = $this->parser->subparse(array($this, 'decideIfFork'));
        $tests = array($expr, $body);
        $else = null;
        $end = false;

        while (!$end) {
            switch ($stream->next()->getValue()) {
                case 'else':
                    $stream->expect(Token::BLOCK_END);
                    $else = $this->parser->subparse(array($this, 'decideIfEnd'));
                    break;

                case 'elseif':
                    $expr = $this->parser->getExpressionParser()->parseExpression();
                    $stream->expect(Token::BLOCK_END);
                    $body = $this->parser->subparse(array($this, 'decideIfFork'));
                    $tests[] = $expr;
                    $tests[] = $body;
                    break;

                case 'endif':
                    $end = true;
                    break;

                default:
                    throw new SyntaxErrorException(sprintf('Unexpected end of template. Expected were the following tags "else", "elseif", or "endif" to close the "if" block started at line %d)', $lineno), $stream->getCurrent()->getLine(), $stream->getFilename());
            }
        }

        $stream->expect(Token::BLOCK_END);

        return new IfNode(new Node($tests), $else, $lineno, $this->getTag());
    }

    public function decideIfFork(Token $token)
    {
        return $token->test(array('elseif', 'else', 'endif'));
    }

    public function decideIfEnd(Token $token)
    {
        return $token->test(array('endif'));
    }
}
