<?php

namespace hafriedlander\Peg\Compiler\Token;

use hafriedlander\Peg\Compiler\Token;
use hafriedlander\Peg\Compiler\PHPBuilder;

class Recurse extends Token {

	public function __construct($value) {
		parent::__construct('recurse', $value);
	}

	public function matchFunction($value) {
		return "'" . $this->functionName($value) . "'";
	}

	public function matchCode($value) {
		$function = $this->matchFunction($value);
		$storetag = $this->functionName(
			$this->tag
				? $this->tag
				: $this->matchFunction($value)
		);

		if (\hafriedlander\Peg\Compiler::$debug) {
			$debugHeader = PHPBuilder::build()
				->l(
					'$indent = str_repeat("| ", $this->debugDepth / 2);',
					'$this->debugDepth += 2;',
					'$sub = ((strlen($this->string) - $this->pos) > 40) ? (substr($this->string, $this->pos, 40) . "...") : substr($this->string, $this->pos);',
					'$sub = preg_replace(\'/(\r|\n)+/\', " {NL} ", $sub);',
					sprintf('print $indent . "Matching: <%s> in \'".$sub."\' \n";', $function)
				);

			$debugMatch = PHPBuilder::build()
				->l(
					'print $indent . "OK\n";',
					'$this->debugDepth -= 2;'
				);

			$debugFail = PHPBuilder::build()
				->l(
					'print $indent . "-\n";',
					'$this->debugDepth -= 2;'
				);
		} else {
			$debugHeader = $debugMatch = $debugFail = \null;
		}

		$builder = PHPBuilder::build()->l(
			'$key = \'match_\'.'.$function.'; $pos = $this->pos;',
			$debugHeader,
			'$subres = $this->packhas($key, $pos)' . "\n\t"
			. '? $this->packread($key, $pos)' . "\n\t"
			. ': $this->packwrite($key, $pos, $this->{$key}(\array_merge($stack, [$result])));',
			$this->matchFailConditional(
				'$subres !== \false',
				PHPBuilder::build()->l(
					$debugMatch,
					$this->tag === \false ?
						'$this->store($result, $subres);' :
						'$this->store($result, $subres, "' . $storetag . '");'
				),
				PHPBuilder::build()->l(
					$debugFail
				)
			)
		);

		return $builder;

	}

}
