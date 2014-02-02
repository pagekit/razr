<?php

namespace Razr\Extension;

use Razr\Environment;

abstract class Extension implements ExtensionInterface
{
    public function initRuntime(Environment $environment)
    {
    }

    public function getTokenParsers()
    {
        return array();
    }

    public function getFilters()
    {
        return array();
    }

    public function getFunctions()
    {
        return array();
    }

    public function getOperators()
    {
        return array();
    }

    public function getGlobals()
    {
        return array();
    }
}
