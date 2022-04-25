<?php

namespace hafriedlander\Peg\Compiler;

class PHPBuilder {

	private array $lines = [];

	static function build () {
		return new PHPBuilder;
	}

	function l(...$args) {

		foreach ($args as $lines) {

			if (!$lines) {
				continue;
			}

			if (\is_string($lines)) {
				$lines = preg_split('/\r\n|\r|\n/', $lines);
			}

			if ($lines instanceof self) {
				$lines = $lines->lines;
			} else {
				$lines = \array_map('rtrim', $lines);
			}

			if (!$lines) {
				continue;
			}

			$this->lines = \array_merge($this->lines, $lines);

		}

		return $this;
	}

	function b(...$args) {

		$entry = \array_shift($args);

		$block = new PHPBuilder;
		$block->l(...$args);
		$this->lines[] = [$entry, $block->lines];

		return $this;
	}

	function replace(array $replacements, array &$array = \null) {
		if ($array === \null) {
			unset($array);
			$array =& $this->lines;
		}

		$i = 0;
		while ($i < \count($array)) {

			/* Recurse into blocks */
			if (\is_array($array[$i])) {
				$this->replace($replacements, $array[$i][1]);

				if (\count($array[$i][1]) === 0) {
					$nextelse = isset($array[$i + 1])
						&& \is_array($array[$i + 1])
						&& \preg_match('/^\s*else\s*$/i', $array[$i + 1][0]);

					$delete = \preg_match('/^\s*else\s*$/i', $array[$i][0]);
					$delete = $delete || (\preg_match('/^\s*if\s*\(/i', $array[$i][0]) && !$nextelse);

					if ($delete) {
						// Is this always safe? Not if the expression has side-effects.
						// print "/* REMOVING EMPTY BLOCK: " . $array[$i][0] . "*/\n";
						\array_splice($array, $i, 1);
						continue;
					}
				}
			} else {
				/* Handle replacing lines with \null to remove, or string, array of strings or PHPBuilder to replace */
				if (\array_key_exists($array[$i], $replacements)) {
					$rep = $replacements[$array[$i]];

					if ($rep === \null) {
						\array_splice($array, $i, 1);
						continue;
					}

					if (\is_string($rep)) {
						$array[$i] = $rep;
						$i++ ;
						continue;
					}

					if ($rep instanceof self) {
						$rep = $rep->lines;
					}

					if (\is_array($rep)) {
						\array_splice($array, $i, 1, $rep); $i += \count($rep) + 1;
						continue;
					}

					throw new \Exception('Unknown type passed to PHPBuilder#replace');
				}
			}

			$i++;
		}

		return $this;
	}

	function render(?array $array = \null, string $indent = ""): string {

		if ($array === \null) {
			$array = $this->lines;
		}

		$out = [];
		foreach ($array as $line) {
			if (\is_array($line)) {
				[$entry, $block] = $line;
				$str = $this->render($block, $indent . "\t");

				if (\strlen($str) < 40) {
					$out[] = $indent . $entry . ' { ' . \ltrim($str) . ' }';
				} else {
					$out[] = $indent . $entry . ' {';
					$out[] = $str;
					$out[] = $indent . '}';
				}
			} else {
				$out[] = $indent . $line;
			}
		}

		return \implode(\PHP_EOL, $out);

	}

}
