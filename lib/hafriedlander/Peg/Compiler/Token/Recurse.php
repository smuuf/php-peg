<?php

namespace hafriedlander\Peg\Compiler\Token;

use hafriedlander\Peg\Compiler\Token;
use hafriedlander\Peg\Compiler\PHPBuilder;

class Recurse extends Token {

	public function __construct($value) {
		parent::__construct('recurse', $value) ;
	}

	public function match_function($value) {
		return $this->function_name($value);
	}

	public function match_code($value) {
		$function = $this->match_function($value) ;
		$storetag = $this->function_name($this->tag ? $this->tag : $this->match_function($value)) ;

		if (\hafriedlander\Peg\Compiler::$debug) {
			$debug_header = PHPBuilder::build()
				->l(
					'$indent = str_repeat("\e[90m| \e[0m", $this->depth / 2);',
					'$this->depth += 2;',
					'$sub = (strlen( $this->string ) - $this->pos > 40) ? substr($this->string, $this->pos, 40) . "...") : substr($this->string, $this->pos);',
					'$sub = preg_replace(\'/(\r|\n)+/\', " {NL} ", $sub);',
					sprintf('print $indent . "Matching \e[32m%s\e[0m \"\e[36m".$sub."\e[0m\" \n";', $function)
				);

			$debug_match = PHPBuilder::build()
				->l(
					'print $indent . "\e[1m\e[42mOK\n\e[0m";',
					'$this->depth -= 2;'
				);

			$debug_fail = PHPBuilder::build()
				->l(
					'print $indent . "-\n";',
					'$this->depth -= 2;'
				);
		} else {
			$debug_header = $debug_match = $debug_fail = \null ;
		}

		$builder = PHPBuilder::build()->l(
			'$key = \'match_' . $function . '\'; $pos = $this->pos;',
			$debug_header,
			'$subres = $this->packhas($key, $pos)' . "\n\t"
			. '? $this->packread($key, $pos)' . "\n\t"
			. ': $this->packwrite($key, $pos, $this->match_' . $function . '($newStack));',
			$this->match_fail_conditional(
				'$subres !== \false',
				PHPBuilder::build()->l(
					$debug_match,
					$this->tag === \false ?
						'$this->store($result, $subres);' :
						'$this->store($result, $subres, "' . $storetag . '");'
				),
				PHPBuilder::build()->l(
					$debug_fail
				)
			)
		);

		$builder->needsStack = true;
		return $builder;

	}

}
