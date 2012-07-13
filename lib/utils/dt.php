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
 * This class is designed for different manipulating with date and time.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.utils
 */
class DT
{
  /**
   * Instance of class \DateTime being responsible for representation of date and time.
   *
   * @var \DateTime
   * @access protected
   */
  protected $dt = null;
  
  /**
   * Validates the specified date format.
   * Method returns TRUE if the given string is a correct date formatted according to the specified format and FALSE otherwise.
   * If the date format is not specified the method will try to compare the given date with one of the possible formats.
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
   * Checks whether the given year is leap.
   * If a year is not given the current year will be checked.
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
   * Converts the given date formatted to the one format to another.
   * The method returns FALSE if the given date is not valid.
   *
   * @param string $date - string representing the date.
   * @param string $out - output date format string.
   * @param string $in - input date format string.
   * @return string
   * @access public
   * @static
   */
  public static function format($date, $out, $in = null)
  {
    $dt = $in !== null ? date_create_from_format($in, $date) : date_create($date);
    return ($dt) ? $dt->format($out) : false;
  }
  
  /**
   * Converts the given date formatted to the specified format to the sql date format.
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
    return self::format($date, $shortFormat ? 'Y-m-d' : 'Y-m-d H:i:s', $format);
  }

  /**
   * Converts the given date formatted to the sql date format to the given format.
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
    return self::format($date, $format, $shortFormat ? 'Y-m-d' : 'Y-m-d H:i:s');
  }
  
  /**
   * Converts the given date formatted to the specified format to the atom date format.
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
    return self::format($date, \DateTime::ATOM, $format);
  }
  
  /**
   * Converts the given date formatted to the atom date format to the given format.
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
    return self::format($date, $format, \DateTime::ATOM);
  }
  
  /**
   * Converts the given date formatted to the specified format to the RSS date format.
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
    return self::format($date, \DateTime::RSS, $format);
  }
  
  /**
   * Converts the given date formatted to the RSS date format to the given format.
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
    return self::format($date, $format, \DateTime::RSS);
  }
  
  /**
   * Converts the given date formatted to the specified format to the cookie date format.
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
    return self::format($date, \DateTime::COOKIE, $format);
  }
  
  /**
   * Converts the given date formatted to the cookie date format to the given format.
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
    return self::format($date, $format, \DateTime::COOKIE);
  }
  
  /**
   * Compares the two given dates.
   * The method returns 1 if the second date larger than the first date.
   * The method returns -1 if the second date smaller than the first date.
   * The method returns 0 if the both dates are equals.
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
   * Computes the difference in the date components between the two dates given in the same format.
   * The value of component of a date maybe one of following values: second, minute, hour, day, week, month, year.
   * By default the method returns difference between dates in days.
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
  public static function difference($date1, $date2, $format = null, $component = '%R%a')
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
   * This method returns array of the following elements: second, minute, hour, day.
   * The method returns FALSE if the given dates are not valid.
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
    $res = array();
    $res['day'] = floor($d / 86400);
    $d = $d % 86400;
    $res['hour'] = floor($d / 3600);
    $d = $d % 3600;
    $res['minute'] = floor($d / 60);
    $res['second'] = $d % 60;
    return $res;
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
   * The time zone can be an instance of \DateTimeZone or a string.
   *
   * @param string|\DateTimeZone $timezone
   * @return string
   * @access public
   * @static
   */
  public static function getTimeZoneAbbreviation($timezone)
  {
    $dt = new \DateTime('now', $timezone instanceof \DateTimeZone ? $timezone : new \DateTimeZone($timezone)); 
    return $dt->format('T');
  }
  
  /**
   * Returns numerically index array with all timezone identifiers.
   * If $combine is TRUE the method returns associated array with keys being time zones.
   *
   * @param boolean $combine
   * @param \DateTimeZone $what - one of \DateTimeZone class constants.
   * @param string $country - a two-letter ISO 3166-1 compatible country code. This argument is only used when $what is set to \DateTimeZone::PER_COUNTRY.
   * @return array
   * @static
   */
  public static function getTimeZoneList($combine = false, $what = \DateTimeZone::ALL, $country = null)
  {
    $list = \DateTimeZone::listIdentifiers($what, $country);
    if (!$combine) return $list;
    return array_combine($list, $list);
  }
  
