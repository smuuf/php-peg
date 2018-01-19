<?php

require_once "ParserTestBase.php";

class PackratParserSyntaxTest extends ParserTestBase {

	public function testBasicRuleSyntax() {

		$parser = $this->buildParser('
			/*!*
			String: /("(.|\n)*?"|\'(.|\n)*?\')/
			Number: /-?\d+(\.\d+)?/
			Bool: "true" | "false"
			Regex: "/" /(\\\/|[^\/])+/ "/"

			Literal: Number | String | Bool | Regex
			AddOperator: "+" | "-"
			MultiplyOperator: "*" | "/"

			Add: operands:Factor ( > ops:AddOperator > operands:Factor)*
			Factor: operands:Literal ( > ops:MultiplyOperator > operands:Literal)*

			Expression: Add
			*/
		', 'Packrat');

		$parser->assertMatches('Expression', '1 + 2 * 3 + 4 + 4 + 4 + 4 + 4 / 5 - 6 + 7 * 8 - 9');
		$parser->assertDoesntMatch('Expression', 'variables + do + not + exist');

	}

}
