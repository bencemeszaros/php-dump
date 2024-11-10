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
    object: object = stdClass {
    }
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
- empty objects still add a line break, it will be removed
- the length of strings and arrays aren't displayed, this is for clarity but might be added in the future

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

`var_dump()` is the only multi-argument solution that works with both scalar and non-scalar values, but its format is absolutely hideous: it uses not just square brackets but also double quotes around keys in arrays/objects, it uses a weird, function call-like syntax for types (which is the same syntax for string and array lengths), it uses curly braces for arrays/objects now, and the worst of it all it even breaks between keys and their values. This format is practically unreadable.

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

Using it with objects is even more weird. I don't even know what this is supposed to be:

```php
var_export(new MyClass("foo", 42));
```

```txt
\MyClass::__set_state(array(
   'foo' => 'foo',
   'bar' => 42,
))
```