  /**
   * Converts the given date from one time zone and date format to another time zone and date format.
   *
   * @param string $date - string representing the date.
   * @param string|\DateTimeZone $zoneOut - output date time zone.
   * @param string|\DateTimeZone $zoneIn - input date time zone.
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
   * If parameter $date is not specified the current date will be taken. 
   *
   * @param string|\DateTime $date - string representing the date or a \DateTime object.
   * @param string $format - date format string.
   * @param string|\DateTimeZone $timezone - date time zone.
   * @access public
   */
  public function __construct($date = 'now', $format = null, $timezone = null)
  {
    if ($date instanceof \DateTime) $this->dt = $date;
    else if ($timezone === null)
    {
      if ($format === null) $this->dt = new \DateTime($date);
      else $this->dt = date_create_from_format($format, $date);
    }
    else
    {
      $timezone = $timezone instanceof \DateTimeZone ? $timezone : new \DateTimeZone($timezone);
      if ($format === null) $this->dt = new \DateTime($date, $timezone);
      else $this->dt = date_create_from_format($format, $date, $timezone);
    }
  }
  
  /**
   * Returns a \DateTime object is associated with the DT object.
   *
   * @return \DateTime
   * @access public
   */
  public function getDateTimeObject()
  {
    return $this->dt;
  }
  
  /**
   * Returns the timezone offset in seconds from UTC on success or FALSE on failure.
   *
   * @return integer
   * @access public
   */
  public function getOffset()
  {
    return $this->dt->getOffset();
  }
  
  /**
   * Returns the Unix timestamp representing the date.
   *
   * @return integer
   * @access public
   */
  public function getTimestamp()
  {
    return $this->dt->getTimestamp();
  }
  
  /**
   * Sets the date and time based on an Unix timestamp.
   *
   * @param integer $timestamp
   * @return self
   * @access
   */
  public function setTimestamp($timestamp)
  {
    $this->dt->setTimestamp($timestamp);
    return $this;
  }
  
  /**
   * Returns a \DateTimeZone object on success or FALSE on failure.
   *
   * @return \DateTimeZone
   * @access public
   */
  public function getTimezone()
  {
    return $this->dt->getTimezone();
  }
  
  /**
   * Sets the time zone for the DT object.
   *
   * @param string|\DateTimeZone $timezone
   * @return self
   * @access public
   */
  public function setTimezone($timezone)
  {
    $this->dt->setTimezone($timezone instanceof \DateTimeZone ? $timezone : new \DateTimeZone($timezone));
    return $this;
  }
  
  /**
   * Returns the date formatted to the given format and for the specified time zone.
   *
   * @param string $format
   * @param string|\DateTimeZone $timezone
   * @return string
   * @access public
   */
  public function getDate($format, $timezone = null)
  {
    if ($timezone === null) return $this->dt->format($format);
    $timezone = $timezone instanceof \DateTimeZone ? $timezone : new \DateTimeZone($timezone);
    $tz = $this->dt->getTimezone();
    $this->dt->setTimezone($timezone);
    $date = $this->dt->format($format);
    $this->dt->setTimezone($tz);
    return $date;
  }
  
  /**
   * Sets the date.
   *
   * @param integer $year
   * @param integer $month
   * @param integer $day
   * @return self
   * @access public
   */
  public function setDate($year, $month, $day)
  {
    $this->dt->setDate($year, $month, $day);
    return $this;
  }
  
  /**
   * Sets the time.
   *
   * @param integer $hour
   * @param integer $minute
   * @param integer $second
   * @return self
   * @access public
   */
  public function setTime($hour, $minute, $second = 0)
  {
    $this->dt->setTime($hour, $minute, $second);
    return $this;
  }

  /**
   * Alters the timestamp of a DT object by incrementing or decrementing in a format accepted by strtotime(). 
   *
   * @param string $format
   * @return self
   * @access public
   * @link http://php.net/manual/en/datetime.modify.php
   */
  public function modify($format)
  {
    $this->dt->modify($format);
    return $this;
  }
  
