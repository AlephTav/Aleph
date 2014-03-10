<?php
/**
 * Copyright (c) 2012 Aleph Tav
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated 
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation 
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, 
 * and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO 
 * THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE 
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, 
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @link http://www.4leph.com
 * @copyright Copyright &copy; 2012 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Utils;

/**
 * This class is designed for different manipulations with date and time.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.utils
 */
class DT extends \DateTime
{ 
  /**
   * Validates the specified date format.
   * Method returns TRUE if the given string is a correct date formatted according to the specified format and FALSE otherwise.
   * If the date format is not specified the method will try to match the given date with one of the possible formats.
   *
   * @param string $date - string representing the date.
   * @param string $format - date format string.
   * @return boolean
   * @access public
   * @static
   */
  public static function isDate($date, $format = null)
  {
    return ($format !== null ? date_create_from_format($format, $date) : date_create($date)) !== false;
  }
  
  /**
   * Checks whether the given year is a leap year.
   * If a year is not specified the current year will be checked.
   *
   * @param integer $year
   * @return boolean
   * @static
   */
  public static function isLeapYear($year = null)
  {
    return checkdate(2, 29, $year === null ? date('Y') : (int)$year);
  }
   
  /**
   * Returns associative array with detailed info about given date.
   *
   * @param string $date - string representing the date.
   * @param string $format - date format string.
   * @return array
   * @access public
   * @static
   */
  public static function getInfo($date, $format)
  {
    return date_parse_from_format($format, $date);
  }
  
  /**
   * Returns value of the hour component of the given date.
   *
   * @param string $date - string representing the date.
   * @param string $format - date format string.
   * @return integer
   * @access public
   * @static
   */
  public static function getHour($date, $format)
  {
    $info = self::getInfo($date, $format);
    return $info['hour'];
  }

  /**
   * Returns value of the minute component of the given date.
   *
   * @param string $date - string representing the date.
   * @param string $format - date format string.
   * @return integer
   * @access public
   * @static
   */
  public static function getMinute($date, $format)
  {
    $info = self::getInfo($date, $format);
    return $info['minute'];
  }

  /**
   * Returns value of the second component of the given date.
   *
   * @param string $date - string representing the date.
   * @param string $format - date format string.
   * @return integer
   * @access public
   * @static
   */
  public static function getSecond($date, $format)
  {
    $info = self::getInfo($date, $format);
    return $info['second'];
  }
  
  /**
   * Returns value of the fraction component of the given date.
   *
   * @param string $date - string representing the date.
   * @param string $format - date format string.
   * @return integer
   * @access public
   * @static
   */
  public static function getFraction($date, $format)
  {
    $info = self::getInfo($date, $format);
    return $info['fraction'];
  }

  /**
   * Returns value of the day component of the given date.
   *
   * @param string $date - string representing the date.
   * @param string $format - date format string.
   * @return integer
   * @access public
   * @static
   */
  public static function getDay($date, $format)
  {
    $info = self::getInfo($date, $format);
    return $info['day'];
  }

  /**
   * Returns value of the month component of the given date.
   *
   * @param string $date - string representing the date.
   * @param string $format - date format string.
   * @return integer
   * @access public
   * @static
   */
  public static function getMonth($date, $format)
  {
    $info = self::getInfo($date, $format);
    return $info['month'];
  }

  /**
   * Returns value of the year component of the given date.
   *
   * @param string $date - string representing the date.
   * @param string $format - date format string.
   * @return integer
   * @access public
   * @static
   */
  public static function getYear($date, $format)
  {
    $info = self::getInfo($date, $format);
    return $info['year'];
  }
  
  /**
   * Returns number of days at the specified month of the given year.
   * This method return FALSE if the given year and month isn't valid.
   *
   * @param integer $year
   * @param integer $month
   * @return integer
   * @access public
   * @static
   */
  public static function getMonthDays($year, $month)
  {
    $month = (int)$month; $year = (int)$year;
    if (!checkdate($month, 1, $year)) return false;
    if ($month == 2)
    {
      $days = 28;
      if (self::isLeapYear($year)) $days++;
    }
    else if ($month < 8) $days = 30 + $month % 2;
    else $days = 31 - $month % 2;
    return $days;
  }
  
