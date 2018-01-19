<?php

namespace hafriedlander\Peg\Parser;

/**
 * By inheriting from Packrat instead of Parser, the parser will run in linear time (instead of exponential like
 * Parser), but will require a lot more memory, since every match-attempt at every position is memorised.
 *
 * We now use a string as a byte-array to store position information rather than a straight array for memory reasons. This
 * means there is a (roughly) 8MB limit on the size of the string we can parse
 *
 * @author Hamish Friedlander
 */
class Packrat extends Basic {
	function __construct( $string ) {
		parent::__construct( $string ) ;
		$this->packres = [];
		$this->packpos = [];
	}

	function packhas($key, $pos) {
		return !empty($this->packres[$key][$pos]);
	}

	function packread($key, $pos) {

		if (!isset($this->packres[$key][$pos])) {
			return false;
		}

		$this->pos = $this->packpos[$key][$pos];
		return $this->packres[$key][$pos];

	}

	function packwrite($key, $pos, $res) {

		if ($res !== \false) {
			$this->packres[$key][$pos] = $res;
			$this->packpos[$key][$pos] = $this->pos;
		} else {
			$this->packres[$key][$pos] = false;
		}

		return $res;

	}
}
