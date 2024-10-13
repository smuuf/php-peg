<?php

namespace hafriedlander\Peg\Compiler;

/**
 * Rule parsing and code generation
 *
 * A rule is the basic unit of a PEG. This parses one rule, and generates a function that will match on a string
 *
 * @author Hamish Friedlander
 */
class Rule extends PHPWriter {

	private const RULE_RX = '@
	(?<name> [\w-]+)                         # The name of the rule
	( \s+ extends \s+ (?<extends>[\w-]+) )?  # The extends word
	( \s* \( (?<arguments>.*) \) )?          # Any variable setters
	(
		\s*(?<matchmark>:) |                  # Marks the matching rule start
		\s*(?<replacemark>;) |                # Marks the replacing rule start
		\s*$
	)
	(?<rule>[\s\S]*)
	@x';

	private const ARGUMENT_RX = '@
	( [^=]+ )    # Name
	=            # Seperator
	( [^=,]+ )   # Variable
	(,|$)
	@x';

	private const REPLACEMENT_RX = '@
	( ([^=]|=[^>])+ )    # What to replace
	=>                   # The replacement mark
	( [^,]+ )            # What to replace it with
	(,|$)
	@x';

	private const RX_RX = '@\G/(
		((\\\\\\\\)*\\\\/) # Escaped \/, making sure to catch all the \\ first, so that we dont think \\/ is an escaped /
		|
		[^/]               # Anything except /
	)*/[a-zA-Z]*@xu';

	private const FUNCTION_RX = '@^\s+function\s+([^\s(]+)\s*(.*)@';

	private const MODE_RULE = 0;
	private const MODE_REPLACE = 1;

	private RuleSet $ruleSet;
	private Token $parsed;

	private array $arguments = [];
	private array $functions = [];

	private string $name;
	private string $rule;
	private ?self $extends = \null;
	private int $mode;

	public function __construct(RuleSet $ruleSet, array $lines) {
		$this->ruleSet = $ruleSet;

		// Find the first line (if any) that's an attached function definition. Can skip first line (unless this block is malformed)
		$lineCount = \count($lines);
		for ($i = 1; $i < $lineCount; $i++) {
			if (\preg_match(self::FUNCTION_RX, $lines[$i])) {
				break;
			}
		}

		// Then split into the two parts
		$spec = \array_slice($lines, 0, $i);
		$funcs = \array_slice($lines, $i);

		// Parse out the spec
		$spec = \implode("\n", $spec);
		if (!\preg_match(self::RULE_RX, $spec, $specmatch)) {
			\user_error('Malformed rule spec ' . $spec, E_USER_ERROR);
		}

		$this->name = $specmatch['name'];

		if ($specmatch['extends']) {
			$this->extends = $this->ruleSet->getRule($specmatch['extends']);
			if (!$this->extends) {
				\user_error('Extended rule ' . $specmatch['extends'] . ' is not defined before being extended', E_USER_ERROR);
			}
		}

		if ($specmatch['arguments']) {
			\preg_match_all(self::ARGUMENT_RX, $specmatch['arguments'], $arguments, \PREG_SET_ORDER);

			foreach ($arguments as $argument) {
				$this->arguments[\trim($argument[1])] = \trim($argument[2]);
			}
		}

		$this->mode = $specmatch['matchmark']
			? self::MODE_RULE
			: self::MODE_REPLACE;

		if ($this->mode === self::MODE_RULE) {
			$this->rule = $specmatch['rule'];
			$this->parseRule();
		} else {
			if (!$this->extends) {
				user_error("Rule $this->name has Replace matcher, but not on an extends rule", E_USER_ERROR);
			}

			\preg_match_all(self::REPLACEMENT_RX, $specmatch['rule'], $replacements, \PREG_SET_ORDER);

			$rule = $this->extends->getRule();

			foreach ($replacements as $replacement) {
				$search = \trim($replacement[1]);
				$replace = \trim($replacement[3]);
				if ($replace == "''" || $replace == '""') {
					$replace = '';
				}

				$rule = \str_replace($search, ' ' . $replace . ' ', $rule);
			}

			$this->rule = $rule;
			$this->parseRule();
		}

		// Parse out the functions
		$activeFunction = \null;

		foreach ($funcs as $line) {
			/* Handle function definitions */
			if (\preg_match(self::FUNCTION_RX, $line, $func_match, 0)) {
				$activeFunction = $func_match[1];
				$this->functions[$activeFunction] = $func_match[2] . \PHP_EOL;
			} else {
				$this->functions[$activeFunction] .= $line . \PHP_EOL;
			}
		}

	}

	public function getName(): string {
		return $this->name;
	}

	public function getRule(): string {
		return $this->rule;
	}

	/* Manual parsing, because we can't bootstrap ourselves yet */
	public function parseRule() {
		$rule = \trim($this->rule);

		$tokens = [];
		$this->tokenize($rule, $tokens);
		$this->parsed = (\count($tokens) == 1 ? \array_pop($tokens) : new Token\Sequence($tokens));
	}

	public function tokenize($str, &$tokens, $o = 0) {

		$length = \strlen($str);
		$pending = new Rule\PendingState();

		while ($o < $length) {
			/* Absorb white-space */
			if (\preg_match('/\G\s+/', $str, $match, 0, $o)) {
				$o += \strlen($match[0]);
			}
			/* Handle expression labels */
			elseif (\preg_match('/\G(\w*):/', $str, $match, 0, $o)) {
				$pending->set('tag', $match[1] ?: '');
				$o += \strlen($match[0]);
			}
			/* Handle descent token */
			elseif (\preg_match('/\G[\w-]+/', $str, $match, 0, $o)) {
				$tokens[] = $t = new Token\Recurse($match[0]);
				$pending->applyIfPresent($t);
				$o += \strlen($match[0]);
			}
			/* Handle " quoted literals */
			elseif (\preg_match('/\G"[^"]*"/', $str, $match, 0, $o)) {
				$tokens[] = $t = new Token\Literal($match[0]);
				$pending->applyIfPresent($t);
				$o += \strlen($match[0]);
			}
			/* Handle ' quoted literals */
			elseif (\preg_match("/\G'[^']*'/", $str, $match, 0, $o)) {
				$tokens[] = $t = new Token\Literal($match[0]);
				$pending->applyIfPresent($t);
				$o += \strlen($match[0]);
			}
			/* Handle regexs */
			elseif (\preg_match(self::RX_RX, $str, $match, 0, $o)) {
				$tokens[] = $t = new Token\Regex($match[0]);
				$pending->applyIfPresent($t);
				$o += \strlen($match[0]);
			}
			/* Handle $ call literals */
			elseif (\preg_match('/\G\$(\w+)/', $str, $match, 0, $o)) {
				$tokens[] = $t = new Token\ExpressionedRecurse($match[1]);
				$pending->applyIfPresent($t);
				$o += \strlen($match[0]);
			}
			/* Handle flags */
			elseif (\preg_match('/\G\@(\w+)/', $str, $match, 0, $o)) {
				$l = \count($tokens) - 1;
				$o += \strlen($match[0]);
				\user_error('TODO: Flags not currently supported', E_USER_WARNING);
			}
			/* Handle control tokens */
			else {
				$c = \substr($str, $o, 1);
				$l = \count($tokens) - 1;
				$o += 1;
				switch ($c) {
					case '?':
						$tokens[$l]->quantifier = ['min' => 0, 'max' => 1];
						break;
					case '*':
						$tokens[$l]->quantifier = ['min' => 0, 'max' => \null];
						break;
					case '+':
						$tokens[$l]->quantifier = ['min' => 1, 'max' => \null];
						break;
					case '{':
						if (\preg_match('/\G\{([0-9]+)(,([0-9]*))?\}/', $str, $matches, 0, $o - 1)) {
							$min = $max = (int) $matches[1];
							if (isset($matches[2])) {
								$max = $matches[3] ? (int) $matches[3] : \null;
							}
							$tokens[$l]->quantifier = ['min' => $min, 'max' => $max];
							$o += \strlen($matches[0]) - 1;
						} else {
							throw new \Exception(sprintf(
								'Unknown quantifier: %s',
								substr($str, $o, 10)
							));
						}
						break;
					case '&':
						$pending->set('positiveLookahead');
						break;
					case '!':
						$pending->set('negativeLookahead');
						break;

					case '.':
						$pending->set('silent');
						break;

					case '[':
					case ']':
						$tokens[] = new Token\Whitespace(\false);
						break;
					case '<':
					case '>':
						$tokens[] = new Token\Whitespace(\true);
						break;

					case '(':
						$subtokens = [];
						$o = $this->tokenize($str, $subtokens, $o);
						$tokens[] = $t = new Token\Sequence($subtokens); $pending->applyIfPresent($t);
						break;
					case ')':
						return $o;

					case '|':
						$option1 = $tokens;
						$option2 = [];
						$o = $this->tokenize($str, $option2, $o);

						$option1 = (\count($option1) == 1) ? $option1[0] : new Token\Sequence($option1);
						$option2 = (\count($option2) == 1) ? $option2[0] : new Token\Sequence($option2);

						$pending->applyIfPresent($option2);

						$tokens = [new Token\Option($option1, $option2)];
						return $o;

					default:
						\user_error("Can't parse '$c' - attempting to skip", E_USER_WARNING);
				}
			}
		}

		return $o;
	}

	/**
	 * Generate the PHP code for a function to match against a string for this rule
	 */
	public function compile($indent) {
		$fnName = $this->functionName($this->name);

		// Build the typestack
		$typestack = [];
		$class = $this;
		do {
			$typestack[] = $this->functionName($class->name);
		} while ($class = $class->extends);

		$typestack = "['" . \implode("','", $typestack) . "']";

		$match = PHPBuilder::build();

		$match->l("protected \$match_{$fnName}_typestack = $typestack;");

		$block = $this->parsed->compile()->replace([
			'MATCH' => 'return $this->finalise($result);',
			'FAIL' => 'return \false;'
		]);

		// Build an array of additional arguments to add to result node (if any).
		$arguments = $this->arguments
			? (", " . \var_export($this->arguments, \true))
			: '';

		$match->b(
			"function match_{$fnName}(\$stack = [])",
			"\$matchrule = '$fnName';",
			"\$this->currentRule = \$matchrule;",
			"\$result = \$this->construct(\$matchrule, \$matchrule$arguments); ",
			$block
		);

		$functions = [];
		foreach ($this->functions as $name => $function) {
			$fnName = $this->functionName(
				\preg_match('/^_/', $name)
					? $this->name . $name
					: $this->name . '_' . $name
			);
			$functions[] = \implode(\PHP_EOL, [
				'public function ' . $fnName . ' ' . $function
			]);
		}

		// print_r( $match ); return '';
		return $match->render(\null, $indent)
			. \PHP_EOL
			. \PHP_EOL
			. \implode(\PHP_EOL, $functions);

	}
}
