<?php

namespace hafriedlander\Peg\Parser;

/**
 * We cache the last regex result. This is a low-cost optimization, because we have to do an un-anchored match + check match position anyway
 * (alternative is to do an anchored match on a string cut with substr, but that is very slow for long strings). We then don't need to recheck
 * for any position between current position and eventual match position - result will be the same
 *
 *  Of course, the next regex might be outside that bracket - after the bracket if other matches have progressed beyond the match position, or before
 *  the bracket if a failed match + restore has moved the current position backwards - so we have to check that too.
 */
class CachedRegexp {

	// S: Extra analysis is performed.
	// x: Ignore extra whitespace.
	const DEFAULT_MODIFIERS = 'Sx';

	private string $rx;
	private Basic $parser;
	private array $matches = [];
	private ?int $matchPos;
	private ?int $checkPos;

	public function __construct(Basic $parser, string $rx) {

		// Modifiers can be specified multiple times, so no need to check for
		// uniqueness.
		$this->rx = $rx . self::DEFAULT_MODIFIERS;
		$this->parser = $parser;

		$this->matchPos = \null; // \null is no-match-to-end-of-string, unless checkPos also == \null, in which case means undefined.
		$this->checkPos = \null;

	}

	public function match() {

		$currentPos = $this->parser->getPos();
		$dirty = $this->checkPos === \null
			|| $this->checkPos > $currentPos
			|| ($this->matchPos !== \null && $this->matchPos < $currentPos);

		if ($dirty) {

			$this->checkPos = $currentPos;
			$matched = \preg_match(
				$this->rx,
				$this->parser->getString(),
				$this->matches,
				\PREG_OFFSET_CAPTURE,
				$this->checkPos
			);

			$this->matchPos = $matched
				? $this->matches[0][1]
				: \null;

		}

		if ($this->matchPos !== $currentPos) {
			return \false;
		}

		$this->parser->addPos(\strlen($this->matches[0][0]));
		return $this->matches[0][0];

	}
}
