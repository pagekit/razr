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