<?php

namespace hafriedlander\Peg\Compiler\Token;

class Regex extends Expressionable {

	public static function escape($rx) {
		$rx = \str_replace("'", "\\'", $rx);
		$rx = \str_replace('\\\\', '\\\\\\\\', $rx);
		return $rx;
	}

	function __construct($value) {
		parent::__construct('rx', self::escape($value));
	}

	function matchCode($value) {
		return parent::matchCode("'{$value}'");
	}

}
