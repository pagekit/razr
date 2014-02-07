<?php

namespace Razr;

use Razr\Exception\SyntaxErrorException;
use Razr\Node\Expression\ArrayNode;
use Razr\Node\Expression\AssignNameNode;
use Razr\Node\Expression\ConditionalNode;
use Razr\Node\Expression\ConstantNode;
use Razr\Node\Expression\FilterNode;
use Razr\Node\Expression\FunctionNode;
use Razr\Node\Expression\GetAttrNode;
use Razr\Node\Expression\NameNode;
use Razr\Node\Expression\ParentNode;
use Razr\Node\Node;
use Razr\Token\Token;

class ExpressionParser
{
    protected $parser;
    protected $unary;
    protected $binary;

    public function __construct(Parser $parser, array $unary, array $binary)
    {
        $this->parser = $parser;
        $this->unary  = $unary;
        $this->binary = $binary;
    }

    public function parseExpression($precedence = 0)
    {
        $expr  = $this->getPrimary();
        $token = $this->parser->getCurrentToken();

        while ($operator = $this->getBinaryOperator($token) and $operator->getPrecedence() >= $precedence) {
            $this->parser->getStream()->next();
            $expr1 = $this->parseExpression($operator->isLeftAssociative() ? $operator->getPrecedence() + 1 : $operator->getPrecedence());
            $expr = $operator->getNode($expr, $expr1, $token->getLine());
            $token = $this->parser->getCurrentToken();
        }

        if (0 === $precedence) {
            return $this->parseConditionalExpression($expr);
        }

        return $expr;
    }

    protected function getPrimary()
    {
        $token = $this->parser->getCurrentToken();

        if ($operator = $this->getUnaryOperator($token)) {

            $this->parser->getStream()->next();
            $expr = $this->parseExpression($operator->getPrecedence());

            return $this->parsePostfixExpression($operator->getNode($expr, $token->getLine()));

        } elseif ($token->test(Token::PUNCTUATION, '(')) {

            $this->parser->getStream()->next();
            $expr = $this->parseExpression();
            $this->parser->getStream()->expect(Token::PUNCTUATION, ')', 'An opened parenthesis is not properly closed');

            return $this->parsePostfixExpression($expr);
        }

        return $this->parsePrimaryExpression();
    }

    protected function parseConditionalExpression($expr)
    {
        while ($this->parser->getStream()->nextIf(Token::PUNCTUATION, '?')) {

            if (!$this->parser->getStream()->nextIf(Token::PUNCTUATION, ':')) {
                $expr2 = $this->parseExpression();
                if ($this->parser->getStream()->nextIf(Token::PUNCTUATION, ':')) {
                    $expr3 = $this->parseExpression();
                } else {
                    $expr3 = new ConstantNode('', $this->parser->getCurrentToken()->getLine());
                }
            } else {
                $expr2 = $expr;
                $expr3 = $this->parseExpression();
            }

            $expr = new ConditionalNode($expr, $expr2, $expr3, $this->parser->getCurrentToken()->getLine());
        }

        return $expr;
    }

    public function parsePrimaryExpression()
    {
        $token = $this->parser->getCurrentToken();

        switch ($token->getType()) {

            case Token::NAME:

                $this->parser->getStream()->next();

                switch ($token->getValue()) {

                    case 'true':
                    case 'TRUE':

                        $node = new ConstantNode(true, $token->getLine());
                        break;

                    case 'false':
                    case 'FALSE':

                        $node = new ConstantNode(false, $token->getLine());
                        break;

                    case 'null':
                    case 'NULL':

                        $node = new ConstantNode(null, $token->getLine());
                        break;

                    default:

                        if ('(' === $this->parser->getCurrentToken()->getValue()) {
                            $node = $this->getFunctionNode($token->getValue(), $token->getLine());
                        } else {
                            $node = new NameNode($token->getValue(), $token->getLine());
                        }
                }
                break;

            case Token::NUMBER:

                $this->parser->getStream()->next();
                $node = new ConstantNode($token->getValue(), $token->getLine());
                break;

            case Token::STRING:

                $this->parser->getStream()->next();
                $node = new ConstantNode($token->getValue(), $token->getLine());
                break;

            case Token::OPERATOR:

                if (preg_match(Lexer::REGEX_NAME, $token->getValue(), $matches) && $matches[0] == $token->getValue()) {
                    // in this context, string operators are variable names
                    $this->parser->getStream()->next();
                    $node = new NameNode($token->getValue(), $token->getLine());
                    break;
                }

            default:

                if ($token->test(Token::PUNCTUATION, '[')) {
                    $node = $this->parseArrayExpression();
                } else {
                    throw new SyntaxErrorException(sprintf('Unexpected token "%s" of value "%s"', Token::typeToEnglish($token->getType(), $token->getLine()), $token->getValue()), $token->getLine(), $this->parser->getFilename());
                }
        }

        return $this->parsePostfixExpression($node);
    }

