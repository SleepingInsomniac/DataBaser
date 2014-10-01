<?php
namespace Dbaser;

class ModelCollection extends \Lx\Object implements \ArrayAccess, \Iterator {
	
	private
		$collection = array();
	
	function __construct() {
		$this->collection = func_get_args();
	}
	
	// ================================
	// = Array Access Implementation: =
	// ================================
		
    function offsetSet    ($offset, $value) { is_null($offset) ? $this->collection[] = $value : $this->collection[$offset] = $value; }
	function offsetExists ($offset) { return isset($this->collection[$offset]); }
	function offsetUnset  ($offset) { unset($this->collection[$offset]); }
    function offsetGet    ($offset) { if (isset($this->collection[$offset])) return $this->collection[$offset]; }
	
	// =======================
	// = Iterator Interface: =
	// =======================
	
    function rewind  () { reset($this->collection); }
    function current () { return current($this->collection); }
    function key     () { return key($this->collection); }
    function next    () { next($this->collection); }
    function valid   () { return $this->offsetExists($this->key()); }
	
	// ==========================
	// = End Interfaces =
	// ==========================
	
	function length    () { return count($this->collection); }
	function getLength () { return count($this->collection); } // getter
	function keys      () { return array_keys($this->collection); }
	function getKeys   () { return array_keys($this->collection); } // getter
    
	function push    ($object) { return array_push($this->collection, $object); }
	function unshift ($object) { return array_unshift($this->collection, $object); }
	function pop     () { return array_pop($this->collection); }
	function shift   () { return array_shift($this->collection); }
	
	function __toString() {
		return print_r($this->collection, true);
	}
}