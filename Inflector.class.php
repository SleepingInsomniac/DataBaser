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
		
		"/y$/i" => "ies", // ends in y
		"/(ss)/i" => "$1es", // ends in ss
		"/([^aeious])$/i" => "$1s" // ends in non-vowel
		
	];
	
	static function plural($word) {
		return static::replaceWord($word, static::$pluralRules);
	}
	
	
	static $singularRules = [
		
		"/ies$/i" => "y",
		"/(ss)es$/i" => "$1",
		"/s$/i" => ""
		
	];
	
	static function singular($word) {
		return static::replaceWord($word, static::$singularRules);
	}
	
}