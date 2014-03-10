<?php

namespace Razr;

use Razr\Exception\Exception;
use Razr\Exception\RuntimeException;

/**
 * @copyright Copyright (c) 2009-2014 by the Twig Team
 */
abstract class Template
{
    const ANY_CALL    = 'any';
    const ARRAY_CALL  = 'array';
    const METHOD_CALL = 'method';

    protected $env;
    protected $parent;
    protected $parents;
    protected $blocks = array();
    protected static $cache = array();

    /**
     * Constructor.
     *
     * @param Environment $env
     */
    public function __construct(Environment $env)
    {
        $this->env = $env;
    }

    /**
     * Returns the environment instance.
     *
     * @return Environment
     */
    public function getEnvironment()
    {
        return $this->env;
    }

    /**
     * Returns the parent template.
     *
     * This method is for internal use only and should never be called
     * directly.
     *
     * @return Template|false The parent template or false if there is no parent
     */
    public function getParent(array $context)
    {
        if (null !== $this->parent) {
            return $this->parent;
        }

        $parent = $this->doGetParent($context);

        if (false === $parent) {
            return false;
        } elseif ($parent instanceof Template) {
            $name = $parent->getTemplateName();
            $this->parents[$name] = $parent;
            $parent = $name;
        } elseif (!isset($this->parents[$parent])) {
            $this->parents[$parent] = $this->env->loadTemplate($parent);
        }

        return $this->parents[$parent];
    }

    protected function doGetParent(array $context)
    {
        return false;
    }

    /**
     * Displays a parent block.
     *
     * This method is for internal use only and should never be called
     * directly.
     *
     * @param string $name    The block name to display from the parent
     * @param array  $context The context
     * @param array  $blocks  The current set of blocks
     */
    public function displayParentBlock($name, array $context, array $blocks = array())
    {
        $name = (string) $name;

        if (false !== $parent = $this->getParent($context)) {
            $parent->displayBlock($name, $context, $blocks);
        } else {
            throw new RuntimeException(sprintf('The template has no parent defining the "%s" block', $name), -1, $this->getTemplateName());
        }
    }

    /**
     * Displays a block.
     *
     * This method is for internal use only and should never be called
     * directly.
     *
     * @param string $name    The block name to display
     * @param array  $context The context
     * @param array  $blocks  The current set of blocks
     */
    public function displayBlock($name, array $context, array $blocks = array())
    {
        $name = (string) $name;

        $template = null;
        if (isset($blocks[$name])) {
            $template = $blocks[$name][0];
            $block = $blocks[$name][1];
            unset($blocks[$name]);
        } elseif (isset($this->blocks[$name])) {
            $template = $this->blocks[$name][0];
            $block = $this->blocks[$name][1];
        }

        if (null !== $template) {
            try {
                $template->$block($context, $blocks);
            } catch (Exception $e) {
                throw $e;
            } catch (\Exception $e) {
                throw new RuntimeException(sprintf('An exception has been thrown during the rendering of a template ("%s").', $e->getMessage()), -1, $template->getTemplateName(), $e);
            }
        } elseif (false !== $parent = $this->getParent($context)) {
            $parent->displayBlock($name, $context, array_merge($this->blocks, $blocks));
        }
    }

    /**
     * Renders a parent block.
     *
     * This method is for internal use only and should never be called
     * directly.
     *
     * @param string $name    The block name to render from the parent
     * @param array  $context The context
     * @param array  $blocks  The current set of blocks
     *
     * @return string The rendered block
     */
    public function renderParentBlock($name, array $context, array $blocks = array())
    {
        ob_start();

        $this->displayParentBlock($name, $context, $blocks);

        return ob_get_clean();
    }

    /**
     * Renders a block.
     *
     * This method is for internal use only and should never be called
     * directly.
     *
     * @param string $name    The block name to render
     * @param array  $context The context
     * @param array  $blocks  The current set of blocks
     *
     * @return string The rendered block
     */
    public function renderBlock($name, array $context, array $blocks = array())
    {
        ob_start();

        $this->displayBlock($name, $context, $blocks);

        return ob_get_clean();
    }

