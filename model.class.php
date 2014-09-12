<?php
namespace Dbaser;

class Model extends Base {
	
	static protected $primaryKey = null;
	static protected $tableName = null;
	static protected $columns = ["*"]; // override this for speed performance
	static protected $hasMany = [];
	static protected $belongsTo = null;
	static protected $hasOne = [];
	
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
		
	// ======================
	// = end static methods =
	// ======================
	
	function properties() {
		$reflect = new \ReflectionClass($this);
		$props   = $reflect->getProperties();
		foreach ($props as $prop) {
			// find out how to itterate over runtime defined properties
			$prop = $prop->getName();
			echo $prop."\n";
			// yield $this->$prop;
		}
	}
	
	protected $query = null;
	
	function __construct($vars = array()) {
		foreach ($vars as $property => $value) {
			$this->$property = $value;
		}
	}
	
	function getQuery() {
		return $this->query;
	}
	
	function save() {
		if ($this->query) {
			
		}
	}
	
}