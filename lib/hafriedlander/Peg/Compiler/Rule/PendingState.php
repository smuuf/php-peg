<?php

namespace hafriedlander\Peg\Compiler\Rule;

/**
 * Handles storing of information for an expression that applies to the <i>next</i> token, and deletion of that
 * information after applying
 *
 * @author Hamish Friedlander
 */
class PendingState {

	private ?string $what = \null;
	private $val;

	function set(string $what, $val = \true) {
		$this->what = $what;
		$this->val = $val;
	}

	function applyIfPresent(object $on) {

		if ($this->what === \null) {
			return;
		}

		$what = $this->what;
		$on->$what = $this->val;
		$this->what = \null;

	}
}
