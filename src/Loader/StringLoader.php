<?php

namespace Razr\Loader;

/**
 * @copyright Copyright (c) 2009-2014 by the Twig Team
 */
class StringLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getSource($name)
    {
        return $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKey($name)
    {
        return $name;
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh($name, $time)
    {
        return true;
    }
}
