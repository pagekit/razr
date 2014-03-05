<?php

namespace Razr\Extension;

use Razr\Environment;
use Razr\Exception\InvalidArgumentException;
use Razr\Exception\RuntimeException;
use Razr\Operator\BinaryOperator;
use Razr\Operator\UnaryOperator;
use Razr\SimpleFilter;
use Razr\SimpleFunction;
use Razr\Template;
use Razr\Token\TokenParser\BlockTokenParser;
use Razr\Token\TokenParser\ExtendsTokenParser;
use Razr\Token\TokenParser\ForeachTokenParser;
use Razr\Token\TokenParser\IfTokenParser;
use Razr\Token\TokenParser\SetTokenParser;
use Razr\Token\TokenParser\WhileTokenParser;

class CoreExtension extends Extension
{
    protected $env;
    protected $timezone;
    protected $dateFormats  = array('F j, Y H:i', '%d days');
    protected $numberFormat = array(0, '.', ',');
    protected $escapers = array();

    public function getName()
    {
        return 'core';
    }

    public function getTimezone()
    {
        if (!$this->timezone) {
            $this->timezone = new \DateTimeZone(date_default_timezone_get());
        }

        return $this->timezone;
    }

    public function getDateFormat()
    {
        return $this->dateFormats;
    }

    public function getNumberFormat()
    {
        return $this->numberFormat;
    }

    public function getEscaper($strategy)
    {
        if (!isset($this->escapers[$strategy])) {
            throw new InvalidArgumentException(sprintf('No registered escaper for strategy "%s".', $strategy));
        }

        return $this->escapers[$strategy];
    }

    public function getEscapers()
    {
        return $this->escapers;
    }

    public function setTimezone($timezone)
    {
        $this->timezone = $timezone instanceof \DateTimeZone ? $timezone : new \DateTimeZone($timezone);
    }

    public function setDateFormat($format = null, $dateIntervalFormat = null)
    {
        if (null !== $format) {
            $this->dateFormats[0] = $format;
        }

        if (null !== $dateIntervalFormat) {
            $this->dateFormats[1] = $dateIntervalFormat;
        }
    }

    public function setNumberFormat($decimal, $decimalPoint, $thousandSep)
    {
        $this->numberFormat = array($decimal, $decimalPoint, $thousandSep);
    }

    public function setEscaper($strategy, $escaper)
    {
        $this->escapers[$strategy] = $escaper;
    }

    public function initRuntime(Environment $env)
    {
        $this->env = $env;

        foreach (self::initializeEscapers() as $strategy => $escaper) {
            $this->setEscaper($strategy, $escaper);
        }
    }

    public function getTokenParsers()
    {
        return array(
            new BlockTokenParser,
            new ExtendsTokenParser,
            new ForeachTokenParser,
            new IfTokenParser,
            new SetTokenParser,
            new WhileTokenParser,
        );
    }

    public function getFilters()
    {
        $filters = array(

            // formatting filters
            new SimpleFilter('abs', 'abs'),
            new SimpleFilter('format', 'sprintf'),
            new SimpleFilter('replace', 'strtr'),
            new SimpleFilter('date', array($this, 'formatDate')),
            new SimpleFilter('number', array($this, 'formatNumber')),
            new SimpleFilter('round', array($this, 'roundNumber')),

            // encoding
            new SimpleFilter('json_encode', 'json_encode'),
            new SimpleFilter('url_encode', array($this, 'encodeUrl')),

            // string filters
            new SimpleFilter('lower', 'strtolower'),
            new SimpleFilter('nl2br', 'nl2br'),
            new SimpleFilter('striptags', 'strip_tags'),
            new SimpleFilter('trim', 'trim'),
            new SimpleFilter('upper', 'strtoupper'),

            // array helpers
            new SimpleFilter('explode', array($this, 'explodeArray')),
            new SimpleFilter('implode', array($this, 'implodeArray')),
            new SimpleFilter('keys', array($this, 'keysArray')),
            new SimpleFilter('merge', array($this, 'mergeArray')),

            // string/array helpers
            new SimpleFilter('length', array($this, 'length')),
            new SimpleFilter('slice', array($this, 'slice')),

            // escaping
            new SimpleFilter('e', array($this, 'escape')),
            new SimpleFilter('escape', array($this, 'escape')),
        );

        return $filters;
    }

    public function getFunctions()
    {
        return array(
            new SimpleFunction('max', 'max'),
            new SimpleFunction('min', 'min'),
            new SimpleFunction('range', 'range'),
            new SimpleFunction('constant', array($this, 'getConstant')),
            new SimpleFunction('date', array($this, 'getDateTime')),
            new SimpleFunction('dump', array($this, 'varDump'), array('needs_context' => true)),
            new SimpleFunction('include', array($this, 'includeTemplate'), array('needs_environment' => true, 'needs_context' => true)),
        );
    }

