<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\TestCase;

use hafriedlander\Peg;
use hafriedlander\Peg\Parser\Basic;

class ParserTestWrapper {

	/**
	 * @var class-string<Basic>
	 */
	private string $parserClass;

	function __construct(string $parserClass) {
		$this->parserClass = $parserClass;
	}

	function functionName($str): string {
		$str = preg_replace('/-/', '_', $str);
		$str = preg_replace('/\$/', 'DLR', $str);
		$str = preg_replace('/\*/', 'STR', $str);
		$str = preg_replace('/[^\w]+/', '', $str);
		return $str;
	}

	function match($method, $string, $allowPartial = false) {

		$class = $this->parserClass;
		$func = $this->functionName('match_' . $method);

		$parser = new $class($string);
		$res = $parser->$func();

		return ($allowPartial || $parser->getPos() === strlen($string))
			? $res
			: false;

	}

	function matches($method, $string, $allowPartial = false) {
		return $this->match($method, $string, $allowPartial) !== false;
	}

	function assertMatches($method, $string, $message = null) {
		Assert::true(
			$this->matches($method, $string),
			$message
				? $message
				: "Assert parser method $method matches string $string"
			);
	}

	function assertDoesntMatch($method, $string, $message = null) {
		Assert::false(
			$this->matches($method, $string),
			$message
				? $message
				: "Assert parser method $method doesn't match string $string"
		);
	}

}

class ParserTestBase extends TestCase {

	function buildParser(
		string $grammar,
		string $baseClass = Basic::class,
	): ParserTestWrapper {

		$class = 'Parser_' . md5(uniqid());
		eval(Peg\Compiler::compile("
			class $class extends $baseClass {
				$grammar
			}
		"));

		return new ParserTestWrapper($class);

	}

}
