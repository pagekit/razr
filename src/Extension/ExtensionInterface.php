<?php

namespace Razr\Extension;

use Razr\Environment;

/**
 * @copyright Copyright (c) 2009-2014 by the Twig Team
 */
interface ExtensionInterface
{
    public function initRuntime(Environment $environment);

    public function getTokenParsers();

    public function getFilters();

    public function getFunctions();

    public function getOperators();

    public function getGlobals();

    public function getName();
}
