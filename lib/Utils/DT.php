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
     * @param string $date A string representing the date.
     * @param string $format The date format string.
     * @return bool
     */
    public static function isDate(string $date, string $format = null) : bool
    {
        return ($format !== null ? date_create_from_format($format, $date) : date_create($date)) !== false;
    }

    /**
     * Checks whether the given year is a leap year.
     * If a year is not specified the current year will be checked.
     *
     * @param int $year
     * @return bool
     */
    public static function isLeapYear(int $year = null) : bool
    {
        return checkdate(2, 29, $year === null ? date('Y') : (int)$year);
    }

    /**
     * Returns associative array with detailed info about given date.
     *
     * @param string $date A string representing the date.
     * @param string $format The date format string.
     * @return array
     */
    public static function getInfo(string $date, string $format) : array
    {
        return date_parse_from_format($format, $date);
    }

    /**
     * Returns value of the hour component of the given date.
     *
     * @param string $date A string representing the date.
     * @param string $format The date format string.
     * @return int
     */
    public static function getHour(string $date, string $format) : int
    {
        return static::getInfo($date, $format)['hour'];
    }

    /**
     * Returns value of the minute component of the given date.
     *
     * @param string $date A string representing the date.
     * @param string $format The date format string.
     * @return int
     */
    public static function getMinute(string $date, string $format) : int
    {
        return static::getInfo($date, $format)['minute'];
    }

    /**
     * Returns value of the second component of the given date.
     *
     * @param string $date A string representing the date.
     * @param string $format The date format string.
     * @return int
     */
    public static function getSecond(string $date, string $format) : int
    {
        return static::getInfo($date, $format)['second'];
    }

    /**
     * Returns value of the fraction component of the given date.
     *
     * @param string $date A string representing the date.
     * @param string $format The date format string.
     * @return int
     */
    public static function getFraction(string $date, string $format) : int
    {
        return static::getInfo($date, $format)['fraction'];
    }

    /**
     * Returns value of the day component of the given date.
     *
     * @param string $date A string representing the date.
     * @param string $format The date format string.
     * @return int
     */
    public static function getDay(string $date, string $format) : int
    {
        return static::getInfo($date, $format)['day'];
    }

    /**
     * Returns value of the month component of the given date.
     *
     * @param string $date A string representing the date.
     * @param string $format The date format string.
     * @return int
     */
    public static function getMonth(string $date, string $format) : int
    {
        return static::getInfo($date, $format)['month'];
    }

    /**
     * Returns value of the year component of the given date.
     *
     * @param string $date A string representing the date.
     * @param string $format The date format string.
     * @return int
     */
    public static function getYear(string $date, string $format) : int
    {
        return static::getInfo($date, $format)['year'];
    }

    /**
     * Returns number of days at the specified month of the given year.
     * This method return FALSE if the given year and month isn't valid.
     *
     * @param int $year
     * @param int $month
     * @return int|bool
     */
    public static function getMonthDays(int $year, int $month)
    {
        $month = (int)$month;
        $year = (int)$year;
        if (!checkdate($month, 1, $year)) {
            return false;
        }
        if ($month == 2) {
            $days = 28;
            if (static::isLeapYear($year)) {
                $days++;
            }
        } else if ($month < 8) {
            $days = 30 + $month % 2;
        } else {
            $days = 31 - $month % 2;
        }
        return $days;
    }

    /**
     * Returns unix timestamp for the given date formatted to the specified format.
     * If the date format is not specified the method will try to parse the date from one of the suitable formats.
     *
     * @param string|\DateTime $date A string representing the date or a DateTime object.
     * @param string $format The date format string.
     * @return int
     */
    public static function toTimestamp($date, string $format = null) : int
    {
        return (new static($date, null, $format))->getTimestamp();
    }

    /**
     * Converts the given date from one format to another.
     * If the input date format is not specified the method will try to parse the date from one of the suitable formats.
     *
     * @param string|\DateTime $date A string representing the date or a DateTime object.
     * @param string $outputFormat The output date format string.
     * @param string $inputFormat The input date format string.
     * @return string
     */
    public static function toDate($date, string $outputFormat = null, string $inputFormat = null) : string
    {
        return (new static($date, null, $inputFormat))->format($outputFormat);
    }

    /**
     * Converts the given date from the specified format to the MySQL date format ("Y-m-d" or "Y-m-d H:i:s").
     * If the input date format is not specified the method will try to parse the date from one of the suitable formats.
     *
     * @param string|\DateTime $date A string representing the date or a DateTime object.
     * @param string $format The input date format string.
     * @param bool $shortFormat If TRUE the short sql date format (Y-m-d) will be used.
     * @return string
     */
    public static function toSQL($date, string $format = null, bool $shortFormat = false) : string
    {
        return static::toDate($date, $shortFormat ? 'Y-m-d' : 'Y-m-d H:i:s', $format);
    }

    /**
     * Converts the given date from the MySQL date format ("Y-m-d" or "Y-m-d H:i:s") to the specified format.
     *
     * @param string|\DateTime $date A string representing the date or a DateTime object.
     * @param string $format The output date format string.
     * @param bool $shortFormat If TRUE the short sql date format (Y-m-d) will be used.
     * @return string
     */
    public static function fromSQL($date, string $format = null, bool $shortFormat = false) : string
    {
        return static::toDate($date, $format, $shortFormat ? 'Y-m-d' : 'Y-m-d H:i:s');
    }

    /**
     * Converts the given date from specified format to the atom date format.
     *
     * @param string|\DateTime $date A string representing the date or a DateTime object.
     * @param string $format The input date format string.
     * @return string
     */
    public static function toAtom($date, string $format = null) : string
    {
        return static::toDate($date, static::ATOM, $format);
    }

    /**
     * Converts the given date formatted from the atom date format to the given format.
     *
     * @param string|\DateTime $date A string representing the date or a DateTime object.
     * @param string $format The output date format string.
     * @return string
     */
    public static function fromAtom($date, string $format = null) : string
    {
        return static::toDate($date, $format, static::ATOM);
    }

    /**
     * Converts the given date from specified format to the RSS date format.
     * If the input date format is not specified the method will try to parse the date from one of the suitable formats.
     *
     * @param string|\DateTime $date A string representing the date or a DateTime object.
     * @param string $format The input date format string.
     * @return string
     */
    public static function toRSS($date, string $format = null) : string
    {
        return static::toDate($date, static::RSS, $format);
    }

    /**
     * Converts the given date from RSS date format to the given format.
     *
     * @param string|\DateTime $date A string representing the date or a DateTime object.
     * @param string $format The output date format string.
     * @return string
     */
    public static function fromRSS($date, string $format = null) : string
    {
        return static::toDate($date, $format, static::RSS);
    }

    /**
     * Converts the given date from specified format to the cookie date format.
     * If the input date format is not specified the method will try to parse the date from one of the suitable formats.
     *
     * @param string|\DateTime $date A string representing the date or a DateTime object.
     * @param string $format The input date format string.
     * @return string
     */
    public static function toCookie($date, string $format = null) : string
    {
        return static::toDate($date, static::COOKIE, $format);
    }

    /**
     * Converts the given date from cookie date format to the given format.
     *
     * @param string|\DateTime $date A string representing the date or a DateTime object.
     * @param string $format - the output date format string.
     * @return string
     */
    public static function fromCookie($date, string $format = null) : string
    {
        return static::toDate($date, $format, static::COOKIE);
    }

    /**
     * Converts the given date from specified format to the W3C date format.
     * If the input date format is not specified the method will try to parse the date from one of the suitable formats.
     *
     * @param string|\DateTime $date A string representing the date or a DateTime object.
     * @param string $format The input date format string.
     * @return string
     */
    public static function toW3C($date, string $format = null) : string
    {
        return static::toDate($date, static::W3C, $format);
    }

    /**
     * Converts the given date from the W3C date format to the given format.
     *
     * @param string|\DateTime $date A string representing the date or a DateTime object.
     * @param string $format The output date format string.
     * @return string
     */
    public static function fromW3C($date, string $format = null) : string
    {
        return static::toDate($date, $format, static::W3C);
    }

    /**
     * Converts the given date from specified format to the ISO8601 date format.
     * If the input date format is not specified the method will try to parse the date from one of the suitable formats.
     *
     * @param string|\DateTime $date A string representing the date or a DateTime object.
     * @param string $format The input date format string.
     * @return string
     */
    public static function toISO8601($date, string $format = null) : string
    {
        return static::toDate($date, static::ISO8601, $format);
    }

    /**
     * Converts the given date from the ISO8601 date format to the given format.
     *
     * @param string|\DateTime $date A string representing the date or a DateTime object.
     * @param string $format The output date format string.
     * @return string
     */
    public static function fromISO8601($date, string $format = null) : string
    {
        return static::toDate($date, $format, static::ISO8601);
    }

    /**
     * Converts the given date from specified format to the RFC2822 date format.
     * If the input date format is not specified the method will try to parse the date from one of the suitable formats.
     *
     * @param string|\DateTime $date A string representing the date or a DateTime object.
     * @param string $format The input date format string.
     * @return string
     */
    public static function toRFC2822($date, string $format = null) : string
    {
        return static::toDate($date, static::RFC2822, $format);
    }

    /**
     * Converts the given date from the ISO8601 date format to the given format.
     *
     * @param string|\DateTime $date A string representing the date.
     * @param string $format The output date format string.
     * @return string
     * @access public
     * @static
     */
    public static function fromRFC2822($date, string $format = null) : string
    {
        return static::toDate($date, $format, static::RFC2822);
    }

    /**
     * Compares two given dates.
     * The method returns 1 if the second date larger than the first date.
     * The method returns -1 if the second date smaller than the first date.
     * The method returns 0 if the both dates are equal.
     *
     * @param string|\DateTime $date1 A string or DateTime object representing the first date.
     * @param string|\DateTime $date2 A string or DateTime object representing the second date.
     * @param string $format The date format string.
     * @return int
     */
    public static function compare($date1, $date2, string $format = null) : int
    {
        $v1 = static::toTimestamp($date1, $format);
        $v2 = static::toTimestamp($date2, $format);
        return $v1 === $v2 ? 0 : ($v2 > $v1 ? 1 : -1);
    }

    /**
     * Computes the difference between two dates given in the same format.
     * The method returns difference between dates in days by default.
     *
     * @param string|\DateTime $date1 A string or DateTime object representing the first date.
     * @param string|\DateTime $date2 A string or DateTime object representing the second date.
     * @param string $format The date format string.
     * @param bool $absolute Determines whether the difference should be forced to be positive.
     * @return array
     */
    public static function difference($date1, $date2, string $format = null, bool $absolute = true) : array
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
     * @param string|\DateTime $date1 A string or DateTime object representing the first date.
     * @param string|\DateTime $date2 A string or DateTime object representing the second date.
     * @param string $format The date format string.
     * @param bool $absolute Determines whether the difference should be forced to be positive.
     * @return int
     */
    public static function diffInYears($date1, $date2, string $format = null, bool $absolute = true) : int
    {
        $date1 = new static($date1, null, $format);
        $date2 = new static($date2, null, $format);
        return date_diff($date2, $date1, $absolute)->format('%r%y');
    }

    /**
     * Returns the difference between two dates in months.
     *
     * @param string|\DateTime $date1 A string or DateTime object representing the first date.
     * @param string|\DateTime $date2 A string or DateTime object representing the second date.
     * @param string $format The date format string.
     * @param bool $absolute Determines whether the difference should be forced to be positive.
     * @return int
     */
    public static function diffInMonths($date1, $date2, string $format = null, bool $absolute = true) : int
    {
        $date1 = new static($date1, null, $format);
        $date2 = new static($date2, null, $format);
        return 12 * (int)date_diff($date2, $date1, $absolute)->format('%r%y') +
                    (int)date_diff($date2, $date1, $absolute)->format('%r%m');
    }

    /**
     * Returns the difference between two dates in weeks.
     *
     * @param string|\DateTime $date1 A string or DateTime object representing the first date.
     * @param string|\DateTime $date2 A string or DateTime object representing the second date.
     * @param string $format The date format string.
     * @param bool $absolute Determines whether the difference should be forced to be positive.
     * @return int
     */
    public static function diffInWeeks($date1, $date2, string $format = null, bool $absolute = true) : int
    {
        $date1 = new static($date1, null, $format);
        $date2 = new static($date2, null, $format);
        return date_diff($date2, $date1, $absolute)->format('%r%a') / 7;
    }

    /**
     * Returns the difference between two dates in days.
     *
     * @param string|\DateTime $date1 A string or DateTime object representing the first date.
     * @param string|\DateTime $date2 A string or DateTime object representing the second date.
     * @param string $format The date format string.
     * @param bool $absolute Determines whether the difference should be forced to be positive.
     * @return int
     */
    public static function diffInDays($date1, $date2, string $format = null, bool $absolute = true) : int
    {
        $date1 = new static($date1, null, $format);
        $date2 = new static($date2, null, $format);
        return date_diff($date2, $date1, $absolute)->format('%r%a');
    }

    /**
     * Returns the difference between two dates in hours.
     *
     * @param string|\DateTime $date1 A string or DateTime object representing the first date.
     * @param string|\DateTime $date2 A string or DateTime object representing the second date.
     * @param string $format The date format string.
     * @param bool $absolute Determines whether the difference should be forced to be positive.
     * @return int
     */
    public static function diffInHours($date1, $date2, string $format = null, bool $absolute = true) : int
    {
        $date1 = new static($date1, null, $format);
        $date2 = new static($date2, null, $format);
        $d = $date1->getTimestamp() - $date2->getTimestamp();
        $d = $absolute ? abs($d) : $d;
        return $d / 3600;
    }

    /**
     * Returns the difference between two dates in minutes.
     *
     * @param string|\DateTime $date1 A string or DateTime object representing the first date.
     * @param string|\DateTime $date2 A string or DateTime object representing the second date.
     * @param string $format The date format string.
     * @param bool $absolute Determines whether the difference should be forced to be positive.
     * @return int
     */
    public static function diffInMinutes($date1, $date2, string $format = null, bool $absolute = true) : int
    {
        $date1 = new static($date1, null, $format);
        $date2 = new static($date2, null, $format);
        $d = $date1->getTimestamp() - $date2->getTimestamp();
        $d = $absolute ? abs($d) : $d;
        return $d / 60;
    }

    /**
     * Returns the difference between two dates in seconds.
     *
     * @param string|\DateTime $date1 A string or DateTime object representing the first date.
     * @param string|\DateTime $date2 A string or DateTime object representing the second date.
     * @param string $format The date format string.
     * @param bool $absolute Determines whether the difference should be forced to be positive.
     * @return int
     */
    public static function diffInSeconds($date1, $date2, string $format = null, bool $absolute = true) : int
    {
        $date1 = new static($date1, null, $format);
        $date2 = new static($date2, null, $format);
        $d = $date1->getTimestamp() - $date2->getTimestamp();
        return $absolute ? abs($d) : $d;
    }

    /**
     * Calculates the difference between the two dates given in the same format.
     * If the specific date format is not set the method will try to parse dates into one of the suitable formats.
     * The method returns array of the following elements: second, minute, hour, day.
     *
     * @param string|\DateTime $date1 A string or DateTime object representing the first date.
     * @param string|\DateTime $date2 A string or DateTime object representing the second date.
     * @param string $format The date format string.
     * @return array
     */
    public static function countdown($date1, $date2, string $format = null) : array
    {
        $date1 = new static($date1, null, $format);
        $date2 = new static($date2, null, $format);
        list($year, $month, $day, $hour, $minute, $second) =
            explode(' ', date_diff($date1, $date2, true)->format('%r%y %m %d %h %i %s'));
        return compact('year', 'month', 'day', 'hour', 'minute', 'second');
    }

    /**
     * Returns text representation of the time elapsed since the specified date.
     *
     * @param string|\DateTime $date1 A string or DateTime object representing the first date.
     * @param string|\DateTime $date2 A string or DateTime object representing the second date.
     * @param string $format The date format string.
     * @return string
     */
    public static function formattedDifference($date1, $date2, string $format = null) : string
    {
        $isNow = $date1 === 'now' || $date2 === 'now';
        $dt = new static($date1, null, $format);
        $interval = $dt->diff(new DT($date2, null, $format));
        $parts = ['y' => 'year', 'm' => 'month', 'd' => 'day', 'h' => 'hour', 'i' => 'minute', 's' => 'second'];
        foreach ($parts as $part => $name) {
            if ($interval->{$part} > 0) {
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
     * @param string $format The output date format.
     * @param string|\DateTimeZone $timezone
     * @return string
     */
    public static function now(string $format = null, $timezone = null) : string
    {
        return (new static('now'))->format($format, $timezone);
    }

    /**
     * Returns tomorrow's date.
     *
     * @param string $format The output date format.
     * @param string|\DateTimeZone $timezone
     * @return string
     */
    public static function tomorrow(string $format = null, $timezone = null) : string
    {
        return (new static('tomorrow'))->format($format, $timezone);
    }

    /**
     * Returns yesterday's date.
     *
     * @param string $format The output date format.
     * @param string|\DateTimeZone $timezone
     * @return string
     */
    public static function yesterday(string $format = null, $timezone = null) : string
    {
        return (new static('yesterday'))->format($format, $timezone);
    }

    /**
     * Returns the default time zone used by all date/time functions.
     *
     * @return string
     */
    public static function getDefaultTimeZone() : string
    {
        return date_default_timezone_get();
    }

    /**
     * Sets the default time zone used by all date/time functions in a script.
     * This method returns FALSE if the timezone isn't valid, or TRUE otherwise.
     *
     * @param string $timezone The time zone identifier, like UTC or Europe/Lisbon.
     * @return bool
     */
    public static function setDefaultTimeZone(string $timezone) : bool
    {
        return date_default_timezone_set($timezone);
    }

    /**
     * Returns abbreviation of the given time zone.
     * The time zone can be an instance of DateTimeZone or a string.
     *
     * @param string|\DateTimeZone $timezone
     * @return string
     */
    public static function getTimeZoneAbbreviation($timezone) : string
    {
        return (new static('now', $timezone))->format('T');
    }

    /**
     * Returns numerical index array with all timezone identifiers.
     * If $combine is TRUE the method returns associated array with keys which are time zones.
     *
     * @param bool $combine Determines whether the method returns an associative array.
     * @param int $what One of DateTimeZone class constants.
     * @param string $country A two-letter ISO 3166-1 compatible country code. This argument is only used when $what is set to DateTimeZone::PER_COUNTRY.
     * @return array
     */
    public static function getTimeZoneList(bool $combine = false,
                                           int $what = \DateTimeZone::ALL, string $country = null) : array
    {
        $list = \DateTimeZone::listIdentifiers($what, $country);
        return $combine ? array_combine($list, $list) : $list;
    }

    /**
     * Returns list (associative array) of timezones in GMT format.
     *
     * @param array $replacement Can be used to replace some timezone names by others.
     * @param int $what One of DateTimeZone class constants.
     * @param string $country A two-letter ISO 3166-1 compatible country code. This argument is only used when $what is set to DateTimeZone::PER_COUNTRY.
     * @return array
     */
    public static function getGMTTimeZoneList(array $replacement = null,
                                              int $what = \DateTimeZone::ALL, string $country = null) : array
    {
        $list = [];
        foreach (\DateTimeZone::listIdentifiers($what, $country) as $zone) {
            $tz = new \DateTimeZone($zone);
            $offset = $tz->getOffset(new \DateTime('now', $tz));
            $hours = str_pad(intval(abs($offset) / 3600), 2, '0', STR_PAD_LEFT);
            $minutes = str_pad(intval(abs($offset) % 3600 / 60), 2, '0', STR_PAD_LEFT);
            if (isset($replacement[$zone])) {
                $tz = $replacement[$zone];
            } else {
                $tz = explode('/', str_replace('_', ' ', $zone));
                array_shift($tz);
                $tz = implode(' - ', $tz);
            }
            $list[$zone] = '(GMT' . ($offset >= 0 ? '+' : '-') . $hours . ':' . $minutes . ') ' . $tz;
        }
        array_pop($list);
        uasort($list, function ($a, $b) {
            $aa = (int)substr($a, 4, 3);
            $bb = (int)substr($b, 4, 3);
            if ($aa == $bb) {
                return substr($a, 12) > substr($b, 12);
            }
            return $aa > $bb;
        });
        return $list;
    }

    /**
     * Returns timezone string in GMT format.
     *
     * @param string|\DateTimeZone $timezone Timezone name or timezone object.
     * @return string
     */
    public static function getGMTTimeZone($timezone) : string
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
     * @param string|\Datetime $date A string or DateTime object representing the date.
     * @param string|\DateTimeZone $outputTimezone Output date time zone.
     * @param string|\DateTimeZone $inputTimezone Input date time zone.
     * @param string $outputFormat The output format string.
     * @param string $inputFormat The input format string.
     * @return string
     */
    public static function zone2zone($date, $outputTimezone, $inputTimezone,
                                     string $outputFormat = null, string $inputFormat = null) : string
    {
        return (new static($date, $inputTimezone, $inputFormat))->format($outputFormat, $outputTimezone);
    }

    /**
     * Create a DT instance from a specific format.
     *
     * @param string $format The date format string.
     * @param string|\DateTime $date A string representing the date or a DateTime object.
     * @param string|\DateTimeZone $timezone The desired time zone.
     * @return \Aleph\Utils\DT
     */
    public static function createFromFormat($format, $date, $timezone = null) : DT
    {
        return new static($date, $timezone, $format);
    }

    /**
     * Create a datetime instance from a timestamp.
     *
     * @param int $timestamp
     * @param string|\DateTimeZone $timezone
     * @return \Aleph\Utils\DT
     */
    public static function createFromTimestamp(int $timestamp, $timezone = null) : DT
    {
        return (new static('now', $timezone))->setTimestamp($timestamp);
    }

    /**
     * Converts the given timezone string to DateTimeZone object.
     *
     * @param string|\DateTimeZone $timezone The timezone to convert.
     * @return \DateTimeZone
     * @throws \InvalidArgumentException
     */
    public static function normalizeTimeZone($timezone) : \DateTimeZone
    {
        if (!($timezone instanceof \DateTimeZone)) {
            if ($timezone === null) {
                return new \DateTimeZone(date_default_timezone_get());
            }
            try {
                $timezone = new \DateTimeZone($timezone);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException(sprintf(static::ERR_DT_1, $timezone));
            }
        }
        return $timezone;
    }

    /**
     * Constructor.
     * If $date is not specified the current date will be taken.
     *
     * @param string|\DateTime $date A string representing the date or a DateTime object.
     * @param string|\DateTimeZone $timezone The date time zone.
     * @param string $format The date format string.
     */
    public function __construct($date = 'now', $timezone = null, string $format = null)
    {
        if (!($date instanceof \DateTime)) {
            $timezone = static::normalizeTimeZone($timezone);
            if ($format === null) {
                $date = date_create($date, $timezone);
            } else {
                $date = date_create_from_format($format, $date, $timezone);
            }
            if ($date === false) {
                throw new \InvalidArgumentException(implode(PHP_EOL, static::getLastErrors()['errors']));
            }
        } else if ($timezone !== null) {
            $date->setTimezone(static::normalizeTimeZone($timezone));
        }
        parent::__construct($date->format('Y-m-d H:i:s.u'), $date->getTimezone());
    }

    /**
     * Returns a copy of the datetime instance.
     *
     * @return \Aleph\Utils\DT
     */
    public function copy() : DT
    {
        return new static($this);
    }

    /**
     * Returns string representation of the date time object.
     * It returns date in self::DEFAULT_DATETIME_FORMAT
     *
     * @return string
     */
    public function __toString() : string
    {
        return $this->format();
    }

    /**
     * Sets the time zone for the Aleph\Utils\DT object.
     * Returns the DT object for method chaining or FALSE on failure.
     *
     * @param string|\DateTimeZone $timezone
     * @return \Aleph\Utils\DT
     */
    public function setTimezone($timezone) : DT
    {
        parent::setTimezone(static::normalizeTimeZone($timezone));
        return $this;
    }

    /**
     * Returns the Unix timestamp representing the date.
     *
     * @param string|\DateTimeZone $timezone
     * @return int
     */
    public function getTimestamp($timezone = null) : int
    {
        if ($timezone === null) {
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
     * @param string $format The format accepted by date().
     * @param string|\DateTimeZone $timezone The timezone of the output date.
     * @return string
     */
    public function format($format = null, $timezone = null) : string
    {
        $format = $format === null ? static::$defaultDateTimeformat : $format;
        if ($timezone === null) {
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
     * @param int $second
     * @return int|\Aleph\Utils\DT
     */
    public function second(int $second = null)
    {
        if ($second === null) {
            return (int)$this->format('s');
        }
        return $this->setTime($this->hour(), $this->minute(), $second);
    }

    /**
     * Gets or sets the number of minutes.
     *
     * @param int $minute
     * @return int|\Aleph\Utils\DT
     */
    public function minute(int $minute = null)
    {
        if ($minute === null) {
            return (int)$this->format('i');
        }
        return $this->setTime($this->hour(), $minute, $this->second());
    }

    /**
     * Gets or sets the number of hours.
     *
     * @param int $hour
     * @return int|\Aleph\Utils\DT
     */
    public function hour(int $hour = null)
    {
        if ($hour === null) {
            return (int)$this->format('G');
        }
        return $this->setTime($hour, $this->minute(), $this->second());
    }

    /**
     * Gets or sets the number of days.
     *
     * @param int $day
     * @return int|\Aleph\Utils\DT
     */
    public function day(int $day = null)
    {
        if ($day === null) {
            return (int)$this->format('j');
        }
        return $this->setDate($this->year(), $this->month(), $day);
    }

    /**
     * Gets or sets the number of months.
     *
     * @param int $month
     * @return int|\Aleph\Utils\DT
     */
    public function month(int $month = null)
    {
        if ($month === null) {
            return (int)$this->format('n');
        }
        return $this->setDate($this->year(), $month, $this->day());
    }

    /**
     * Gets or sets the number of years.
     *
     * @param int $year
     * @return int|\Aleph\Utils\DT
     */
    public function year(int $year = null)
    {
        if ($year === null) {
            return (int)$this->format('Y');
        }
        return $this->setDate($year, $this->month(), $this->day());
    }

    /**
     * Determines if the datetime instance is today.
     *
     * @return bool
     */
    public function isToday() : bool
    {
        return $this->format('Y-m-d') === static::now('Y-m-d');
    }

    /**
     * Determines if the datetime instance is tomorrow.
     *
     * @return bool
     */
    public function isTomorrow() : bool
    {
        return $this->format('Y-m-d') === static::tomorrow('Y-m-d');
    }

    /**
     * Determines if the datetime instance is yesterday.
     *
     * @return bool
     */
    public function isYesterday() : bool
    {
        return $this->format('Y-m-d') === static::yesterday('Y-m-d');
    }

    /**
     * Determines if the datetime instance is in the future.
     *
     * @return bool
     */
    public function isFuture() : bool
    {
        return $this > new static('now', $this->getTimezone());
    }

    /**
     * Determines if the datetime instance is in the past.
     *
     * @return bool
     */
    public function isPast() : bool
    {
        return $this < new static('now', $this->getTimezone());
    }

    /**
     * Determines if the datetime instance has a leap year.
     *
     * @return bool
     */
    public function hasLeapYear() : bool
    {
        return $this->format('L') == 1;
    }

    /**
     * Adds an amount of days, months, years, hours, minutes and seconds to a Aleph\Utils\DT object.
     * Returns the DT object for method chaining or FALSE on failure.
     *
     * @param string|\DateInterval A DateInterval object or interval string.
     * @return \Aleph\Utils\DT
     * @link http://www.php.net/manual/en/class.dateinterval.php
     */
    public function add($interval) : DT
    {
        return parent::add($interval instanceof \DateInterval ? $interval : \DateInterval::createFromDateString($interval));
    }

    /**
     * Subtracts an amount of days, months, years, hours, minutes and seconds from a Aleph\Utils\DT object.
     * Returns the DT object for method chaining or FALSE on failure.
     *
     * @param string|\DateInterval A DateInterval object or interval string.
     * @return \Aleph\Utils\DT
     * @link http://www.php.net/manual/en/class.dateinterval.php
     */
    public function sub($interval) : DT
    {
        return parent::sub($interval instanceof \DateInterval ? $interval : \DateInterval::createFromDateString($interval));
    }

    /**
     * Returns the difference between the datetime instance and the given date.
     *
     * @param string|\DateTime $date The date to compare to.
     * @param bool $absolute Determines whether to return absolute difference.
     * @return \DateInterval
     */
    public function diff($date, $absolute = false) : \DateInterval
    {
        return parent::diff(new static($date), $absolute);
    }

    /**
     * Adds an amount of days to the datetime instance. The amount of days can be negative.
     * Returns the DT object for method chaining or FALSE on failure.
     *
     * @param int $day
     * @return \Aleph\Utils\DT
     */
    public function addDay(int $day = 1) : DT
    {
        return $this->modify((int)$day . ' day');
    }

    /**
     * Adds an amount of months to the datetime instance. The amount of months can as well be negative.
     * Returns the DT object for method chaining or FALSE on failure.
     *
     * @param int $month
     * @return \Aleph\Utils\DT
     */
    public function addMonth(int $month = 1) : DT
    {
        return $this->modify((int)$month . ' month');
    }

    /**
     * Adds an amount of years to the datetime instance. The amount of years might as well be negative.
     * Returns the DT object for method chaining or FALSE on failure.
     *
     * @param int $year
     * @return \Aleph\Utils\DT
     */
    public function addYear(int $year = 1) : DT
    {
        return $this->modify((int)$year . ' year');
    }

    /**
     * Adds an amount of hours to the datetime instance. The amount of hours might as well be negative.
     * Returns the DT object for method chaining or FALSE on failure.
     *
     * @param int $hour
     * @return \Aleph\Utils\DT
     */
    public function addHour(int $hour = 1) : DT
    {
        return $this->modify((int)$hour . ' hour');
    }

    /**
     * Adds an amount of minutes to the datetime instance. The amount of minutes  might as well be negative.
     * Returns the DT object for method chaining or FALSE on failure.
     *
     * @param int $minute
     * @return \Aleph\Utils\DT
     */
    public function addMinute(int $minute = 1) : DT
    {
        return $this->modify((int)$minute . ' minute');
    }

    /**
     * Adds an amount of seconds to the datetime instance. The amount of seconds might as well be negative.
     * Returns the DT object for method chaining or FALSE on failure.
     *
     * @param int $second
     * @return \Aleph\Utils\DT
     */
    public function addSecond(int $second = 1) : DT
    {
        return $this->modify((int)$second . ' second');
    }

    /**
     * Resets the time to 00:00:00
     *
     * @return \Aleph\Utils\DT
     */
    public function startOfDay() : DT
    {
        return $this->setTime(0, 0, 0);
    }

    /**
     * Resets the time to 23:59:59
     *
     * @return \Aleph\Utils\DT
     */
    public function endOfDay() : DT
    {
        return $this->setTime(23, 59, 59);
    }

    /**
     * Resets the date to the first day of the month and the time to 00:00:00
     *
     * @return \Aleph\Utils\DT
     */
    public function startOfMonth() : DT
    {
        return $this->startOfDay()->day(1);
    }

    /**
     * Resets the date to end of the month and time to 23:59:59
     *
     * @return \Aleph\Utils\DT
     */
    public function endOfMonth() : DT
    {
        return $this->day($this->format('t'))->endOfDay();
    }

    /**
     * Resets the date to the first day of the year and the time to 00:00:00
     *
     * @return \Aleph\Utils\DT
     */
    public function startOfYear() : DT
    {
        return $this->month(1)->startOfMonth();
    }

    /**
     * Resets the date to end of the year and time to 23:59:59
     *
     * @return \Aleph\Utils\DT
     */
    public function endOfYear() : DT
    {
        return $this->month(12)->endOfMonth();
    }

    /**
     * Returns Generator object representing of date period.
     *
     * @param string|\DateInterval $interval The interval between recurrences within the period.
     * @param int|\DateTimeInterface $end The number of recurrences or an object implementing DateTimeInterface.
     * @param bool $excludeStartDate Determines whether the start date is excluded.
     * @return \Generator
     */
    public function getPeriod($interval, $end, bool $excludeStartDate = false) : \Generator
    {
        if (!($interval instanceof \DateInterval)) {
            $interval = \DateInterval::createFromDateString($interval);
        }
        if (!($end instanceof \DateTimeInterface)) {
            $end = (int)$end;
        }
        $dt = clone $this;
        if (!$excludeStartDate) {
            $k = 0;
        } else {
            $dt->add($interval);
            $k = 1;
        }
        if ($end instanceof \DateTimeInterface) {
            while ($end >= $dt) {
                yield (clone $dt);
                $dt->add($interval);
            }
        } else {
            while ($end >= $k) {
                yield (clone $dt);
                $dt->add($interval);
                $k++;
            }
        }
    }
}