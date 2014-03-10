<?php

namespace Razr\Extension;

use Razr\Token\TokenParser\TokenParserInterface;

/**
 * @copyright Copyright (c) 2009-2014 by the Twig Team
 */
class StagingExtension extends Extension
{
    protected $functions = array();
    protected $filters = array();
    protected $tokenParsers = array();
    protected $globals = array();

    public function getName()
    {
        return 'staging';
    }

    public function getFunctions()
    {
        return $this->functions;
    }

    public function addFunction($name, $function)
    {
        $this->functions[$name] = $function;
    }

    public function getFilters()
    {
        return $this->filters;
    }

    public function addFilter($name, $filter)
    {
        $this->filters[$name] = $filter;
    }

    public function getTokenParsers()
    {
        return $this->tokenParsers;
    }

    public function addTokenParser(TokenParserInterface $parser)
    {
        $this->tokenParsers[] = $parser;
    }

    public function getGlobals()
    {
        return $this->globals;
    }

    public function addGlobal($name, $value)
    {
        $this->globals[$name] = $value;
    }
}
