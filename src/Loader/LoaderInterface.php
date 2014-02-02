<?php

namespace Razr\Loader;

interface LoaderInterface
{
    /**
     * Gets the source code of a template, given its name.
     *
     * @param  string $name
     * @return string
     */
    public function getSource($name);

    /**
     * Gets the cache key to use for the cache for a given template name.
     *
     * @param  string $name
     * @return string
     */
    public function getCacheKey($name);

    /**
     * Returns true if the template is still fresh.
     *
     * @param  string $name
     * @param  int    $time
     * @return bool
     */
    public function isFresh($name, $time);
}
