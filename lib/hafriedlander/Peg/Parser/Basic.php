<?php

namespace hafriedlander\Peg\Parser;

/**
 * Parser base class
 * - handles current position in string
 * - handles matching that position against literal or rx
 * - some abstraction of code that would otherwise be repeated many times in a compiled grammer, mostly related to calling user functions
 *   for result construction and building
 */
class Basic {

	protected string $string = '';
	protected int $debugDepth = 0;
	protected int $pos = 0;
	protected int $farthestPos = 0;
	protected ?string $currentRule = \null;
	protected ?string $farthestRule = \null;
	private array $isCallableCache = [];
	private array $regexps = [];

	public function __construct(string $string) {
		$this->string = $string;
		$this->regexps = [];
	}

	public function setPos(int $n): void {

		if ($n > $this->farthestPos) {
			$this->farthestRule = $this->currentRule;
			$this->farthestPos = $n;
		}

		$this->pos = $n;

	}

	public function addPos(int $n): void {
		$this->setPos($this->pos + $n);
	}

	public function getPos(): int {
		return $this->pos;
	}

	public function getFarthestPos(): int {
		return $this->farthestPos;
	}

	public function getFarthestRule(): ?string {
		return $this->farthestRule;
	}

	public function getString(): string {
		return $this->string;
	}

	protected function isCallable($name) {
		return $this->isCallableCache[$name]
			?? ($this->isCallableCache[$name] = \is_callable([$this, $name]));
	}

	protected function whitespace() {

		$matched = \preg_match(
			'/[ \t]+/',
			$this->string,
			$matches,
			\PREG_OFFSET_CAPTURE,
			$this->pos
		);

		if ($matched && $matches[0][1] === $this->pos) {
			$this->addPos(\strlen($matches[0][0]));
			return ' ';
		}

		return \false;

	}

	protected function literal($token) {
		/* Debugging: * / print( "Looking for token '$token' @ '" . substr( $this->string, $this->pos ) . "'\n" ) ; /* */
		$toklen = \strlen($token);
		$substr = \substr($this->string, $this->pos, $toklen);
		if ($substr === $token) {
			$this->addPos($toklen);
			return $token;
		}

		return \false;
	}

	protected function rx($rx) {
		$this->regexps[$rx] ??= new CachedRegexp($this, $rx);
		return $this->regexps[$rx]->match();
	}

	protected function expression($result, $stack, $value) {
		$stack[] = $result;
		$rv = \false;

		/* Search backwards through the sub-expression stacks */
		for ($i = \count($stack) - 1; $i >= 0; $i--) {
			$node = $stack[$i];

			if (isset($node[$value])) {
				$rv = $node[$value];
				break;
			}

			foreach ($this->typestack($node['_matchrule']) as $type) {
				if ($this->isCallable($method = "{$type}_DLR{$value}")) {
					$rv = $this->{$method}();
					if ($rv !== \false) {
						break;
					}
				}
			}
		}

		if ($rv === \false) {
			$rv = @$this->$value;
		}

		if ($rv === \false) {
			$rv = @$this->$value();
		}

		return \is_array($rv) ? $rv['text'] : ($rv ?: '');
	}

	public function packhas($key, $pos) {
		return \false;
	}

	public function packread($key, $pos) {
		throw new \Exception('PackRead after PackHas=>\false in Parser.php');
	}

	public function packwrite($key, $pos, $res) {
		return $res;
	}

	public function typestack($name) {
		return $this->{"match_{$name}_typestack"};
	}

	public function construct($matchrule, $name, $arguments = []) {

		$result = [
			'_matchrule' => $matchrule,
			'name' => $name,
			'text' => '',
			'offset' => $this->pos,
		];

		if ($arguments) {
			$result = \array_merge($result, $arguments);
		}

		foreach ($this->typestack($matchrule) as $type) {
			if ($this->isCallable($method = "{$type}__construct")) {
				$this->{$method}(...[&$result]);
				break;
			}
		}

		return $result;

	}

	public function finalise(&$result) {
		foreach ($this->typestack($result['_matchrule']) as $type) {
			if ($this->isCallable($method = "{$type}__finalise")) {
				$this->{$method}(...[&$result]);
				break;
			}
		}

		return $result;
	}

	public function store(&$result, $subres, $storetag = \null) {
		$result['text'] .= $subres['text'];
		$storecalled = \false;

		foreach ($this->typestack($result['_matchrule']) as $type) {

			$method = $storetag
				? "{$type}_{$storetag}"
				: "{$type}_{$subres['name']}";

			if ($this->isCallable($method)) {
				$this->{$method}(...[&$result, $subres]);
				$storecalled = \true;
				break;
			}

			$method = "{$type}_STR";
			if ($this->isCallable($method)) {
				$this->{$method}(...[&$result, $subres]);
				$storecalled = \true;
				break;
			}

		}

		if ($storetag && !$storecalled) {
			if (!isset($result[$storetag])) {
				$result[$storetag] = $subres;
			} else {
				if (isset($result[$storetag]['text'])) {
					$result[$storetag] = [$result[$storetag]];
				}

				$result[$storetag][] = $subres;
			}
		}

	}

}