    /**
     * Returns whether a block exists or not.
     *
     * This method is for internal use only and should never be called
     * directly.
     *
     * This method does only return blocks defined in the current template.
     *
     * It does not return blocks from parent templates as the parent
     * template name can be dynamic, which is only known based on the
     * current context.
     *
     * @param string $name The block name
     *
     * @return Boolean true if the block exists, false otherwise
     */
    public function hasBlock($name)
    {
        return isset($this->blocks[(string) $name]);
    }

    /**
     * Returns all block names.
     *
     * This method is for internal use only and should never be called
     * directly.
     *
     * @return array An array of block names
     *
     * @see hasBlock
     */
    public function getBlockNames()
    {
        return array_keys($this->blocks);
    }

    /**
     * Returns all blocks.
     *
     * This method is for internal use only and should never be called
     * directly.
     *
     * @return array An array of blocks
     *
     * @see hasBlock
     */
    public function getBlocks()
    {
        return $this->blocks;
    }

    /**
     * {@inheritdoc}
     */
    public function display(array $context, array $blocks = array())
    {
        $this->displayWithErrorHandling($this->env->mergeGlobals($context), $blocks);
    }

    /**
     * {@inheritdoc}
     */
    public function render(array $context)
    {
        $level = ob_get_level();

        ob_start();

        try {
            $this->display($context);
        } catch (\Exception $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $e;
        }

        return ob_get_clean();
    }

    protected function displayWithErrorHandling(array $context, array $blocks = array())
    {
        try {
            $this->doDisplay($context, $blocks);
        } catch (Exception $e) {

            if (!$e->getTemplateFile()) {
                $e->setTemplateFile($this->getTemplateName());
            }

            // this is mostly useful for Error_Loader exceptions
            // see Error_Loader
            if (false === $e->getTemplateLine()) {
                $e->setTemplateLine(-1);
                $e->guess();
            }

            throw $e;
        } catch (\Exception $e) {
            throw new RuntimeException(sprintf('An exception has been thrown during the rendering of a template ("%s").', $e->getMessage()), -1, $this->getTemplateName(), $e);
        }
    }

    /**
     * Auto-generated method to display the template with the given context.
     *
     * @param array $context An array of parameters to pass to the template
     * @param array $blocks  An array of blocks to pass to the template
     */
    abstract protected function doDisplay(array $context, array $blocks = array());

    /**
     * Returns a variable from the context.
     *
     * This method is for internal use only and should never be called
     * directly.
     *
     * This method should not be overridden in a sub-class as this is an
     * implementation detail that has been introduced to optimize variable
     * access for versions of PHP before 5.4. This is not a way to override
     * the way to get a variable value.
     *
     * @param array   $context           The context
     * @param string  $item              The variable to return from the context
     * @param Boolean $ignoreStrictCheck Whether to ignore the strict variable check or not
     *
     * @return The content of the context variable
     *
     * @throws RuntimeException if the variable does not exist and running in strict mode
     */
    final protected function getContext($context, $item, $ignoreStrictCheck = false)
    {
        if (!array_key_exists($item, $context)) {
            if ($ignoreStrictCheck || !$this->env->isStrictVariables()) {
                return null;
            }

            throw new RuntimeException(sprintf('Variable "%s" does not exist', $item), -1, $this->getTemplateName());
        }

        return $context[$item];
    }

