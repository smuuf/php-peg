<?php

namespace hafriedlander\Peg\Parser;

/**
 * FalseOnlyPackrat only remembers which results where \false. Experimental.
 *
 * @author Hamish Friedlander
 */
class FalseOnlyPackrat extends Basic {

    public function __construct($string) {

		parent::__construct($string);
		$this->packstate = [];

    }

    public function packhas($key, $pos) {
		return ($this->packstate[$key][$pos] ?? \false) == 'F';
    }

    public function packread($key, $pos) {
        return \false;
    }

    public function packwrite($key, $pos, $result) {

		if (!isset($this->packstate[$key])) {
            $this->packstate[$key] = [];
        }

        if ($result === \false) {
            $this->packstate[$key][$pos] = 'F';
        }

		return $result;

	}

}
