<?php

/**
 * @class regex allows you to test regular expressions.
 *
 * The primary aim of this set of classes is to provide feedback to
 * users about their regular expressions in systems that allow users
 * to input their own regexes. It is assumed that these classes will
 * only be used in the user interface of a system and not when the
 * system is actually applying the regex. An invalid regular
 * expression is of no use to anybody and should not be stored by a
 * system. Also these classes incur an additional overhead
 * unnecessary when using a valid regex.
 */
class regex
{

/**
 * @var string $find regular expression to be used
 */
	protected $find = '';

/**
 * @var string $replace replacement pattern
 */
	protected $replace = '';

/**
 * @var array $error list of errors caused by the regular expression
 */
	protected $errors = array();

/**
 * @var string $type regex object type
 */
	protected $type = 'regex';

/**
 * @var array $input_errors list of errors caused at a programatic
 * level by bad input values
 */
	protected $input_errors = array();

/**
 * @var integer $sample_len maximum number of characters displayed in
 * the 'sample' index in the report output array
 */
	protected static $sample_len = 300;

/**
 * @var integer $sample_len_dot number of characters from the start
 *	of a string shown before appending an elipsus to the
 *	truncated sample string and then displayed in the 'sample'
 *	index in the report output array
 */
	protected static $sample_len_dot = 297;

/**
 * @var object $micro_time an object for doing calculations with large
 * floatingpoint numbers
 */
	protected static $micro_time = null;

/**
 * @method __construct()
 * @param string $find valid regular expression to use for matching
 * @param string $replace (optional) replacement string
 * @param array $errors (optional) If there are errors in the regex
 *	  then regex_error::__construct() will use the error array
 *	  to generate useful feedback about what is wrong with the
 *	  regex
 */
	protected function __construct( $find , $replace = false , $errors = false )
	{
		// NOTE: It makes no sence within the context of this
		//	 class to allow the 'e' (evaluate) modifier
		//	 so we is stripped from modifiers part of the
		//	 regex
		if( empty($errors) && preg_match( '/^(.).*?(?<=(?<=\\\\\\\\)|(?<!\\\\))\1(.*)$/is' , $find , $matches ) )
		{
			$find = preg_replace( '/'.$matches[2].'$/' , str_replace('e','',$matches[2]) , $find );
		}
		$this->find = $find;
		$this->highlighted = '<span class="ok">'.htmlspecialchars($find).'</span>';
		$this->replace = $this->fix_line_end($replace);
		$this->errors = $errors;

		if( is_null(self::$micro_time) )
		{
			self::$micro_time = micro_time::get_obj();
		}
	}

/**
 * @method process() takes a given regular expression and returns
 * the appropriate regex object
 *
 * @param string $find regular expression to be used
 * @param string $replace pattern/string to replace matched string
 *
 * @return object appropriate regex object:
 *	   if regex has a replace a regex_replace object is returned
 *	   if regex has an error a regex_error object is returned
 *	   otherwise a regex_match object is returned
 */
	public static function process( $find , $replace = false )
	{
		$errors = array();
		if( !is_string($find) )
		{
			$errors[] = 'regex::process() first paramater must be a string. '.gettype($find).' given!';
			// throw
		}
		if( !is_string($replace) && $replace !== false )
		{
			$errors[] = 'regex::process() second paramater must be a string (or void). '.gettype($find).' given!';
			// throw
		}

// turn PHP errors on or clear errors
		$tmp = self::valid_regex( $find );
		if( $tmp !== true )
		{
			$errors = array_merge( $errors , $tmp );
		}

		if( empty($errors) )
		{
			if( $replace === false )
			{
				return new regex_match( $find );
			}
			else
			{
				return new regex_replace( $find , $replace );
			}
		}
		else
		{
			return new regex_error( $find , $replace , $errors);
		}
	}


