<?php

namespace Dbaser;

class Inflector {
	
	static function replaceWord($word, $rules) {
		
		foreach ($rules as $rx => $rp) {
			$try = preg_replace($rx, $rp, $word);
			if ($try != $word) return $try;
		}
		
	}
	
	
	static $pluralRules = [
		
		// irregulars
		"/person/i" => "people",
		
		"/([^aoieu])y$/i" => "$1ies",    // ends in y
		"/(ss|sh|ch|dg)e?$/i" => "$1es", // ends in something requiring an es
		"/(.)$/i" => "$1s"               // append s
		
	];
	
	static function plural($word) {
		return static::replaceWord($word, static::$pluralRules);
	}
	
	
	static $singularRules = [
		
		// irregulars
		"/people/i" => "person",
		
		"/ies$/i" => "y",              // convert ies to y
		"/(ss|sh|ch|dg)es$/i" => "$1", // convert back
		"/s$/i" => ""                  // trim off trailling s
		
	];
	
	static function singular($word) {
		return static::replaceWord($word, static::$singularRules);
	}
	
}