<?php

namespace hafriedlander\Peg\Compiler;

/**
 * PHPWriter contains several code generation snippets that are used both by the Token and the Rule compiler
 */
class PHPWriter {

	static $varid = 0 ;

	function varid() {
		return '_' . (self::$varid++) ;
	}

	function function_name( $str ) {
		$str = \preg_replace( '/-/', '_', $str ) ;
		$str = \preg_replace( '/\$/', 'DLR', $str ) ;
		$str = \preg_replace( '/\*/', 'STR', $str ) ;
		$str = \preg_replace( '/[^\w]+/', '', $str ) ;
		return $str ;
	}

	function save($id) {
		return PHPBuilder::build()
			->l(
			'$res'.$id.' = $result;',
			'$pos'.$id.' = $this->pos;'
		);
	}

	function restore( $id, $remove = \false ) {
		$code = PHPBuilder::build()
			->l(
			'$result = $res'.$id.';',
			'$this->pos = $pos'.$id.';'
		);

		if ( $remove ) $code->l(
			'unset( $res'.$id.' );',
			'unset( $pos'.$id.' );'
		);

		return $code ;
	}

	function match_fail_conditional( $on, $match = \null, $fail = \null ) {
		return PHPBuilder::build()
			->b( 'if (' . $on . ')',
			$match,
			'MATCH'
		)
			->b( 'else',
			$fail,
			'FAIL'
		);
	}

	function match_fail_block( $code ) {
		$id = $this->varid() ;

		return PHPBuilder::build()
			->l(
			'$'.$id.' = \null;'
		)
			->b( 'do',
			$code->replace(array(
				'MBREAK' => '$'.$id.' = \true; break;',
				'FBREAK' => '$'.$id.' = \false; break;'
			))
		)
			->l(
			'while(0);'
		)
			->b( 'if( $'.$id.' === \true )', 'MATCH' )
			->b( 'if( $'.$id.' === \false)', 'FAIL'  )
			;
	}
}
