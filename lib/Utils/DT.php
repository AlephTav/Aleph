<?php
/**
 * Copyright (c) 2013 - 2015 Aleph Tav
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
 * @copyright Copyright &copy; 2013 - 2015 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Utils;

/**
 * This class is designed for different manipulations with date and time.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.1.1
 * @package aleph.utils
 */
class DT extends \DateTime
{
    /**
     * Error message templates.
     */
    const ERR_DT_1 = 'Invalid timezone "%s".';
    
    /**
     * Default datetime format to use for __toString() and format() methods.
     */
    const DEFAULT_DATETIME_FORMAT = 'Y-m-d H:i:s';
    
    /**
     * Default output date format.
     *
     * @var string $defaultDateTimeformat
     * @access protected
     * @static
     */
    protected static $defaultDateTimeformat = self::DEFAULT_DATETIME_FORMAT;
    
    /**
     * Validates the specified date format.
     * Method returns TRUE if the given string is a correct date formatted according to the specified format and FALSE otherwise.
     * If the date format is not specified the method will try to match the given date with one of the possible formats.
     *
     * @param string $date - string representing the date.
     * @param string $format - the date format string.
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
     * @param string $format - the date format string.
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
     * @param string $format - the date format string.
     * @return integer
     * @access public
     * @static
     */
    public static function getHour($date, $format)
    {
        return static::getInfo($date, $format)['hour'];
    }

    /**
     * Returns value of the minute component of the given date.
     *
     * @param string $date - string representing the date.
     * @param string $format - the date format string.
     * @return integer
     * @access public
     * @static
     */
    public static function getMinute($date, $format)
    {
        return static::getInfo($date, $format)['minute'];
    }

    /**
     * Returns value of the second component of the given date.
     *
     * @param string $date - string representing the date.
     * @param string $format - the date format string.
     * @return integer
     * @access public
     * @static
     */
    public static function getSecond($date, $format)
    {
        return static::getInfo($date, $format)['second'];
    }
  
    /**
     * Returns value of the fraction component of the given date.
     *
     * @param string $date - string representing the date.
     * @param string $format - the date format string.
     * @return integer
     * @access public
     * @static
     */
    public static function getFraction($date, $format)
    {
        return static::getInfo($date, $format)['fraction'];
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
        return static::getInfo($date, $format)['day'];
    }

    /**
     * Returns value of the month component of the given date.
     *
     * @param string $date - string representing the date.
     * @param string $format - the date format string.
     * @return integer
     * @access public
     * @static
     */
    public static function getMonth($date, $format)
    {
        return static::getInfo($date, $format)['month'];
    }

    /**
     * Returns value of the year component of the given date.
     *
     * @param string $date - string representing the date.
     * @param string $format - the date format string.
     * @return integer
     * @access public
     * @static
     */
    public static function getYear($date, $format)
    {
        return static::getInfo($date, $format)['year'];
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
        $month = (int)$month;
        $year = (int)$year;
        if (!checkdate($month, 1, $year))
        {
            return false;
        }
        if ($month == 2)
        {
            $days = 28;
            if (static::isLeapYear($year))
            {
                $days++;
            }
        }
        else if ($month < 8)
        {
            $days = 30 + $month % 2;
        }
        else
        {
            $days = 31 - $month % 2;
        }
        return $days;
    }
  
    /**
     * Returns unix timestamp for the given date formatted to the specified format.
     * If the date format is not specified the method will try to parse the date from one of the suitable formats.
     *
     * @param string|DateTimeInterface $date - string representing the date.
     * @param string $format - the date format string.
     * @return integer
     * @access public
     * @static
     */
    public static function toTimestamp($date, $format = null)
    {
        return (new static($date, null, $format))->getTimestamp();
    }

    /**
     * Converts the given date from one format to another.
     * If the input date format is not specified the method will try to parse the date from one of the suitable formats.
     *
     * @param string|DateTimeInterface $date - string representing the date.
     * @param string $outputFormat - the output date format string.
     * @param string $inputFormat - the input date format string.
     * @return string
     * @access public
     * @static
     */
    public static function toDate($date, $outputFormat = null, $inputFormat = null)
    {
        return (new static($date, null, $inputFormat))->format($outputFormat);
    }
  
