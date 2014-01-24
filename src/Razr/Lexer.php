<?php

namespace Razr;

use Razr\Token\Token;
use Razr\Token\TokenStream;
use Razr\Exception\SyntaxErrorException;

class Lexer
{
    const STATE_DATA            = 0;
    const STATE_BLOCK           = 1;
    const STATE_VAR             = 2;
    const STATE_VAR_EXP         = 3;
    const STATE_STRING          = 4;
    const REGEX_NAME            = '/[a-zA-Z_][a-zA-Z0-9_]*/A';
    const REGEX_NUMBER          = '/[0-9]+(?:\.[0-9]+)?/A';
    const REGEX_STRING          = '/"([^#"\\\\]*(?:\\\\.[^#"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\'/As';
    const REGEX_DQ_STRING_DELIM = '/"/A';
    const REGEX_DQ_STRING_PART  = '/[^#"\\\\]*(?:(?:\\\\.|#(?!\{))[^#"\\\\]*)*/As';
    const PUNCTUATION           = '()[]{}?:.,|';

    protected $env;
    protected $tags;
    protected $tokens;
    protected $code;
    protected $cursor;
    protected $lineno;
    protected $end;
    protected $state;
    protected $states;
    protected $brackets;
    protected $filename;
    protected $regexes;
    protected $position;
    protected $positions;
    protected $currentVarBlockLine;

    public function __construct(Environment $env)
    {
        $this->env = $env;
        $this->tags = $env->getTokenParserTags();
        $this->regexes = array(
            'tag_char'  => '/@{2}|@(?=\(|[a-zA-Z_])/s',
            'tag_start' => '/\(|([a-zA-Z_][a-zA-Z0-9_]*)(\s*\()?/A',
            'tag_part'  => '/\(|\[|[\.\|]([a-zA-Z_][a-zA-Z0-9_]*)/A',
            'tag_end'   => '/(\s*\))\n?/A',
            'operator'  => $this->getOperatorRegex()
        );
    }

    public function tokenize($code, $filename = null)
    {
        if (function_exists('mb_internal_encoding') && ((int) ini_get('mbstring.func_overload')) & 2) {
            $mbEncoding = mb_internal_encoding();
            mb_internal_encoding('ASCII');
        }

        $this->code = str_replace(array("\r\n", "\r"), "\n", $code);
        $this->filename = $filename;
        $this->cursor = 0;
        $this->lineno = 1;
        $this->end = strlen($this->code);
        $this->tokens = array();
        $this->state = self::STATE_DATA;
        $this->states = array();
        $this->brackets = array();
        $this->position = -1;

        preg_match_all($this->regexes['tag_char'], $this->code, $matches, PREG_OFFSET_CAPTURE);
        $this->positions = $matches;

        while ($this->cursor < $this->end) {
            switch ($this->state) {
                case self::STATE_DATA:
                    $this->lexData();
                    break;

                case self::STATE_BLOCK:
                    $this->lexBlock();
                    break;

                case self::STATE_VAR:
                    $this->lexVar();
                    break;

                case self::STATE_VAR_EXP:
                    $this->lexVarExp();
                    break;
            }
        }

        if (!empty($this->brackets)) {
            list($expect, $lineno) = array_pop($this->brackets);
            throw new SyntaxErrorException(sprintf('Unclosed "%s" at line %d in file %s', $expect, $lineno, $this->filename));
        }

        if ($this->state == self::STATE_VAR) {
            $this->pushToken(Token::VAR_END);
            $this->popState();
        }

        $this->pushToken(Token::EOF);

        if (isset($mbEncoding)) {
            mb_internal_encoding($mbEncoding);
        }

        return new TokenStream($this->tokens, $this->filename);
    }

