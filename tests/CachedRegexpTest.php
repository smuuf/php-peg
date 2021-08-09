<?php

use \hafriedlander\Peg\Parser\CachedRegexp;

class CachedRegexpTest extends \PHPUnit\Framework\TestCase {

	public function testMatching() {

        $fakeParser = (object) [
            'string' => 'xyzabcdef',
            'pos' => 0,
        ];

        $rx = "/[abc]{3}/";
        $r = new CachedRegexp($fakeParser, $rx);

        // False, because the regex doesn't match the 'string' exactly at the 'pos' offset.
        $this->assertFalse($r->match());

        $rx = "/[xyz]{3}/";
        $r = new CachedRegexp($fakeParser, $rx);

        // If regex matches the 'string' at the 'pos', the match() method returns the matched string.
        $this->assertSame('xyz', $r->match());

	}


}
