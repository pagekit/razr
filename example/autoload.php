<?php

spl_autoload_register(function($class) {

    $class = str_replace('Razr\\', '', $class);
    $path  = realpath(__DIR__.'/../src/').'/';

    if (($namespace = strrpos($class = ltrim($class, '\\'), '\\')) !== false) {
        $path .= strtr(substr($class, 0, ++$namespace), '\\', '/');
    }

    require($path.strtr(substr($class, $namespace), '_', '/').'.php');
});

class Article
{
    const NAME = 'Constant Name';

    protected $title;
    protected $content;
    protected $author;
    protected $date;

    public function __construct($title, $content, $author, $date = null)
    {
        $this->title = $title;
        $this->content = $content;
        $this->author = $author;
        $this->date = $date ?: new \DateTime;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function getDate()
    {
        return $this->date;
    }
}
