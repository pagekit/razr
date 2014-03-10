<?php

namespace Razr\Extension;

use Razr\Environment;

/**
 * @copyright Copyright (c) 2009-2014 by the Twig Team
 */
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
