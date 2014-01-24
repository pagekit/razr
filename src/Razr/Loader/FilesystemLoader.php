<?php

namespace Razr\Loader;

use Razr\Exception\RuntimeException;

class FilesystemLoader implements LoaderInterface
{
    protected $paths;

    /**
     * Constructor.
     *
     * @param array $paths
     */
    public function __construct($paths = array())
    {
        $this->paths = (array) $paths;
    }

    /**
     * {@inheritdoc}
     */
    public function getSource($name)
    {
        return file_get_contents($this->findTemplate($name));
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKey($name)
    {
        return $this->findTemplate($name);
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh($name, $time)
    {
        return filemtime($this->findTemplate($name)) <= $time;
    }

    /**
     * Finds a template by a given name.
     */
    protected function findTemplate($name)
    {
        $name = (string) $name;

        if (self::isAbsolutePath($name) && is_file($name)) {
            return $name;
        }

        $name = ltrim(strtr($name, '\\', '/'), '/');

        foreach ($this->paths as $path) {
            if (is_file($file = $path.'/'.$name)) {
                return $file;
            }
        }

        throw new RuntimeException(sprintf('Unable to find template "%s" (looked into: %s).', $name, implode(', ', $this->paths)));
    }

    /**
     * Returns true if the file is an existing absolute path.
     *
     * @param  string $file
     * @return boolean
     */
    protected static function isAbsolutePath($file)
    {
        if ($file[0] == '/' || $file[0] == '\\' || (strlen($file) > 3 && ctype_alpha($file[0]) && $file[1] == ':' && ($file[2] == '\\' || $file[2] == '/')) || null !== parse_url($file, PHP_URL_SCHEME)) {
            return true;
        }

        return false;
    }
}