    protected function lexData()
    {
        // if no matches are left we return the rest of the template as simple text token
        if ($this->position == count($this->positions[0]) - 1) {
            $this->pushToken(Token::TEXT, substr($this->code, $this->cursor));
            $this->cursor = $this->end;
            return;
        }

        // find the first token after the current cursor
        $position = $this->positions[0][++$this->position];
        while ($position[1] < $this->cursor) {
            if ($this->position == count($this->positions[0]) - 1) {
                return;
            }
            $position = $this->positions[0][++$this->position];
        }

        // push the template text first
        $text = substr($this->code, $this->cursor, $position[1] - $this->cursor);
        $this->pushToken(Token::TEXT, $text);
        $this->moveCursor($text);

        // move cursor after @
        $this->cursor++;

        if (preg_match($this->regexes['tag_start'], $this->code, $match, null, $this->cursor)) {

            $this->currentVarBlockLine = $this->lineno;

            if (isset($match[1]) && in_array($match[1], $this->tags)) {

                // block tag
                $this->pushToken(Token::BLOCK_START);
                $this->pushToken(Token::NAME, $match[1]);
                $this->moveCursor($match[0]);

                if (isset($match[2])) {
                    $this->pushState(self::STATE_BLOCK);
                    $this->lexBlock();
                } else {

                    if (preg_match('/\n/A', $this->code, $match, null, $this->cursor)) {
                        $this->moveCursor($match[0]);
                    }

                    $this->pushToken(Token::BLOCK_END);
                }

            } elseif (isset($match[1])) {

                // var tag
                $this->pushState(self::STATE_VAR);
                $this->pushToken(Token::VAR_START);
                $this->pushToken(Token::NAME, $match[1]);
                $this->moveCursor($match[1]);
                $this->lexVar();

            } else {

                // var exp tag
                $this->pushState(self::STATE_VAR_EXP);
                $this->pushToken(Token::VAR_START);
                $this->moveCursor($match[0]);
                $this->lexExpression();
            }
        }
    }

    protected function lexBlock()
    {
        // block part
        if (empty($this->brackets) && preg_match($this->regexes['tag_part'], $this->code, $match, null, $this->cursor)) {

            $this->lexExpression();

            if (isset($match[1])) {
                $this->pushToken(Token::NAME, $match[1]);
                $this->moveCursor($match[1]);
            }

        // block end
        } elseif (empty($this->brackets) && preg_match($this->regexes['tag_end'], $this->code, $match, null, $this->cursor)) {
            $this->moveCursor($match[0]);
            $this->pushToken(Token::BLOCK_END);
            $this->popState();
        } else {
            $this->lexExpression();
        }
    }

    protected function lexVar()
    {
        // var part
        if (empty($this->brackets) && preg_match($this->regexes['tag_part'], $this->code, $match, null, $this->cursor)) {

            $this->lexExpression();

            if (isset($match[1])) {
                $this->pushToken(Token::NAME, $match[1]);
                $this->moveCursor($match[1]);
            }

        // var end
        } elseif (empty($this->brackets)) {
            $this->pushToken(Token::VAR_END);
            $this->popState();
        } else {
            $this->lexExpression();
        }
    }

    protected function lexVarExp()
    {
        if (empty($this->brackets) && preg_match($this->regexes['tag_end'], $this->code, $match, null, $this->cursor)) {
            $this->moveCursor($match[1]);
            $this->pushToken(Token::VAR_END);
            $this->popState();
        } else {
            $this->lexExpression();
        }
    }

