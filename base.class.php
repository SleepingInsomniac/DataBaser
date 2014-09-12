<?php
namespace Dbaser;

class Base extends \Lx\Object{
	protected static $connection;
	
	static function setConnection($value) {
		if (!($value instanceof \Mysqli))
			throw new \Exception("Connection must be instance of Mysqli");
		if (!static::$connection)
			static::$connection = $value;
	}
	
	// ==================================================
	// = Resolve the datatypes for the DB automatically =
	// ==================================================
	protected static function dataTypes($params) {
		$types = "";
		foreach ($params as $value) {
			switch (gettype($value)) {
				case "integer": $vt = 'i'; break;
				case "float":   $vt = 'f'; break;
				case 'double':  $vt = 'd'; break;
				case "string":
				default:        $vt = 's'; break;
			}
			$types .= $vt;
		}
		return $types;
	}
	
	// ==============================================================
	// = This function talks to the mysqli object and gets the data =
	// ==============================================================
	// static function queryRaw() { return call_user_func_array([get_called_class(), 'query'], func_get_args()); }
	protected static function query($sql, $params = null, $datatypes = null) {
		// if datatypes aren't provided, figure them out.
		if ($params && !$datatypes) $datatypes = static::dataTypes($params);
		
		$stmt = self::$connection->prepare($sql);
		if (!$stmt) return self::$connection->error;
		if ($params) {
			$temp_params = array($datatypes); // add the datatypes
			foreach($params as &$value) {
				if (gettype($value) == 'array') {
					var_dump($sql);
					var_dump($params);
				}
				$temp_params[] = &$value; // must be a reference
			}
			call_user_func_array(array($stmt, "bind_param"), $temp_params);
		}
		
		$stmt->execute();
		$stmt->store_result();
		if (self::$connection->error) return self::$connection->error;
		$md = $stmt->result_metadata();

		$temp_result = array();
		$result = array();
		$return = array();

		if ($md) {
			foreach($md->fetch_fields() as $field) {
				$temp_result[$field->name] = null;
				$result[$field->name] = &$temp_result[$field->name];
			}
			call_user_func_array(array($stmt, "bind_result"), $temp_result);
			while($stmt->fetch()) {
				$row = array();
				// to avoid copying the pointers and getting the last result for all rows
				foreach ($result as $key => $value) $row[$key] = $value;
				$return[] = $row;
			}
		}
		
		if (self::$connection->insert_id || preg_match("/(^| )insert /i", $sql))
			return self::$connection->insert_id;

		$stmt->free_result();

		return $return;
		
	}
	
}