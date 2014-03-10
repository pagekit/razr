<?php

namespace Razr\Token\TokenParser;

use Razr\Parser;

/**
 * @copyright Copyright (c) 2009-2014 by the Twig Team
 */
abstract class TokenParser implements TokenParserInterface
{
    /**
     * @var Parser
     */
    protected $parser;

    /**
     * {@inheritdoc}
     */
    public function getTags()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function setParser(Parser $parser)
    {
        $this->parser = $parser;
    }
}
