<?php

namespace Razr;

use Razr\Exception\RuntimeException;
use Razr\Exception\SyntaxErrorException;

class Lexer
{
    const STATE_DATA      = 0;
    const STATE_OUTPUT    = 1;
    const STATE_DIRECTIVE = 2;
    const REGEX_CHAR      = '/@{2}|@(?=\(|[a-zA-Z_])/s';
    const REGEX_START     = '/\(|([a-zA-Z_][a-zA-Z0-9_]*)(\s*\()?/A';
    const REGEX_STRING    = '/"([^#"\\\\]*(?:\\\\.[^#"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\'/As';

    protected $engine;
    protected $tokens;
    protected $code;
    protected $source;
    protected $cursor;
    protected $lineno;
    protected $end;
    protected $state;
    protected $states;
    protected $brackets;
    protected $filename;
    protected $position;
    protected $positions;

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
     * Gets the token stream from a template.
     *
     * @param  string $code
     * @param  string $filename
     * @throws SyntaxErrorException
     * @return TokenStream
     */
    public function tokenize($code, $filename = null)
    {
        if (function_exists('mb_internal_encoding') && ((int) ini_get('mbstring.func_overload')) & 2) {
            $encoding = mb_internal_encoding();
            mb_internal_encoding('ASCII');
        }

        $this->code = str_replace(array("\r\n", "\r"), "\n", $code);
        $this->source = '';
        $this->filename = $filename;
        $this->cursor = 0;
        $this->lineno = 1;
        $this->end = strlen($this->code);
        $this->tokens = array();
        $this->state = self::STATE_DATA;
        $this->states = array();
        $this->brackets = array();
        $this->position = -1;

        preg_match_all(self::REGEX_CHAR, $this->code, $this->positions, PREG_OFFSET_CAPTURE);

        while ($this->cursor < $this->end) {
            switch ($this->state) {
                case self::STATE_DATA:
                    $this->lexData();
                    break;

                case self::STATE_OUTPUT:
                    $this->lexOutput();
                    break;

                case self::STATE_DIRECTIVE:
                    $this->lexDirective();
                    break;
            }
        }

        if ($this->state != self::STATE_DATA) {
            $this->addCode(' ?>');
            $this->popState();
        }

        if (!empty($this->brackets)) {
            list($expect, $lineno) = array_pop($this->brackets);
            throw new SyntaxErrorException(sprintf('Unclosed "%s" at line %d in file %s', $expect, $lineno, $this->filename));
        }

        if (isset($encoding)) {
            mb_internal_encoding($encoding);
        }

        return new TokenStream(token_get_all($this->source));
    }

    /**
     * Lex data.
     */
    protected function lexData()
    {
        if ($this->position == count($this->positions[0]) - 1) {
            $this->addCode(substr($this->code, $this->cursor));
            $this->cursor = $this->end;
            return;
        }

        $position = $this->positions[0][++$this->position];

        while ($position[1] < $this->cursor) {
            if ($this->position == count($this->positions[0]) - 1) {
                return;
            }
            $position = $this->positions[0][++$this->position];
        }

        $this->addCode($text = substr($this->code, $this->cursor, $position[1] - $this->cursor));
        $this->moveCursor($text);
        $this->cursor++;

        if (preg_match(self::REGEX_START, $this->code, $match, null, $this->cursor)) {
            if (isset($match[1])) {

                $this->addCode('<?php /* DIRECTIVE */');
                $this->pushState(self::STATE_DIRECTIVE);
                $this->addCode($match[1]);
                $this->moveCursor($match[1]);

                if (isset($match[2])) {
                    $this->moveCursor(rtrim($match[2], '('));
                    $this->lexExpression();
                }

            } else {

                $this->addCode('<?php /* OUTPUT */');
                $this->pushState(self::STATE_OUTPUT);
                $this->lexExpression();

            }
        }
    }

    /**
     * Lex output.
     */
    protected function lexOutput()
    {
        if (empty($this->brackets)) {
            $this->addCode(' ?>');
            $this->popState();
        } else {
            $this->lexExpression();
        }
    }

    /**
     * Lex directive.
     */
    protected function lexDirective()
    {
        if (empty($this->brackets)) {
            $this->addCode(' ?>');
            $this->popState();
        } else {
            $this->lexExpression();
        }
    }

    /**
     * Lex expression.
     */
    protected function lexExpression()
    {
        if (preg_match(self::REGEX_STRING, $this->code, $match, null, $this->cursor)) {
            $this->addCode($match[0]);
            $this->moveCursor($match[0]);
        }

        if (strpos('([{', $this->code[$this->cursor]) !== false) {
            $this->brackets[] = array($this->code[$this->cursor], $this->lineno);
        } elseif (strpos(')]}', $this->code[$this->cursor]) !== false) {

            if (empty($this->brackets)) {
                throw new SyntaxErrorException(sprintf('Unexpected "%s" at line %d in file %s', $this->code[$this->cursor], $this->lineno, $this->filename));
            }

            list($expect, $lineno) = array_pop($this->brackets);

            if ($this->code[$this->cursor] != strtr($expect, '([{', ')]}')) {
                throw new SyntaxErrorException(sprintf('Unclosed "%s" at line %d in file %s', $expect, $lineno, $this->filename));
            }
        }

        $this->addCode($this->code[$this->cursor++]);
    }

    /**
     * Adds a piece of code to the source.
     *
     * @param string $code
     */
    protected function addCode($code)
    {
        $this->source .= $code;
    }

    /**
     * Moves the cursor of the length of the given text.
     *
     * @param string $text
     */
    protected function moveCursor($text)
    {
        $this->cursor += strlen($text);
        $this->lineno += substr_count($text, "\n");
    }

    /**
     * Pushes a state onto the state stack.
     *
     * @param int $state
     */
    protected function pushState($state)
    {
        $this->states[] = $this->state;
        $this->state = $state;
    }

    /**
     * Pops the last state off the state stack.
     */
    protected function popState()
    {
        if (count($this->states) === 0) {
            throw new RuntimeException('Cannot pop state without a previous state');
        }

        $this->state = array_pop($this->states);
    }
}
