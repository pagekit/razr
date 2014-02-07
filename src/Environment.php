<?php

namespace Razr;

use Razr\Exception\InvalidArgumentException;
use Razr\Exception\LogicException;
use Razr\Exception\RuntimeException;
use Razr\Exception\UnexpectedValueException;
use Razr\Extension\CoreExtension;
use Razr\Extension\ExtensionInterface;
use Razr\Extension\StagingExtension;
use Razr\Loader\LoaderInterface;
use Razr\Node\Node;
use Razr\Token\TokenParser\TokenParserInterface;
use Razr\Token\TokenStream;

class Environment
{
    const VERSION = '0.9.0';

    protected $lexer;
    protected $parser;
    protected $compiler;
    protected $staging;
    protected $extensions;
    protected $parsers;
    protected $filters;
    protected $functions;
    protected $globals;
    protected $unary;
    protected $binary;
    protected $runtimeInitialized;
    protected $extensionInitialized;
    protected $cache;
    protected $loader;
    protected $charset;
    protected $autoReload;
    protected $strictVariables;
    protected $baseTemplateClass;
    protected $templateClassPrefix = '__RazrTemplate_';
    protected $loadedTemplates = array();

    public function __construct(LoaderInterface $loader = null, array $options = array())
    {
        if ($loader) {
            $this->loader = $loader;
        }

        $options = array_merge(array(
            'cache'               => false,
            'charset'             => 'UTF-8',
            'auto_reload'         => true,
            'strict_variables'    => false,
            'base_template_class' => 'Razr\Template'
        ), $options);

        $this->cache              = $options['cache'];
        $this->charset            = $options['charset'];
        $this->autoReload         = $options['auto_reload'];
        $this->strictVariables    = $options['strict_variables'];
        $this->baseTemplateClass  = $options['base_template_class'];

        $this->addExtension(new CoreExtension);
        $this->staging = new StagingExtension;
    }

    public function getLoader()
    {
        if (null === $this->loader) {
            throw new LogicException('You must set a loader first.');
        }

        return $this->loader;
    }

    public function setLoader(LoaderInterface $loader)
    {
        $this->loader = $loader;
    }

    public function isAutoReload()
    {
        return $this->autoReload;
    }

    public function setAutoReload($autoReload)
    {
        return $this->autoReload = $autoReload;
    }

    public function getCache()
    {
        return $this->cache;
    }

    public function getCacheFilename($name)
    {
        if (false === $this->cache) {
            return false;
        }

        $class = substr($this->getTemplateClass($name), strlen($this->templateClassPrefix));

        return $this->getCache().'/'.substr($class, 0, 2).'/'.substr($class, 2, 2).'/'.substr($class, 4).'.php';
    }

    public function setCache($cache)
    {
        $this->cache = $cache ? $cache : false;
    }

    public function getCharset()
    {
        return $this->charset;
    }

    public function setCharset($charset)
    {
        $this->charset = strtoupper($charset);
    }

    public function isStrictVariables()
    {
        return $this->strictVariables;
    }

    public function setStrictVariables($strictVariables)
    {
        return $this->strictVariables = $strictVariables;
    }

    public function getTemplateClass($name, $index = null)
    {
        return $this->templateClassPrefix.sha1($name).(null === $index ? '' : '_'.$index);
    }

    public function getTemplateClassPrefix()
    {
        return $this->templateClassPrefix;
    }

    public function getBaseTemplateClass()
    {
        return $this->baseTemplateClass;
    }

    public function setBaseTemplateClass($class)
    {
        $this->baseTemplateClass = $class;
    }

    public function getLexer()
    {
        if (null === $this->lexer) {
            $this->lexer = new Lexer($this);
        }

        return $this->lexer;
    }

    public function setLexer(Lexer $lexer)
    {
        $this->lexer = $lexer;
    }

    public function getParser()
    {
        if (null === $this->parser) {
            $this->parser = new Parser($this);
        }

        return $this->parser;
    }