	static public function valid_regex( $find )
	{
		$find = trim($find);
		$errors = array();

		if( !is_string($find) || $find == '' )
		{
			return array( 'Not a string or empty string' );
		}

		if( empty($errors) )
		{
			if($old_track_errors = ini_get('track_errors'))
			{
				$old_php_errormsg = isset($php_errormsg)?$php_errormsg:false;
			}
			else
			{
				ini_set('track_errors' , 1);
			}
			unset($php_errormsg);

			$display_errors = ini_get('display_errors');
			ini_set('display_errors','stderr');

			$html_errors = ini_get('html_errors');
			ini_set('html_errors','false');

			preg_match( $find , '' );

			ini_set('display_errors',$display_errors);
			ini_set('html_errors',$html_errors);

			unset( $display_errors , $html_errors );

			if( isset($php_errormsg) )
			{
				$php_errormsg = str_replace('preg_match(): ','',$php_errormsg);
				$errors[] = $php_errormsg;
				unset( $php_errormsg );
			}
			if($old_track_errors)
			{
				$php_errormsg = isset($old_php_errormsg)?$old_php_errormsg:false;
			}
			else
			{
				ini_set('track_errors' , 0);
			}
		}

		if( empty($errors) )
		{
			return true;
		}
		else
		{
			return $errors;
		}
	}

/**
 * @method report provides information about what the regex matched
 * in the sample string
 *
 * @param string $sample text the regex is to be applied to
 *
 * @return array with five associative values:
 *		[string]  'output'  array of information about the
 *				    regex in relation to the sample
 *		[string]  'time'    the time taken to execute the
 *				    regex on the sample
 *		[integer] 'count'   the number of matches the regex
 * 				    had on the sample
 *		[string]  'regex'   regular expression used
 *		[string]  'replace' replacement pattern/string used
 */
	public function report( $sample )
	{
		$sample_str = $this->valid_input($sample);
		if( $sample_str === false )
		{
			$sample_str = '';
		}
		return $this->error(
			array(
				 'output' => array($sample)
				,'time' => '-1'
				,'count' => -1
				,'regex' => $this->find
				,'sample' => $sample_str
				,'replace' => $this->replace
				,'type' => $this->type
			)
		);
	}

/**
 * @method is_valid() tells whether the object holds a valid regex
 * @param void
 * @return boolean
 *
 * NOTE: regex_match and regex_replace contain only valid regular
 *	 expressions, where-as regex_error contains only invalid
 *	 regular expressions)
 */
	public function is_valid()
	{
		return true;
	}

/**
 * @method analyse looks at how to break up the regex or how to fix a
 * broken regex
 *
 * @param void
 * @return array
 */
	public function alalyse()
	{
		return array();
	}

/**
 * @method get_output() returns the sample input after being passed
 * through preg_replace
 *
 * @param string $sample text the regex is to be applied to
 * @return array with three associative values:
 *		[string] 'output' string processed sample
 *		[float] 'time' the time taken to execute the regex on
 *			the sample
 *		[integer] 'count' number of matches the regex had on
 *			the sample
 */
	public function get_output( $sample )
	{
		return $sample;
	}


/**
 * @method errors() returns all error information generated by the
 * object, including input and preg errors
 *
 * @param void
 * @return array an array of strings with error messages.
 */
	public function get_errors()
	{
		return array(
			 'error_message' => 'No errors'
			,'error_highlighted' => '<span class="ok">'.$this->find.'</span>'
		);
	}

/**
 * @method get_regex() returns the regular expression
 * @param void
 * @return string regular expression used in the object
 */
	public function get_regex( $highlighted = false )
	{
		if( $highlighted === true )
		{
			return $this->find;
		}
	}

/**
 * @method get_replace() returns the replacement string
 * @param void
 * @return string replacement string used in the object (if any)
 */
	public function get_replace()
	{
		return $this->replace;
	}

	public function valid_input($input)
	{
		if( !is_string($input) )
		{
			$backtr = debug_backtrace();
			$obj =  $backtr[1]['class'];
			$func = $backtr[1]['function'];
			$this->input_errors[] = "$obj::$func() first paramater must be a string. ".gettype($input).' given!';
			return false;
		}
		else
		{
			if( strlen($input) > regex::$sample_len )
			{
				return substr( $input , regex::$sample_len_dot ).'...';
			}
			else
			{
				return $input;
			}
		}
	}


/**
 * @method set_sample_len() sets the maximum length of the sample
 * before it's truncated in the returned output of the
 * regex::report() array.
 *
 * @param integer $len a number greater than 3 [default: 300]
 *
 * @return boolean true if regex::sample_len is updatedl False
 * 	   otherwise.
 */
	public static function set_sample_len( $len )
	{
		if( !is_int($len) || $len < 3 )
		{
			// throw
			return false;
		}
		else
		{
			regex::$sample_len = $len;
			regex::$sample_len_dot = ($len - 3);
			return true;
		}
	}

/**
 * @method h() shortcut method for htmlspecialchars()
 * @param string
 * @return string
 */
	protected function h( $input )
	{
		return htmlspecialchars($input);
	}

