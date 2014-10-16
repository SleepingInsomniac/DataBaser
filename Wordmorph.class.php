<?php

namespace Dbaser;

class Wordmorph {
	
	static function plural($word) {
		
		$word = preg_replace("/y$/i", "ie", $word) . "s"; // substitute y for ie and append s
		return $word;
		
	}
	
	static function singular($ward) {
		
		$word = preg_replace("/ies$/i", "y", $word);
		$word = preg_replace("/s$/i", "", $word);
		return $word;
		
	}
	
}