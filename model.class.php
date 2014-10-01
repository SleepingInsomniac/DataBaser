<?php
namespace Dbaser;

class Model extends Base {
	
	static protected $primaryKey = 'id';
	static protected $tableName = null; // override this if you want a different table nam than the pluralized version of your class name
	static protected $columns = ["*"]; // override this for speed performance
	
	//relations: [tableName => className]
	static protected $hasMany = [];
	static protected $manyToMany = [];
			
	protected static function passiveProperties() {
		$passProps = [
			static::$primaryKey,
			'created_at',
			'updated_at',
			'isNew'
		];
		
		// exclude relations as well...
		$passProps = array_merge($passProps, array_keys(static::$manyToMany));
		
		return $passProps;
	}
	
	// if the static var tableName isn't set, figure out based on conventions
	static function tableName() {
		if (static::$tableName) return static::$tableName;
		$tname = strtolower( get_called_class() );
				
		return static::plural($tname);
	}
	
	// generate a query object that has the base columns and table select;
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
	
	// =============================================
	// = Find singular record based on primary key =
	// =============================================
	static function find($id) {
		$query = static::baseQuery()->where("id = ?", [$id]);
		$result = static::query($query, $query->params, function($row) {return new static($row);});
		if (gettype($result) == "array")
			return current($result);
			
		return $result;
	}
	
	// ==============================================
	// = Find where column name (equals/sign) value =
	// ==============================================
	static function findByName($array, $sign = "=") {
		$query = static::baseQuery();
		foreach ($array as $col => $val)
			$query->where("`".static::tableName()."`.$col $sign ?", [$val]);
		$result = static::query($query, $query->params);
		foreach ($result as &$obj) $obj = new static($obj);
		return $result;
	}
	
	// =================
	// = Select random =
	// =================
	static function random($limit = 1) {
		$query = static::baseQuery()->orderBy("RAND()")->limit($limit);
		$result = static::query($query, $query->params, function($row) {return new static($row);});
		if (count($result) == 1)
			return current($result);
		return $result;
	}
	
	// =================================================
	// = Generate an array of objects based on raw SQL =
	// =================================================
	static function fromSQL($sql, $params = array(), $dataTypes = null) {
		return static::query($sql, $params, function($row) { return new static($row); }, $datatypes);
	}
	
	// ==============================
	// = info based static methods: =
	// ==============================
	
	// return the number of records in a table
	static function records() {
		$tname = static::tableName();
		$pk = static::$primaryKey;
		return current(current(static::query("SELECT COUNT(`$tname`.`$pk`) AS count FROM `$tname`;")));
	}
	
	// where column (condition) value
	static function count($condition) {
		$tname = static::tableName();
		$pk = static::$primaryKey;
		return current(current(static::query(
			"SELECT COUNT(`$tname`.`$pk`) AS count FROM `$tname`
			 WHERE $condition;"
		)));
	}
	
	// ===================
	// = get by relation =
	// ===================
	static function byRelation($model, $pkValue = null) {
		// if a model object is passed in, we can extrapolate the values from that.
		if ($model instanceof Dbaser\Model) {
			$model = $model->className;
			$pkValue = $model->{$model::$primaryKey};
		}
		
		// in the case of a many to many relation
		if (in_array($model, static::$manyToMany)) {
			$joint = static::tableJoin(static::tableName(), $model::tableName());			
			$query = new Query($joint); // query from the joint table
			$query->select([static::tableName() => static::$columns]); // select only the columns from this class
			$t = static::tableName();
			$pk = static::$primaryKey;
			$query->join("INNER JOIN `$t` ON `$joint`.`$t` = `$t`.`$pk`"); // join the requested class on the join table based on primary key
			$query->where("`$joint`.`{$model::tableName()}` = ?", [$pkValue]); // limit to the primary key of relation
			// run the query and get back a 2d array
			return $model::query($query, $query->params, function($row) {
				// convert array to object.
				return new static($row);
			});
		}
		
		if (in_array(get_called_class(), $model::$hasMany)) {
			return static::findByName([static::singular($model) => $pkValue]);
		}
		
		// at this point all else has failed.
		return false;
	}
	
	///////////////////// ====================== //////////////////////////
	///////////////////// = end static methods = //////////////////////////
	///////////////////// ====================== //////////////////////////
	
	private
		$isNew = true; // tracks the new status of record
	
	function __construct($vars = array()) {
		foreach ($vars as $property => $value) $this->$property = $value; // load all of the properties
		if (isset($this->{static::$primaryKey})) $this->isNew = false; // update the status of isNew...
	}
	
	// ====================
	// = Relation methods =
	// ====================
	
	// override the get function to catch and initialize relation properties
	function __get($prop) {
		
		// lazy load relations
		if ( isset(static::$manyToMany[$prop]) && !isset($this->$prop) ) $this->manyToMany($prop);
		if ( isset(static::$hasMany[$prop])    && !isset($this->$prop) ) $this->hasMany($prop);
		
		return parent::__get($prop);
	}
		
	protected function hasMany($foreignTable) {
		$className = static::$hasMany[$foreignTable];
		// select from the foreign table
		$query = new Query($foreignTable);
		// column name in foreign table is singular...
		$cname = static::singular(static::tableName());
		// get a 2d array where the primary key matches the value of this objcet's primary key
		$query->select([$foreignTable => $className::$columns])->where("`$foreignTable`.`$cname` = ?", [$this->{static::$primaryKey}]);
		$this->$foreignTable = $className::query($query, $query->params);
		// convert the results to their respective class;
		if ($this->$foreignTable)
			foreach ($this->$foreignTable as &$row) $row = new $className($row);
	}
	
	protected function manyToMany($foreignTable) {
		// get classname defined in the static $manyToMany (k/v) array
		$className = static::$manyToMany[$foreignTable];
		// get the proper order of tables as per naming convention for join table.
		$joint = static::tableJoin(static::tableName(), $foreignTable);
		// query from the joint table
		$query = new Query($joint);
		// select only the columns from requested class
		$query->select([$foreignTable => $className::$columns]);
		// join the requested class on the join table based on primary key
		$query->join("INNER JOIN `{$className::tableName()}` ON `$joint`.`{$className::tableName()}` = `{$className::tableName()}`.`{$className::$primaryKey}`");
		// limit to the primary key of this table
		$query->where("`$joint`.`{$this::tableName()}` = ?", [$this->{static::$primaryKey}]);
		// run the query and get back a 2d array, set to the requested prop
		$this->$foreignTable = $className::query($query, $query->params);
		// convert the arrays to the correct class
		foreach ($this->$foreignTable as &$row) $row = new $className($row);
	}
	
	// ========================
	// = end relation methods =
	// ========================
	
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
		return $result;
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