  /**
   * Returns unix timestamp for the given date formatted to the specified format.
   * If the date format is not specified the method will try to parse the date from one of the suitable formats.
   * The method returns FALSE if the given date is not valid.
   *
   * @param string $date - string representing the date.
   * @param string $format - date format string.
   * @return integer
   * @access public
   * @static
   */
  public static function timestamp($date, $format = null)
  {
    $dt = $format !== null ? date_create_from_format($format, $date) : date_create($date);
    return ($dt) ? $dt->getTimestamp() : false;
  }

  /**
   * Converts the given date from one format to another.
   * If the input date format is not specified the method will try to parse the date from one of the suitable formats.
   * The method returns FALSE if the given date is not valid.
   *
   * @param string $date - string representing the date.
   * @param string $out - output date format string.
   * @param string $in - input date format string.
   * @return string
   * @access public
   * @static
   */
  public static function date2date($date, $out, $in = null)
  {
    $dt = $in !== null ? date_create_from_format($in, $date) : date_create($date);
    return ($dt) ? $dt->format($out) : false;
  }
  
  /**
   * Converts the given date from the specified format to the MySQL date format ("Y-m-d" or "Y-m-d H:i:s").
   * If the input date format is not specified the method will try to parse the date from one of the suitable formats.
   * The method returns FALSE if the given date is not valid.
   *
   * @param string $date - string representing the date.
   * @param string $format - input date format string.
   * @param boolean $shortFormat - if TRUE the short sql date format (Y-m-d) will be used.
   * @return string 
   * @access public
   * @static
   */
  public static function date2sql($date, $format = null, $shortFormat = false)
  {
    return self::date2date($date, $shortFormat ? 'Y-m-d' : 'Y-m-d H:i:s', $format);
  }

  /**
   * Converts the given date from the MySQL date format ("Y-m-d" or "Y-m-d H:i:s") to the specified format.
   * The method returns FALSE if the given date is not valid.
   *
   * @param string $date - string representing the date.
   * @param string $format - output date format string.
   * @param bool $shortFormat - if TRUE the short sql date format (Y-m-d) will be used.
   * @return string 
   * @access public
   * @static
   */
  public static function sql2date($date, $format, $shortFormat = false)
  {
    return self::date2date($date, $format, $shortFormat ? 'Y-m-d' : 'Y-m-d H:i:s');
  }
  
  /**
   * Converts the given date from specified format to the atom date format.
   * The method returns FALSE if the given date is not valid.
   *
   * @param string $date - string representing the date.
   * @param string $format - input date format string.
   * @return string 
   * @access public
   * @static
   */
  public static function date2atom($date, $format = null)
  {
    return self::date2date($date, \DateTime::ATOM, $format);
  }
  
  /**
   * Converts the given date formatted from the atom date format to the given format.
   * The method returns FALSE if the given date is not valid.
   *
   * @param string $date - string representing the date.
   * @param string $format - output date format string.
   * @return string 
   * @access public
   * @static
   */
  public static function atom2date($date, $format)
  {
    return self::date2date($date, $format, \DateTime::ATOM);
  }
  
  /**
   * Converts the given date from specified format to the RSS date format.
   * If the input date format is not specified the method will try to parse the date from one of the suitable formats.
   * The method returns FALSE if the given date is not valid.
   *
   * @param string $date - string representing the date.
   * @param string $format - input date format string.
   * @return string 
   * @access public
   * @static
   */
  public static function date2rss($date, $format = null)
  {
    return self::date2date($date, \DateTime::RSS, $format);
  }
  
  /**
   * Converts the given date from RSS date format to the given format.
   * The method returns FALSE if the given date is not valid.
   *
   * @param string $date - string representing the date.
   * @param string $format - output date format string.
   * @return string 
   * @access public
   * @static
   */
  public static function rss2date($date, $format)
  {
    return self::date2date($date, $format, \DateTime::RSS);
  }
  
  /**
   * Converts the given date from specified format to the cookie date format.
   * If the input date format is not specified the method will try to parse the date from one of the suitable formats.
   * The method returns FALSE if the given date is not valid.
   *
   * @param string $date - string representing the date.
   * @param string $format - input date format string.
   * @return string 
   * @access public
   * @static
   */
  public static function date2cookie($date, $format = null)
  {
    return self::date2date($date, \DateTime::COOKIE, $format);
  }
  
  /**
   * Converts the given date from cookie date format to the given format.
   * The method returns FALSE if the given date is not valid.
   *
   * @param string $date - string representing the date.
   * @param string $format - output date format string.
   * @return string 
   * @access public
   * @static
   */
  public static function cookie2date($date, $format)
  {
    return self::date2date($date, $format, \DateTime::COOKIE);
  }
  
