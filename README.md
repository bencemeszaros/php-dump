# phpDump

`phpDump` is a custom and lightweight alternative to the extremely cringe standard print tools in PHP. Use this function to print any number of values with any type in a simple and concise format that always ends with a line break.

No excessive operators, symbols, punctuation, line breaks and whitespace and no dependencies.

## Example usage

A call like this:

```php
phpDump(null, true, false, 42, 3.14, "foo", [], new MyClass, fopen(__FILE__, "r"));
```

Prints this:

```txt
null
true
false
42
3.14
"foo"
[]
MyClass {
    foo: string = uninitialized
    null: null = null
    true: true = true
    false: false = false
    int: int = 42
    float: float = 3.14
    string: string = "foo"
    array: array = []
    object: object = stdClass {}
    resource = resource

    __construct()
    myFunction()
}
resource
```

## Features

- prints any number of values
- prints any type: `null`, `bool`, `int`, `float`, `string`, `array`, `object`, `resource`
- prints nested structures
- prints uninitialized properties
- prints union type declarations
- prints class methods
- always ends with a line break

## Upcoming features and fixes
- empty objects still add a line break, it will be removed (fixed)
- the length of strings and arrays aren't displayed, this is for clarity but might be added in the future
- circular references might break this print (needs investigation)

## Requirements

This code was designed to work with PHP 8 and later, but you can adapt it for older versions since it barely uses any advanced features.

## Motivation
There are a number of standard print tools in PHP but they all suffer from major issues:

### print

`print` is probably the worst: it can hande only a single value, it can only handle scalar types and even then `null` and `false` are both converted to the empty string (so no output whatsoever), and `true` is converted to `1` (so can be mistaken for a number/integer).

```php
print null; //prints nothing
print true; //1
print false; //prints nothing
```

### echo

`echo` is a bit better as it can handle multiple values, but it can still only handle scalar types, and has the same issues with `null`, `true` and `false`.

```php
echo null, true, false; //1
```

### print_r()

`print_r()` can finally handle both scalar and non-scalar values, but it can handle only a single value again, and it still has the same issues with `null`, `true` and `false`. For some weird reason, it also uses square brackets around keys in arrays/objects, it uses three line breaks even for an empty array/object and even adds an extra after them. For a single scalar value it doesn't add a trailing line break.

```php
print_r([["foo" => "bar"], []]);
```

```txt
Array
(
    [0] => Array
        (
            [foo] => bar
        )

    [1] => Array
        (
        )

)
```

### var_dump()

`var_dump()` is the only multi-argument solution that works with both scalar and non-scalar values, but its format is absolutely hideous: it uses not just square brackets but also double quotes around keys in arrays/objects, it uses a weird, function call-like syntax for types (which is the same syntax for string and array lengths), it uses curly braces for arrays/objects now, and worst of all it even breaks between keys and their values. This format is practically unreadable.

```php
var_dump([["foo" => "bar"], []]);
```

```txt
array(2) {
  [0]=>
  array(1) {
    ["foo"]=>
    string(3) "bar"
  }
  [1]=>
  array(0) {
  }
}
```

### var_export()

`var_export` can only handle a single scalar or non-scalar value, it uses only single quotes around array/object keys, it doesn't show any type or length information and it uses much less line breaks, but this format is ugly as hell too: it adds a trailing comma both in arrays and objects, it doesn't add a final line break, not even after arrays/objects, but it does add a line break before an array/object if they are property values and it adds a line break even inside empty arrays/objects.

```php
var_export([["foo" => "bar"], []]);
```

```txt
array (
  0 =>
  array (
    'foo' => 'bar',
  ),
  1 =>
  array (
  ),
)
```

Using it with objects is even weirder. I don't even know what this is supposed to be:

```php
var_export(new MyClass("foo", 42));
```

```txt
\MyClass::__set_state(array(
   'foo' => 'foo',
   'bar' => 42,
))
```

## What didn't work

After dismissing all built-in print formats, the next idea was to use an existing syntax, preferably something that could be copy–pasted back into PHP, before inventing something new.

### Printing PHP syntax

One idea was to print PHP syntax right away. Whilst it sounds good for scalar values, PHP "arrays" are unnecessarily verbose with string keys:

```php
[
    "foo" => true,
    "bar" => 42,
    "baz" => "hey"
];
```

```txt
[
    foo: true,
    bar: 42,
    baz: "hey"
]
```

Objects are even worse because this would essentially print class definitions not instances, which would lead to several problems:
- PHP class definitions cannot be nested while instances can be,
- Type declarations come before the property names, which is less readable and less intuitive, and
- PHP class definitions are unnecessarily verbose, too:

```php
class MyClass {

    public string $foo;

    public function __construct(
        public null $null = null,
        public true $true = true,
        public false $false = false,
        public int $int = 42,
        public float $float = 3.14,
        public string $string = "foo",
        public array $array = [],
        public object $object = new stdClass,
        public $resource = null
    ) {
        $this->resource = fopen(__FILE__, "r");
    }

    public function myFunction() {}
}
```

```txt
MyClass {
    foo: string = uninitialized
    null: null = null
    true: true = true
    false: false = false
    int: int = 42
    float: float = 3.14
    string: string = "foo"
    array: array = []
    object: object = stdClass {}
    resource = resource
    __construct()
    myFunction()
}
```

And whilst copy-pasting sounds like a useful feature to have, it is actually pretty rare that it comes up in practice, if ever.

### Printing JSON syntax

Another idea was to utilize JSON, but that is even more chaotic:
- JSON can only support six basic types (null, bool, number, string, array, object) and everything in PHP has to be mapped to one of them, which is already a huge mess (PHP "array" might map to a JSON array or a JSON object, etc.),
- It cannot even map many things we need (various special values like INF, NAN or even resources, custom type declarations, uninitialized, private or protected members, invalid UTF-8, etc.),
- It can present something entirely different (custom JsonSerializable implementation), and
- It is verbose, too (quotes, escape sequences everywhere).

But if you really want to format your output as JSON, you can already do so without a custom library:

```php
echo json_encode($data, JSON_PRETTY_PRINT);
```