	protected function fix_line_end( $input )
	{
		$find = array(
			 '/(?<![^\\\\])\\\\r/'
			,'/(?<![^\\\\])\\\\n/'
			,'/(?<![^\\\\])\\\\t/'
//			,'/(?<![^\\\\])\\\\[rnt]/'
		);
		$replace = array(
			 "\r"
			,"\n"
			,"\t"
		);
		return preg_replace( $find , $replace , $input );
	}
}




// +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+




/**
 * @class regex_match provides feedback on how a regular expression
 * will behave when applied to a given string.
 */
class regex_match extends regex
{
/**
 * @var string $type regex object type
 */
	protected $type = 'match';


/**
 * @method report provides information about what the regex matched
 * in the sample string
 *
 * @param string $sample text the regex is to be applied to
 *
 * @return array with five associative values:
 *		[string]  'output'  array of information about the
 *				    regex in relation to the sample
 *		[float]   'time'    the time taken to execute the
 *				    regex on the sample
 *		[integer] 'count'   the number of matches the regex
 * 				    had on the sample
 *		[string]  'regex'   regular expression used
 *		[string]  'replace' replacement pattern/string used
 */
	public function report( $sample )
	{
		$sample_str = $this->valid_input($sample);
		if( $sample_str !== false )
		{
			$start = microtime();
			preg_match_all( $this->find , $sample , $matches , PREG_SET_ORDER );
			$end = microtime();
			$time = self::$micro_time->mt_subtract( $start , $end );
			$count = count($matches);
		}
		else
		{
			$matches = $this->input_errors;
			$time = '-1';
			$count = -1;
			$sample_str = '';
		}

		$output = array_merge(
			 array(
				 'output' => $matches
				,'time' => $time
				,'count' => $count
				,'regex' => $this->find
				,'sample' => $sample_str
				,'replace' => $this->replace
				,'type' => $this->type
			 )
			,$this->get_errors()
		);
		return $output;
	}
}




// +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+




/**
 * @class regex_replace provides feedback on how a regular expression
 * and associated replace string will behave when applied to a given
 * string.
 */
class regex_replace extends regex_match
{
/**
 * @var string $type regex object type
 */
	protected $type = 'replace';


/**
 * @method get_output() returns the sample input after being passed
 * through preg_replace
 *
 * @param string $sample text the regex is to be applied to
 * @return array with three associative values:
 *		[string] 'output' string processed sample
 *		[float] 'time' the time taken to execute the regex on
 *			the sample
 *		[integer] 'count' number of matches the regex had on
 *			the sample
 */
	public function get_output( $sample )
	{
		$sample_str = $this->valid_input($sample);
		if( $sample_str !== false )
		{
			$start = microtime();
			$output = preg_replace( $this->find , $this->replace , $sample , -1 , $count );
			$end = microtime();
			$time = self::$micro_time->mt_subtract( $start , $end );
		}
		else
		{
			$output = $this->errors;
			$time = '-1';
			$count = -1;
		}

		return $output;
	}
}




// +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+




/**
 * @class regex_error provides feedback on what is wrong with a
 * regular expression
 */