    /**
     * Converts the given date from the specified format to the MySQL date format ("Y-m-d" or "Y-m-d H:i:s").
     * If the input date format is not specified the method will try to parse the date from one of the suitable formats.
     *
     * @param string|DateTimeInterface $date - string representing the date.
     * @param string $format - the input date format string.
     * @param boolean $shortFormat - if TRUE the short sql date format (Y-m-d) will be used.
     * @return string 
     * @access public
     * @static
     */
    public static function toSQL($date, $format = null, $shortFormat = false)
    {
        return static::toDate($date, $shortFormat ? 'Y-m-d' : 'Y-m-d H:i:s', $format);
    }

    /**
     * Converts the given date from the MySQL date format ("Y-m-d" or "Y-m-d H:i:s") to the specified format.
     *
     * @param string|DateTimeInterface $date - string representing the date.
     * @param string $format - the output date format string.
     * @param bool $shortFormat - if TRUE the short sql date format (Y-m-d) will be used.
     * @return string 
     * @access public
     * @static
     */
    public static function fromSQL($date, $format = null, $shortFormat = false)
    {
        return static::toDate($date, $format, $shortFormat ? 'Y-m-d' : 'Y-m-d H:i:s');
    }
  
    /**
     * Converts the given date from specified format to the atom date format.
     *
     * @param string $date - string representing the date.
     * @param string $format - the input date format string.
     * @return string 
     * @access public
     * @static
     * @static
     */
    public static function toAtom($date, $format = null)
    {
        return static::toDate($date, static::ATOM, $format);
    }
  
    /**
     * Converts the given date formatted from the atom date format to the given format.
     *
     * @param string|DateTimeInterface $date - string representing the date.
     * @param string $format - the output date format string.
     * @return string 
     * @access public
     * @static
     */
    public static function fromAtom($date, $format = null)
    {
        return static::toDate($date, $format, static::ATOM);
    }
  
    /**
     * Converts the given date from specified format to the RSS date format.
     * If the input date format is not specified the method will try to parse the date from one of the suitable formats.
     *
     * @param string|DateTimeInterface $date - string representing the date.
     * @param string $format - the input date format string.
     * @return string 
     * @access public
     * @static
     */
    public static function toRSS($date, $format = null)
    {
        return static::toDate($date, static::RSS, $format);
    }
  
    /**
     * Converts the given date from RSS date format to the given format.
     *
     * @param string|DateTimeInterface $date - string representing the date.
     * @param string $format - the output date format string.
     * @return string 
     * @access public
     * @static
     */
    public static function fromRSS($date, $format = null)
    {
        return static::toDate($date, $format, static::RSS);
    }
  
    /**
     * Converts the given date from specified format to the cookie date format.
     * If the input date format is not specified the method will try to parse the date from one of the suitable formats.
     *
     * @param string|DateTimeInterface $date - string representing the date.
     * @param string $format - the input date format string.
     * @return string 
     * @access public
     * @static
     */
    public static function toCookie($date, $format = null)
    {
        return static::toDate($date, static::COOKIE, $format);
    }
  
    /**
     * Converts the given date from cookie date format to the given format.
     *
     * @param string|DateTimeInterface $date - string representing the date.
     * @param string $format - the output date format string.
     * @return string 
     * @access public
     * @static
     */
    public static function fromCookie($date, $format = null)
    {
        return static::toDate($date, $format, static::COOKIE);
    }
    
    /**
     * Converts the given date from specified format to the W3C date format.
     * If the input date format is not specified the method will try to parse the date from one of the suitable formats.
     *
     * @param string|DateTimeInterface $date - string representing the date.
     * @param string $format - the input date format string.
     * @return string 
     * @access public
     * @static
     */
    public static function toW3C($date, $format = null)
    {
        return static::toDate($date, static::W3C, $format);
    }
    
