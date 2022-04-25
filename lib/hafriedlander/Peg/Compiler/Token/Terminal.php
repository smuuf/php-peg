<?php

namespace hafriedlander\Peg\Compiler\Token;

use hafriedlander\Peg\Compiler\Token;

abstract class Terminal extends Token {

	function setText($text) {
		return $this->silent
			? \null
			: '$result["text"] .= ' . $text . ';';
	}

	protected function matchCode($value) {
		return $this->matchFailConditional(
			'($subres = $this->' . $this->type . '(' . $value . ')) !== \false',
			$this->setText('$subres')
		);
	}

}