    public function getOperators()
    {
        return array(
            array(
                '!'  => new UnaryOperator(50, 'Razr\Node\Expression\Unary\NotNode'),
                '-'  => new UnaryOperator(500, 'Razr\Node\Expression\Unary\NegNode'),
                '+'  => new UnaryOperator(500, 'Razr\Node\Expression\Unary\PosNode'),
            ),
            array(
                'or'  => new BinaryOperator(10, BinaryOperator::LEFT, 'Razr\Node\Expression\Binary\OrWordNode'),
                'and' => new BinaryOperator(15, BinaryOperator::LEFT, 'Razr\Node\Expression\Binary\AndWordNode'),
                '||'  => new BinaryOperator(20, BinaryOperator::LEFT, 'Razr\Node\Expression\Binary\OrNode'),
                '&&'  => new BinaryOperator(25, BinaryOperator::LEFT, 'Razr\Node\Expression\Binary\AndNode'),
                '=='  => new BinaryOperator(30, BinaryOperator::LEFT, 'Razr\Node\Expression\Binary\EqualNode'),
                '!='  => new BinaryOperator(30, BinaryOperator::LEFT, 'Razr\Node\Expression\Binary\NotEqualNode'),
                '===' => new BinaryOperator(30, BinaryOperator::LEFT, 'Razr\Node\Expression\Binary\EqualTypeNode'),
                '!==' => new BinaryOperator(30, BinaryOperator::LEFT, 'Razr\Node\Expression\Binary\NotEqualTypeNode'),
                '<'   => new BinaryOperator(30, BinaryOperator::LEFT, 'Razr\Node\Expression\Binary\LessNode'),
                '>'   => new BinaryOperator(30, BinaryOperator::LEFT, 'Razr\Node\Expression\Binary\GreaterNode'),
                '>='  => new BinaryOperator(30, BinaryOperator::LEFT, 'Razr\Node\Expression\Binary\GreaterEqualNode'),
                '<='  => new BinaryOperator(30, BinaryOperator::LEFT, 'Razr\Node\Expression\Binary\LessEqualNode'),
                '+'   => new BinaryOperator(40, BinaryOperator::LEFT, 'Razr\Node\Expression\Binary\AddNode'),
                '-'   => new BinaryOperator(40, BinaryOperator::LEFT, 'Razr\Node\Expression\Binary\SubNode'),
                '~'   => new BinaryOperator(50, BinaryOperator::LEFT, 'Razr\Node\Expression\Binary\ConcatNode'),
                '*'   => new BinaryOperator(60, BinaryOperator::LEFT, 'Razr\Node\Expression\Binary\MulNode'),
                '/'   => new BinaryOperator(60, BinaryOperator::LEFT, 'Razr\Node\Expression\Binary\DivNode'),
                '%'   => new BinaryOperator(60, BinaryOperator::LEFT, 'Razr\Node\Expression\Binary\ModNode'),
            ),
        );
    }

    public function formatDate($date, $format = null, $timezone = null)
    {
        if (null === $format) {
            $formats = $this->getDateFormat();
            $format  = $date instanceof \DateInterval ? $formats[1] : $formats[0];
        }

        if ($date instanceof \DateInterval) {
            return $date->format($format);
        }

        return $this->getDateTime($date, $timezone)->format($format);
    }

    public function getDateTime($date = null, $timezone = null)
    {
        if (!$timezone) {
            $defaultTimezone = $this->getTimezone();
        } elseif (!$timezone instanceof \DateTimeZone) {
            $defaultTimezone = new \DateTimeZone($timezone);
        } else {
            $defaultTimezone = $timezone;
        }

        if ($date instanceof \DateTime || $date instanceof \DateTimeInterface) {

            $returningDate = new \DateTime($date->format('c'));

            if (false !== $timezone) {
                $returningDate->setTimezone($defaultTimezone);
            } else {
                $returningDate->setTimezone($date->getTimezone());
            }

            return $returningDate;
        }

        $asString = (string) $date;

        if (ctype_digit($asString) || (!empty($asString) && '-' === $asString[0] && ctype_digit(substr($asString, 1)))) {
            $date = '@'.$date;
        }

        $date = new \DateTime($date, $defaultTimezone);

        if (false !== $timezone) {
            $date->setTimezone($defaultTimezone);
        }

        return $date;
    }

    public function formatNumber($number, $decimal = null, $decimalPoint = null, $thousandSep = null)
    {
        $defaults = $this->getNumberFormat();

        if (null === $decimal) {
            $decimal = $defaults[0];
        }

        if (null === $decimalPoint) {
            $decimalPoint = $defaults[1];
        }

        if (null === $thousandSep) {
            $thousandSep = $defaults[2];
        }

        return number_format((float) $number, $decimal, $decimalPoint, $thousandSep);
    }

