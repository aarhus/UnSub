<?php

// True
function myFunction($arg1, $arg2=true)
{
}
function myFunction($arg1, $arg2=true)
{
}
function myFunction($arg1, $arg2=true)
{
}

if ($variable === true) { 
}
if ($variable === true) { 
}
if ($variable === true) { 
}


// False
function myFunction($arg1, $arg2=false)
{
}
function myFunction($arg1, $arg2=false)
{
}
function myFunction($arg1, $arg2=false)
{
}

if ($variable === false) { 
}
if ($variable === false) { 
}
if ($variable === false) { 
}


// Null
function myFunction($arg1, $arg2=null)
{
}
function myFunction($arg1, $arg2=null)
{
}
function myFunction($arg1, $arg2=null)
{
}

if ($variable === null) { 
}
if ($variable === null) { 
}
if ($variable === null) { 
}

$x = new stdClass();
$x->null = 7;

use Zend\Log\Writer\Null as NullWriter;
new \Zend\Log\Writer\Null();

namespace False;

class True extends Null implements False
{
}

use True\Something;
use Something\True;
class MyClass
{
    public function myFunction()
    {
        $var = array('foo' => new True());
    }
}

$x = $f?false:true;
$x = $f? false:true;

class MyClass
{
    // Spice things up a little.
    const true = false;
}

var_dump(MyClass::true);

function true()
{
}

// Issue #3332 - ignore type declarations, but not default values.
class TypedThings
{
    const MYCONST = false;

    public int|false $int = false;
    public Type|null $int = new MyObj(null);

    private function typed(int|false $param = null, Type|null $obj = new MyObj(false)) : string|false|null
    {
        if (true === false) {
            return null;
        }
    }
}

$cl = function (int|false $param = null, Type|null $obj = new MyObj(false)) : string|false|null {
};
