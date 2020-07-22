<?php

/**
 * Basic error handling for regular expressions
 *
 * PHP version 7.2
 *
 * @category RegexTest
 * @package  RegexTest
 * @author   Evan Wills <evan.i.wills@gmail.com>
 * @license  GPL3 https://www.gnu.org/licenses/gpl-3.0.en.html
 * @link     https://github.com/evanwills/js_regex_tester/
 */

/**
 * Get PHP error message (if any) for a particular regex
 *
 * Takes a supplied regular expression and runs it through
 * the appropriate PHP core function trapping any errror
 * message generated and returns it.
 *
 * @param string $regex PCRE compatible Regular expression to be tested
 *                      (including delimiters and modifiers)
 *
 * @return string,boolean If the supplied regular expression generated
 *                        an error.
 *                        FALSE if the supplied regular expression
 *                        didn't generate an error
 */
function regexError($regex)
{
    if ($old_track_errors = ini_get('track_errors')) {
        $old_php_errormsg = isset($php_errormsg) ? $php_errormsg : false;
    } else {
        ini_set('track_errors', 1);
    }

    unset($php_errormsg);

    @preg_match($regex, '');

    $output = isset($php_errormsg) ? $php_errormsg : '';

    if ($old_track_errors) {
        $php_errormsg = isset($old_php_errormsg) ? $old_php_errormsg : false;
    } else {
        ini_set('track_errors', 0);
    }

    return $output;
}

/**
 * Test whether a given regex has an error or not
 *
 * @param string $regex PCRE compatible regular expression
 *                      (including delimiters and modifiers)
 *
 * @return boolean
 */
function regexHasError($regex)
{
    return (regexError($regex) !== '');
}

/**
 * Get debug info for a given regular expression
 *
 * @param string $regex PCRE compatible regular expression
 *                      (including delimiters and modifiers)
 *
 * @return boolean
 */
function regexDebug($regex)
{
    $output = regexError($regex);
    $ok = 'PCRE compatible regular expression is valid';
    return ($output !== '') ? $output : $ok;
}