    /**
     * Returns the attribute value for a given array/object.
     *
     * @param mixed   $object            The object or array from where to get the item
     * @param mixed   $item              The item to get from the array or object
     * @param array   $arguments         An array of arguments to pass if the item is an object method
     * @param string  $type              The type of attribute (@see Template constants)
     * @param Boolean $isDefinedTest     Whether this is only a defined check
     * @param Boolean $ignoreStrictCheck Whether to ignore the strict attribute check or not
     *
     * @return mixed The attribute value, or a Boolean when $isDefinedTest is true, or null when the attribute is not set and $ignoreStrictCheck is true
     *
     * @throws RuntimeException if the attribute does not exist and running in strict mode and $isDefinedTest is false
     */
    protected function getAttribute($object, $item, array $arguments = array(), $type = self::ANY_CALL, $isDefinedTest = false, $ignoreStrictCheck = false)
    {
        // array
        if (self::METHOD_CALL !== $type) {
            $arrayItem = is_bool($item) || is_float($item) ? (int) $item : $item;

            if ((is_array($object) && array_key_exists($arrayItem, $object))
                || ($object instanceof \ArrayAccess && isset($object[$arrayItem]))
            ) {
                if ($isDefinedTest) {
                    return true;
                }

                return $object[$arrayItem];
            }

            if (self::ARRAY_CALL === $type || !is_object($object)) {
                if ($isDefinedTest) {
                    return false;
                }

                if ($ignoreStrictCheck || !$this->env->isStrictVariables()) {
                    return null;
                }

                if (is_object($object)) {
                    throw new RuntimeException(sprintf('Key "%s" in object (with ArrayAccess) of type "%s" does not exist', $arrayItem, get_class($object)), -1, $this->getTemplateName());
                } elseif (is_array($object)) {
                    throw new RuntimeException(sprintf('Key "%s" for array with keys "%s" does not exist', $arrayItem, implode(', ', array_keys($object))), -1, $this->getTemplateName());
                } elseif (self::ARRAY_CALL === $type) {
                    throw new RuntimeException(sprintf('Impossible to access a key ("%s") on a %s variable ("%s")', $item, gettype($object), $object), -1, $this->getTemplateName());
                } else {
                    throw new RuntimeException(sprintf('Impossible to access an attribute ("%s") on a %s variable ("%s")', $item, gettype($object), $object), -1, $this->getTemplateName());
                }
            }
        }

        if (!is_object($object)) {
            if ($isDefinedTest) {
                return false;
            }

            if ($ignoreStrictCheck || !$this->env->isStrictVariables()) {
                return null;
            }

            throw new RuntimeException(sprintf('Impossible to invoke a method ("%s") on a %s variable ("%s")', $item, gettype($object), $object), -1, $this->getTemplateName());
        }

        $class = get_class($object);

        // object property
        if (self::METHOD_CALL !== $type) {
            if (isset($object->$item) || array_key_exists((string) $item, $object)) {
                if ($isDefinedTest) {
                    return true;
                }

                if ($this->env->hasExtension('sandbox')) {
                    $this->env->getExtension('sandbox')->checkPropertyAllowed($object, $item);
                }

                return $object->$item;
            }
        }

        // object method
        if (!isset(self::$cache[$class]['methods'])) {
            self::$cache[$class]['methods'] = array_change_key_case(array_flip(get_class_methods($object)));
        }

        $call = false;
        $lcItem = strtolower($item);
        if (isset(self::$cache[$class]['methods'][$lcItem])) {
            $method = (string) $item;
        } elseif (isset(self::$cache[$class]['methods']['get'.$lcItem])) {
            $method = 'get'.$item;
        } elseif (isset(self::$cache[$class]['methods']['is'.$lcItem])) {
            $method = 'is'.$item;
        } elseif (isset(self::$cache[$class]['methods']['__call'])) {
            $method = (string) $item;
            $call = true;
        } else {
            if ($isDefinedTest) {
                return false;
            }

            if ($ignoreStrictCheck || !$this->env->isStrictVariables()) {
                return null;
            }

            throw new RuntimeException(sprintf('Method "%s" for object "%s" does not exist', $item, get_class($object)), -1, $this->getTemplateName());
        }

        if ($isDefinedTest) {
            return true;
        }

        if ($this->env->hasExtension('sandbox')) {
            $this->env->getExtension('sandbox')->checkMethodAllowed($object, $method);
        }

        // Some objects throw exceptions when they have __call, and the method we try
        // to call is not supported. If ignoreStrictCheck is true, we should return null.
        try {
            $ret = call_user_func_array(array($object, $method), $arguments);
        } catch (\BadMethodCallException $e) {
            if ($call && ($ignoreStrictCheck || !$this->env->isStrictVariables())) {
                return null;
            }
            throw $e;
        }

        // useful when calling a template method from a template
        // this is not supported but unfortunately heavily used in the Symfony profiler
        if ($object instanceof Template) {
            return $ret === '' ? '' : new Markup($ret, $this->env->getCharset());
        }

        return $ret;
    }

    /**
     * This method is only useful when testing. Do not use it.
     */
    public static function clearCache()
    {
        self::$cache = array();
    }

    abstract public function getTemplateName();
}
