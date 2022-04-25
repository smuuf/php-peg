# PEG parser-generator for PHP

### Disclaimer
This is a _somehow opinionated_ continuation of [combyna/php-peg](https://github.com/combyna/php-peg), which was "a minimally invasive fork" of the original [hafriedlander/php-peg](https://github.com/hafriedlander/php-peg).

From the several originally available types of PEG parsers only `Basic` and `Packrat` _(recommended)_ remain now.

## Notable features of this fork:
- **Fix, Optimization**: *Packrat parser overhaul.* Simplified logic using arrays instead of a string. Arrays ultimately seemed to be more fit for the job, *memory-wise*. This also fixed occasional problem with accessing undefined indexes in packrat cache.
- **Modern code style:** Codebase uses new _(PHP7+)_ language features and code format more familiar to current modern PHP.
- **CLI interface is removed:** Just call `\hafriedlander\Peg\Compiler::compile($grammarDefinitionFile)` directly however you like.
- **Testing**: Test suite is rewritten to use [`Nette Tester`](https://github.com/nette/tester) instead of [PHPUnit](https://github.com/sebastianbergmann/phpunit).
- **Fixed**: Catastrophic backtracking problem *(sometimes happening when compiling a larger grammar)* avoided by simplifying regex that searches for grammar definitions.
- **Optimization**: Using native PHP constants and functions with absolute namespace is slightly faster *(changed in generated code, too)*.
- **Optimization**: Use strict comparisons where possible *(even in generated code)*.

## Documentation
See the [documentation of the original library](https://github.com/hafriedlander/php-peg).
