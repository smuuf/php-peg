<?php

namespace hafriedlander\Peg\Compiler;

class RuleSet {

	/** @var Rule[] */
	private array $rules = [];

	function addRule(string $indent, array $lines, &$out): void {

		$rule = new Rule($this, $lines);
		$ruleName = $rule->getName();
		$this->rules[$ruleName] = $rule;

		$out[] = $indent . '/* ' . $ruleName . ':' . $rule->getRule() . ' */' . \PHP_EOL;
		$out[] = $rule->compile($indent);
		$out[] = \PHP_EOL;

	}

	public function getRule(string $name): ?Rule {
		return $this->rules[$name] ?? \null;
	}

	function compile($indent, $rulestr) {

		$out = [];
		$block = [];

		foreach (\preg_split('/\r\n|\r|\n/', $rulestr) as $line) {

			// Ignore blank lines
			if (!\trim($line)) {
				continue;
			}

			// Ignore comments
			if (\preg_match('/^[\x20\t]*#/', $line)) {
				continue;
			}

			// Strip off indent
			if (!empty($indent)) {
				if (\strpos($line, $indent) === 0) $line = \substr($line, \strlen($indent));
				else \user_error('Non-blank line with inconsistent index in parser block', E_USER_ERROR);
			}

			// Any indented line, add to current set of lines
			if (\preg_match('/^[\x20\t]/', $line)) {
				$block[] = $line;
			} else {

				// Any non-indented line marks a new block. Add a rule for the current block, then start a new block
				if (\count($block)) {
					$this->addRule($indent, $block, $out);
				}

				$block = [$line];

			}

		}

		// Any unfinished block add a rule for
		if (\count($block)) {
			$this->addRule($indent, $block, $out);
		}

		// And return the compiled version
		return \implode('', $out);

	}

}
