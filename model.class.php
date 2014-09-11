<?php
namespace Dbaser;

class Model extends Base {
	
	static protected $primaryKey = null;
	static protected $tableName = null;
	static protected $columns = ["*"]; // override this for speed performance
	
	static function tableName() {
		if (static::$tableName) return static::$tableName;
		return strtolower(get_called_class()) . "s";
	}
	
	static function all() {
		$query = new Query(static::tableName());
		$query->select([static::tableName() => static::$columns]);
		$rows = static::query($query);
		$result = array();
		foreach ($rows as $row) $result[] = new static($row);
		// echo "$query\n";
		return $result;
	}
	
	static function find($id) {
		$query = new Query(static::tableName());
		$query->select([static::tableName() => static::$columns])->where("id = ?", [$id]);
		$result = static::query($query, $query->params);
		if (count($result) > 0)
			return new static(current($result));
		return
			null;
	}
	
	static function findByName($array) {
		$query = new Query(static::tableName());
		$query->select([static::tableName() => static::$columns]);
		foreach ($array as $col => $val) $query->where("$col = ?", [$val]);
		echo $query;
		$result = static::query($query, $query->params);
		foreach ($result as &$obj) $obj = new static($obj);
		return $result;
	}
		
	// ======================
	// = end static methods =
	// ======================
	
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