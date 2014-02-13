<?php

abstract class micro_time
{
	const REGEX = '/^0(\.[0-9]+) ([0-9]+)$/';
	protected function __construct() {}

/**
 * @method mt_subtract() retuns the time difference between two
 * microtime results
 *
 * Because of PHPs rounding off of floats when you're finding the
 * difference between two microtime values it's necessary to fiddle
 * with the floating point precision
 *
 * @param string $start microtime() from just before regex was run
 * @param string $end microtime() from just after regex was run
 *
 * @return string difference between $start and $end
 */
	abstract public function mt_subtract( $start , $end );

/**
 * @method get_obj() returns the microtime handlerthat will work on
 *	   this system
 * @param void
 * @return object
 */
	public static function get_obj()
	{
		if( function_exists('bcsub') )
		{
			return new micro_time_bc();
		}
		elseif( function_exists('gmp_strval') )
		{
			return new micro_time_gmp();
		}
		else
		{
			return new micro_time_basic();
		}
	}

}

class micro_time_bc extends micro_time
{
/**
 * @method mt_subtract() retuns the time difference between two
 * microtime results
 *
 * Because of PHPs rounding off of floats when you're finding the
 * difference between two microtime values it's necessary to fiddle
 * with the floating point precision
 *
 * @param string $start microtime() from just before regex was run
 * @param string $end microtime() from just after regex was run
 *
 * @return string difference between $start and $end
 */
	public function mt_subtract( $start , $end )
	{
		return bcsub(
			 preg_replace( self::REGEX , '\2\1' , $end )
			,preg_replace( self::REGEX , '\2\1' , $start )
			,8
		);
	}
}

class micro_time_gmp extends micro_time
{
/**
 * @method mt_subtract() retuns the time difference between two
 * microtime results
 *
 * Because of PHPs rounding off of floats when you're finding the
 * difference between two microtime values it's necessary to fiddle
 * with the floating point precision
 *
 * @param string $start microtime() from just before regex was run
 * @param string $end microtime() from just after regex was run
 *
 * @return string difference between $start and $end
 */
	public function mt_subtract( $start , $end )
	{
		return preg_replace(
			  '/(?<=\.[0-9]{8}).*$/'
			 ,''
			 ,gmp_strval(
				gmp_sub(
					 preg_replace( self::REGEX , '\2\1' , $end )
					,preg_replace( self::REGEX , '\2\1' , $start )
				)
			 )
		);
					
	}
}
