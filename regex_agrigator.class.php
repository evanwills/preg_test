<?php


/**
 * @class regex_agrigator provides additional functionality for regex classes to allow multiple regular expressions, multiple replacment values and multiple sample strings to all be tested/processed in a single pass
 */

if( !class_exists('regex') )
{
	require('regex.class.php');
}

class regex_agrigator
{

/**
 * @var array $regexes list of regex objects
 */
	protected $regexes = array();

/**
 * @var array $samples list of sample strings to apply regexes to
 */
	protected $samples = array();

/**
 * @var string $tab string of tabs to provide indenting for HTML tags
 */
	protected $tab = "\t\t\t\t\t\t";

/**
 * @var boolean $dud whether or not any of the supplied regexes had an
 *	error
 */
	protected $dud = false;
/**
 * @method __construct()
 *
 * @param array $find list of regexes to be tested
 * @param array $replace list of replacement values/patterns to be
 *	  used
 * @param array $samples list of sample strings to test the regexes
 *	  against
 *
 */
	public function __construct( $find , $replace , $samples )
	{
		$input = array( 'find' , 'replace' , 'samples' );

		foreach( $input as $value )
		{
			if( is_string($$value) )
			{
				$this->$value = array($$value);
			}
			elseif( !is_array($$value) )
			{
//				throw
			}
			else
			{
				$this->$value = $$value;
			}
		}
		if( !empty($replace) )
		{
			$input = '';
		}
		else
		{
			$input = false;
		}

		foreach( $find as $key => $value )
		{
			if( !isset($replace[$key]) )
			{
				$replacement = $input;
			}
			else
			{
				$replacement = $replace[$key];
			}
			$this->regexes[] = regex::process( $value , $replacement );
		}
		$this->samples = $samples; 
	}

/**
 * @method report() applies all regexes to a given string.
 *
 * Regexes are applied in order of inclusion. What was matched is
 * shown along with how long it took, how many matches were made,
 * what if any error messages were generated and the regex with
 * errors highligted
 *
 * @param string $sample sample text regexes are to be tested against
 *
 * @return array list of results for each regex supplied
 */
	public function report( $samples )
	{
		$output = array();
		foreach( $samples as $sample )
		{
			$sub_output = array();
			foreach( $this->regexes as $regex )
			{
				$sub_output[] = $regex->report($sample);
				$sample = $regex->get_output($sample);
				$c = ( count($sub_output) - 1 );
				if( $sub_output[$c]['type'] == 'error' )
				{
					$this->dud = true;
				}
			}
			$output[] = $sub_output;
		}
		return $output;
	}

/**
 * @method process_samples() applies regex find and replace to the sample
 * supplied.
 *
 * @param string $sample sample text regexes are to be tested against
 *
 * @return array list of results for each regex supplied
 */
	public function process_samples( $samples )
	{
		$output = array();
		foreach( $samples as $sample )
		{
			foreach( $this->regexes as $regex )
			{
				$sample = $regex->get_output($sample);
			}
			$output[] = self::split_clean($sample);
		}
		return $output;
	}

	public function is_dud()
	{
		return $this->dud;
	}

/**
 * @method regex_explode() makes it easy to prepare inputs for
 *	  regex_agrigator
 * 
 * @param string $input string that may or may not need to be split
 *
 * @param boolean $multi whether or not to split the string
 *
 * @param string $split characters used to split the input string if
 *	  required
 *
 * @return array an array containing at least one item
 */
	public static function regex_explode( $input , $multi = false , $split = "\n" )
	{
		if( $multi === false )
		{
			return array($input);
		}
		else
		{
			return explode( self::split_clean($split) , $input );
		}
	}


/**
 * @method regex_implode() makes it easy to return processed inputs
 *	  to strings
 * 
 * @param array $input list of strings that need to be concatinated
 *
 * @param boolean $multi whether or not to split the string
 *
 * @param string $split characters used to split the input string if
 *	  required
 *
 * @return string a single string containing all the items in the
 *	  array delimited by $split.
 */
	public static function regex_implode( $input , $multi = false , $split = "\n" )
	{
		if( is_array( $input) )
		{
			return implode( self::split_clean($split) , $input );
		}
		elseif( is_string($input) )
		{
			return $input;
		}
		return '';
	}

	protected static function split_clean( $input )
	{
		return str_replace(
			 array( "\r\n" , '\n' , '\r' , '\t' )
			,array( "\n" , "\n" , "\r" , "\t" )
			,$input
		);
	}
}

