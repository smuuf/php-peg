<?php

namespace hafriedlander\Peg\Compiler\Token;

class Whitespace extends Terminal {

	function __construct($optional) {
		parent::__construct('whitespace', $optional);
	}

	/* Call recursion indirectly */
	function matchCode($value) {
		$code = parent::matchCode('');
		return $value ? $code->replace(['FAIL' => \null]) : $code;
	}

}
