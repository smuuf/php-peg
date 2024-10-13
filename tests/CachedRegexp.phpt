<?php

declare(strict_types=1);

use Tester\Assert;

use hafriedlander\Peg\Parser\Basic;
use hafriedlander\Peg\Parser\CachedRegexp;

require __DIR__ . '/bootstrap.php';

$fakeParser = new Basic('xyzabcdef');

$rx = "/[abc]{3}/";
$r = new CachedRegexp($fakeParser, $rx);

// False, because the regex doesn't match the 'string' exactly at the 'pos' offset.

Assert::false($r->match());

$rx = "/[xyz]{3}/";
$r = new CachedRegexp($fakeParser, $rx);

// If regex matches the 'string' at the 'pos', the match() method returns the matched string.
Assert::same('xyz', $r->match());
