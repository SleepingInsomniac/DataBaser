<?php

require 'Inflector.class.php';

class InflectorTest extends PHPUnit_Framework_TestCase {
	
	static $strings = [
		"test"           => "tests",
		"word"           => "words",
		"baby"           => "babies",
		"gate"           => "gates",
		"gatedCommunity" => "gatedCommunities",
		"toy"            => "toys",
		"oat"            => "oats",
		"money"          => "moneys", // This is questionable
		// irregulars
		"person"         => "people",
		"knife"          => "knives",
		"loaf"           => "loaves",
		"thesis"         => "theses"
	];
	
	public function testReturnsPluralOfStrings() {
		
		foreach (self::$strings as $singular => $plural) {
			$this->assertEquals($plural, Dbaser\Inflector::plural($singular));
		}
		
	}
	
	public function testReturnsSingularOfStrings() {
		
		foreach (self::$strings as $singular => $plural) {
			$this->assertEquals($singular, Dbaser\Inflector::singular($plural));
		}
		
	}
	
}
