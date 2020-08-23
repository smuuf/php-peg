<?php

namespace hafriedlander\Peg\Compiler\Token;

abstract class Expressionable extends Terminal {

	const EXPR_REGEX = '/ \$(\w+) | { \$(\w+) } /x';

	function contains_expression( $value ){
		return \preg_match(self::EXPR_REGEX, $value);
	}

	function expression_replace($matches) {
		return '\'.$this->expression($result, $stack, \'' . ($matches[1] ?: $matches[2]) . "').'";
	}

	function match_code( $value ) {
		$value = \preg_replace_callback(self::EXPR_REGEX, [$this, 'expression_replace'], $value);
		return parent::match_code($value);
	}
}
