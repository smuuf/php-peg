<?php

namespace hafriedlander\Peg\Compiler;

/**
 * PHPWriter contains several code generation snippets that are used both by the Token and the Rule compiler
 */
class PHPWriter {

	public static $varid = 0;

	public function varid() {
		return '_' . (self::$varid++);
	}

	public function functionName($str) {
		$str = \preg_replace('/-/', '_', $str);
		$str = \preg_replace('/\$/', 'DLR', $str);
		$str = \preg_replace('/\*/', 'STR', $str);
		$str = \preg_replace('/[^\w]+/', '', $str);
		return $str;
	}

	public function save($id) {
		return PHPBuilder::build()
			->l(
				'$res' . $id . ' = $result;',
				'$pos' . $id . ' = $this->pos;'
			);
	}

	public function restore($id, $remove = \false) {
		$code = PHPBuilder::build()
			->l(
				'$result = $res' . $id . ';',
				'$this->setPos($pos' . $id . ');'
			);

		if ($remove) {
			$code->l(
				'unset($res' . $id . ', $pos' . $id . ');'
			);
		}

		return $code;
	}

	public function matchFailConditional($on, $match = \null, $fail = \null) {
		return PHPBuilder::build()
			->b(
				'if (' . $on . ')',
				$match,
				'MATCH'
			)
			->b(
				'else',
				$fail,
				'FAIL'
			);
	}

	public function matchFailBlock($code) {
		$id = $this->varid();

		return PHPBuilder::build()
			->l(
				'$' . $id . ' = \null;'
			)
			->b(
				'do',
				$code->replace([
					'MBREAK' => '$' . $id . ' = \true; break;',
					'FBREAK' => '$' . $id . ' = \false; break;'
				])
			)
			->l('while(\false);')
			->b('if($' . $id . ' === \true)', 'MATCH')
			->b('if($' . $id . ' === \false)', 'FAIL');
	}
}
