<?php

namespace hafriedlander\Peg;

class Compiler {

	/** @var Compiler\RuleSet[] */
	private static array $parsers = [];

	public static bool $debug = \false;

	private static function createParser(array $match) {
		$name = $match['name'] ?? 'Anonymous Parser';

		// Handle pragmas.
		if ($match['pragmas'] ?? \false) {
			foreach (\explode('!', $match['pragmas']) as $pragma) {

				$pragma = \trim($pragma);
				if ($pragma === '') {
					continue;
				}

				switch ($pragma) {
					case 'debug':
						self::$debug = \true;
						break;
					default:
						throw new \RuntimeException("Unknown pragma '$pragma' encountered");
				}

			}
		}

		self::$parsers[$name] ??= new Compiler\RuleSet;

		// We allow indenting of the whole rule block, but only to the level
		// of the comment start's indent */
		$indent = $match['indent'];

		return self::$parsers[$name]->compile($indent, $match['grammar']);

	}

	public static function compile(string $string): string {

		static $rx = '@
			# Optional indent and marker of grammar definition start.
			^(?<indent>\h*)/\*!\*

			# Optional pragmas and optional name.
			\h*(?<pragmas>(!\w+)+\h+)?(?<name>(\w)+)?\h*\r?\n

			# Any character
			(?<grammar>.+)(?=\*/)

			# Grammar definition end.
			\*/
		@smx';

		return preg_replace_callback(
			$rx,
			[self::class, 'createParser'],
			$string
		);

	}

}
