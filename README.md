# PEG parser-generator for PHP

### Disclaimer
This is a continuation of [combyna/php-peg](https://github.com/combyna/php-peg), which itself was "a minimally invasive fork" of the original [hafriedlander/php-peg](https://github.com/hafriedlander/php-peg).

## Main features of this fork:
- **Fix, Optimization**: *Packrat parser overhaul.* Simplified logic using arrays instead of a string. Arrays ultimately seemed to be more fit for the job, *memory-wise*. This also fixed occasional problem with accessing undefined indexes in packrat cache.
- **Fixed**: Catastrofic backtracking problem *(sometimes happening when compiling a larger grammar)* avoided by simplifying regex that searches for grammar definitions.
- **Fixed**: Fixed tests for PHPUnit 6.5 *(which is now also added as `dev` dependency to `composer.json`)*
- **Optimization**: Using native PHP constants and functions with absolute namespace is slightly faster *(changed in generated code, too)*.
- **Optimization**: Use strict comparisons where possible *(even in generated code)*.

## Documentation
See the [documentation of the original library](https://github.com/hafriedlander/php-peg).
