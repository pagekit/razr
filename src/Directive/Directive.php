<?php

namespace Razr\Directive;

use Razr\Engine;

abstract class Directive implements DirectiveInterface
{
    protected $name;
    protected $engine;
    protected $parser;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @{inheritdoc}
     */
    public function setEngine(Engine $engine)
    {
        $this->engine = $engine;
        $this->parser = $engine->getParser();
    }
}