    /**
     * Converts the given date from the W3C date format to the given format.
     *
     * @param string|DateTimeInterface $date - string representing the date.
     * @param string $format - the output date format string.
     * @return string 
     * @access public
     * @static
     */
    public static function fromW3C($date, $format = null)
    {
        return static::toDate($date, $format, static::W3C);
    }
    
    /**
     * Converts the given date from specified format to the ISO8601 date format.
     * If the input date format is not specified the method will try to parse the date from one of the suitable formats.
     *
     * @param string|DateTimeInterface $date - string representing the date.
     * @param string $format - the input date format string.
     * @return string 
     * @access public
     * @static
     */
    public static function toISO8601($date, $format = null)
    {
        return static::toDate($date, static::ISO8601, $format);
    }
    
    /**
     * Converts the given date from the ISO8601 date format to the given format.
     *
     * @param string|DateTimeInterface $date - string representing the date.
     * @param string $format - the output date format string.
     * @return string 
     * @access public
     * @static
     */
    public static function fromISO8601($date, $format = null)
    {
        return static::toDate($date, $format, static::ISO8601);
    }
    
    /**
     * Converts the given date from specified format to the RFC2822 date format.
     * If the input date format is not specified the method will try to parse the date from one of the suitable formats.
     *
     * @param string|DateTimeInterface $date - string representing the date.
     * @param string $format - the input date format string.
     * @return string 
     * @access public
     * @static
     */
    public static function toRFC2822($date, $format = null)
    {
        return static::toDate($date, static::RFC2822, $format);
    }
    
    /**
     * Converts the given date from the ISO8601 date format to the given format.
     *
     * @param string|DateTimeInterface $date - string representing the date.
     * @param string $format - the output date format string.
     * @return string 
     * @access public
     * @static
     */
    public static function fromRFC2822($date, $format = null)
    {
        return static::toDate($date, $format, static::RFC2822);
    }
  
    /**
     * Compares two given dates.
     * The method returns 1 if the second date larger than the first date.
     * The method returns -1 if the second date smaller than the first date.
     * The method returns 0 if the both dates are equal.
     *
     * @param string|DateTimeInterface $date1 - string representing the first date.
     * @param string|DateTimeInterface $date2 - string representing the second date.
     * @param string $format - the date format string.
     * @return integer
     * @access public
     * @static
     */
    public static function compare($date1, $date2, $format = null)
    {
        $v1 = static::toTimestamp($date1, $format);
        $v2 = static::toTimestamp($date2, $format);
        if ($v2 > $v1) return 1;
        if ($v2 < $v1) return -1;
        return 0;
    }
  
    /**
     * Computes the difference between two dates given in the same format.
     * The method returns difference between dates in days by default.
     *
     * @param string|DateTimeInterface $date1 - string representing the first date.
     * @param string|DateTimeInterface $date2 - string representing the second date.
     * @param string $format - the date format string.
     * @param boolean $absolute - determines whether the difference should be forced to be positive.
     * @return array
     * @access public
     * @static
     */
    public static function difference($date1, $date2, $format = null, $absolute = true)
    {
        $date1 = new static($date1, null, $format);
        $date2 = new static($date2, null, $format);
        $d = $date1->getTimestamp() - $date2->getTimestamp();
        $d = $absolute ? abs($d) : $d;
        $days = (int)date_diff($date2, $date1, $absolute)->format('%r%a');
        $res['year'] = (int)date_diff($date2, $date1, $absolute)->format('%r%y');
        $res['month'] = 12 * $res['year'] + (int)date_diff($date2, $date1, $absolute)->format('%r%m');
        $res['weeks'] = (int)($days / 7);
        $res['day'] = $days;
        $res['hour'] = (int)($d / 3600);
        $res['minute'] = (int)($d / 60);
        $res['second'] = (int)$d;
        return $res;
    }
    
    /**
     * Returns the difference between two dates in years.
     *
     * @param string|DateTimeInterface $date1 - string representing the first date.
     * @param string|DateTimeInterface $date2 - string representing the second date.
     * @param string $format - the date format string.
     * @param boolean $absolute - determines whether the difference should be forced to be positive.
     * @return integer
     * @access public
     */
    public static function diffInYears($date1, $date2, $format = null, $absolute = true)
    {
        $date1 = new static($date1, null, $format);
        $date2 = new static($date2, null, $format);
        return (int)date_diff($date2, $date1, $absolute)->format('%r%y');
    }
    
