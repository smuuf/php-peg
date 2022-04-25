<?php

namespace hafriedlander\Peg\Compiler\Token;

abstract class Expressionable extends Terminal {

	const EXPR_REGEX = '/ \$(\w+) | { \$(\w+) } /x';

	function containsExpression($value) {
		return \preg_match(self::EXPR_REGEX, $value);
	}

	function expressionReplace($matches) {
		return '\'.$this->expression($result, $stack, \'' . ($matches[1] ?: $matches[2]) . "').'";
	}

	function matchCode($value) {

		$value = \preg_replace_callback(
			self::EXPR_REGEX,
			[$this, 'expressionReplace'],
			$value
		);

		return parent::matchCode($value);

	}
}
