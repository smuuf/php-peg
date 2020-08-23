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

	private $isCallableCache = [];

	public function __construct($string) {
		$this->string = $string;
		$this->pos = 0;
		$this->depth = 0;
		$this->regexps = [];
	}

	protected function isCallable($name) {
		return $this->isCallableCache[$name]
			?? ($this->isCallableCache[$name] = \is_callable([$this, $name]));
	}

	public function whitespace() {

		$matched = \preg_match(
			'/[ \t]+/',
			$this->string,
			$matches,
			\PREG_OFFSET_CAPTURE,
			$this->pos
		);

		if ($matched && $matches[0][1] === $this->pos) {
			$this->pos += \strlen($matches[0][0]);
			return ' ';
		}

		return \false;

	}

	public function literal($token) {
		/* Debugging: * / print( "Looking for token '$token' @ '" . substr( $this->string, $this->pos ) . "'\n" ) ; /* */
		$toklen = \strlen($token);
		$substr = \substr($this->string, $this->pos, $toklen);
		if ($substr === $token) {
			$this->pos += $toklen;
			return $token;
		}

		return \false;
	}

	public function rx($rx) {

		if (!isset($this->regexps[$rx])) {
			$this->regexps[$rx] = new CachedRegexp($this, $rx);
		}

		return $this->regexps[$rx]->match();
	}

	public function expression($result, $stack, $value) {
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
				$callback = [$this, "{$type}_DLR{$value}"];
				if (is_callable($callback)) {
					$rv = $callback();
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
			if ($this->isCallable($method = $storetag ? "{$type}_{$storetag}" : "{$type}_{$subres['name']}")) {
				$this->{$method}(...[&$result, $subres]);
				$storecalled = \true;
				break;
			}

			if ($this->isCallable($method = "{$type}_STR")) {
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
