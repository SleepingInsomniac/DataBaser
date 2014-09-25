<?php
namespace Dbaser;

class Model extends Base {
	
	static protected $primaryKey = 'id';
	static protected $tableName = null;
	static protected $columns = ["*"]; // override this for speed performance
	static protected $hasMany = [];
	static protected $belongsTo = null;
	static protected $hasOne = [];
	
	private $isNew = true;
	
	static function passiveProperties() {
		return [
			static::$primaryKey,
			'created_at',
			'updated_at',
			'isNew'
		];
	}
	
	static function tableName() {
		if (static::$tableName) return static::$tableName;
		return strtolower( get_called_class() ) . "s";
	}
	
	protected static function baseQuery() {
		$tname = static::tableName();
		$query = new Query($tname);
		return $query->select([$tname => static::$columns]);
	}
	
	static function all() {
		$query = static::baseQuery();
		$result = static::query($query);
		foreach ($result as &$row) $row = new static($row);
		return $result;
	}
	
	static function find($id) {
		$query = static::baseQuery()->where("id = ?", [$id]);
		$result = static::query($query, $query->params);
		if (count($result) > 0)
			return new static(current($result));
			
		return null;
	}
	
	static function findByName($array) {
		$query = static::baseQuery();
		foreach ($array as $col => $val)
			$query->where("`".static::tableName()."`.$col = ?", [$val]);
		$result = static::query($query, $query->params);
		foreach ($result as &$obj) $obj = new static($obj);
		return $result;
	}
	
	static function random() {
		$query = static::baseQuery()->orderBy("RAND()")->limit(1);
		$result = static::query($query, $query->params);
		if (count($result) > 0)
			return new static(current($result));		
	}
	
	// ======================
	// = end static methods =
	// ======================
		
	function __construct($vars = array()) {
		foreach ($vars as $property => $value) {
			$this->$property = $value;
		}
		if (isset($this->{static::$primaryKey})) $this->isNew = false;
	}
	
	// getter for $->propsArray;
	function getPropsArray() {
		// return properties as array
		$properties = [];
		foreach ($this as $prop => $value) {
			if (!in_array($prop, static::passiveProperties()))
				$properties[$prop] = $value;
		} 
		return $properties;
	}
	
	function isNew() {
		if (!isset($this->{static::$primaryKey})) return true;
		return $this->isNew;
	}
		
	function save() {
		// if there's no pimary key, this is a new record.
		if ($this->isNew()) return $this->insert();
		$tname = static::tableName();
		$query = new Query($tname);
		$query->update($tname, $this->propsArray)->where(static::$primaryKey.' = ?', [$this->{static::$primaryKey}])->limit(1);
		$result = static::query($query, $query->params);
		return $result;
	}
	
	function insert() {
		$tname = static::tableName();
		$properties = $this->propsArray;
		if (count($properties) < 1) $properties[static::$primaryKey] = null;
		$query = new Query($tname);
		$query->insert($tname, $properties);
		return static::query($query, $query->params);
	}
	
	
	function __toString() {
		return print_r($this, true);
	}
	
}