    /**
     * Returns the difference between two dates in months.
     *
     * @param string|DateTimeInterface $date1 - string representing the first date.
     * @param string|DateTimeInterface $date2 - string representing the second date.
     * @param string $format - the date format string.
     * @param boolean $absolute - determines whether the difference should be forced to be positive.
     * @return integer
     * @access public
     */
    public static function diffInMonths($date1, $date2, $format = null, $absolute = true)
    {
        $date1 = new static($date1, null, $format);
        $date2 = new static($date2, null, $format);
        return 12 * (int)date_diff($date2, $date1, $absolute)->format('%r%y') + (int)date_diff($date2, $date1, $absolute)->format('%r%m');
    }
    
    /**
     * Returns the difference between two dates in weeks.
     *
     * @param string|DateTimeInterface $date1 - string representing the first date.
     * @param string|DateTimeInterface $date2 - string representing the second date.
     * @param string $format - the date format string.
     * @param boolean $absolute - determines whether the difference should be forced to be positive.
     * @return integer
     * @access public
     */
    public static function diffInWeeks($date1, $date2, $format = null, $absolute = true)
    {
        $date1 = new static($date1, null, $format);
        $date2 = new static($date2, null, $format);
        return (int)(date_diff($date2, $date1, $absolute)->format('%r%a') / 7);
    }
    
    /**
     * Returns the difference between two dates in days.
     *
     * @param string|DateTimeInterface $date1 - string representing the first date.
     * @param string|DateTimeInterface $date2 - string representing the second date.
     * @param string $format - the date format string.
     * @param boolean $absolute - determines whether the difference should be forced to be positive.
     * @return integer
     * @access public
     */
    public static function diffInDays($date1, $date2, $format = null, $absolute = true)
    {
        $date1 = new static($date1, null, $format);
        $date2 = new static($date2, null, $format);
        return (int)date_diff($date2, $date1, $absolute)->format('%r%a');
    }
    
    /**
     * Returns the difference between two dates in hours.
     *
     * @param string|DateTimeInterface $date1 - string representing the first date.
     * @param string|DateTimeInterface $date2 - string representing the second date.
     * @param string $format - the date format string.
     * @param boolean $absolute - determines whether the difference should be forced to be positive.
     * @return integer
     * @access public
     */
    public static function diffInHours($date1, $date2, $format = null, $absolute = true)
    {
        $date1 = new static($date1, null, $format);
        $date2 = new static($date2, null, $format);
        $d = $date1->getTimestamp() - $date2->getTimestamp();
        $d = $absolute ? abs($d) : $d;
        return (int)($d / 3600);
    }
    
    /**
     * Returns the difference between two dates in minutes.
     *
     * @param string|DateTimeInterface $date1 - string representing the first date.
     * @param string|DateTimeInterface $date2 - string representing the second date.
     * @param string $format - the date format string.
     * @param boolean $absolute - determines whether the difference should be forced to be positive.
     * @return integer
     * @access public
     */
    public static function diffInMinutes($date1, $date2, $format = null, $absolute = true)
    {
        $date1 = new static($date1, null, $format);
        $date2 = new static($date2, null, $format);
        $d = $date1->getTimestamp() - $date2->getTimestamp();
        $d = $absolute ? abs($d) : $d;
        return (int)($d / 60);
    }
    
    /**
     * Returns the difference between two dates in seconds.
     *
     * @param string|DateTimeInterface $date1 - string representing the first date.
     * @param string|DateTimeInterface $date2 - string representing the second date.
     * @param string $format - the date format string.
     * @param boolean $absolute - determines whether the difference should be forced to be positive.
     * @return integer
     * @access public
     */
    public static function diffInSeconds($date1, $date2, $format = null, $absolute = true)
    {
        $date1 = new static($date1, null, $format);
        $date2 = new static($date2, null, $format);
        $d = $date1->getTimestamp() - $date2->getTimestamp();
        return $absolute ? (int)abs($d) : (int)$d;
    }
  
