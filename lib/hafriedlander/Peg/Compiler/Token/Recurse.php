<?php

namespace hafriedlander\Peg\Compiler\Token;

use hafriedlander\Peg\Compiler\Token;
use hafriedlander\Peg\Compiler\PHPBuilder;

class Recurse extends Token {
	function __construct( $value ) {
		parent::__construct( 'recurse', $value ) ;
	}

	function match_function( $value ) {
		return "'".$this->function_name($value)."'";
	}

	function match_code( $value ) {
		$function = $this->match_function($value) ;
		$storetag = $this->function_name( $this->tag ? $this->tag : $this->match_function($value) ) ;

		if ( \hafriedlander\Peg\Compiler::$debug ) {
			$debug_header = PHPBuilder::build()
				->l(
				'$indent = str_repeat("\e[90m| \e[0m", $this->depth / 2);',
				'$this->depth += 2;',
				'$sub = (strlen( $this->string ) - $this->pos > 40) ? substr($this->string, $this->pos, 40) . "...") : substr($this->string, $this->pos);',
				'$sub = preg_replace(\'/(\r|\n)+/\', " {NL} ", $sub);',
				sprintf('print $indent . "Matching \e[32m%s\e[0m \"\e[36m".$sub."\e[0m\" \n";', trim($function, "'"))
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
		}
		else {
			$debug_header = $debug_match = $debug_fail = \null ;
		}

		return PHPBuilder::build()->l(
			'$matcher = \'match_\'.'.$function.'; $key = $matcher; $pos = $this->pos;',
			$debug_header,
			'$subres = $this->packhas($key, $pos) ? $this->packread($key, $pos) : $this->packwrite($key, $pos, $this->$matcher($newStack));',
			$this->match_fail_conditional( '$subres !== \false',
				PHPBuilder::build()->l(
					$debug_match,
					$this->tag === \false ?
						'$this->store($result, $subres);' :
						'$this->store($result, $subres, "'.$storetag.'");'
				),
				PHPBuilder::build()->l(
					$debug_fail
				)
			));
	}
}
