<?php

namespace Razr\Node;

use Razr\Compiler;
use Razr\Node\Expression\ConstantNode;
use Razr\Node\Expression\ExpressionNode;

/**
 * @copyright Copyright (c) 2009-2014 by the Twig Team
 */
class ModuleNode extends Node
{
    public function __construct(Node $body, ExpressionNode $parent = null, Node $blocks, $filename)
    {
        parent::__construct(compact('parent', 'body', 'blocks'), array('filename' => $filename, 'index' => null), 1);
    }

    public function setIndex($index)
    {
        $this->setAttribute('index', $index);
    }

    public function compile(Compiler $compiler)
    {
        $this->compileTemplate($compiler);
    }

    protected function compileTemplate(Compiler $compiler)
    {
        if (!$this->getAttribute('index')) {
            $compiler->write('<?php');
        }

        $this->compileClassHeader($compiler);

        if (count($this->getNode('blocks')) || null === $this->getNode('parent') || $this->getNode('parent') instanceof ConstantNode) {
            $this->compileConstructor($compiler);
        }

        $this->compileGetParent($compiler);

        $this->compileDisplayHeader($compiler);

        $this->compileDisplayBody($compiler);

        $this->compileDisplayFooter($compiler);

        $compiler->subcompile($this->getNode('blocks'));

        $this->compileGetTemplateName($compiler);

        $this->compileDebugInfo($compiler);

        $this->compileClassFooter($compiler);
    }

    protected function compileGetParent(Compiler $compiler)
    {
        if (null === $this->getNode('parent')) {
            return;
        }

        $compiler
            ->write("protected function doGetParent(array \$context)\n", "{\n")
            ->indent()
            ->write("return ")
        ;

        if ($this->getNode('parent') instanceof ConstantNode) {
            $compiler->subcompile($this->getNode('parent'));
        } else {
            $compiler
                ->raw("\$this->env->resolveTemplate(")
                ->subcompile($this->getNode('parent'))
                ->raw(")")
            ;
        }

        $compiler
            ->raw(";\n")
            ->outdent()
            ->write("}\n\n")
        ;
    }

    protected function compileDisplayBody(Compiler $compiler)
    {
        $compiler->subcompile($this->getNode('body'));

        if (null !== $this->getNode('parent')) {
            if ($this->getNode('parent') instanceof ConstantNode) {
                $compiler->write("\$this->parent");
            } else {
                $compiler->write("\$this->getParent(\$context)");
            }
            $compiler->raw("->display(\$context, array_merge(\$this->blocks, \$blocks));\n");
        }
    }

    protected function compileClassHeader(Compiler $compiler)
    {
        $compiler
            ->write("\n\n")
            // if the filename contains */, add a blank to avoid a PHP parse error
            ->write("/* ".str_replace('*/', '* /', $this->getAttribute('filename'))." */\n")
            ->write('class '.$compiler->getEnvironment()->getTemplateClass($this->getAttribute('filename'), $this->getAttribute('index')))
            ->raw(sprintf(" extends %s\n", $compiler->getEnvironment()->getBaseTemplateClass()))
            ->write("{\n")
            ->indent()
        ;
    }

    protected function compileConstructor(Compiler $compiler)
    {
        $compiler
            ->write("public function __construct(Razr\Environment \$env)\n", "{\n")
            ->indent()
            ->write("parent::__construct(\$env);\n\n")
        ;

        // parent
        if (null === $this->getNode('parent')) {
            $compiler->write("\$this->parent = false;\n");
        } elseif ($this->getNode('parent') instanceof ConstantNode) {
            $compiler
                ->write("\$this->parent = \$this->env->loadTemplate(")
                ->subcompile($this->getNode('parent'))
                ->raw(");\n")
            ;
        }

        // blocks
        $compiler
            ->write("\$this->blocks = array(\n")
            ->indent()
        ;

        foreach ($this->getNode('blocks') as $name => $node) {
            $compiler
                ->write(sprintf("'%s' => array(\$this, 'block_%s'),\n", $name, $name))
            ;
        }

        $compiler
            ->outdent()
            ->write(");\n")
            ->outdent()
            ->write("}\n\n");
        ;
    }

    protected function compileDisplayHeader(Compiler $compiler)
    {
        $compiler
            ->write("protected function doDisplay(array \$context, array \$blocks = array())\n", "{\n")
            ->indent()
        ;
    }

    protected function compileDisplayFooter(Compiler $compiler)
    {
        $compiler
            ->outdent()
            ->write("}\n\n")
        ;
    }

    protected function compileClassFooter(Compiler $compiler)
    {
        $compiler
            ->outdent()
            ->write("}\n")
        ;
    }

    protected function compileGetTemplateName(Compiler $compiler)
    {
        $compiler
            ->write("public function getTemplateName()\n", "{\n")
            ->indent()
            ->write('return ')
            ->repr($this->getAttribute('filename'))
            ->raw(";\n")
            ->outdent()
            ->write("}\n\n")
        ;
    }

    protected function compileDebugInfo(Compiler $compiler)
    {
        $compiler
            ->write("public function getDebugInfo()\n", "{\n")
            ->indent()
            ->write(sprintf("return %s;\n", str_replace("\n", '', var_export(array_reverse($compiler->getDebugInfo(), true), true))))
            ->outdent()
            ->write("}\n")
        ;
    }

    protected function compileLoadTemplate(Compiler $compiler, $node, $var)
    {
        if ($node instanceof ConstantNode) {
            $compiler
                ->write(sprintf("%s = \$this->env->loadTemplate(", $var))
                ->subcompile($node)
                ->raw(");\n")
            ;
        } else {
            $compiler
                ->write(sprintf("%s = ", $var))
                ->subcompile($node)
                ->raw(";\n")
                ->write(sprintf("if (!%s", $var))
                ->raw(" instanceof Template) {\n")
                ->indent()
                ->write(sprintf("%s = \$this->env->loadTemplate(%s);\n", $var, $var))
                ->outdent()
                ->write("}\n")
            ;
        }
    }
}