  /**
   * Compares two given dates.
   * The method returns 1 if the second date larger than the first date.
   * The method returns -1 if the second date smaller than the first date.
   * The method returns 0 if the both dates are equal.
   * The method returns FALSE if the given dates are not valid.
   *
   * @param string $date1 - string representing the first date.
   * @param string $date2 - string representing the second date.
   * @param string $format - date format string.
   * @return integer
   * @access public
   * @static
   */
  public static function compare($date1, $date2, $format = null)
  {
    $v1 = self::timestamp($date1, $format);
    $v2 = self::timestamp($date2, $format);
    if ($v1 === false || $v2 === false) return false;
    if ($v2 > $v1) return 1;
    if ($v2 < $v1) return -1;
    return 0;
  }
  
  /**
   * Computes the difference between two dates given in the same format within the date components.
   * The method returns difference between dates in days by default.
   * The method returns FALSE if the given dates are not valid.
   *
   * @param string $date1 - string representing the first date.
   * @param string $date2 - string representing the second date.
   * @param string $format - date format string.
   * @param string $component - date component.
   * @return string
   * @access public
   * @static
   * @link http://php.net/manual/en/dateinterval.format.php
   */
  public static function difference($date1, $date2, $format = null, $component = '%r%a')
  {
    if ($format === null)
    {
      $dt1 = date_create($date1);
      $dt2 = date_create($date2);
    }
    else
    {
      $dt1 = date_create_from_format($format, $date1);
      $dt2 = date_create_from_format($format, $date2);
    }
    return ($dt1 && $dt2) ? date_diff($dt1, $dt2)->format($component) : false;
  }
  
  /**
   * Calculates the difference between the two dates given in the same format.
   * If the specific date format is not set the method will try to parse dates into one of the suitable formats.
   * The method returns array of the following elements: second, minute, hour, day 
   * and returns FALSE if the given dates are not valid.
   *
   * @param string $date1 - string representing the first date.
   * @param string $date2 - string representing the second date.
   * @param string $format - date format string.
   * @return array 
   * @access public
   * @static
   */
  public static function countdown($date1, $date2, $format = null)
  {
    $v1 = self::timestamp($date1, $format);
    $v2 = self::timestamp($date2, $format);
    if ($v1 === false || $v2 === false) return false;
    $d = $v2 - $v1;
    $res = [];
    $res['day'] = floor($d / 86400);
    $d %= 86400;
    $res['hour'] = floor($d / 3600);
    $d %= 3600;
    $res['minute'] = floor($d / 60);
    $res['second'] = $d % 60;
    return $res;
  }
  
  /**
   * Returns text representation of the time elapsed since the specified date.
   *
   * @param string | DateTimeInterface $date - the start date.
   * @param string | DateTimeInterface $now - the current date.
   * @param string $format - date format string.
   * @return string
   * @access public
   */
  public static function past($date, $now, $format = null)
  {
    $dt = new DT($date, $format);
    $interval = $dt->diff(new DT($now, $format));
    $parts = ['y' => 'year', 'm' => 'month', 'd' => 'day', 'h' => 'hour', 'i' => 'minute', 's' => 'second'];
    foreach ($parts as $part => $name)
    {
      if ($interval->{$part} > 0)
      {
        if ($interval->{$part} == 1) $res = 'a ' . $name;
        else $res = $interval->{$part} . ' ' . $name . 's';
        return $res . ' ago';
      }
    }
    return 'just now';
  }
  
  /**
   * Returns today's date.
   *
   * @param string $format - output date format.
   * @param string | DateTimeZone $timezone
   * @return string
   * @access public
   * @static
   */
  public static function now($format, $timezone = null)
  {
    return (new DT('now'))->getDate($format, $timezone);
  }
  
  /**
   * Returns tomorrow's date.
   *
   * @param string $format - output date format.
   * @param string | DateTimeZone $timezone
   * @return string
   * @access public
   * @static
   */
  public static function tomorrow($format, $timezone = null)
  {
    return (new DT('tomorrow'))->getDate($format, $timezone);
  }
  
  /**
   * Returns yesterday's date.
   *
   * @param string $format - output date format.
   * @param string | DateTimeZone $timezone
   * @return string
   * @access public
   * @static
   */
  public static function yesterday($format, $timezone = null)
  {
    return (new DT('yesterday'))->getDate($format, $timezone);
  }
  
  /**
   * Returns the default time zone used by all date/time functions.
   *
   * @return string
   * @access public
   * @static
   */
  public static function getDefaultTimeZone()
  {
    return date_default_timezone_get();
  }
  
