<?php

/* phpDump.php

PHP Patch

2024-06-19
- script created

*/

/*
PROBLEMS
- cannot handle resource
- object isn't recursive (doesn't print properties storing array or object)
- comma after array key-values?
- comma after object property-values/methods?

TESTS
- Scalar
    - null, bool, int, float, string
- Array
- Object
    - properties only
    - methods only
    - no properties, no methods
    - properties and methods both
    - with modifiers
    - without modifiers
    - defined, undefined properties
    - implicit, explicit type

Map type names (from gettype() to actual types)
- NULL - null
- boolean - bool
- integer - int
- double - float
*/

function phpDump(...$values) {
    foreach ($values as $value) {
        echo phpPrintValue($value, "") . PHP_EOL;
    }
}

function phpPrintValue($value, $indent) {
    switch (gettype($value)) {
        case "NULL": //not "null"!
            return "null";
        case "boolean": //not "bool"!
            return $value ? "true" : "false";
        case "integer": //not "int"!
        case "double": //not "float"!
            return $value;
        case "string":
            return '"' . $value . '"';
        case "array":
            if (
                sizeof($value) === 0
            ) {
                return "{}";
            }
            $print = "{" . PHP_EOL;
            foreach ($value as $k => $v) {
                $print .= $indent . "    " . $k . ": " . phpPrintValue($v, $indent . "    ") . "," . PHP_EOL;
            }
            $print = substr($print, 0, -2); //remove last comma and line break
            $print .= PHP_EOL;
            $print .= $indent . "}";
            return $print;
        case "object":
            //if empty, print "classname {}" only (single line)
            $print = get_class($value) . " {" . PHP_EOL; //object might be empty, add "{}" only
            $print .= phpPrintObject($value, $indent . "    ");
            $print .= $indent . "}";
            return $print;
        case "resource":
            return "resource";
    }
}

function phpPrintObject(
    mixed $input,
    string $indent = "    ",
    bool $withModifiers = false
) {

    /* Caveats
    - does not stop if $input is not defined (should it?)
    - ERROR: if object is instantiated but property remains uninitialized, this function fails
    - WARNING: when accessing the type/class of the property, if it is undefined $property->getType()->getName() should be used (fuck knows why)
    */
    
    $class = get_class($input);
    $reflection = new ReflectionClass($class);

    //check properties of parent traits, interfaces, etc.?
    //get_parent_class($class); //recursive check for parent methods and properties (should be added to results?)
    //get_class_vars(get_class($input)); //class properties (includes: uninitialized)
    //get_object_vars($input); //instance properties (includes: dynamic, excludes: uninitialized)

    //Get properties (do we need type if wasn't specified?)
    $properties = [];
    foreach ($reflection->getProperties() as $property) {

        $name = $property->getName(); //string
        $type = $property->hasType() ? $property->getType()->__toString() : ""; //can handle union types, true only if type is explicit in class (what if type is set to null?)
        $value = $property->isInitialized($input) ?
            phpPrintValue( //print actual value, allow recursion
                $property->getValue($input), //this throws if property is uninitialized
                $indent
            ) : "uninitialized";
        $static = $property->isStatic();
        $visibility = $property->isPrivate() ? "private" : ($property->isProtected() ? "protected" : ($property->isPublic() ? "public" : ""));
        $readonly = $property->isReadonly();

        $properties[] = [
            "name" => $name,
            "type" => $type,
            "value" => $value,
            "static" => $static,
            "visibility" => $visibility,
            "readonly" => $readonly
        ];
    }

    //Get methods
    $methods = "";
    foreach ($reflection->getMethods() as $method) {
        $methods .= $indent;
        $methods .= $method->getName() . "()";

        if (
            $withModifiers
        ) {
            $methods .= " (";
            $methods .= substr(
                ($method->isFinal() ? "final " : "") .
                ($method->isAbstract() ? "abstract " : "") .
                ($method->isPrivate() ? "private " : "") .
                ($method->isProtected() ? "protected " : "") .
                ($method->isPublic() ? "public " : ""),
                0,
                -1
            ); //remove last space
            $methods .= ")";
        }
        $methods .= PHP_EOL;
    }

    //Print
    $print = "";
    
    foreach ($properties as $property) { //if no properties, this is all skipped
        $print .= $indent;
        $print .= $property["name"];
        $print .= $property["type"] !== "" ? (": " . $property["type"]) : ""; //add type (if any)
        $print .= " =";
        $print .= $property["value"] !== "" ? (" " . $property["value"]) : ""; //add value (if any)
        if (
            $withModifiers
        ) {
            $print .= $property["static"] === true ? " (static)" : ""; //add static (if any)
            $print .= $property["visibility"] !== "" ? (" (" . $property["visibility"] . ")") : ""; //add visibility (if any)
            $print .= $property["readonly"] === true ? " (readonly)" : "";
        }
        $print .= PHP_EOL;
    }

    //Add line between properties and methods if both exist (do we need this?)
    if (
        count($properties) > 0 &&
        $methods !== ""
    ) {
        $print .= PHP_EOL;
    }

    if (
        $methods !== ""
    ) {
        $print .= $methods;
    }
    
    return $print;
}