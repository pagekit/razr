<?php

namespace Razr\Loader;

use Razr\Exception\RuntimeException;

class ChainLoader implements LoaderInterface
{
    protected $loaders = array();

    /**
     * Constructor.
     *
     * @param LoaderInterface[] $loaders
     */
    public function __construct(array $loaders = array())
    {
        foreach ($loaders as $loader) {
            $this->addLoader($loader);
        }
    }

    /**
     * Adds a loader instance.
     *
     * @param LoaderInterface $loader
     */
    public function addLoader(LoaderInterface $loader)
    {
        $this->loaders[] = $loader;
    }

    /**
     * {@inheritdoc}
     */
    public function getSource($name)
    {
        foreach ($this->loaders as $loader) {
            try {
                return $loader->getSource($name);
            } catch (RuntimeException $e) {}
        }

        throw new RuntimeException(sprintf('Template "%s" is not defined (%s).', $name));
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKey($name)
    {
        foreach ($this->loaders as $loader) {
            try {
                return $loader->getCacheKey($name);
            } catch (RuntimeException $e) {}
        }

        throw new RuntimeException(sprintf('Template "%s" is not defined (%s).', $name));
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh($name, $time)
    {
        foreach ($this->loaders as $loader) {
            try {
                return $loader->isFresh($name, $time);
            } catch (RuntimeException $e) {}
        }

        return false;
    }
}
