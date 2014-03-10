<?php

namespace Razr\Node;

use Razr\Compiler;
use Razr\Exception\LogicException;

/**
 * @copyright Copyright (c) 2009-2014 by the Twig Team
 */
class Node implements \Countable, \IteratorAggregate
{
    protected $nodes;
    protected $attributes;
    protected $lineno;
    protected $tag;

    public function __construct(array $nodes = array(), array $attributes = array(), $lineno = 0, $tag = null)
    {
        $this->nodes = $nodes;
        $this->attributes = $attributes;
        $this->lineno = $lineno;
        $this->tag = $tag;
    }

    public function compile(Compiler $compiler)
    {
        foreach ($this->nodes as $node) {
            $node->compile($compiler);
        }
    }

    public function getLine()
    {
        return $this->lineno;
    }

    public function getNodeTag()
    {
        return $this->tag;
    }

    public function hasAttribute($name)
    {
        return array_key_exists($name, $this->attributes);
    }

    public function getAttribute($name)
    {
        if (!array_key_exists($name, $this->attributes)) {
            throw new LogicException(sprintf('Attribute "%s" does not exist for Node "%s".', $name, get_class($this)));
        }

        return $this->attributes[$name];
    }

    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    public function removeAttribute($name)
    {
        unset($this->attributes[$name]);
    }

    public function hasNode($name)
    {
        return array_key_exists($name, $this->nodes);
    }

    public function getNode($name)
    {
        if (!array_key_exists($name, $this->nodes)) {
            throw new LogicException(sprintf('Node "%s" does not exist for Node "%s".', $name, get_class($this)));
        }

        return $this->nodes[$name];
    }

    public function setNode($name, $node = null)
    {
        $this->nodes[$name] = $node;
    }

    public function removeNode($name)
    {
        unset($this->nodes[$name]);
    }

    public function count()
    {
        return count($this->nodes);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->nodes);
    }

    public function __toString()
    {
        $attributes = array();
        foreach ($this->attributes as $name => $value) {
            $attributes[] = sprintf('%s: %s', $name, str_replace("\n", '', var_export($value, true)));
        }

        $repr = array(get_class($this).'('.implode(', ', $attributes));

        if (count($this->nodes)) {
            foreach ($this->nodes as $name => $node) {
                $len = strlen($name) + 4;
                $noderepr = array();
                foreach (explode("\n", (string) $node) as $line) {
                    $noderepr[] = str_repeat(' ', $len).$line;
                }

                $repr[] = sprintf('  %s: %s', $name, ltrim(implode("\n", $noderepr)));
            }

            $repr[] = ')';
        } else {
            $repr[0] .= ')';
        }

        return implode("\n", $repr);
    }
}
