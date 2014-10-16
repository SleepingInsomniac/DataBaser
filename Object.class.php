<?php
namespace Dbaser;

class Object {
		
	function __get($property)         { return $this->emulate("get", $property); }
	function __set($property, $value) { return $this->emulate("set", $property, $value); }
	
	private function emulate($action, $property, $value = null) {
		// requested property converted to method (test => getTest())
		$method = $action . ucfirst($property);
		if (method_exists($this, $method)) return $this->$method($value);
		
		if (property_exists($this, $property)) {
			$ref = new \ReflectionProperty($this, $property);
			if ($ref->isProtected() || $ref->isPrivate()) {
				return; // prevent private and protected ivars from getting out.
			}
		}
		
		// catchall method that gets and sets undeclared variables.
		return $this->synthesize($action, $property, $value);
	}
	
	protected function synthesize($action, $property, $value) {
		if ($action == 'get') {
			if (!isset($this->$property)) return;
			return $this->$property;
		}
		if ($action == 'set') {
			$this->$property = $value;
		}
	}
		
	function getClassName() {
		$class = explode('\\', get_class($this));
		return array_pop($class);
	}
	
}