  /**
   * Sets the default time zone used by all date/time functions in a script.
   * This method returns FALSE if the timezone isn't valid, or TRUE otherwise.
   *
   * @param string $timezone - the time zone identifier, like UTC or Europe/Lisbon.
   * @return boolean
   * @access public
   * @static
   */
  public static function setDefaultTimeZone($timezone)
  {
    return date_default_timezone_set($timezone);
  }
  
  /**
   * Returns abbreviation of the given time zone.
   * The time zone can be an instance of DateTimeZone or a string.
   *
   * @param string | DateTimeZone $timezone
   * @return string
   * @access public
   * @static
   */
  public static function getTimeZoneAbbreviation($timezone)
  {
    return (new \DateTime('now', $timezone instanceof \DateTimeZone ? $timezone : new \DateTimeZone($timezone)))->format('T');
  }
  
  /**
   * Returns numerical index array with all timezone identifiers.
   * If $combine is TRUE the method returns associated array with keys which are time zones.
   *
   * @param boolean $combine - determines whether the method returns an associative array.
   * @param integer $what - one of DateTimeZone class constants.
   * @param string $country - a two-letter ISO 3166-1 compatible country code. This argument is only used when $what is set to DateTimeZone::PER_COUNTRY.
   * @return array
   * @access public
   * @static
   */
  public static function getTimeZoneList($combine = false, $what = \DateTimeZone::ALL, $country = null)
  {
    $list = \DateTimeZone::listIdentifiers($what, $country);
    if (!$combine) return $list;
    return array_combine($list, $list);
  }
  
  /**
   * Returns list (associative array) of timezones in GMT format.
   *
   * @param array $replacement - can be used to replace some timezone names by others.
   * @param integer $what - one of DateTimeZone class constants.
   * @param string $country - a two-letter ISO 3166-1 compatible country code. This argument is only used when $what is set to DateTimeZone::PER_COUNTRY.
   * @return array
   * @access public
   * @static
   */
  public static function getGMTTimeZoneList(array $replacement = null, $what = \DateTimeZone::ALL, $country = null)
  {
    $list = [];
    foreach (\DateTimeZone::listIdentifiers($what, $country) as $zone)
    {
      $tz = new \DateTimeZone($zone);
      $offset = $tz->getOffset(new \DateTime('now', $tz));
      $hours = str_pad(intval(abs($offset) / 3600), 2, '0', STR_PAD_LEFT);
      $minutes = str_pad(intval(abs($offset) % 3600 / 60), 2, '0', STR_PAD_LEFT);
      if (isset($replacement[$zone])) $tz = $replacement[$zone];
      else
      {
        $tz = explode('/', str_replace('_', ' ', $zone));
        array_shift($tz);
        $tz = implode(' - ', $tz);
      }
      $list[$zone] = '(GMT' . ($offset >= 0 ? '+' : '-') . $hours . ':' . $minutes . ') ' . $tz;
    }
    array_pop($list);
    uasort($list, function($a, $b)
    {
      $aa = (int)substr($a, 4, 3);
      $bb = (int)substr($b, 4, 3);
      if ($aa == $bb) return substr($a, 12) > substr($b, 12);
      return $aa > $bb;
    });
    return $list;
  }
  
  /**
   * Returns timezone string in GMT format.
   *
   * @param string | DateTimeZone $timezone - timezone name or object.
   * @return string
   * @access public
   * @static
   */
  public static function getGMTTimeZone($timezone)
  {
    $tz = $timezone instanceof \DateTimeZone ? $timezone : new \DateTimeZone($timezone);
    $offset = $tz->getOffset(new \DateTime('now', $tz));
    $hours = str_pad(intval(abs($offset) / 3600), 2, '0', STR_PAD_LEFT);
    $minutes = str_pad(intval(abs($offset) % 3600 / 60), 2, '0', STR_PAD_LEFT);
    $tz = explode('/', str_replace('_', ' ', $timezone));
    array_shift($tz);
    $tz = implode(' - ', $tz);
    return '(GMT' . ($offset >= 0 ? '+' : '-') . $hours . ':' . $minutes . ') ' . $tz;
  }
  
