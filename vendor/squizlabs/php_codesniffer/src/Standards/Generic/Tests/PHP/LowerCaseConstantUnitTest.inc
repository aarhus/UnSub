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
$x->NULL = 7;

use Zend\Log\Writer\NULL as NullWriter;
new \Zend\Log\Writer\NULL();

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
    const TRUE = false;
}

var_dump(MyClass::TRUE);

function tRUE()
{
}

$input->getFilterChain()->attachByName('Null', ['type' => Null::TYPE_STRING]);

// Issue #3332 - ignore type declarations, but not default values.
class TypedThings
{
    const MYCONST = false;

    public int|FALSE $int = false;
    public Type|NULL $int = new MyObj(null);

    private function typed(int|FALSE $param = null, Type|NULL $obj = new MyObj(false)) : string|FALSE|NULL
    {
        if (true === false) {
            return null;
        }
    }
}

$cl = function (int|FALSE $param = null, Type|NULL $obj = new MyObj(false)) : string|FALSE|NULL {
};