    public function roundNumber($value, $precision = 0, $method = 'common')
    {
        if ('common' == $method) {
            return round($value, $precision);
        }

        if ('ceil' != $method && 'floor' != $method) {
            throw new RuntimeException('The round filter only supports the "common", "ceil", and "floor" methods.');
        }

        return $method($value * pow(10, $precision)) / pow(10, $precision);
    }

    public function encodeUrl($url, $raw = false)
    {
        if (is_array($url)) {
            return http_build_query($url, '', '&');
        }

        if ($raw) {
            return rawurlencode($url);
        }

        return urlencode($url);
    }

    public function keysArray($array)
    {
        if (is_object($array) && $array instanceof \Traversable) {
            return array_keys(iterator_to_array($array));
        }

        if (!is_array($array)) {
            return array();
        }

        return array_keys($array);
    }

    public function mergeArray($arr1, $arr2)
    {
        if (!is_array($arr1) || !is_array($arr2)) {
            throw new RuntimeException('The merge filter only works with arrays or hashes.');
        }

        return array_merge($arr1, $arr2);
    }

    public function slice($array, $offset, $length = null, $preserveKeys = false)
    {

        if (is_string($array)) {

            if (function_exists('mb_get_info') && null !== $charset = $this->env->getCharset()) {
                return mb_substr($array, $offset, null === $length ? mb_strlen($array, $charset) - $offset : $length, $charset);
            }

            return null === $length ? substr($array, $offset) : substr($array, $offset, $length);
        }

        if (is_object($array) && $array instanceof \Traversable) {
            $array = iterator_to_array($array, false);
        }

        return array_slice((array) $array, $offset, $length, $preserveKeys);
    }

    public function implodeArray($array, $glue = '')
    {
        if (is_object($array) && $array instanceof \Traversable) {
            $array = iterator_to_array($array, false);
        }

        return implode($glue, (array) $array);
    }

    public function explodeArray($value, $delimiter, $limit = null)
    {
        if (empty($delimiter)) {
            return str_split($value, null === $limit ? 1 : $limit);
        }

        return null === $limit ? explode($delimiter, $value) : explode($delimiter, $value, $limit);
    }

    public function length($value)
    {
        return is_scalar($value) ? strlen($value) : count($value);
    }

    public function escape($value, $strategy = 'html', $charset = null)
    {
        if (is_numeric($value)) {
            return $value;
        }

        if (!$charset) {
            $charset = $this->env->getCharset();
        }

        return call_user_func($this->getEscaper($strategy), $value, $charset);
    }

    public function getConstant($name, $object = null)
    {
        if ($object !== null) {
            $name = sprintf('%s::%s', get_class($object), $name);
        }

        return constant($name);
    }

    public function includeTemplate(Environment $env, $context, $template, $variables = array())
    {
        return $env->loadTemplate($template)->render(array_merge($context, $variables));
    }

    public function varDump($context)
    {
        ob_start();

        $count = func_num_args();

        if (1 === $count) {

            $vars = array();

            foreach ($context as $key => $value) {
                if (!$value instanceof Template) {
                    $vars[$key] = $value;
                }
            }

            var_dump($vars);

        } else {
            for ($i = 1; $i < $count; $i++) {
                var_dump(func_get_arg($i));
            }
        }

        return ob_get_clean();
    }

    public function ensureTraversable($seq)
    {
        if ($seq instanceof \Traversable || is_array($seq)) {
            return $seq;
        }

        return array();
    }

    public static function convertEncoding($string, $to, $from)
    {
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($string, $to, $from);
        } elseif (function_exists('iconv')) {
            return iconv($from, $to, $string);
        }

        throw new RuntimeException('No suitable convert encoding function (use UTF-8 as your encoding or install the iconv or mbstring extension).');
    }

    protected static function initializeEscapers()
    {
        return array(

            'html' =>

                function ($value, $charset = 'UTF-8') {
                    return is_string($value) ? htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, $charset, false) : $value;
                },

            'js' =>

                function ($value, $charset = 'UTF-8') {

                    if ('UTF-8' != $charset) {
                        $value = self::convertEncoding($value, 'UTF-8', $charset);
                    }

                    $callback = function ($matches) {

                        $char = $matches[0];

                        // \xHH
                        if (!isset($char[1])) {
                            return '\\x'.substr('00'.bin2hex($char), -2);
                        }

                        // \uHHHH
                        $char = self::convertEncoding($char, 'UTF-16BE', 'UTF-8');

                        return '\\u'.substr('0000'.bin2hex($char), -4);
                    };

                    if (null === $value = preg_replace_callback('#[^\p{L}\p{N} ]#u', $callback, $value)) {
                        throw new InvalidArgumentException('The string to escape is not a valid UTF-8 string.');
                    }

                    if ('UTF-8' != $charset) {
                        $value = self::convertEncoding($value, $charset, 'UTF-8');
                    }

                    return $value;
                }
        );
    }
}
