<?php

namespace Razr;

use Razr\Exception\SyntaxErrorException;
use Razr\Node\BlockNode;
use Razr\Node\BlockReferenceNode;
use Razr\Node\BodyNode;
use Razr\Node\ModuleNode;
use Razr\Node\Node;
use Razr\Node\NodeOutputInterface;
use Razr\Node\PrintNode;
use Razr\Node\TextNode;
use Razr\Token\Token;
use Razr\Token\TokenStream;

class Parser
{
    protected $env;
    protected $stream;
    protected $parent;
    protected $handlers;
    protected $blocks;
    protected $blockStack;
    protected $expressionParser;
    protected $stack = array();

    public function __construct(Environment $env)
    {
        $this->env = $env;
    }

    public function getEnvironment()
    {
        return $this->env;
    }

    public function getVarName()
    {
        return sprintf('__internal_%s', sha1(uniqid(mt_rand(), true)));
    }

    public function getFilename()
    {
        return $this->stream->getFilename();
    }

    public function parse(TokenStream $stream, $test = null, $dropNeedle = false)
    {
        // push all variables into the stack to keep the current state of the parser
        $vars = get_object_vars($this);
        unset($vars['stack'], $vars['env'], $vars['handlers'], $vars['expressionParser']);
        $this->stack[] = $vars;

        // tag handlers
        if (null === $this->handlers) {
            foreach ($this->handlers = $this->env->getTokenParsers() as $handler) {
                $handler->setParser($this);
            }
        }

        if (null === $this->expressionParser) {
            $this->expressionParser = new ExpressionParser($this, $this->env->getUnaryOperators(), $this->env->getBinaryOperators());
        }

        $this->stream = $stream;
        $this->parent = null;
        $this->blocks = array();
        $this->blockStack = array();

        try {

            $body = $this->subparse($test, $dropNeedle);

            if (null !== $this->parent) {
                if (null === $body = $this->filterBodyNodes($body)) {
                    $body = new Node;
                }
            }

        } catch (SyntaxErrorException $e) {

            if (!$e->getTemplateFile()) {
                $e->setTemplateFile($this->getFilename());
            }

            if (!$e->getTemplateLine()) {
                $e->setTemplateLine($this->stream->getCurrent()->getLine());
            }

            throw $e;
        }

        $node = new ModuleNode(new BodyNode(array($body)), $this->parent, new Node($this->blocks), $this->getFilename());

        // restore previous stack so previous parse() call can resume working
        foreach (array_pop($this->stack) as $key => $val) {
            $this->$key = $val;
        }

        return $node;
    }

    public function subparse($test, $dropNeedle = false)
    {
        $lineno = $this->getCurrentToken()->getLine();
        $rv = array();

        while (!$this->stream->isEOF()) {
            switch ($this->getCurrentToken()->getType()) {

                case Token::TEXT:

                    $token = $this->stream->next();
                    $rv[]  = new TextNode($token->getValue(), $token->getLine());
                    break;

                case Token::VAR_START:

                    $token = $this->stream->next();
                    $expr  = $this->expressionParser->parseExpression();
                    $this->stream->expect(Token::VAR_END);
                    $rv[] = new PrintNode($expr, $token->getLine());
                    break;

                case Token::BLOCK_START:

                    $this->stream->next();
                    $token = $this->getCurrentToken();

                    if (null !== $test && call_user_func($test, $token)) {

                        if ($dropNeedle) {
                            $this->stream->next();
                        }

                        if (1 === count($rv)) {
                            return $rv[0];
                        }

                        return new Node($rv, array(), $lineno);
                    }

                    $subparser = isset($this->handlers[$token->getValue()]) ? $this->handlers[$token->getValue()] : null;

                    if (null === $subparser) {

                        if (null !== $test) {

                            $error = sprintf('Unexpected tag name "%s"', $token->getValue());

                            if (is_array($test) && isset($test[0]) && $test[0] instanceof TokenParserInterface) {
                                $error .= sprintf(' (expecting closing tag for the "%s" tag defined near line %s)', $test[0]->getTag(), $lineno);
                            }

                            throw new SyntaxErrorException($error, $token->getLine(), $this->getFilename());
                        }

                        $message = sprintf('Unknown tag name "%s"', $token->getValue());

                        if ($alt = $this->env->computeAlternatives($token->getValue(), array_keys($this->env->getTags()))) {
                            $message = sprintf('%s. Did you mean "%s"', $message, implode('", "', $alt));
                        }

                        throw new SyntaxErrorException($message, $token->getLine(), $this->getFilename());
                    }

                    $this->stream->next();

                    $node = $subparser->parse($token);

                    if (null !== $node) {
                        $rv[] = $node;
                    }

                    break;

                default:
                    throw new SyntaxErrorException('Lexer or parser ended up in unsupported state.', 0, $this->getFilename());
            }
        }

        if (1 === count($rv)) {
            return $rv[0];
        }

        return new Node($rv, array(), $lineno);
    }

    public function addHandler($name, $class)
    {
        $this->handlers[$name] = $class;
    }

    public function getBlockStack()
    {
        return $this->blockStack;
    }

    public function peekBlockStack()
    {
        return $this->blockStack[count($this->blockStack) - 1];
    }

    public function popBlockStack()
    {
        array_pop($this->blockStack);
    }

    public function pushBlockStack($name)
    {
        $this->blockStack[] = $name;
    }

    public function hasBlock($name)
    {
        return isset($this->blocks[$name]);
    }

    public function getBlock($name)
    {
        return $this->blocks[$name];
    }

    public function setBlock($name, BlockNode $value)
    {
        $this->blocks[$name] = new BodyNode(array($value), array(), $value->getLine());
    }

    public function isMainScope()
    {
        return 0 == count($this->blockStack);
    }

    /**
     * Gets the expression parser.
     *
     * @return ExpressionParser The expression parser
     */
    public function getExpressionParser()
    {
        return $this->expressionParser;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * Gets the token stream.
     *
     * @return TokenStream The token stream
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * Gets the current token.
     *
     * @return Token The current token
     */
    public function getCurrentToken()
    {
        return $this->stream->getCurrent();
    }

    protected function filterBodyNodes(Node $node)
    {
        // check that the body does not contain non-empty output nodes
        if (($node instanceof TextNode && !ctype_space($node->getAttribute('data'))) || (!$node instanceof TextNode && !$node instanceof BlockReferenceNode && $node instanceof NodeOutputInterface)) {

            if (false !== strpos((string) $node, chr(0xEF).chr(0xBB).chr(0xBF))) {
                throw new SyntaxErrorException('A template that extends another one cannot have a body but a byte order mark (BOM) has been detected; it must be removed.', $node->getLine(), $this->getFilename());
            }

            throw new SyntaxErrorException('A template that extends another one cannot have a body.', $node->getLine(), $this->getFilename());
        }

        // bypass "set" nodes as they "capture" the output
        if ($node instanceof SetNode) {
            return $node;
        }

        if ($node instanceof NodeOutputInterface) {
            return;
        }

        foreach ($node as $k => $n) {
            if (null !== $n && null === $this->filterBodyNodes($n)) {
                $node->removeNode($k);
            }
        }

        return $node;
    }
}