    /**
     * Calculates the difference between the two dates given in the same format.
     * If the specific date format is not set the method will try to parse dates into one of the suitable formats.
     * The method returns array of the following elements: second, minute, hour, day.
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
        $date1 = new static($date1, null, $format);
        $date2 = new static($date2, null, $format);
        list($year, $month, $day, $hour, $minute, $second) = explode(' ', date_diff($date1, $date2, true)->format('%r%y %m %d %h %i %s'));
        return compact('year', 'month', 'day', 'hour', 'minute', 'second');
    }
  
    /**
     * Returns text representation of the time elapsed since the specified date.
     *
     * @param string|DateTimeInterface $date - the start date.
     * @param string|DateTimeInterface $now - the second date.
     * @param string $format - the date format string.
     * @return string
     * @access public
     */
    public static function formattedDifference($date1, $date2, $format = null)
    {
        $isNow = $date1 === 'now' || $date2 === 'now';
        $dt = new static($date1, null, $format);
        $interval = $dt->diff(new DT($date2, null, $format));
        $parts = ['y' => 'year', 'm' => 'month', 'd' => 'day', 'h' => 'hour', 'i' => 'minute', 's' => 'second'];
        foreach ($parts as $part => $name)
        {
            if ($interval->{$part} > 0)
            {
                if ($interval->{$part} == 1) $res = 'a ' . $name;
                else $res = $interval->{$part} . ' ' . $name . 's';
                return $res . ($isNow ? ' ago' : ($interval->invert == 1 ? ' after' : ' before'));
            }
        }
        return 'just now';
    }
  
    /**
     * Returns today's date.
     *
     * @param string $format - the output date format.
     * @param string|DateTimeZone $timezone
     * @return string
     * @access public
     * @static
     */
    public static function now($format = null, $timezone = null)
    {
        return (new static('now'))->format($format, $timezone);
    }
  
    /**
     * Returns tomorrow's date.
     *
     * @param string $format - the output date format.
     * @param string|DateTimeZone $timezone
     * @return string
     * @access public
     * @static
     */
    public static function tomorrow($format = null, $timezone = null)
    {
        return (new static('tomorrow'))->format($format, $timezone);
    }
  
