<?php

namespace Razr\Storage;

abstract class Storage
{
    protected $template;

    /**
     * Constructor.
     *
     * @param string $template
     */
    public function __construct($template)
    {
        $this->template = $template;
    }

    /**
     * Gets the object string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->template;
    }

    /**
     * Gets the template content.
     *
     * @return string
     */
    abstract public function getContent();
}