  /**
   * Adds an amount of days, months, years, hours, minutes and seconds to a DT object.
   *
   * @param string|\DateInterval - a \DateInterval object or interval string.
   * @return self
   * @access public
   * @link http://www.php.net/manual/en/class.dateinterval.php
   */
  public function add($interval)
  {
    $this->dt->add($interval instanceof \DateInterval ? $interval : \DateInterval::createFromDateString($interval));
    return $this;
  }
  
  /**
   * Subtracts an amount of days, months, years, hours, minutes and seconds from a DT object.
   *
   * @param string|\DateInterval - a \DateInterval object or interval string.
   * @return self
   * @access public
   * @link http://www.php.net/manual/en/class.dateinterval.php
   */
  public function sub($interval)
  {
    $this->dt->sub($interval instanceof \DateInterval ? $interval : \DateInterval::createFromDateString($interval));
    return $this;
  }

  /**
   * Adds an amount of days to a DT object. The amount of days can be negative.
   *
   * @param integer $day
   * @return DT
   * @access public
   */
  public function addDay($day)
  {
    $this->dt->modify(($day > 0 ? '+' : '-') . abs($day) . ' day');
    return $this;
  }

  /**
   * Adds an amount of months to a DT object. The amount of months can be negative.
   *
   * @param integer $month
   * @return self
   * @access public
   */
  public function addMonth($month)
  {
    $this->dt->modify(($month > 0 ? '+' : '-') . abs($month) . ' month');
    return $this;
  }

  /**
   * Adds an amount of years to a DT object. The amount of years can be negative.
   *
   * @param integer $year
   * @return self
   * @access public
   */
  public function addYear($year)
  {
    $this->dt->modify(($year > 0 ? '+' : '-') . abs($year) . ' year');
    return $this;
  }

  /**
   * Adds an amount of hours to a DT object. The amount of hours can be negative.
   *
   * @param integer $hour
   * @return self
   * @access public
   */
  public function addHour($hour)
  {
    $this->dt->modify(($hour > 0 ? '+' : '-') . abs($hour) . ' hour');
    return $this;
  }

  /**
   * Adds an amount of minutes to a DT object. The amount of minutes can be negative.
   *
   * @param integer $minute
   * @return self
   * @access public
   */
  public function addMinute($minute)
  {
    $this->dt->modify(($minute > 0 ? '+' : '-') . abs($minute) . ' minute');
    return $this;
  }

  /**
   * Adds an amount of seconds to a DT object. The amount of seconds can be negative.
   *
   * @param integer $second
   * @return self
   * @access public
   */
  public function addSecond($second)
  {
    $this->dt->modify(($second > 0 ? '+' : '-') . abs($second) . ' second');
    return $this;
  }
  
  /**
   * Returns the difference between two DT objects.
   *
   * @param \Aleph\Utils\DT $date - the date to compare to.
   * @param boolean $absolute - determines whether to return absolute difference.
   */
  public function diff(DT $date, $absolute = false)
  {
    return $this->dt->diff($date->getDateTimeObject(), $absolute);
  }
  
  /**
   * Returns \Closure object representating of date period.
   *
   * @param string|\DateInterval $interval
   * @param integer|\Aleph\Utils\DT - the number of recurrences or DT object.
   * @param boolean $excludeStartDate - determines whether the start date is excluded.
   * @return \Closure
   * @access public
   */
  public function getPeriod($interval, $end, $excludeStartDate = false)
  {
    if (!($interval instanceof \DateInterval)) $interval = \DateInterval::createFromDateString($interval);
    if ($end instanceof DT) $end = $end->getDateTimeObject();
    $dp = new \DatePeriod($this->dt, $interval, $end, $excludeStartDate ? \DatePeriod::EXCLUDE_START_DATE : null);
    $period = array(); foreach ($dp as $dt) $period[] = new self($dt);
    return function() use($period)
    {
      static $p;
      if (empty($p)) $p = $period;
      $dt = current($p);
      if ($dt !== false) next($p);
      return $dt;
    };
  }
}