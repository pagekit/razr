<?php

namespace Razr;

use Razr\Exception\LogicException;
use Razr\Node\ModuleNode;
use Razr\Node\Node;

/**
 * @copyright Copyright (c) 2009-2014 by the Twig Team
 */
class Compiler
{
    protected $env;
    protected $filename;
    protected $source;
    protected $sourceOffset;
    protected $sourceLine;
    protected $lastLine;
    protected $indentation;
    protected $debugInfo = array();

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
     * Gets the current filename.
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Gets the current PHP code after compilation.
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Compiles a node.
     *
     * @param  Node $node
     * @param  int  $indentation
     * @return Compiler
     */
    public function compile(Node $node, $indentation = 0)
    {
        $this->source = '';
        $this->sourceOffset = 0;
        $this->sourceLine = 1; // source code starts at 1 (as we then increment it when we encounter new lines)
        $this->lastLine = null;
        $this->indentation = $indentation;

        if ($node instanceof ModuleNode) {
            $this->filename = $node->getAttribute('filename');
        }

        $node->compile($this);

        return $this;
    }

    public function subcompile(Node $node, $raw = true)
    {
        if (false === $raw) {
            $this->addIndentation();
        }

        $node->compile($this);

        return $this;
    }

    /**
     * Adds a raw string to the compiled code.
     *
     * @param  string $string
     * @return Compiler
     */
    public function raw($string)
    {
        $this->source .= $string;

        return $this;
    }

    /**
     * Writes a string to the compiled code by adding indentation.
     *
     * @return Compiler
     */
    public function write()
    {
        $strings = func_get_args();

        foreach ($strings as $string) {
            $this->addIndentation();
            $this->source .= $string;
        }

        return $this;
    }

    /**
     * Appends an indentation to the current PHP code after compilation.
     *
     * @return Compiler
     */
    public function addIndentation()
    {
        $this->source .= str_repeat(' ', $this->indentation * 4);

        return $this;
    }

    /**
     * Adds a quoted string to the compiled code.
     *
     * @param  string $value
     * @return Compiler
     */
    public function string($value)
    {
        $this->source .= sprintf('"%s"', addcslashes($value, "\0\t\"\$\\"));

        return $this;
    }

    /**
     * Returns a PHP representation of a given value.
     *
     * @param  mixed $value
     * @return Compiler
     */
    public function repr($value)
    {
        if (is_int($value) || is_float($value)) {
            if (false !== $locale = setlocale(LC_NUMERIC, 0)) {
                setlocale(LC_NUMERIC, 'C');
            }

            $this->raw($value);

            if (false !== $locale) {
                setlocale(LC_NUMERIC, $locale);
            }
        } elseif (null === $value) {
            $this->raw('null');
        } elseif (is_bool($value)) {
            $this->raw($value ? 'true' : 'false');
        } elseif (is_array($value)) {
            $this->raw('array(');
            $first = true;
            foreach ($value as $key => $value) {
                if (!$first) {
                    $this->raw(', ');
                }
                $first = false;
                $this->repr($key);
                $this->raw(' => ');
                $this->repr($value);
            }
            $this->raw(')');
        } else {
            $this->string($value);
        }

        return $this;
    }

    /**
     * Adds debugging information.
     *
     * @param  Node $node
     * @return Compiler
     */
    public function addDebugInfo(Node $node)
    {
        if ($node->getLine() != $this->lastLine) {
            $this->write("// line {$node->getLine()}\n");

            // when mbstring.func_overload is set to 2
            // mb_substr_count() replaces substr_count()
            // but they have different signatures!
            if (((int) ini_get('mbstring.func_overload')) & 2) {
                // this is much slower than the "right" version
                $this->sourceLine += mb_substr_count(mb_substr($this->source, $this->sourceOffset), "\n");
            } else {
                $this->sourceLine += substr_count($this->source, "\n", $this->sourceOffset);
            }

            $this->sourceOffset = strlen($this->source);
            $this->debugInfo[$this->sourceLine] = $node->getLine();

            $this->lastLine = $node->getLine();
        }

        return $this;
    }

    public function getDebugInfo()
    {
        return $this->debugInfo;
    }

    /**
     * Indents the generated code.
     *
     * @param  integer $step
     * @return Compiler
     */
    public function indent($step = 1)
    {
        $this->indentation += $step;

        return $this;
    }

    /**
     * Outdents the generated code.
     *
     * @param  integer $step
     * @return Compiler
     * @throws LogicException
     */
    public function outdent($step = 1)
    {
        // can't outdent by more steps than the current indentation level
        if ($this->indentation < $step) {
            throw new LogicException('Unable to call outdent() as the indentation would become negative');
        }

        $this->indentation -= $step;

        return $this;
    }
}
