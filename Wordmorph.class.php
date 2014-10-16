<?php

namespace Dbaser;

class Wordmorph {
	
	static $irregularRules = [
	
	    // Words ending in with a consonant and `o`.
	    ['volcano', 'volcanoes'],
	    ['tornado', 'tornadoes'],
	    ['torpedo', 'torpedoes'],
	    // Ends with `us`.
	    ['genus',  'genera'],
	    ['viscus', 'viscera'],
	    // Ends with `ma`.
	    ['stigma',   'stigmata'],
	    ['stoma',    'stomata'],
	    ['dogma',    'dogmata'],
	    ['lemma',    'lemmata'],
	    ['schema',   'schemata'],
	    ['anathema', 'anathemata'],
	    // Other irregular rules.
	    ['ox',      'oxen'],
	    ['axe',     'axes'],
	    ['die',     'dice'],
	    ['yes',     'yeses'],
	    ['foot',    'feet'],
	    ['eave',    'eaves'],
	    ['beau',    'beaus'],
	    ['goose',   'geese'],
	    ['tooth',   'teeth'],
	    ['quiz',    'quizzes'],
	    ['human',   'humans'],
	    ['proof',   'proofs'],
	    ['carve',   'carves'],
	    ['valve',   'valves'],
	    ['thief',   'thieves'],
	    ['genie',   'genies'],
	    ['groove',  'grooves'],
	    ['pickaxe', 'pickaxes'],
	    ['whiskey', 'whiskies']
	
	];
	
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