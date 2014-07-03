# Razr - The powerful PHP template engine

Razr is a powerful PHP template engine for PHP, whose syntax was inspired by ASP.NET Razor.

## Usage

Render a template:

```php
$razr = new Razr\Engine(new Razr\Loader\StringLoader);
echo $razr->render('Hello @( $name )!', array('name' => 'World'));
```

Render a template file with caching:

```php
$razr = new Razr\Engine(new Razr\Loader\FilesystemLoader(__DIR__), '/path/to/cache');
echo $razr->render('hello.razr.php', array('name' => 'World'));
```

## Syntax

The Razr syntax uses `@` as special character. It is used to indicate a dynamic statement for the template engine. Within the `@()` notation you may use regular PHP. The following statements are supported.

### Echo data

Use the `@()` notation to echo any PHP data with escaping enabled by default.

**Example**

```html
<h1>@( $title )</h1>
@( 23 * 42 )
@( "<Data> is escaped by default." )
```

**Output**

```html
<h1>Some title</h1>
966
&lt;Data&gt; is escaped by default.
```

### Echo raw data

Use the `@raw()` directive to output any PHP data without escaping.

**Example**

```html
@raw("This will <strong>not</strong> be escaped.")
```

**Output**

```html
This will <strong>not</strong> be escaped.
```

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
<h1>@( $title )</h1>
<p>by @( $artist.name ), @( $artist.homepage )</p>
```

**Output**

```html
<h1>I am the walrus</h1>
<p>by The Beatles, http://www.thebeatles.com</p>
```

### Set variable values

**Example**

```html
@set($msg = "Hello World!")
@( $msg )
```

**Output**

```html
Hello World!
```


### Conditional control structures

Use `@if`, `@elseif`, `@else` for conditional control structures. Use any boolean PHP expression.

**Example**

```html
@set($expression = false)
@if( $expression )
    One.
@elseif ( !$expression ) 
    Two.
@else
    Three.
@endif
```

**Output**

```html
Two.
```


### Loops

You can use loop statements like `foreach` and `while`.

```html
@foreach($values as $key => $value)
    <p>@( $key ) - @( $value )</p>
@endforeach

@foreach([1,2,3] as $number)
    <p>@( $number )</p>
@endforeach

@while(true)
    <p>Infinite loop.</p>
@endwhile
```

### Include

Extract reusable pieces of markup to an external file using partials and the `@include` directive. You can pass an array of arguments as a second parameter.

**Example**

```html
<section>@include('partial.razr', ['param' => 'parameter'])</section>
```

`partial.razr`:

```html
<p>Partial with @( $param )<p>
```

**Output**

```html
<section><p>Partial with parameter<p><section>
```

### Extending templates with blocks

Use the `@block` directive to define blocks inside a template. Other template files can extend those files and define their own content for the defined blocks without changing the rest of the markup.

**Example**

```html
@include('child.razr', ['param' => 'parameter'])
```

`parent.razr`:

```html
<h1>Parent template</h1>

@block('contentblock')
    <p>Parent content.</p>
@endblock

<p>Parent content outside of the block.</p>
```

`child.razr`:

```html
@extend('parent.razr')

@block('contentblock')
    <p>You can extend themes and overwrite content inside blocks. Paremeters are available as well: @( $param ).</p>
@endblock

```

**Output**

```html
<h1>Parent template</h1>

<p>You can extend themes and overwrite content inside blocks. Paremeters are available as well: parameter.</p>

<p>Parent content outside of the block.</p>
```
