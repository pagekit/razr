<?php

namespace Razr\Directive;

use Razr\Token;
use Razr\TokenStream;

class IncludeDirective extends Directive
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->name = 'include';
    }

    /**
     * @{inheritdoc}
     */
    public function parse(TokenStream $stream, Token $token)
    {
        if ($stream->nextIf('include') && $stream->expect('(')) {
            return sprintf("\$_defined = array%s; echo(\$this->render(\$_defined[0], array_merge(get_defined_vars(), isset(\$_defined[1]) ? \$_defined[1] : [])))", $this->parser->parseExpression());
        }
    }
}
