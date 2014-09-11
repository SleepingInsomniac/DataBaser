<?php
namespace Lx;

class Object {
		
	function __get($property)         { return $this->emulate("get", $property); }
	function __set($property, $value) { return $this->emulate("set", $property, $value); }
	
	private function emulate($action, $property, $value = null) {
		$method = $action.ucfirst($property);
		if (method_exists($this, $method)) return $this->$method($value);
		if (property_exists($this, $property)) {
			$ref = new \ReflectionProperty($this, $property);
			if ($ref->isProtected() || $ref->isPrivate()) {
				trigger_error("Tried to access protected or private variable '$property'.");
				return null;
			}
		}
		return $this->synthesize($action, $property, $value);
	}
	private function synthesize($action, $property, $value) {
		if ($action == 'get') return $this->$property;
		if ($action == 'set') $this->$property = $value;
	}
	
	protected
		$className;
	
	function getClassName() {
		return get_class($this);
	}
	
}