    public function parseArrayExpression()
    {
        $stream = $this->parser->getStream();
        $stream->expect(Token::PUNCTUATION, '[', 'An array element was expected');

        $node  = new ArrayNode(array(), $stream->getCurrent()->getLine());
        $hash  = false;
        $first = true;

        while (!$stream->test(Token::PUNCTUATION, ']')) {

            if (!$first) {

                $stream->expect(Token::PUNCTUATION, ',', 'An array element must be followed by a comma');

                // trailing ,?
                if ($stream->test(Token::PUNCTUATION, ']')) {
                    break;
                }
            }

            $token = $stream->getCurrent();
            $value = $this->parseExpression();

            // is hash ?
            if ($first && $stream->nextIf(Token::OPERATOR, '=>')) {
                $hash = true;
            } elseif ($hash) {
                $stream->expect(Token::OPERATOR, '=>', 'An array key must be followed by a =>');
            }

            if ($hash) {

                if ($token == $stream->look(-2) && ($token->test(Token::STRING) || $token->test(Token::NUMBER))) {
                    $node->addElement($this->parseExpression(), $value);
                } else {
                    throw new SyntaxErrorException(sprintf('A hash key must be a quoted string or a number (unexpected token "%s" of value "%s"', Token::typeToEnglish($token->getType(), $token->getLine()), $token->getValue()), $token->getLine(), $this->parser->getFilename());
                }

            } else {

                $node->addElement($value);

            }

            $first = false;
        }

        $stream->expect(Token::PUNCTUATION, ']', 'An opened array is not properly closed');

        return $node;
    }

    public function parsePostfixExpression($node)
    {
        while (true) {

            $token = $this->parser->getCurrentToken();

            if ($token->getType() == Token::PUNCTUATION) {
                if ('.' == $token->getValue() || '[' == $token->getValue()) {
                    $node = $this->parseSubscriptExpression($node);
                } elseif ('|' == $token->getValue()) {
                    $node = $this->parseFilterExpression($node);
                } else {
                    break;
                }
            } else {
                break;
            }
        }

        return $node;
    }

    public function getFunctionNode($name, $line)
    {
        switch ($name) {

            case 'parent':

                $args = $this->parseArguments();

                if (!count($this->parser->getBlockStack())) {
                    throw new SyntaxErrorException('Calling "parent" outside a block is forbidden', $line, $this->parser->getFilename());
                }

                if (!$this->parser->getParent()) {
                    throw new SyntaxErrorException('Calling "parent" on a template that does not extend another template is forbidden', $line, $this->parser->getFilename());
                }

                return new ParentNode($this->parser->peekBlockStack(), $line);

            case 'attribute':

                $args = $this->parseArguments();

                if (count($args) < 2) {
                    throw new SyntaxErrorException('The "attribute" function takes at least two arguments (the variable and the attributes)', $line, $this->parser->getFilename());
                }

                return new GetAttrNode($args->getNode(0), $args->getNode(1), count($args) > 2 ? $args->getNode(2) : new ArrayNode(array(), $line), Template::ANY_CALL, $line);

            default:

                $args  = $this->parseArguments(true);
                $class = $this->getFunctionNodeClass($name, $line);

                return new $class($name, $args, $line);
        }
    }

