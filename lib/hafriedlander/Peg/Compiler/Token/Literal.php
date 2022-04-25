<?php

namespace hafriedlander\Peg\Compiler\Token;

use hafriedlander\Peg\Compiler\PHPBuilder;

class Literal extends Expressionable {

	function __construct($value) {
		parent::__construct('literal', "'" . \substr($value, 1, -1) . "'");
	}

	function matchCode($value) {

		try {
			$evald = eval('return '. $value . ';');
		} catch (\ParseError $e) {
			die("PEG grammar parsing error in >return $value;<': " . $e->getMessage());
		}

		// We inline single-character matches for speed.
		if (!$this->containsExpression($value) && \strlen($evald) === 1) {
			return $this->matchFailConditional('\substr($this->string, $this->pos, 1) === ' . $value,
				PHPBuilder::build()->l(
					'$this->addPos(1);',
					$this->setText($value)
				)
			);
		}

		return parent::matchCode($value);

	}
}
