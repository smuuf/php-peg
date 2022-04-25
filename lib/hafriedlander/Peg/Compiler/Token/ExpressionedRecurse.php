<?php

namespace hafriedlander\Peg\Compiler\Token;

class ExpressionedRecurse extends Recurse {

	function matchFunction($value) {
		return '$this->expression($result, $stack, \'' . $value . '\')';
	}

}
