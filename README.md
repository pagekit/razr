# Razr - The powerful PHP template engine

Razr is a powerful PHP template engine for PHP, whose syntax was inspired by ASP.NET Razor.

## Usage

Render a template:

```php
$razr = new Razr\Environment(new Razr\Loader\StringLoader);
echo $razr->render('Hello @name!', array('name' => 'World'));
```

Render a template file with caching:

```php
$razr = new Razr\Environment(new Razr\Loader\FilesystemLoader(__DIR__), array('cache' => '/path/to/cache'));
echo $razr->render('hello.razr.php', array('name' => 'World'));
```

## Syntax

The Razr syntax uses `@` as special character. It is used to indicate a dynamic statement for the template engine. The following statements are supported.

### Variables

You can access single variables and nested variables in arrays/objects using the following dot `.` notation.

```php
array(
    'title' => 'I am the walrus',
    'artist' => array(
        'name' => 'The Beatles',
        'homepage' => 'http://www.thebeatles.com',
    )
)
```

**Example**

```html
<h1>@title</h1>
<p>by @artist.name, @artist.homepage</p>
```

**Output**

```html
<h1>I am the walrus</h1>
<p>by The Beatles, http://www.thebeatles.com</p>
```

### Filters

You can use filters to modify the output using the following pipe `|` notation.

**Example**

```html
<h1>@title|upper</h1>
<p>by @author.name|lower</p>
```

**Output**

```html
<h1>I AM THE WALRUS</h1>
<p>by the beatles</p>
```

### Functions

You can call functions.

**Example**

```html
@max([1, 3, 2]) # prints 3
@min([1, 3, 2]) # prints 1
```

### Expressions

You can evaluate expressions by using the following syntax `@( ... )`.

```html
@( 1 + 1 ) # prints 2
```

### Conditionals

You can use conditional statements like `if`, `elseif`, `else` and `endif`.

```html
@if(true)
    <p>Is true.</p>
@elseif(1 + 1 == 2)
    <p>Is two.</p>
@else
    <p>Is something else.</p>
@endif
```

### Loops

You can use loop statements like `foreach` and `while`.

```html
@foreach(values as key => value)
    <p>@key - @value</p>
@endforeach

@foreach([1,2,3] as number)
    <p>@number</p>
@endforeach

@while(true)
    <p>Infinite loop.</p>
@endwhile
```

## Credits

The template engine code is based on the Twig template engine by the Twig Team (BSD License).

## Copyright and license

Copyright 2014 Pagekit, [MIT license](LICENSE).