  /**
   * Converts the given date from one time zone and date format to another time zone and date format.
   *
   * @param string $date - string representing the date.
   * @param string | DateTimeZone $zoneOut - output date time zone.
   * @param string | DateTimeZone $zoneIn - input date time zone.
   * @param string $out - output format string.
   * @param string $in - input format string.
   * @return string
   * @access public
   * @static
   */
  public static function zone2zone($date, $zoneOut, $zoneIn, $out, $in = null)
  {
    $zoneIn = $zoneIn instanceof \DateTimeZone ? $zoneIn : new \DateTimeZone($zoneIn);
    $dt = $in === null ? new \DateTime($date, $zoneIn) : date_create_from_format($in, $date, $zoneIn);
    $dt->setTimeZone($zoneOut instanceof \DateTimeZone ? $zoneOut : new \DateTimeZone($zoneOut));
    return $dt->format($out);
  }

  /**
   * Constructor.
   * If $date is not specified the current date will be taken. 
   *
   * @param string | DateTimeInterface $date - string representing the date or an object implementing DateTimeInterface.
   * @param string $format - date format string.
   * @param string | DateTimeZone $timezone - date time zone.
   * @access public
   */
  public function __construct($date = 'now', $format = null, $timezone = null)
  {
    if ($date instanceof \DateTimeInterface) 
    {
      parent::__construct();
      $this->setTimestamp($date->getTimestamp());
      $this->setTimezone($date->getTimezone());
    }
    else if ($timezone === null)
    {
      if ($format === null) parent::__construct($date);
      else 
      {
        parent::__construct();
        $this->setTimestamp(date_create_from_format($format, $date)->getTimestamp());
      }
    }
    else
    {
      $timezone = $timezone instanceof \DateTimeZone ? $timezone : new \DateTimeZone($timezone);
      if ($format === null) parent::__construct($date, $timezone);
      else 
      {
        parent::__construct();
        $dt = date_create_from_format($format, $date, $timezone);
        $this->setTimestamp($dt->getTimestamp());
        $this->setTimezone($dt->getTimezone());
      }
    }
  }
  
  /**
   * Returns string representation of the date time object.
   * The method returns date in format RFC 2822.
   *
   * @return string
   * @access public
   */
  public function __toString()
  {
    return $this->format('r');
  }
  
  /**
   * Sets the time zone for the Aleph\Utils\DT object.
   * Returns the DT object for method chaining or FALSE on failure.
   *
   * @param string | DateTimeZone $timezone
   * @return self
   * @access public
   */
  public function setTimezone($timezone)
  {
    return parent::setTimezone($timezone instanceof \DateTimeZone ? $timezone : new \DateTimeZone($timezone));
  }
  
  /**
   * Returns the Unix timestamp representing the date.
   *
   * @param string | DateTimeZone $timezone
   * @return integer
   * @access public
   */
  public function getTimestamp($timezone = null)
  {
    if ($timezone === null) return parent::getTimestamp();
    $tz = $this->getTimezone();
    $date = parent::getTimestamp() - $this->getOffset();
    $this->setTimezone($timezone);
    $date += $this->getOffset();
    $this->setTimezone($tz);
    return $date;
  }
  
  /**
   * Returns date formatted according to the given format and specified timezone.
   *
   * @param string $format - format accepted by date().
   * @param string | DateTimeZone $timezone - timezone of the output date.
   * @return string
   * @access public
   */
  public function format($format, $timezone = null)
  {
    if ($timezone === null) return parent::format($format);
    $tz = $this->getTimezone();
    $this->setTimezone($timezone);
    $date = parent::format($format);
    $this->setTimezone($tz);
    return $date;
  }
  
  /**
   * Adds an amount of days, months, years, hours, minutes and seconds to a Aleph\Utils\DT object.
   * Returns the DT object for method chaining or FALSE on failure.
   *
   * @param string | \DateInterval - a \DateInterval object or interval string.
   * @return self
   * @access public
   * @link http://www.php.net/manual/en/class.dateinterval.php
   */
  public function add($interval)
  {
    return parent::add($interval instanceof \DateInterval ? $interval : \DateInterval::createFromDateString($interval));
  }
  
  /**
   * Subtracts an amount of days, months, years, hours, minutes and seconds from a Aleph\Utils\DT object.
   * Returns the DT object for method chaining or FALSE on failure.
   *
   * @param string | \DateInterval - a \DateInterval object or interval string.
   * @return self
   * @access public
   * @link http://www.php.net/manual/en/class.dateinterval.php
   */
  public function sub($interval)
  {
    return parent::sub($interval instanceof \DateInterval ? $interval : \DateInterval::createFromDateString($interval));
  }

