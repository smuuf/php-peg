<?php

namespace hafriedlander\Peg\Parser;

/**
 * By inheriting from Packrat instead of Parser, the parser will run in linear time (instead of exponential like
 * Parser), but will require a lot more memory, since every match-attempt at every position is memorised.
 *
 * Originally a single string was used as a storage for memoizing parser results.
 * This approach is now abandoned in favor of true PHP arrays, which now seem to be a better choice, since PHP 7.0
 * optimized internal workings of PHP arrays. It seems that using array as a storage actually has lower memory
 * footprint than the original "string" implementation.
 *
 * This refactoring also significantly simplified the packrat parser's code.
 *
 * @author Premysl Karbula
 * @author Hamish Friedlander
 */
class Packrat extends Basic {

	function __construct($string) {

		parent::__construct($string) ;

		$this->packres = [];
		$this->packpos = [];

	}

	function packhas($key, $pos) {
		return !empty($this->packres[$key][$pos]);
	}

	function packread($key, $pos) {

		if (!isset($this->packres[$key][$pos])) {
			return \false;
		}

		$this->pos = $this->packpos[$key][$pos];
		return $this->packres[$key][$pos];

	}

	function packwrite($key, $pos, $result) {

		if ($result !== \false) {
			$this->packres[$key][$pos] = $result;
			$this->packpos[$key][$pos] = $this->pos;
		} else {
			$this->packres[$key][$pos] = \false;
		}

		return $result;

	}
}
