<?php

namespace Razr\Token\TokenParser;

use Razr\Exception\SyntaxErrorException;
use Razr\Node\BlockNode;
use Razr\Node\BlockReferenceNode;
use Razr\Node\Node;
use Razr\Node\PrintNode;
use Razr\Token\Token;

/**
 * @copyright Copyright (c) 2009-2014 by the Twig Team
 */
class BlockTokenParser extends TokenParser
{
    public function getTag()
    {
        return 'block';
    }

    public function getTags()
    {
        return array('endblock');
    }

    public function parse(Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $name = $stream->expect(Token::STRING)->getValue();

        if ($this->parser->hasBlock($name)) {
            throw new SyntaxErrorException(sprintf("The block '$name' has already been defined line %d", $this->parser->getBlock($name)->getLine()), $stream->getCurrent()->getLine(), $stream->getFilename());
        }

        $this->parser->setBlock($name, $block = new BlockNode($name, new Node(array()), $lineno));
        $this->parser->pushBlockStack($name);

        if ($stream->nextIf(Token::BLOCK_END)) {

            $body = $this->parser->subparse(array($this, 'decideBlockEnd'), true);

            if ($token = $stream->nextIf(Token::STRING)) {
                $value = $token->getValue();

                if ($value != $name) {
                    throw new SyntaxErrorException(sprintf("Expected endblock for block '$name' (but %s given)", $value), $stream->getCurrent()->getLine(), $stream->getFilename());
                }
            }

        } else {

            $body = new Node(array(
                new PrintNode($this->parser->getExpressionParser()->parseExpression(), $lineno),
            ));

        }

        $stream->expect(Token::BLOCK_END);

        $block->setNode('body', $body);
        $this->parser->popBlockStack();

        return new BlockReferenceNode($name, $lineno, $this->getTag());
    }

    public function decideBlockEnd(Token $token)
    {
        return $token->test('endblock');
    }
}
