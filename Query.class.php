<?php
namespace Dbaser;

class Query extends Object {
	
	protected
		$from = array(),
		$stmts = array(),
		$params = array();
	
	function __construct($from) {
		$this->from = (array) $from;
	}
	
	function getParams() {
		return $this->params;
	}
	
	function insert($table, $cols) {
		$qs = array();
		$cs = array();
		$params = array();
		foreach ($cols as $col => $value) {
			$qs[] = '?';
			$params[] = $value;
			$cs[] = $col;
		}
		$sql = "INSERT INTO `$table` (`" . implode($cs, "`, `") . "`) VALUES (" . implode($qs, ",") . ");";
		$this->appendStmt('insert', $sql, $params);
		return $this;
	}
	
	function select($tables, $distinct = false) {
		($distinct) ? $type = "select distinct" : $type = 'select';
		if (gettype($tables) == 'array') {
			$stmts = array();
			foreach ($tables as $table => $cols) {
				$stmts[] = " `$table`.". implode($cols, ", `$table`.");
			}
			$this->appendStmt($type, implode($stmts, ","));
		} else {
			$this->appendStmt($type, $tables);
		}
		
		return $this;
	}
	
	function update($table, $vars) {
		$this->appendStmt('update', "UPDATE `$table` SET");
		$updates = [];
		$vals = [];
		foreach ($vars as $key => $value) {
			$updates[] = "\t`$key` = ?";
			$vals[] = $value;
		}
		$this->appendStmt('update', implode($updates, ",\n"), $vals);
		return $this;
	}
	
	function delete($table) {
		$this->appendStmt('delete', "DELETE FROM `$table`");
		return $this;
	}
	
	protected function appendStmt($type, $sql, $params = null) {
		if (!isset($this->stmts[$type])) $this->stmts[$type] = array();
		$this->stmts[$type][] = $sql;
		if ($params) $this->params = array_merge($this->params, $params);
		return $this;
	}
	
	function join($sql, $params = null) {
		return $this->appendStmt('join', $sql, $params);
	}
	
	// =========
	// = Where =
	// =========
	function where($sql, $params = null) {
		if (gettype($sql) == 'array') {
			$cols = array();
			foreach ($sql as $col => $val) {
				$cols[] = "`$col` = $val";
			}
			$sql = implode($cols, " AND ");
		}
		
		isset($this->stmts['where']) ? $sql = "AND $sql" : $sql = "WHERE $sql";
		return $this->appendStmt('where', $sql, $params);
	}
	function andWhere($sql, $params = null) {
		isset($this->stmts['where']) ? $sql = "AND $sql" : $sql = "WHERE $sql";
		return $this->appendStmt('where', $sql, $params);
	}
	function orWhere ($sql, $params = null) {
		isset($this->stmts['where']) ? $sql = "OR $sql" : $sql = "WHERE $sql";
		return $this->appendStmt('where', $sql, $params);
	}
	
	// =========
	// = Limit =
	// =========
	function limit($count) {
		$this->appendStmt('limit', "LIMIT ?", [$count]);
		return $this;
	}
	
	// ==========
	// = Offset =
	// ==========
	function offset($count) {
		$this->appendStmt('offset', "OFFSET ?", [$count]);
		return $this;
	}
	
	// ===========
	// = OrderBy =
	// ===========
	function orderBy($columns, $direction = "") {
		
		if (gettype($columns) == 'array') {
			$cols = array();
			foreach ($columns as $col => $dir) {
				$cols[] = "`$col` $dir";
			}
			$cols = implode($cols, ",");
		} else {
			$cols = "$columns $direction";
		}
		
		if (!isset($this->stmts['orderBy']))
			$cols = "ORDER BY $cols";
		
		$this->appendStmt('orderBy', "$cols");

		return $this;
	}
	
	// ========================================================================================================
	// = Convert the statements into a string, automatically called when implicit string conversion is called =
	// ========================================================================================================
	function render() {
		$sql = array();
		foreach ($this->stmts as $type => $stmts) {
			switch($type) {
				case "select":
				case "select distinct":
					$sql[] = strToUpper($type) . " " . implode($stmts, "\n") . " FROM ".implode($this->from, ", ");
					break;
				default:
					$sql[] = implode($stmts, "\n");
					break;
			}
		}
		return implode($sql, "\n").";";
	}
		
	function toString() {
		return "$this";
	}
	
	function __toString() {
		return $this->render();
	}
		
}
