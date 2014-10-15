<?php
namespace Dbaser;

class Model extends Base {
	
	static protected $primaryKey = 'id';
	static protected $tableName = null; // override this if you want a different table nam than the pluralized version of your class name
	static protected $columns = ["*"]; // override this for speed performance
	
	// relations: [propertyName => className]
	static protected $hasOne     = [];
	static protected $hasMany    = [];
	static protected $manyToMany = [];
	
	// when a manyToMany join should return extra columns
	// [propertyName => [..cols...]]
	static protected $richJoin = [];
	
	// propertios to ignore when saving
	static $readOnly = [];
	
	protected function readOnly() {		
		// exclude relations as well...
		// if not mysql with cry about invalid column references
		return array_merge(
			[ // default passive properties
				static::$primaryKey,
				'created_at',
				'updated_at',
				'isNew'
			],
			static::$readOnly, // user defined...
			array_keys(static::$manyToMany)
		);
	}
	
	// if the static var tableName isn't set, figure out based on conventions
	// class names should be singular capitalized camelcase
	// table names should be plural lowercase with underscores
	static function tableName() {
		if (static::$tableName) return static::$tableName;
		$tname = get_called_class();                             // get the class name
		$tname = preg_replace("/(?<!^)([A-Z])/", "_$1", $tname); // convert camelcase to underscores
		$tname = strtolower( $tname );                           // all lowercase
		return static::plural($tname);                           // return the plural version
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
		$class = get_called_class();
		$obj = new $class($params);
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
	static function all($opts = array()) {
		$query = static::baseQuery();
		
		self::setDefaults($opts, [
			"orderBy" => ["pattern" => "/^[a-z_]+$/i"],
			"direction" => [
				'pattern' => "/ASC|DESC/i",
				'value' => 'ASC'
			]
		]);
		
		if (isset($opts['orderBy'])) $query->orderBy($opts['orderBy'], $opts['direction']);
		if (isset($opts['limit']))   $query->limit($opts['limit']);
		if (isset($opts['offset']))  $query->offset($opts['offset']);
		
		$class = get_called_class();
		$result = static::query(
			$query,
			$query->params,
			function($row) use ($class) {
				return new $class($row);
			}
		);
		return $result;
	}
	
	// =============================================
	// = Find singular record based on primary key =
	// =============================================
	static function find($id) {
		
		$query = static::baseQuery()->where("id = ?", [$id]);
		
		$class = get_called_class();
		$result = static::query(
			$query,
			$query->params,
			function($row) use ($class) {
				return new $class($row);
			}
		);
		if (gettype($result) == "array")
			return current($result);
			
		return $result;
	}
	
	// =======================
	// = Search for a record =
	// =======================
	static function search($colvals = array(), $opts = array()) {
		$query = static::baseQuery();
		$tname = static::tableName();
		
		self::setDefaults($opts, [
			"limit" => ["pattern" => "/\d+/"]
		]);
		
		foreach ($colvals as $col => &$val) {
			if (!strstr($val, "%")) // if they didn't define wildcards themselves...
				$val = "%$val%"; // add the wildcards
			$query->where("`$tname`.`$col` LIKE ?", [$val]); // append the filter
		}
		
		if (isset($opts['limit'])) {
			$query->limit($opts['limit']); // if limit is set, limit it.
		}
		
		$class = get_called_class();
		return static::query($query, $query->params, function($row) use ($class) {
			return new $class($row);
		});
		
	}
	
	// ==============================================
	// = Find where column name (equals/sign) value =
	// ==============================================
	static function findByName($array, $options = array()) {
		// set up the default options
		self::setDefaults($options, [
			"sign" => ["value" => "=", "pattern" => "/^(=|<|>|LIKE)$/"],
			"limit" => ["pattern" => "/\d+/"]
		]);
		
		$class = get_called_class();
		$query = static::baseQuery();
		foreach ($array as $col => $val) {
			$query->where("`".static::tableName()."`.$col {$options['sign']} ?", [$val]);
			if (isset($options['limit'])) {
				$query->limit($options['limit']);
			}
		}
		$result = static::query(
			$query,
			$query->params,
			function($row) use ($class) {
				return new $class($row);
			}
		);
		// foreach ($result as &$obj) $obj = new $class($obj);
		return $result;
	}
		
	// =================
	// = Select random =
	// =================
	static function random($limit = 1, $options = array()) {
		$query = static::baseQuery();
		$class = get_called_class();
		
		if (isset($options['where'])) $query->where($options['where']);
		$query->orderBy("RAND()")->limit($limit);

		$result = static::query(
			$query,
			$query->params,
			function($row) use ($class) {
				return new $class($row);
			}
		);
		
		if (count($result) == 1)
			return current($result);
		return $result;
	}
	
	// ============================
	// = Starting with a somthing =
	// ============================
	static function startingWith($prop, $letter) {
		return static::findByName([$prop => "$letter%"], ['sign' => "LIKE"]);
	}
	
	// =================================================
	// = Generate an array of objects based on raw SQL =
	// =================================================
	static function fromSQL($sql, $params = array(), $dataTypes = null) {
		$class = get_called_class();
		return static::query(
			$sql,
			$params,
			function($row) use ($class) {
				return new $class($row);
			},
			$dataTypes
		);
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
			$query
				->join("INNER JOIN `$t` ON `$joint`.`$t` = `$t`.`$pk`") // join the requested class on the join table based on primary key
				->where("`$joint`.`{$model::tableName()}` = ?", [$pkValue]); // limit to the primary key of relation
			// run the query and get back a 2d array
			$class = get_called_class();
			return $model::query(
				$query,
				$query->params,
				function($row) use ($class) {
					return new $class($row);
				}
			);
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
		foreach ($vars as $property => $value) {
			if (!isset(static::$hasOne[$property])) $this->$property = $value; // load all of the properties
		}
		if (isset($this->{static::$primaryKey})) $this->isNew = false; // update the status of isNew...
	}
	
	// ====================
	// = Relation methods =
	// ====================
	
	// override the get function to catch and initialize relation properties
	function __get($prop) {
		
		// if ($value = parent::__get($prop)) return $value;
		
		// lazy load relations
		
		if ( isset(static::$hasOne[$prop])     && !isset($this->$prop) ) $this->hasOne($prop);
		   ( isset(static::$richJoin[$prop]) ) ? $extraCols = static::$richJoin[$prop] : $extraCols = [];
		if ( isset(static::$manyToMany[$prop]) && !isset($this->$prop) ) $this->manyToMany($prop, $extraCols);
		if ( isset(static::$hasMany[$prop])    && !isset($this->$prop) ) $this->hasMany($prop);
		
		return parent::__get($prop);
	}
	
	protected function hasOne($prop) {
		$className = static::$hasOne[$prop];
		$foreignTable = $className::tableName();
		
		$fkColumn = static::singular($className::tableName());
		
		$query = $className::baseQuery();
		$query->join("INNER JOIN `$this->tableName` ON `$this->tableName`.`$fkColumn` = `$foreignTable`.`{$className::$primaryKey}`");
		$query->where("`$this->tableName`.`$this->primaryKeyName` = ?", [$this->primaryKey]);
		$query->limit(1);
				
		$foreignObject = $className::query($query, $query->params, function($row) use ($className) {
			return new $className($row);
		});
		if (empty($foreignObject)) {
			$this->$prop = null;
		} else {
			$this->$prop = current($foreignObject);
		}
	}
		
	protected function hasMany($prop) {
		$className = static::$hasMany[$prop];
		$foreignTable = $className::tableName();
		// select from the foreign table
		$query = new Query($foreignTable);
		// column name in foreign table is singular...
		$cname = static::singular(static::tableName());
		// get a 2d array where the primary key matches the value of this objcet's primary key
		$query
			->select([$foreignTable => $className::$columns])
			->where("`$foreignTable`.`$cname` = ?", [$this->{static::$primaryKey}]);
		$this->$prop = $className::query(
			$query,
			$query->params,
			function($row) use ($className) {
				return new $className($row);
			}
		);
	}
	
	protected function manyToMany($prop, $extraColumns = array()) {
		// get classname defined in the static $manyToMany (k/v) array
		$className = static::$manyToMany[$prop];
		$foreignTable = $className::tableName();
		// get the proper order of tables as per naming convention for join table.
		$joint = static::tableJoin($this->tableName, $foreignTable);
		// query from the joint table
		$query = new Query($joint);
		// select only the columns from requested class
		$select = [$foreignTable => $className::$columns];
		
		if (!empty($extraColumns)) {
			$select[$joint] = $extraColumns; // add in additional columns
			static::$readOnly = array_merge(static::$readOnly, $extraColumns); // don't save these
		}
		
		$query->select($select);
		// join the requested class on the join table based on primary key
		$query->join("INNER JOIN `{$className::tableName()}` ON `$joint`.`{$className::tableName()}` = `{$className::tableName()}`.`{$className::$primaryKey}`");
		// limit to the primary key of this table
		$query->where("`$joint`.`$this->tableName` = ?", [$this->primaryKey]);
		// run the query and get back a 2d array, set to the requested prop
		$this->$prop = new ModelCollection(
			$className::query(
				$query,
				$query->params,
				function ($row) use ($className) {
					return new $className($row);
				}
			),
			$this
		);
	}
	
	// ========================
	// = end relation methods =
	// ========================
	
	// return properties as array
	function toArray() {
		$properties = [];
		foreach ($this as $prop => $value) {
			if (!in_array($prop, $this->readOnly())) {
				if ($value instanceof Model) {
					$properties[$prop] = $value->primaryKey;
				} else {
					$properties[$prop] = $value;
				}
			}
		} 
		return $properties;
	}
	
	function isNew() {
		if (isset($this->{static::$primaryKey})) return false;
		return $this->isNew;
	}
		
	function save($sync = true) {
		// insert if new
		if ($this->isNew())
			return $this->insert($sync);
		$query = new Query($this->tableName);
		$query->update($this->tableName, $this->toArray())->where($this->primaryKeyName.' = ?', [$this->primaryKey])->limit(1);
		$result = static::query($query, $query->params);
		if ($sync) $this->sync();
		return (bool) $result;
	}
	
	// =====================================================
	// = Sync the object properties to the database values =
	// =====================================================
	function sync() {
		if ($this->isNew()) return false;
		$query = static::baseQuery()->where("$this->primaryKeyName = ?", [$this->primaryKey]);
		$params = current(static::query($query, $query->params));
		foreach ($params as $key => $param) $this->$key = $param;
	}
	
	protected function insert($sync = true) {
		$tname = static::tableName();
		$properties = $this->toArray();
		if (count($properties) < 1) $properties[static::$primaryKey] = null;
		$query = new Query($tname);
		$query->insert($tname, $properties);
		$pk = static::query($query, $query->params); // could return error;
		$this->id = $pk;
		if ($sync)
			$this->sync();
		return true;
	}
	
	function destroy() {
		if ($this->isNew()) return false;
		static::delete($this->id);
		$this->id = null;
	}
	
	function __toString() {
		return print_r($this, true);
	}
	
	// =====================================
	// = Query options resolution function =
	// =====================================
	// accepts an array of names,
	// each containing a default value and
	// regex matching pattern for validation;

	protected static function setDefaults(&$options, $defaults) {
		foreach ($defaults as $name => $default) {
			if (isset($options[$name])) {
				if (isset($default['pattern'])) {
					if (!preg_match($default['pattern'], $options[$name]))
						unset($options[$name]);
				}
			} else {
				if (isset($default['value']))
					$options[$name] = $default['value'];
			}
		}
		// return $options;
	}
	
	// ===========
	// = Getters =
	// ===========
	// these are automatically computed when requesting the camel-case property name ( getThingName : $obj->thingName )
	
	function getPrimaryKey     () { return $this->{$this::$primaryKey}; }
	function getPrimaryKeyName () { return $this::$primaryKey; }
	function getTableName      () { return $this->tableName(); }
	
}