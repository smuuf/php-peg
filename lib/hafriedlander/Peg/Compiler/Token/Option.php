<?php

namespace hafriedlander\Peg\Compiler\Token;

use hafriedlander\Peg\Compiler\Token;
use hafriedlander\Peg\Compiler\PHPBuilder;

class Option extends Token {

	function __construct($opt1, $opt2) {
		parent::__construct('option', [$opt1, $opt2]);
	}

	function matchCode($value) {

		$id = $this->varid();
		$code = PHPBuilder::build()->l($this->save($id));

		foreach ($value as $opt) {
			$code->l(
				$opt->compile()->replace([
					'MATCH' => 'MBREAK',
					'FAIL' => \null
				]),
				$this->restore($id)
			);
		}

		$code->l('FBREAK');
		return $this->matchFailBlock($code);

	}

}
