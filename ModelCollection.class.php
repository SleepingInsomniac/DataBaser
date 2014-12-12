<?php
namespace Dbaser;

class ModelCollection extends Base implements \ArrayAccess, \Iterator {
	
	protected
		$owner = null; // the owning database model in the relation.
	
	public
		$collection = array();
	
	function __construct ($array = array(), $owner = null) {
		$this->collection = $array;
		$this->owner = $owner;
	}
	
	protected function addRelation($object) {
		if ( !isset($this->owner) ) return false; // can't add without
		$owner = $this->owner;
		
		$joint = static::tableJoin($owner->tableName, $object->tableName); // get the joint table
		$query = new Query($joint);
		$query->insert($joint, [
			$owner->tableName => $owner->primaryKey,
			$object->tableName => $object->primaryKey
		]);
		
		$key = static::query($query, $query->params);
		$this->collection[] = $object; // append to the array...
		return $key;
	}
	
	protected function removeRelation($object) {
		if ( !isset($this->owner) ) return false; // can't add without
		$owner = $this->owner;
		
		$joint = static::tableJoin($owner->tableName, $object->tableName); // get the joint table
		$query = new Query($joint);
		$query->delete($joint)->where("$object->tableName = ? AND $owner->tableName = ?", [$object->primaryKey, $owner->primaryKey]);
		static::query($query, $query->params);
	}
	
	function findByName($column, $value) {
	    $result = [];
	    foreach($this->collection as $object) {
	        if ($object->$column == $value)
	        $result[] = $object;
	    }
	    return $result;
	}
	
	// ================================
	// = Array Access Implementation: =
	// ================================
	
    function offsetSet    ($offset, $value) {
        $this->addRelation($value);
    }
    
	function offsetUnset  ($offset) {
	    $this->removeRelation($this->collection[$offset]);
	    unset($this->collection[$offset]);
	}
	
	function offsetExists ($offset) {
	    return isset( $this->collection[$offset] );
	}
	
    function offsetGet    ($offset) {
        if (isset( $this->collection[$offset] ))
            return $this->collection[$offset];
    }
	
	// =======================
	// = Iterator Interface: =
	// =======================
	
    function rewind  () {        reset   ( $this->collection ); }
    function current () { return current ( $this->collection ); }
    function key     () { return key     ( $this->collection ); }
    function next    () {        next    ( $this->collection ); }
    function valid   () { return $this->offsetExists($this->key()); }
	
	// ==========================
	// = End Interfaces =
	// ==========================
	
	function length  () { return count      ( $this->collection ); }
	function keys    () { return array_keys ( $this->collection ); }
    
	function push    ($object) { $this->addRelation($object); }
	function pop     ()        { $object = array_pop ( $this->collection ); $this->removeRelation($object); return $object; }
	// function shift   ()        { return array_shift   ( $this->collection ); }
	// function unshift ($object) { return array_unshift ( $this->collection, $object ); }
	
	function delete($offset) { $this->offsetUnset($offset); }
	
	function removeWhere($kay, $doke) {
		// okay-doke.
		foreach ($this->collection as $o) {
			if ($o->$kay == $doke) {
				$this->removeRelation($o);
			}
		}
	}
	
	function __toString () { return print_r( $this->collection, true ); }
	
	// =====================
	// = Getters / Setters =
	// =====================
	
	function getLength () { return count      ( $this->collection ); } // getter
	function getKeys   () { return array_keys ( $this->collection ); } // getter
	
}