    /**
     * Returns yesterday's date.
     *
     * @param string $format - the output date format.
     * @param string|DateTimeZone $timezone
     * @return string
     * @access public
     * @static
     */
    public static function yesterday($format = null, $timezone = null)
    {
        return (new static('yesterday'))->format($format, $timezone);
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
     * @param string|DateTimeZone $timezone
     * @return string
     * @access public
     * @static
     */
    public static function getTimeZoneAbbreviation($timezone)
    {
        return (new static('now', $timezone))->format('T');
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
        return $combine ? array_combine($list, $list) : $list;
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
            if (isset($replacement[$zone]))
            {
                $tz = $replacement[$zone];
            }
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
            if ($aa == $bb)
            {
                return substr($a, 12) > substr($b, 12);
            }
            return $aa > $bb;
        });
        return $list;
    }
  
    /**
     * Returns timezone string in GMT format.
     *
     * @param string|DateTimeZone $timezone - timezone name or object.
     * @return string
     * @access public
     * @static
     */
    public static function getGMTTimeZone($timezone)
    {
        $tz = static::normalizeTimeZone($timezone);
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
     * @param string|DateTimeZone $outputTimezone - output date time zone.
     * @param string|DateTimeZone $inputTimezone - input date time zone.
     * @param string $outputFormat - the output format string.
     * @param string $inputFormat - the input format string.
     * @return string
     * @access public
     * @static
     */
    public static function zone2zone($date, $outputTimezone, $inputTimezone, $outputFormat = null, $inputFormat = null)
    {
        return (new static($date, $inputTimezone, $inputFormat))->format($outputFormat, $outputTimezone);
    }
  
    /**
     * Create a DT instance from a specific format.
     *
     * @param string $format - the date format string.
     * @param string|DateTimeInterface $date - string representing the date or an object implementing DateTimeInterface.
     * @param string|DateTimeZone $timezone - the desired time zone.
     * @return static
     * @access public
     * @static
     */
    public static function createFromFormat($format, $date, $timezone = null)
    {
        return new static($date, $timezone, $format);
    }
    
    /**
     * Create a datetime instance from a timestamp.
     *
     * @param integer $timestamp
     * @param string|DateTimeZone $timezone
     * @return static
     * @access public
     * @static
     */
    public static function createFromTimestamp($timestamp, $timezone = null)
    {
        return (new static('now', $timezone))->setTimestamp($timestamp);
    }
    
    /**
     * Converts the given timezone string to DateTimeZone object.
     *
     * @param string $timezone|DateTimeZone - the timezone to convert.
     * @return DateTimeZone
     * @throws InvalidArgumentException
     * @access public
     * @static
     */
    public static function normalizeTimeZone($timezone)
    {
        if (!($timezone instanceof \DateTimeZone))
        {
            if ($timezone === null)
            {
                return new \DateTimeZone(date_default_timezone_get());
            }
            try
            {
                $timezone = new \DateTimeZone($timezone);
            }
            catch (\Exception $e)
            {
                throw new \InvalidArgumentException(sprintf(static::ERR_DT_1, $timezone));
            }
        }
        return $timezone;
    }

    /**
     * Constructor.
     * If $date is not specified the current date will be taken. 
     *
     * @param string|DateTimeInterface $date - string representing the date or an object implementing DateTimeInterface.
     * @param string|DateTimeZone $timezone - date time zone.
     * @param string $format - the date format string.
     * @access public
     */
    public function __construct($date = 'now', $timezone = null, $format = null)
    {
        if (!($date instanceof \DateTimeInterface))
        {
            $timezone = static::normalizeTimeZone($timezone);
            if ($format === null)
            {
                $date = date_create($date, $timezone);
            }
            else
            {
                $date = date_create_from_format($format, $date, $timezone);
            }
            if ($date === false)
            {
                throw new \InvalidArgumentException(implode(PHP_EOL, static::getLastErrors()['errors']));
            }
        }
        else if ($timezone !== null)
        {
            $date->setTimezone(static::normalizeTimeZone($timezone));
        }
        parent::__construct($date->format('Y-m-d H:i:s.u'), $date->getTimezone());
    }
    
    /**
     * Returns a copy of the datetime instance.
     *
     * @return static
     * @access public
     */
    public function copy()
    {
        return new static($this);
    }
  
    /**
     * Returns string representation of the date time object.
     * It returns date in self::DEFAULT_DATETIME_FORMAT
     *
     * @return string
     * @access public
     */
    public function __toString()
    {
        return $this->format();
    }
  
    /**
     * Sets the time zone for the Aleph\Utils\DT object.
     * Returns the DT object for method chaining or FALSE on failure.
     *
     * @param string|DateTimeZone $timezone
     * @return static
     * @access public
     */
    public function setTimezone($timezone)
    {
        return parent::setTimezone(static::normalizeTimeZone($timezone));
    }
  
    /**
     * Returns the Unix timestamp representing the date.
     *
     * @param string|DateTimeZone $timezone
     * @return integer
     * @access public
     */
    public function getTimestamp($timezone = null)
    {
        if ($timezone === null)
        {
            return parent::getTimestamp();
        }
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
     * @param string $format - the format accepted by date().
     * @param string|DateTimeZone $timezone - the timezone of the output date.
     * @return string
     * @access public
     */
    public function format($format = null, $timezone = null)
    {
        $format = $format === null ? static::$defaultDateTimeformat : $format;
        if ($timezone === null)
        {
            return parent::format($format);
        }
        $tz = $this->getTimezone();
        $this->setTimezone($timezone);
        $date = parent::format($format);
        $this->setTimezone($tz);
        return $date;
    }
    
    /**
     * Gets or sets the number of seconds.
     *
     * @param integer $second
     * @return integer|static
     * @access public
     */
    public function second($second = null)
    {
        if ($second === null)
        {
            return (int)$this->format('s');
        }
        return $this->setTime($this->hour(), $this->minute(), $second);
    }
    
    /**
     * Gets or sets the number of minutes.
     *
     * @param integer $minute
     * @return integer|static
     * @access public
     */
    public function minute($minute = null)
    {
        if ($minute === null)
        {
            return (int)$this->format('i');
        }
        return $this->setTime($this->hour(), $minute, $this->second());
    }
    
    /**
     * Gets or sets the number of hours.
     *
     * @param integer $hour
     * @return integer|static
     * @access public
     */
    public function hour($hour = null)
    {
        if ($hour === null)
        {
            return (int)$this->format('G');
        }
        return $this->setTime($hour, $this->minute(), $this->second());
    }
    
    /**
     * Gets or sets the number of days.
     *
     * @param integer $day
     * @return integer|static
     * @access public
     */
    public function day($day = null)
    {
        if ($day === null)
        {
            return (int)$this->format('j');
        }
        return $this->setDate($this->year(), $this->month(), $day);
    }
    
    /**
     * Gets or sets the number of months.
     *
     * @param integer $month
     * @return integer|static
     * @access public
     */
    public function month($month = null)
    {
        if ($month === null)
        {
            return (int)$this->format('n');
        }
        return $this->setDate($this->year(), $month, $this->day());
    }
    
    /**
     * Gets or sets the number of years.
     *
     * @param integer $year
     * @return integer|static
     * @access public
     */
    public function year($year = null)
    {
        if ($year === null)
        {
            return (int)$this->format('Y');
        }
        return $this->setDate($year, $this->month(), $this->day());
    }
    
    /**
     * Determines if the datetime instance is today.
     *
     * @return boolean
     * @access public
     */
    public function isToday()
    {
        return $this->format('Y-m-d') === static::now('Y-m-d');
    }
    
    /**
     * Determines if the datetime instance is tomorrow.
     *
     * @return boolean
     * @access public
     */
    public function isTomorrow()
    {
        return $this->format('Y-m-d') === static::tomorrow('Y-m-d');
    }
    
    /**
     * Determines if the datetime instance is yesterday.
     *
     * @return boolean
     * @access public
     */
    public function isYesterday()
    {
        return $this->format('Y-m-d') === static::yesterday('Y-m-d');
    }
    
    /**
     * Determines if the datatime instance is in the future.
     *
     * @return boolean
     * @access public
     */
    public function isFuture()
    {
        return $this > new static('now', $this->getTimezone());
    }
    
    /**
     * Determines if the datetime instance is in the past.
     *
     * @return boolean
     * @access public
     */
    public function isPast()
    {
        return $this < new static('now', $this->getTimezone());
    }
    
    /**
     * Determines if the datetime instance has a leap year.
     *
     * @return boolean
     * @access public
     */
    public function hasLeapYear()
    {
        return $this->format('L') == 1;
    }
  
    /**
     * Adds an amount of days, months, years, hours, minutes and seconds to a Aleph\Utils\DT object.
     * Returns the DT object for method chaining or FALSE on failure.
     *
     * @param string|DateInterval - a DateInterval object or interval string.
     * @return static
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
     * @param string|DateInterval - a DateInterval object or interval string.
     * @return static
     * @access public
     * @link http://www.php.net/manual/en/class.dateinterval.php
     */
    public function sub($interval)
    {
        return parent::sub($interval instanceof \DateInterval ? $interval : \DateInterval::createFromDateString($interval));
    }
    
    /**
     * Returns the difference between the datetime instance and the given date.
     *
     * @param mixed $date - the date to compare to.
     * @param boolean $absolute - determines whether to return absolute difference.
     * @return DateInterval
     * @access public
     */
    public function diff($date, $absolute = false)
    {
        return parent::diff(new static($date), $absolute);
    }

    /**
     * Adds an amount of days to the datetime instance. The amount of days can be negative.
     * Returns the DT object for method chaining or FALSE on failure.
     *
     * @param integer $day
     * @return static
     * @access public
     */
    public function addDay($day = 1)
    {
        return $this->modify((int)$day . ' day');
    }

    /**
     * Adds an amount of months to the datetime instance. The amount of months can as well be negative.
     * Returns the DT object for method chaining or FALSE on failure.
     *
     * @param integer $month
     * @return static
     * @access public
     */
    public function addMonth($month = 1)
    {
        return $this->modify((int)$month . ' month');
    }

    /**
     * Adds an amount of years to the datetime instance. The amount of years might as well be negative.
     * Returns the DT object for method chaining or FALSE on failure.
     *
     * @param integer $year
     * @return static
     * @access public
     */
    public function addYear($year = 1)
    {
        return $this->modify((int)$year . ' year');
    }

    /**
     * Adds an amount of hours to the datetime instance. The amount of hours might as well be negative.
     * Returns the DT object for method chaining or FALSE on failure.
     *
     * @param integer $hour
     * @return static
     * @access public
     */
    public function addHour($hour = 1)
    {
        return $this->modify((int)$hour . ' hour');
    }

    /**
     * Adds an amount of minutes to the datetime instance. The amount of minutes  might as well be negative.
     * Returns the DT object for method chaining or FALSE on failure.
     *
     * @param integer $minute
     * @return static
     * @access public
     */
    public function addMinute($minute = 1)
    {
        return $this->modify((int)$minute . ' minute');
    }

    /**
     * Adds an amount of seconds to the datetime instance. The amount of seconds might as well be negative.
     * Returns the DT object for method chaining or FALSE on failure.
     *
     * @param integer $second
     * @return static
     * @access public
     */
    public function addSecond($second = 1)
    {
        return $this->modify((int)$second . ' second');
    }
    
    /**
     * Resets the time to 00:00:00
     *
     * @return static
     * @access public
     */
    public function startOfDay()
    {
        return $this->setTime(0, 0, 0);
    }
    
    /**
     * Resets the time to 23:59:59
     *
     * @return static
     * @access public
     */
    public function endOfDay()
    {
        return $this->setTime(23, 59, 59);
    }
    
     /**
     * Resets the date to the first day of the month and the time to 00:00:00
     *
     * @return static
     * @access public
     */
    public function startOfMonth()
    {
        return $this->startOfDay()->day(1);
    }
    
    /**
     * Resets the date to end of the month and time to 23:59:59
     *
     * @return static
     * @access public
     */
    public function endOfMonth()
    {
        return $this->day($this->format('t'))->endOfDay();
    }
    
    /**
     * Resets the date to the first day of the year and the time to 00:00:00
     *
     * @return static
     * @access public 
     */
    public function startOfYear()
    {
        return $this->month(1)->startOfMonth();
    }
    
    /**
     * Resets the date to end of the year and time to 23:59:59
     *
     * @return static
     * @access public
     */
    public function endOfYear()
    {
        return $this->month(12)->endOfMonth();
    }
  
    /**
     * Returns Generator object representing of date period.
     *
     * @param string|DateInterval $interval - the interval between recurrences within the period.
     * @param integer|DateTimeInterface $end - the number of recurrences or an object implementing DateTimeInterface.
     * @param boolean $excludeStartDate - determines whether the start date is excluded.
     * @return Generator
     * @access public
     */
    public function getPeriod($interval, $end, $excludeStartDate = false)
    {
        if (!($interval instanceof \DateInterval))
        {
            $interval = \DateInterval::createFromDateString($interval);
        }
        if (!($end instanceof \DateTimeInterface))
        {
            $end = (int)$end;
        }
        $dt = clone $this;
        if (!$excludeStartDate)
        {
            $k = 0;
        }
        else 
        {
            $dt->add($interval);
            $k = 1;
        }
        if ($end instanceof \DateTimeInterface)
        {
            while ($end >= $dt)
            {
                yield (clone $dt);
                $dt->add($interval);
            }
        }
        else
        {
            while ($end >= $k)
            {
                yield (clone $dt);
                $dt->add($interval);
                $k++;
            }
        }
    }
}