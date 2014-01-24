<?php

namespace Razr\Token\TokenParser;

use Razr\Parser;
use Razr\Token\Token;

interface TokenParserInterface
{
    /**
     * Gets the main tag name.
     *
     * @return string
     */
    public function getTag();

    /**
     * Gets all additional tag names.
     *
     * @return array
     */
    public function getTags();

    /**
     * Sets the parser.
     *
     * @param  $parser
     */
    public function setParser(Parser $parser);

    /**
     * Parses a token and returns a node.
     *
     * @param Token $token
     * @return NodeInterface
     */
    public function parse(Token $token);
}
