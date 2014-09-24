<?php
namespace Dbaser;

class Query extends \Lx\Object {
	
	static function insert($table, $cols) {
		$qs = array();
		$cs = array();
		$params = array();
		foreach ($cols as $col => $value) {
			$qs[] = '?';
			$params[] = $value;
			$cs[] = $col;
		}
		return "INSERT INTO `$table` (`" . implode($cs, "`, `") . "`) VALUES (" . implode($qs, ",") . ");";
	}
	
	// ==============
	// = end static =
	// ==============
	
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
	
	function select($tables, $distinct = false) {
		($distinct) ? $type = "select distinct" : $type = 'select';
		foreach ($tables as $table => $cols) {
			$stmt = strToUpper($type) ." `$table`.". implode($cols, ", `$table`.");
			$this->appendStmt($type, $stmt);
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
	
	protected function appendStmt($type, $sql, $params = null) {
		if (!isset($this->stmts[$type])) $this->stmts[$type] = array();
		$this->stmts[$type][] = $sql;
		if ($params) $this->params = array_merge($this->params, $params);
		return $this;
	}
	
	function join($sql, $params = null) {
		return $this->appendStmt('join', $sql, $params);
	}
	
	function where($sql, $params = null) {
		isset($this->stmts['where']) ? $sql = "AND $sql" : $sql = "WHERE $sql";
		return $this->appendStmt('where', $sql, $params);
	}
		
	function andWhere($sql, $params = null) { return $this->appendStmt('where', "AND $sql", $params); }
	function orWhere ($sql, $params = null) { return $this->appendStmt('where', "OR $sql", $params); }
	
	function limit($count) {
		$this->appendStmt('limit', "LIMIT ?", [$count]);
		return $this;
	}
	
	function orderBy($column, $direction = "") {
				
		if (isset($this->stmts['orderBy']) == 0)
			$column = "ORDER BY $column";
		
		$this->appendStmt('orderBy', "$column $direction");

		return $this;
	}
	
	function render() {
		$sql = array();
		foreach ($this->stmts as $type => $stmts) {
			switch($type) {
				case "select":
				case "select distinct":
					$sql[] = implode($stmts, "\n") . " FROM ".implode($this->from, ", ");
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
