<?php

namespace Razr\Extension;

use Razr\Environment;

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
