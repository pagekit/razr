<?php

namespace Razr;

class Parser
{
    protected $engine;
    protected $stream;
    protected $filename;
    protected $variables;

    /**
     * Constructor.
     *
     * @param Engine $engine
     */
    public function __construct(Engine $engine)
    {
        $this->engine = $engine;
    }

    /**
     * Parsing method.
     *
     * @param  TokenStream $stream
     * @param  string      $filename
     * @return string
     */
    public function parse($stream, $filename = null)
    {
        $this->stream = $stream;
        $this->filename = $filename;
        $this->variables = array();

        return $this->parseMain();
    }

    /**
     * Parse main.
     *
     * @return string
     */
    public function parseMain()
    {
        $out = '';

        while ($token = $this->stream->next()) {
            if ($token->test(T_COMMENT, '/* OUTPUT */')) {
                $out .= $this->parseOutput();
            } elseif ($token->test(T_COMMENT, '/* DIRECTIVE */')) {
                $out .= $this->parseDirective();
            } else {
                $out .= $token->getValue();
            }
        }

        if ($this->variables) {
            $info = sprintf('<?php /* %s */ extract(%s, EXTR_SKIP) ?>', $this->filename, str_replace("\n", '', var_export($this->variables, true)));
        } else {
            $info = sprintf('<?php /* %s */ ?>', $this->filename);
        }

        return $info.$out;
    }

    /**
     * Parse output.
     *
     * @return string
     */
    public function parseOutput()
    {
        $out = "echo \$this->escape(";

        while (!$this->stream->test(T_CLOSE_TAG)) {
            $out .= $this->parseExpression();
        }

        return "$out) ";
    }

    /**
     * Parse directive.
     *
     * @return string
     */
    public function parseDirective()
    {
        $out = '';

        foreach ($this->engine->getDirectives() as $directive) {
            if ($out = $directive->parse($this->stream, $this->stream->get())) {
                break;
            }
        }

        return $out;
    }

    /**
     * Parse expression.
     *
     * @return string
     */
    public function parseExpression()
    {
        $out = '';
        $brackets = array();

        do {

            if ($token = $this->stream->nextIf(T_STRING)) {

                $name = $token->getValue();

                if ($this->stream->test('(') && $this->engine->getFunction($name)) {
                    $out .= sprintf("\$this->callFunction('%s', array%s)", $name, $this->parseExpression());
                } else {
                    $out .= $name;
                }

            } elseif ($token = $this->stream->nextIf(T_VARIABLE)) {

                $out .= $this->parseSubscript($var = $token->getValue());
                $this->variables[ltrim($var, '$')] = null;

            } else {

                $token = $this->stream->next();

                if ($token->test(array('(', '['))) {
                    array_push($brackets, $token);
                } elseif ($token->test(array(')', ']'))) {
                    array_pop($brackets);
                }

                $out .= $token->getValue();
            }

        } while (!empty($brackets));

        return $out;
    }

    /**
     * Parse subscript.
     *
     * @param  string $out
     * @return string
     */
    public function parseSubscript($out)
    {
        while (true) {
            if ($this->stream->nextIf('.')) {

                if (!$this->stream->test(T_STRING)) {
                    $this->stream->prev();
                    break;
                }

                $val = $this->stream->next()->getValue();
                $out = sprintf("\$this->getAttribute(%s, '%s'", $out, $val);

                if ($this->stream->test('(')) {
                    $out .= sprintf(", array%s, 'method')", $this->parseExpression());
                } else {
                    $out .= ")";
                }

            } elseif ($this->stream->nextIf('[')) {

                $exp = '';

                while (!$this->stream->test(']')) {
                    $exp .= $this->parseExpression();
                }

                $this->stream->expect(']');
                $this->stream->next();

                $out = sprintf("\$this->getAttribute(%s, %s, array(), 'array')", $out, $exp);

            } else {
                break;
            }
        }

        return $out;
    }
}
