<?php
namespace Dbaser;

class Query extends \Lx\Object {
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
		$ts = array();
		foreach ($tables as $table => $cols) {
			if (!preg_match("/[a-z]+/i", $table) && count($from) == 1)
				$ts[$from[0]] = $cols;
			
			$ts[$table] = $cols;
		}
		if ($distinct)
			$this->stmts['select distinct'] = $ts;
		else
			$this->stmts['select'] = $ts;
		return $this;
	}
	
	function where($where, $params = null) {
		if (!isset($this->stmts['where'])) $this->stmts['where'] = array();
		$this->stmts['where'][] = $where;
		if ($params) $this->params = array_merge($this->params, $params);
		return $this;
	}
	
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
	
	protected function renderStmt($type, $data) {
		$sql = '';
		switch($type) {
			case "select":
			case "select distinct":
				$cols = array();
				foreach ($data as $key => $value) {
					if (preg_match("/[a-z]+/i",$key))
						foreach($value as $col) $cols[] = "$key.$col";
					else
						foreach($value as $col) $cols[] = "$col";
				}
				$sql = strToUpper($type)." ".implode($cols, ", ")." FROM ".implode($this->from, ", ");
				break;
			case "where":
				$sql = strToUpper($type)." ".implode($data, " AND ");
				break;
			case "or":
				$sql = strToUpper($type)." ".implode($data, " OR ");
				break;
			case "and":
				$sql = strToUpper($type)." ".implode($data, " AND ");
				break;
			default:
				$sql = strToUpper($type)." (".implode($data, ",").")";
				break;
		}
		return $sql;
	}
	
	function toString() {
		return "$this";
	}
	
	function __toString() {
		$sql = array();
		foreach ($this->stmts as $type => $data) {
			$sql[] = $this->renderStmt($type, $data);
		}
		return implode($sql, "\n").";";
	}
		
}