    public function parseSubscriptExpression($node)
    {
        $stream = $this->parser->getStream();
        $token  = $stream->next();
        $lineno = $token->getLine();
        $args   = new ArrayNode(array(), $lineno);
        $type   = Template::ANY_CALL;

        if ($token->getValue() == '.') {

            $token = $stream->next();

            if ($token->test(Token::NAME) || $token->test(Token::NUMBER) || ($token->test(Token::OPERATOR) && preg_match(Lexer::REGEX_NAME, $token->getValue()))
            ) {
                $arg = new ConstantNode($token->getValue(), $lineno);

                if ($stream->test(Token::PUNCTUATION, '(')) {
                    $type = Template::METHOD_CALL;
                    foreach ($this->parseArguments() as $n) {
                        $args->addElement($n);
                    }
                }

            } else {
                throw new SyntaxErrorException('Expected name or number', $lineno, $this->parser->getFilename());
            }

        } else {

            $type = Template::ARRAY_CALL;

            // slice?
            $slice = false;

            if ($stream->test(Token::PUNCTUATION, ':')) {
                $slice = true;
                $arg = new ConstantNode(0, $token->getLine());
            } else {
                $arg = $this->parseExpression();
            }

            if ($stream->nextIf(Token::PUNCTUATION, ':')) {
                $slice = true;
            }

            if ($slice) {

                if ($stream->test(Token::PUNCTUATION, ']')) {
                    $length = new ConstantNode(null, $token->getLine());
                } else {
                    $length = $this->parseExpression();
                }

                $class  = $this->getFilterNodeClass('slice', $token->getLine());
                $args   = new Node(array($arg, $length));
                $filter = new $class($node, new ConstantNode('slice', $token->getLine()), $args, $token->getLine());

                $stream->expect(Token::PUNCTUATION, ']');

                return $filter;
            }

            $stream->expect(Token::PUNCTUATION, ']');
        }

        return new GetAttrNode($node, $arg, $args, $type, $lineno);
    }

    public function parseFilterExpression($node)
    {
        $this->parser->getStream()->next();

        return $this->parseFilterExpressionRaw($node);
    }

    public function parseFilterExpressionRaw($node, $tag = null)
    {
        while (true) {

            $token = $this->parser->getStream()->expect(Token::NAME);
            $name  = new ConstantNode($token->getValue(), $token->getLine());

            if (!$this->parser->getStream()->test(Token::PUNCTUATION, '(')) {
                $arguments = new Node;
            } else {
                $arguments = $this->parseArguments(true);
            }

            $class = $this->getFilterNodeClass($name->getAttribute('value'), $token->getLine());
            $node  = new $class($node, $name, $arguments, $token->getLine(), $tag);

            if (!$this->parser->getStream()->test(Token::PUNCTUATION, '|')) {
                break;
            }

            $this->parser->getStream()->next();
        }

        return $node;
    }

