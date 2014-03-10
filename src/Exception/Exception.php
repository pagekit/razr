<?php

namespace Razr\Exception;

use Razr\Template;

/**
 * @copyright Copyright (c) 2009-2014 by the Twig Team
 */
class Exception extends \Exception implements ExceptionInterface
{
    protected $lineno;
    protected $filename;
    protected $rawMessage;

    /**
     * Constructor.
     *
     * Set both the line number and the filename to false to
     * disable automatic guessing of the original template name
     * and line number.
     *
     * Set the line number to -1 to enable its automatic guessing.
     * Set the filename to null to enable its automatic guessing.
     *
     * By default, automatic guessing is enabled.
     *
     * @param string     $message  The error message
     * @param integer    $lineno   The template line where the error occurred
     * @param string     $filename The template file name where the error occurred
     * @param \Exception $previous The previous exception
     */
    public function __construct($message, $lineno = -1, $filename = null, \Exception $previous = null)
    {
        parent::__construct('', 0, $previous);

        $this->lineno = $lineno;
        $this->filename = $filename;

        if (-1 === $this->lineno || null === $this->filename) {
            $this->guessTemplateInfo();
        }

        $this->rawMessage = $message;

        $this->updateRepr();
    }

    /**
     * Gets the raw message.
     *
     * @return string The raw message
     */
    public function getRawMessage()
    {
        return $this->rawMessage;
    }

    /**
     * Gets the filename where the error occurred.
     *
     * @return string The filename
     */
    public function getTemplateFile()
    {
        return $this->filename;
    }

    /**
     * Sets the filename where the error occurred.
     *
     * @param string $filename The filename
     */
    public function setTemplateFile($filename)
    {
        $this->filename = $filename;

        $this->updateRepr();
    }

    /**
     * Gets the template line where the error occurred.
     *
     * @return integer The template line
     */
    public function getTemplateLine()
    {
        return $this->lineno;
    }

    /**
     * Sets the template line where the error occurred.
     *
     * @param integer $lineno The template line
     */
    public function setTemplateLine($lineno)
    {
        $this->lineno = $lineno;

        $this->updateRepr();
    }

    public function guess()
    {
        $this->guessTemplateInfo();
        $this->updateRepr();
    }

    protected function updateRepr()
    {
        $this->message = $this->rawMessage;

        $dot = false;
        if ('.' === substr($this->message, -1)) {
            $this->message = substr($this->message, 0, -1);
            $dot = true;
        }

        if ($this->filename) {
            if (is_string($this->filename) || (is_object($this->filename) && method_exists($this->filename, '__toString'))) {
                $filename = sprintf('"%s"', $this->filename);
            } else {
                $filename = json_encode($this->filename);
            }
            $this->message .= sprintf(' in %s', $filename);
        }

        if ($this->lineno && $this->lineno >= 0) {
            $this->message .= sprintf(' at line %d', $this->lineno);
        }

        if ($dot) {
            $this->message .= '.';
        }
    }

    protected function guessTemplateInfo()
    {
        $template = null;
        $templateClass = null;

        if (version_compare(phpversion(), '5.3.6', '>=')) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT);
        } else {
            $backtrace = debug_backtrace();
        }

        foreach ($backtrace as $trace) {
            if (isset($trace['object']) && $trace['object'] instanceof Template && 'Razr\Template' !== get_class($trace['object'])) {
                $currentClass = get_class($trace['object']);
                $isEmbedContainer = 0 === strpos($templateClass, $currentClass);
                if (null === $this->filename || ($this->filename == $trace['object']->getTemplateName() && !$isEmbedContainer)) {
                    $template = $trace['object'];
                    $templateClass = get_class($trace['object']);
                }
            }
        }

        // update template filename
        if (null !== $template && null === $this->filename) {
            $this->filename = $template->getTemplateName();
        }

        if (null === $template || $this->lineno > -1) {
            return;
        }

        $r = new \ReflectionObject($template);
        $file = $r->getFileName();

        // hhvm has a bug where eval'ed files comes out as the current directory
        if (is_dir($file)) {
            $file = '';
        }

        $exceptions = array($e = $this);
        while ($e = $e->getPrevious()) {
            $exceptions[] = $e;
        }

        while ($e = array_pop($exceptions)) {
            $traces = $e->getTrace();
            while ($trace = array_shift($traces)) {
                if (!isset($trace['file']) || !isset($trace['line']) || $file != $trace['file']) {
                    continue;
                }

                foreach ($template->getDebugInfo() as $codeLine => $templateLine) {
                    if ($codeLine <= $trace['line']) {
                        // update template line
                        $this->lineno = $templateLine;

                        return;
                    }
                }
            }
        }
    }
}