  /**
   * Adds an amount of days to a Aleph\Utils\DT object. The amount of days can be negative.
   * Returns the DT object for method chaining or FALSE on failure.
   *
   * @param integer $day
   * @return self
   * @access public
   */
  public function addDay($day = 1)
  {
    return $this->modify(($day > 0 ? '+' : '-') . abs($day) . ' day');
  }

  /**
   * Adds an amount of months to a Aleph\Utils\DT object. The amount of months can as well be negative.
   * Returns the DT object for method chaining or FALSE on failure.
   *
   * @param integer $month
   * @return self
   * @access public
   */
  public function addMonth($month = 1)
  {
    return $this->modify(($month > 0 ? '+' : '-') . abs($month) . ' month');
  }

  /**
   * Adds an amount of years to a Aleph\Utils\DT object. The amount of years might as well be negative.
   * Returns the DT object for method chaining or FALSE on failure.
   *
   * @param integer $year
   * @return self
   * @access public
   */
  public function addYear($year = 1)
  {
    return $this->modify(($year > 0 ? '+' : '-') . abs($year) . ' year');
  }

  /**
   * Adds an amount of hours to a Aleph\Utils\DT object. The amount of hours might as well be negative.
   * Returns the DT object for method chaining or FALSE on failure.
   *
   * @param integer $hour
   * @return self
   * @access public
   */
  public function addHour($hour = 1)
  {
    return $this->modify(($hour > 0 ? '+' : '-') . abs($hour) . ' hour');
  }

  /**
   * Adds an amount of minutes to a Aleph\Utils\DT object. The amount of minutes  might as well be negative.
   * Returns the DT object for method chaining or FALSE on failure.
   *
   * @param integer $minute
   * @return self
   * @access public
   */
  public function addMinute($minute = 1)
  {
    return $this->modify(($minute > 0 ? '+' : '-') . abs($minute) . ' minute');
  }

  /**
   * Adds an amount of seconds to a Aleph\Utils\DT object. The amount of seconds might as well be negative.
   * Returns the DT object for method chaining or FALSE on failure.
   *
   * @param integer $second
   * @return self
   * @access public
   */
  public function addSecond($second = 1)
  {
    return $this->modify(($second > 0 ? '+' : '-') . abs($second) . ' second');
  }
  
  /**
   * Returns the difference between two Aleph\Utils\DT objects.
   * Returns the DT object for method chaining or FALSE on failure.
   *
   * @param mixed $date - the date to compare to.
   * @param boolean $absolute - determines whether to return absolute difference.
   * @return \DateInterval
   * @access public
   */
  public function diff($date, $absolute = false)
  {
    return parent::diff($date instanceof DT ? $date : new DT(), $absolute);
  }
  
  /**
   * Compares two given dates.
   * The method returns 1 if the second date larger than the first date.
   * The method returns -1 if the second date smaller than the first date.
   * The method returns 0 if the both dates are equal.
   *
   * @param mixed $date - the date to compare to.
   * @param string | DateTimeZone $timezone - timezone of the given dates.
   * @return integer
   * @access public
   * @static
   */
  public function cmp($date, $timezone = null)
  {
    if (!($date instanceof DT)) $date = new DT($date);
    $v1 = $this->getTimestamp($timezone);
    $v2 = $date->getTimestamp($timezone);
    if ($v2 > $v1) return 1;
    if ($v2 < $v1) return -1;
    return 0;
  }
  
  /**
   * Returns Generator object representing of date period.
   *
   * @param string | DateInterval $interval - the interval between recurrences within the period.
   * @param integer | DateTimeInterface $end - the number of recurrences or an object implementing DateTimeInterface.
   * @param boolean $excludeStartDate - determines whether the start date is excluded.
   * @return Generator
   * @access public
   */
  public function getPeriod($interval, $end, $excludeStartDate = false)
  {
    if (!($interval instanceof \DateInterval)) $interval = \DateInterval::createFromDateString($interval);
    if (!($end instanceof \DateTimeInterface)) $end = (int)$end;
    $dt = clone $this;
    if (!$excludeStartDate) $k = 0;
    else 
    {
      $dt->add($interval);
      $k = 1;
    }
    if ($end instanceof \DateTimeInterface)
    {
      while ($end->getTimestamp() >= $dt->getTimestamp())
      {
        yield clone $dt;
        $dt->add($interval);
      }
    }
    else
    {
      while ($end >= $k)
      {
        yield clone $dt;
        $dt->add($interval);
        $k++;
      }
    }
  }
}