    /**
     * Parses arguments.
     *
     * @param Boolean $namedArguments Whether to allow named arguments or not
     * @param Boolean $definition     Whether we are parsing arguments for a function definition
     */
    public function parseArguments($namedArguments = false, $definition = false)
    {
        $args = array();
        $stream = $this->parser->getStream();

        $stream->expect(Token::PUNCTUATION, '(', 'A list of arguments must begin with an opening parenthesis');
        while (!$stream->test(Token::PUNCTUATION, ')')) {

            if (!empty($args)) {
                $stream->expect(Token::PUNCTUATION, ',', 'Arguments must be separated by a comma');
            }

            if ($definition) {
                $token = $stream->expect(Token::NAME, null, 'An argument must be a name');
                $value = new NameNode($token->getValue(), $this->parser->getCurrentToken()->getLine());
            } else {
                $value = $this->parseExpression();
            }

            $name = null;

            if ($namedArguments && $token = $stream->nextIf(Token::OPERATOR, '=')) {

                if (!$value instanceof NameNode) {
                    throw new SyntaxErrorException(sprintf('A parameter name must be a string, "%s" given', get_class($value)), $token->getLine(), $this->parser->getFilename());
                }

                $name = $value->getAttribute('name');

                if ($definition) {

                    $value = $this->parsePrimaryExpression();

                    if (!$this->checkConstantExpression($value)) {
                        throw new SyntaxErrorException(sprintf('A default value for an argument must be a constant (a boolean, a string, a number, or an array).'), $token->getLine(), $this->parser->getFilename());
                    }
                } else {
                    $value = $this->parseExpression();
                }
            }

            if ($definition) {
                if (null === $name) {
                    $name = $value->getAttribute('name');
                    $value = new ConstantNode(null, $this->parser->getCurrentToken()->getLine());
                }
                $args[$name] = $value;
            } else {
                if (null === $name) {
                    $args[] = $value;
                } else {
                    $args[$name] = $value;
                }
            }
        }

        $stream->expect(Token::PUNCTUATION, ')', 'A list of arguments must be closed by a parenthesis');

        return new Node($args);
    }

    public function parseAssignmentExpression()
    {
        $names = array();
        $values = array();
        $stream = $this->parser->getStream();

        while (true) {

            $token = $stream->expect(Token::NAME, null, 'Only variables can be assigned to');

            if (in_array($token->getValue(), array('true', 'false', 'none'))) {
                throw new SyntaxErrorException(sprintf('You cannot assign a value to "%s"', $token->getValue()), $token->getLine(), $this->parser->getFilename());
            }

            $names[] = new AssignNameNode($token->getValue(), $token->getLine());

            $stream->expect(Token::OPERATOR, '=');

            $values[] = $this->parseExpression();

            if (!$stream->nextIf(Token::PUNCTUATION, ',')) {
                break;
            }
        }

        return array('names' => new Node($names), 'values' => new Node($values));
    }

    protected function getUnaryOperator(Token $token)
    {
        return $token->test(Token::OPERATOR) && isset($this->unary[$token->getValue()]) ? $this->unary[$token->getValue()] : null;
    }

    protected function getBinaryOperator(Token $token)
    {
        return $token->test(Token::OPERATOR) && isset($this->binary[$token->getValue()]) ? $this->binary[$token->getValue()] : null;
    }

    protected function getFunctionNodeClass($name, $line)
    {
        $env = $this->parser->getEnvironment();

        if (false === $function = $env->getFunction($name)) {

            $message = sprintf('The function "%s" does not exist', $name);

            if ($alt = $env->computeAlternatives($name, array_keys($env->getFunctions()))) {
                $message = sprintf('%s. Did you mean "%s"', $message, implode('", "', $alt));
            }

            throw new SyntaxErrorException($message, $line, $this->parser->getFilename());
        }

        if ($function instanceof SimpleFunction) {
            return $function->getNodeClass();
        }

        return $function instanceof FunctionNode ? $function->getClass() : 'Razr\Node\Expression\FunctionNode';
    }

    protected function getFilterNodeClass($name, $line)
    {
        $env = $this->parser->getEnvironment();

        if (false === $filter = $env->getFilter($name)) {

            $message = sprintf('The filter "%s" does not exist', $name);

            if ($alt = $env->computeAlternatives($name, array_keys($env->getFilters()))) {
                $message = sprintf('%s. Did you mean "%s"', $message, implode('", "', $alt));
            }

            throw new SyntaxErrorException($message, $line, $this->parser->getFilename());
        }

        if ($filter instanceof SimpleFilter) {
            return $filter->getNodeClass();
        }

        return $filter instanceof FilterNode ? $filter->getClass() : 'Razr\Node\Expression\FilterNode';
    }

    // checks that the node only contains "constant" elements
    protected function checkConstantExpression($node)
    {
        if (!($node instanceof ConstantNode || $node instanceof ArrayNode)) {
            return false;
        }

        foreach ($node as $n) {
            if (!$this->checkConstantExpression($n)) {
                return false;
            }
        }

        return true;
    }
}
