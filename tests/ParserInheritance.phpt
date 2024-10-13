<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

class ParserInheritanceTest extends ParserTestBase {

	public function testBasicInheritance() {

		$parser = $this->buildParser('
			/*!* BasicInheritanceTestParser
			Foo: "a"
			Bar extends Foo
			*/
		');

		Assert::true($parser->matches('Foo', 'a'));
		Assert::true($parser->matches('Bar', 'a'));

		Assert::false($parser->matches('Foo', 'b'));
		Assert::false($parser->matches('Bar', 'b'));
	}


	public function testBasicInheritanceConstructFallback() {

		$parser = $this->buildParser('
			/*!* BasicInheritanceConstructFallbackParser
			Foo: "a"
				function __construct(&$res){ $res["test"] = "test"; }
			Bar extends Foo
			*/
		');

		$res = $parser->match('Foo', 'a');
		Assert::same($res['test'], 'test');

		$res = $parser->match('Bar', 'a');
		Assert::same($res['test'], 'test');

		$parser = $this->buildParser('
			/*!* BasicInheritanceConstructFallbackParser2
			Foo: "a"
				function __construct(&$res){ $res["testa"] = "testa"; }
			Bar extends Foo
				function __construct(&$res){ $res["testb"] = "testb"; }
			*/
		');

		$res = $parser->match('Foo', 'a');
		Assert::hasKey('testa', $res);
		Assert::same($res['testa'], 'testa');
		Assert::hasNotKey('testb', $res);

		$res = $parser->match('Bar', 'a');
		Assert::hasKey('testb', $res);
		Assert::same($res['testb'], 'testb');
		Assert::hasNotKey('testa', $res);

	}

	public function testBasicInheritanceStoreFallback() {

		$parser = $this->buildParser('
			/*!* BasicInheritanceStoreFallbackParser
			Foo: Pow:"a"
				function *(&$res, $sub){ $res["test"] = "test"; }
			Bar extends Foo
			*/
		');

		$res = $parser->match('Foo', 'a');
		Assert::same($res['test'], 'test');

		$res = $parser->match('Bar', 'a');
		Assert::same($res['test'], 'test');

		$parser = $this->buildParser('
			/*!* BasicInheritanceStoreFallbackParser2
			Foo: Pow:"a" Zap:"b"
				function *(&$res, $sub){ $res["testa"] = "testa"; }
			Bar extends Foo
				function *(&$res, $sub){ $res["testb"] = "testb"; }
			Baz extends Foo
				function Zap(&$res, $sub){ $res["testc"] = "testc"; }
			*/
		');

		$res = $parser->match('Foo', 'ab');
		Assert::hasKey('testa', $res);
		Assert::same($res['testa'], 'testa');
		Assert::hasNotKey('testb', $res);

		$res = $parser->match('Bar', 'ab');
		Assert::hasKey('testb', $res);
		Assert::same($res['testb'], 'testb');
		Assert::hasNotKey('testa', $res);

		$res = $parser->match('Baz', 'ab');
		Assert::hasKey('testa', $res);
		Assert::same($res['testa'], 'testa');
		Assert::hasKey('testc', $res);
		Assert::same($res['testc'], 'testc');
		Assert::hasNotKey('testb', $res);
	}

	public function testInheritanceByReplacement() {

		$parser = $this->buildParser('
			/*!* InheritanceByReplacementParser
			A: "a"
			B: "b"
			Foo: A B
			Bar extends Foo; B => A
			Baz extends Foo; A => ""
			*/
		');

		$parser->assertMatches('Foo', 'ab');
		$parser->assertMatches('Bar', 'aa');
		$parser->assertMatches('Baz', 'b');

	}

}

(new ParserInheritanceTest)->run();