    protected function lexExpression()
    {
        // whitespace
        if (preg_match('/\s+/A', $this->code, $match, null, $this->cursor)) {
            $this->moveCursor($match[0]);

            if ($this->cursor >= $this->end) {
                throw new SyntaxErrorException(sprintf('Unclosed "%s" at line %d in file %s', ($this->state === self::STATE_BLOCK ? 'block' : 'variable'), $this->currentVarBlockLine, $this->filename));
            }
        }

        // operators
        if (preg_match($this->regexes['operator'], $this->code, $match, null, $this->cursor)) {
            $this->pushToken(Token::OPERATOR, preg_replace('/\s+/', ' ', $match[0]));
            $this->moveCursor($match[0]);
        }
        // names
        elseif (preg_match(self::REGEX_NAME, $this->code, $match, null, $this->cursor)) {
            $this->pushToken(Token::NAME, $match[0]);
            $this->moveCursor($match[0]);
        }
        // numbers
        elseif (preg_match(self::REGEX_NUMBER, $this->code, $match, null, $this->cursor)) {
            $number = (float) $match[0];  // floats
            if (ctype_digit($match[0]) && $number <= PHP_INT_MAX) {
                $number = (int) $match[0]; // integers lower than the maximum
            }
            $this->pushToken(Token::NUMBER, $number);
            $this->moveCursor($match[0]);
        }
        // punctuation
        elseif (false !== strpos(self::PUNCTUATION, $this->code[$this->cursor])) {

            // opening bracket
            if (false !== strpos('([{', $this->code[$this->cursor])) {
                $this->brackets[] = array($this->code[$this->cursor], $this->lineno);
            }

            // closing bracket
            elseif (false !== strpos(')]}', $this->code[$this->cursor])) {

                if (empty($this->brackets)) {
                    throw new SyntaxErrorException(sprintf('Unexpected "%s"', $this->code[$this->cursor]), $this->lineno, $this->filename);
                }

                list($expect, $lineno) = array_pop($this->brackets);

                if ($this->code[$this->cursor] != strtr($expect, '([{', ')]}')) {
                    throw new SyntaxErrorException(sprintf('Unclosed "%s"', $expect), $lineno, $this->filename);
                }
            }

            $this->pushToken(Token::PUNCTUATION, $this->code[$this->cursor]);
            ++$this->cursor;
        }
        // strings
        elseif (preg_match(self::REGEX_STRING, $this->code, $match, null, $this->cursor)) {
            $this->pushToken(Token::STRING, stripcslashes(substr($match[0], 1, -1)));
            $this->moveCursor($match[0]);
        }
        // opening double quoted string
        elseif (preg_match(self::REGEX_DQ_STRING_DELIM, $this->code, $match, null, $this->cursor)) {
            $this->brackets[] = array('"', $this->lineno);
            $this->pushState(self::STATE_STRING);
            $this->moveCursor($match[0]);
        }
        // unlexable
        else {
            throw new SyntaxErrorException(sprintf('Unexpected character "%s"', $this->code[$this->cursor]), $this->lineno, $this->filename);
        }
    }

    protected function pushToken($type, $value = '')
    {
        // do not push empty text tokens
        if (Token::TEXT === $type && '' === $value) {
            return;
        }

        $this->tokens[] = new Token($type, $value, $this->lineno);
    }

    protected function moveCursor($text)
    {
        $this->cursor += strlen($text);
        $this->lineno += substr_count($text, "\n");
    }

    protected function pushState($state)
    {
        $this->states[] = $this->state;
        $this->state = $state;
    }

    protected function popState()
    {
        if (0 === count($this->states)) {
            throw new \Exception('Cannot pop state without a previous state');
        }

        $this->state = array_pop($this->states);
    }

    protected function getOperatorRegex()
    {
        $operators = array_merge(
            array('=', '=>'),
            array_keys($this->env->getUnaryOperators()),
            array_keys($this->env->getBinaryOperators())
        );

        $operators = array_combine($operators, array_map('strlen', $operators));
        arsort($operators);

        $regex = array();
        foreach ($operators as $operator => $length) {
            // an operator that ends with a character must be followed by
            // a whitespace or a parenthesis
            if (ctype_alpha($operator[$length - 1])) {
                $r = preg_quote($operator, '/').'(?=[\s()])';
            } else {
                $r = preg_quote($operator, '/');
            }

            // an operator with a space can be any amount of whitespaces
            $r = preg_replace('/\s+/', '\s+', $r);

            $regex[] = $r;
        }

        return '/'.implode('|', $regex).'/A';
    }
}