class regex_error extends regex
{
/**
 * @var string $type regex object type
 */
	protected $type = 'error';

/**
 * @var $error_processed identifies whether an error message has been
 * processed
 */
	private $error_processed = false;



/**
 * @method report provides information about what the regex matched
 * in the sample string
 *
 * @param string $sample text the regex is to be applied to
 *
 * @return array with five associative values:
 *		[string]  'output'  array of information about the
 *				    regex in relation to the sample
 *		[float]   'time'    the time taken to execute the
 *				    regex on the sample
 *		[integer] 'count'   the number of matches the regex
 * 				    had on the sample
 *		[string]  'regex'   regular expression used
 *		[string]  'replace' replacement pattern/string used
 */
	public function report( $sample )
	{
		$sample_str = $this->valid_input($sample);
		if( $sample_str === false )
		{
			$sample_str = '';
		}
		$output = array_merge(
			array(
				 'output' => array()
				,'time' => "-1"
				,'count' => -1
				,'regex' => $this->find
				,'sample' => $sample_str
				,'replace' => $this->replace
				,'type' => $this->type
			)
			,$this->get_errors()
		);

		return $output;
	}


/**
 * @method errors() returns all error information generated by the
 * object, including input and preg errors
 *
 * @param void
 * @return array an array of strings with error messages.
 */
	public function get_errors()
	{
		if( $this->error_processed === false )
		{
			$e_count = count($this->errors);
			--$e_count;
			$error = $this->errors[$e_count];
			$error_wrap = array( 'open' => '' , 'close' => '' );
			if( preg_match('/missing (?:terminating )?([\]\}\)]) .*? offset ([0-9]+)/' , $error , $matches ) )
			{
				$bracket = $matches[1];
				$offset = ++$matches[2];
				preg_match('/^(.{'.$offset.'})(.*)$/is' , $this->find , $matches );
				$head = $matches[1];
				$tail = $matches[2];
				switch($bracket)
				{
					case ']': $bracket_ = '[';
						break;
					case '}': $bracket_ = '{';
						break;
					case ')': $bracket_ = '(';
						break;
					case '[': $bracket_ = ']';
						break;
					case '{': $bracket_ = '}';
						break;
					case '(': $bracket_ = ')';
						break;
				}
				preg_match( '/^(.*?\\'.$bracket_.')(.*)$/is' , $head , $matches );
				$start = $matches[1];
				$middle = $matches[2];
				$this->highlight = '<span class="ok">'.$this->h($matches[1]).'</span><span class="problem">'.$this->h($matches[2]).'</span><span class="error">'.$this->h($tail).'</span>';
			}
			elseif( preg_match('/Unknown modifier/is' , $error ) )
			{
				preg_match('/^./',$this->find,$matches);
				switch($matches[0])
				{
					case '(': $wrap = ')';
						break;
					case '[': $wrap = ']';
						break;
					case '{': $wrap = '}';
						break;
					case '<': $wrap = '>';
						break;
					default: $wrap = $matches[0];
				}
				$regex = '/^.(.*?)(?<!\\\\)(\\'.$wrap.')(.*)$/s';
				preg_match($regex,$this->find,$matches);
				$this->highlight = '<span class="ok">'.$this->h($matches[1]).'</span><span class="error">'.$this->h($matches[2]).'</span><span class="problem">'.$this->h($matches[3]).'</span>';
			}
			elseif( preg_match('/at offset ([0-9]+)/is' , $error , $matches ) )
			{
				$offset = ++$matches[1];
				preg_match('/^(.{'.$offset.'})(.)(.*)$/is' , $this->find , $matches );
				$this->highlight = '<span class="ok">'.$this->h($matches[1]).'</span><span class="error">'.$this->h($matches[2]).'</span><span class="problem">'.$this->h($matches[3]).'</span>';
			}
			elseif( preg_match('/lookbehind assertion is not fixed length at offset ([0-9]+)/is' , $error , $matches ) )
			{
				$offset = ++$matches[1];
				preg_match('/^(.{'.$offset.'})(.*)$/is' , $this->find , $matches );
				$head = $matches[1];
				$tail = $matches[2];
				preg_match('/^(.*)(\(\?<[=!].*)/is' , $head , $matches );
				$this->highlight = '<span class="ok">'.$this->h($matches[1]).'</span><span class="error">'.$this->h($matches[2]).'</span><span class="problem">'.$this->h($tail).'</span>';
			}
			elseif( preg_match('/No ending (?:matching )?delimiter \'([^\']+)\' found/is' , $error , $matches ) )
			{
				$delim = $matches[1];
				preg_match('/^(.)(.*)$/is' , $this->find , $matches);
				$this->highlight = '<span class="ok">'.$this->h($matches[1]).'</span><span class="error">'.$this->h($matches[2]).'</span>';
			}
			elseif( preg_match('/Delimiter must not be alphanumeric or backslash/i', $error , $matches ) )
			{
				preg_match('/^(.)(.*)$/is' , $this->find , $matches);
				$this->highlight = '<span class="problem">'.$this->h($matches[1]).'</span><span class="error">'.$this->h($matches[2]).'</span>';
			}
			else
			{
				$error_wrap = array( 'open' => '<span class="unknown"' , 'close' => '</span>' );
//				die("PREG encountered an error I couldn't recognise (or at least haven't seen yet): \"$error\"");
			}
			$error = str_replace('preg_match() [function.preg-match]: ','',strip_tags($error));
			$this->errors = array(
				 'error_message' => $error_wrap['open'].$error.$error_wrap['close']
				,'error_highlight' => $this->highlight
			);
			$this->error_processed = true;
		}
		return $this->errors;
	}

/**
 * @method is_valid() tells whether the object holds a valid regex
 * @param void
 * @return boolean false (regex_error only contains invalid objects)
 */
	public function is_valid()
	{
		return false;
	}

}


if( !class_exists( 'micro_time' ) )
{
	require( dirname(__FILE__).'/micro_time.class.php' );
}