    public function setParser(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function getCompiler()
    {
        if (null === $this->compiler) {
            $this->compiler = new Compiler($this);
        }

        return $this->compiler;
    }

    public function setCompiler(Compiler $compiler)
    {
        $this->compiler = $compiler;
    }

    public function render($name, array $context = array())
    {
        return $this->loadTemplate($name)->render($context);
    }

    public function display($name, array $context = array())
    {
        $this->loadTemplate($name)->display($context);
    }

    public function loadTemplate($name, $index = null)
    {
        $class = $this->getTemplateClass($name, $index);

        if (isset($this->loadedTemplates[$class])) {
            return $this->loadedTemplates[$class];
        }

        if (!class_exists($class, false)) {
            if (false === $cache = $this->getCacheFilename($name)) {

                eval('?>'.$this->compileSource($this->getLoader()->getSource($name), $name));

            } else {

                if (!is_file($cache) || ($this->isAutoReload() && !$this->isTemplateFresh($name, filemtime($cache)))) {
                    $this->writeCacheFile($cache, $this->compileSource($this->getLoader()->getSource($name), $name));
                }

                require_once $cache;
            }
        }

        if (!$this->runtimeInitialized) {
            $this->initRuntime();
        }

        return $this->loadedTemplates[$class] = new $class($this);
    }

    public function resolveTemplate($names)
    {
        if (!is_array($names)) {
            $names = array($names);
        }

        foreach ($names as $name) {

            if ($name instanceof Template) {
                return $name;
            }

            try {
                return $this->loadTemplate($name);
            } catch (RuntimeException $e) {}
        }

        if (1 === count($names)) {
            throw $e;
        }

        throw new RuntimeException(sprintf('Unable to find one of the following templates: "%s".', implode('", "', $names)));
    }

    public function isTemplateFresh($name, $time)
    {
        foreach ($this->extensions as $extension) {
            $r = new \ReflectionObject($extension);
            if (filemtime($r->getFileName()) > $time) {
                return false;
            }
        }

        return $this->getLoader()->isFresh($name, $time);
    }


    public function tokenize($source, $name = null)
    {
        return $this->getLexer()->tokenize($source, $name);
    }

    public function parse(TokenStream $stream)
    {
        return $this->getParser()->parse($stream);
    }

    public function compile(Node $node)
    {
        return $this->getCompiler()->compile($node)->getSource();
    }

    public function compileSource($source, $name = null)
    {
        return $this->compile($this->parse($this->tokenize($source, $name)));
    }

    public function initRuntime()
    {
        $this->runtimeInitialized = true;

        foreach ($this->getExtensions() as $extension) {
            $extension->initRuntime($this);
        }
    }

    public function hasExtension($name)
    {
        return isset($this->extensions[$name]);
    }

    public function getExtension($name)
    {
        if (!isset($this->extensions[$name])) {
            throw new RuntimeException(sprintf('The "%s" extension is not enabled.', $name));
        }

        return $this->extensions[$name];
    }

    public function getExtensions()
    {
        return $this->extensions;
    }

    public function setExtensions(array $extensions)
    {
        foreach ($extensions as $extension) {
            $this->addExtension($extension);
        }
    }

    public function addExtension(ExtensionInterface $extension)
    {
        if ($this->extensionInitialized) {
            throw new LogicException(sprintf('Unable to register extension "%s" as extensions have already been initialized.', $extension->getName()));
        }

        $this->extensions[$extension->getName()] = $extension;
    }

    public function getTokenParsers()
    {
        if (!$this->extensionInitialized) {
            $this->initExtensions();
        }

        return $this->parsers;
    }

    public function addTokenParser(TokenParserInterface $parser)
    {
        if ($this->extensionInitialized) {
            throw new LogicException('Unable to add a token parser as extensions have already been initialized.');
        }

        $this->staging->addTokenParser($parser);
    }

    public function getTokenParserTags()
    {
        if (!$this->extensionInitialized) {
            $this->initExtensions();
        }

        $tags = array();

        foreach ($this->parsers as $parser) {
            if ($parser instanceof TokenParserInterface) {

                $tags[] = $parser->getTag();

                if ($t = $parser->getTags()) {
                    $tags = array_merge($tags, $t);
                }
            }
        }

        return $tags;
    }

    public function getFilter($name)
    {
        if (!$this->extensionInitialized) {
            $this->initExtensions();
        }

        if (isset($this->filters[$name])) {
            return $this->filters[$name];
        }

        foreach ($this->filters as $pattern => $filter) {

            $pattern = str_replace('\\*', '(.*?)', preg_quote($pattern, '#'), $count);

            if ($count) {
                if (preg_match('#^'.$pattern.'$#', $name, $matches)) {
                    array_shift($matches);
                    $filter->setArguments($matches);

                    return $filter;
                }
            }
        }

        return false;
    }

    public function getFilters()
    {
        if (!$this->extensionInitialized) {
            $this->initExtensions();
        }

        return $this->filters;
    }

    public function addFilter($name, $filter = null)
    {
        if (!$name instanceof SimpleFilter && !$filter instanceof SimpleFilter) {
            throw new LogicException('A filter must be an instance of Razr\SimpleFilter');
        }

        if ($name instanceof SimpleFilter) {
            $filter = $name;
            $name = $filter->getName();
        }

        if ($this->extensionInitialized) {
            throw new LogicException(sprintf('Unable to add filter "%s" as extensions have already been initialized.', $name));
        }

        $this->staging->addFilter($name, $filter);
    }

    public function getFunction($name)
    {
        if (!$this->extensionInitialized) {
            $this->initExtensions();
        }

        if (isset($this->functions[$name])) {
            return $this->functions[$name];
        }

        foreach ($this->functions as $pattern => $function) {

            $pattern = str_replace('\\*', '(.*?)', preg_quote($pattern, '#'), $count);

            if ($count) {
                if (preg_match('#^'.$pattern.'$#', $name, $matches)) {
                    array_shift($matches);
                    $function->setArguments($matches);

                    return $function;
                }
            }
        }

        return false;
    }

    public function getFunctions()
    {
        if (!$this->extensionInitialized) {
            $this->initExtensions();
        }

        return $this->functions;
    }

    public function addFunction($name, $function = null)
    {
        if (!$name instanceof SimpleFunction && !$function instanceof SimpleFunction) {
            throw new LogicException('A function must be an instance of Razr\SimpleFunction');
        }

        if ($name instanceof SimpleFunction) {
            $function = $name;
            $name = $function->getName();
        }

        if ($this->extensionInitialized) {
            throw new LogicException(sprintf('Unable to add function "%s" as extensions have already been initialized.', $name));
        }

        $this->staging->addFunction($name, $function);
    }

    public function getGlobals()
    {
        if (!$this->runtimeInitialized && !$this->extensionInitialized) {
            return $this->initGlobals();
        }

        if (null === $this->globals) {
            $this->globals = $this->initGlobals();
        }

        return $this->globals;
    }

    public function addGlobal($name, $value)
    {
        if ($this->extensionInitialized || $this->runtimeInitialized) {

            if (null === $this->globals) {
                $this->globals = $this->initGlobals();
            }

            if (!array_key_exists($name, $this->globals)) {
                throw new LogicException(sprintf('Unable to add global "%s" as the runtime or the extensions have already been initialized.', $name));
            }
        }

        if ($this->extensionInitialized || $this->runtimeInitialized) {
            $this->globals[$name] = $value;
        } else {
            $this->staging->addGlobal($name, $value);
        }
    }

    public function mergeGlobals(array $context)
    {
        // we don't use array_merge as the context being generally
        // bigger than globals, this code is faster.
        foreach ($this->getGlobals() as $key => $value) {
            if (!array_key_exists($key, $context)) {
                $context[$key] = $value;
            }
        }

        return $context;
    }

    public function getUnaryOperators()
    {
        if (!$this->extensionInitialized) {
            $this->initExtensions();
        }

        return $this->unary;
    }

    public function getBinaryOperators()
    {
        if (!$this->extensionInitialized) {
            $this->initExtensions();
        }

        return $this->binary;
    }

    public function computeAlternatives($name, $items)
    {
        $alternatives = array();

        foreach ($items as $item) {
            $lev = levenshtein($name, $item);
            if ($lev <= strlen($name) / 3 || false !== strpos($item, $name)) {
                $alternatives[$item] = $lev;
            }
        }

        asort($alternatives);

        return array_keys($alternatives);
    }

    protected function initGlobals()
    {
        $globals = array();

        foreach ($this->extensions as $extension) {

            $extGlob = $extension->getGlobals();

            if (!is_array($extGlob)) {
                throw new UnexpectedValueException(sprintf('"%s::getGlobals()" must return an array of globals.', get_class($extension)));
            }

            $globals[] = $extGlob;
        }

        $globals[] = $this->staging->getGlobals();

        return call_user_func_array('array_merge', $globals);
    }

    protected function initExtensions()
    {
        if ($this->extensionInitialized) {
            return;
        }

        $this->parsers = array();
        $this->filters = array();
        $this->functions = array();
        $this->unary = array();
        $this->binary = array();
        $this->extensionInitialized = true;

        foreach ($this->extensions as $extension) {
            $this->initExtension($extension);
        }

        $this->initExtension($this->staging);
    }

    protected function initExtension(ExtensionInterface $extension)
    {
        foreach ($extension->getFilters() as $name => $filter) {

            if ($name instanceof SimpleFilter) {
                $filter = $name;
                $name = $filter->getName();
            } elseif ($filter instanceof SimpleFilter) {
                $name = $filter->getName();
            }

            $this->filters[$name] = $filter;
        }

        foreach ($extension->getFunctions() as $name => $function) {

            if ($name instanceof SimpleFunction) {
                $function = $name;
                $name = $function->getName();
            } elseif ($function instanceof SimpleFunction) {
                $name = $function->getName();
            }

            $this->functions[$name] = $function;
        }

        foreach ($extension->getTokenParsers() as $parser) {
            if ($parser instanceof TokenParserInterface) {
                $this->parsers[$parser->getTag()] = $parser;
            } else {
                throw new LogicException(sprintf('"%s::getTokenParsers()" must return an array of TokenParserInterface', get_class($extension)));
            }
        }

        if ($operators = $extension->getOperators()) {

            if (2 !== count($operators)) {
                throw new InvalidArgumentException(sprintf('"%s::getOperators()" does not return a valid operators array.', get_class($extension)));
            }

            $this->unary = array_merge($this->unary, $operators[0]);
            $this->binary = array_merge($this->binary, $operators[1]);
        }
    }

    protected function writeCacheFile($file, $content)
    {
        $dir = dirname($file);

        if (!is_dir($dir)) {
            if (false === @mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new RuntimeException("Unable to create the cache directory ($dir).");
            }
        } elseif (!is_writable($dir)) {
            throw new RuntimeException("Unable to write in the cache directory ($dir).");
        }

        if (!file_put_contents($file, $content)) {
            throw new RuntimeException("Failed to write cache file ($file).");
        }
    }
}
