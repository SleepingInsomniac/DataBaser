<?php
namespace Dbaser;

class Model extends Base {
	
	static protected $primaryKey = 'id';
	static protected $tableName = null; // override this if you want a different table nam than the pluralized version of your class name
	static protected $columns = ["*"]; // override this for speed performance
	
	//relations
	static protected $hasMany = [];
	static protected $hasOne = [];
	static protected $manyToMany = [];
	
	function __get($prop) {
		// lazy load relations
		if ( isset(static::$manyToMany[$prop]) && !isset($this->$prop) ) {
			static::manyToMany($prop); // initialize
		}
		return parent::__get($prop);
	}
	// function __set($prop, $value) {
	// 	return parent::__set($prop, $value);
	// }
	
	protected function manyToMany($foreignTable) {
		$className = static::$manyToMany[$foreignTable];
		$joint = static::tableJoin(static::tableName(), $foreignTable);
		$query = new Query($joint);
		$query->select([$className::tableName() => $className::$columns]);
		$query->join("INNER JOIN `{$className::tableName()}` ON `$joint`.`{$className::tableName()}` = `{$className::tableName()}`.`{$className::$primaryKey}`");
		$query->where("`$joint`.`{$this::tableName()}` = ?", [$this->id]);
		$this->$foreignTable = $className::query($query, $query->params);
		foreach ($this->$foreignTable as &$row) $row = new $className($row);
	}
		
	protected static function passiveProperties() {
		return [
			static::$primaryKey,
			'created_at',
			'updated_at',
			'isNew'
		];
	}
	
	static function tableName() {
		if (static::$tableName) return static::$tableName;
		$tname = strtolower( get_called_class() );
		
		// Pluralize
		if (substr($tname, -1) == "y") {
			$tname = substr($tname, 0, strlen($tname) - 1);
			$suffix = "ies";
		} else {
			$suffix = "s";
		}
		
		return $tname . $suffix;
	}
	
	protected static function baseQuery() {
		$tname = static::tableName();
		$query = new Query($tname);
		return $query->select([$tname => static::$columns]);
	}
	
	// =======================
	// = Create a new record =
	// =======================
	static function create($params = array()) {
		$obj = new static($params);
		$obj->save();
		return $obj;
	}
	
	// ===============================
	// = Delete record from database =
	// ===============================
	static function delete($id) {		
		$tname = static::tableName();
		$query = new Query($tname);
		$pk = static::$primaryKey;
		$query->delete($tname)->where("`$tname`.`$pk` = ?", [$id]);
		static::query($query, $query->params);
	}
	
	// ======================
	// = Return all records =
	// ======================
	static function all() {
		$query = static::baseQuery();
		$result = static::query($query, null, function($row) {return new static($row);});
		// foreach ($result as &$row) $row = new static($row);
		return $result;
	}
	
	static function find($id) {
		$query = static::baseQuery()->where("id = ?", [$id]);
		$result = static::query($query, $query->params, function($row) {return new static($row);});
		if (gettype($result) == "array")
			return current($result);
			
		return $result;
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
	
	private
		$isNew = true; // tracks the new status of record
	
	function __construct($vars = array()) {
		foreach ($vars as $property => $value) $this->$property = $value; // load all of the properties
		if (isset($this->{static::$primaryKey})) $this->isNew = false; // update the status of isNew...
	}
	
	// return properties as array
	function toArray() {
		$properties = [];
		foreach ($this as $prop => $value) {
			if (!in_array($prop, static::passiveProperties()))
				$properties[$prop] = $value;
		} 
		return $properties;
	}
	
	function isNew() {
		if (isset($this->{static::$primaryKey})) return false;
		return $this->isNew;
	}
		
	function save() {
		// insert if new
		if ($this->isNew()) return $this->insert();
		$tname = static::tableName();
		$query = new Query($tname);
		$query->update($tname, $this->toArray())->where(static::$primaryKey.' = ?', [$this->{static::$primaryKey}])->limit(1);
		$result = static::query($query, $query->params);
		$this->isNew = false;
		return $this;
	}
	
	// =====================================================
	// = Sync the object properties to the database values =
	// =====================================================
	function sync() {
		if ($this->isNew()) return false;
		$query = static::baseQuery()->where("id = ?", [$this->id]);
		$params = current(static::query($query, $query->params));
		foreach ($params as $key => $param) $this->$key = $param;
	}
	
	protected function insert($sync = true) {
		$tname = static::tableName();
		$properties = $this->toArray();
		if (count($properties) < 1) $properties[static::$primaryKey] = null;
		$query = new Query($tname);
		$query->insert($tname, $properties);
		$this->id = static::query($query, $query->params);
		if ($sync)
			return $this->sync();
		return $this;
	}
	
	function destroy() {
		if ($this->isNew()) return false;
		static::delete($this->id);
		$this->id = null;
	}
	
	function __toString() {
		return print_r($this, true);
	}
	
}