<?php

namespace Aleph\Utils
{
  use Aleph\Core;

  class Translate
  {
    const ERR_TLT_1 = 'Rule [ [{var}] ] contains unavailable constructions. A rule may contains only following constructions: "||", "&&", ",", "(", ")", "-", "+", "preg_match", digit characters and template variables.';
    const ERR_TLT_2 = 'Rule [ [{var}] ] contains unspecified template variable "[{var}]".';
    const ERR_TLT_3 = 'Rule cannot be empty.';
    const ERR_TLT_4 = 'Locale "[{var}]" is not found among phrases\' templates.';
    const ERR_TLT_5 = 'Phrase with ID "[{var}]" is not found among translates\' templates.';
  
    private static $instance = null;
    private $a = null;
    private $cache = null;
    private $locale = array();
    private $localizationFile = null;

    private $languages = array('af' => 'Afrikaans',
                               'sq' => 'Albanian',
                               'ar-dz' => 'Arabic (Algeria)',
                               'ar-bh' => 'Arabic (Bahrain)',
                               'ar-eg' => 'Arabic (Egypt)',
                               'ar-iq' => 'Arabic (Iraq)',
                               'ar-jo' => 'Arabic (Jordan)',
                               'ar-kw' => 'Arabic (Kuwait)',
                               'ar-lb' => 'Arabic (Lebanon)',
                               'ar-ly' => 'Arabic (libya)',
                               'ar-ma' => 'Arabic (Morocco)',
                               'ar-om' => 'Arabic (Oman)',
                               'ar-qa' => 'Arabic (Qatar)',
                               'ar-sa' => 'Arabic (Saudi Arabia)',
                               'ar-sy' => 'Arabic (Syria)',
                               'ar-tn' => 'Arabic (Tunisia)',
                               'ar-ae' => 'Arabic (U.A.E.)',
                               'ar-ye' => 'Arabic (Yemen)',
                               'ar' => 'Arabic',
                               'hy' => 'Armenian',
                               'as' => 'Assamese',
                               'az' => 'Azeri',
                               'eu' => 'Basque',
                               'be' => 'Belarusian',
                               'bn' => 'Bengali',
                               'bg' => 'Bulgarian',
                               'ca' => 'Catalan',
                               'zh-cn' => 'Chinese (China)',
                               'zh-hk' => 'Chinese (Hong Kong SAR)',
                               'zh-mo' => 'Chinese (Macau SAR)',
                               'zh-sg' => 'Chinese (Singapore)',
                               'zh-tw' => 'Chinese (Taiwan)',
                               'zh' => 'Chinese',
                               'hr' => 'Croatian',
                               'cs' => 'Czech',
                               'da' => 'Danish',
                               'div' => 'Divehi',
                               'nl-be' => 'Dutch (Belgium)',
                               'nl' => 'Dutch (Netherlands)',
                               'en-au' => 'English (Australia)',
                               'en-bz' => 'English (Belize)',
                               'en-ca' => 'English (Canada)',
                               'en-ie' => 'English (Ireland)',
                               'en-jm' => 'English (Jamaica)',
                               'en-nz' => 'English (New Zealand)',
                               'en-ph' => 'English (Philippines)',
                               'en-za' => 'English (South Africa)',
                               'en-tt' => 'English (Trinidad)',
                               'en-gb' => 'English (United Kingdom)',
                               'en-us' => 'English (United States)',
                               'en-zw' => 'English (Zimbabwe)',
                               'en' => 'English',
                               'us' => 'English (United States)',
                               'et' => 'Estonian',
                               'fo' => 'Faeroese',
                               'fa' => 'Farsi',
                               'fi' => 'Finnish',
                               'fr-be' => 'French (Belgium)',
                               'fr-ca' => 'French (Canada)',
                               'fr-lu' => 'French (Luxembourg)',
                               'fr-mc' => 'French (Monaco)',
                               'fr-ch' => 'French (Switzerland)',
                               'fr' => 'French (France)',
                               'mk' => 'FYRO Macedonian',
                               'gd' => 'Gaelic',
                               'ka' => 'Georgian',
                               'de-at' => 'German (Austria)',
                               'de-li' => 'German (Liechtenstein)',
                               'de-lu' => 'German (lexumbourg)',
                               'de-ch' => 'German (Switzerland)',
                               'de' => 'German (Germany)',
                               'el' => 'Greek',
                               'gu' => 'Gujarati',
                               'he' => 'Hebrew',
                               'hi' => 'Hindi',
                               'hu' => 'Hungarian',
                               'is' => 'Icelandic',
                               'id' => 'Indonesian',
                               'it-ch' => 'Italian (Switzerland)',
                               'it' => 'Italian (Italy)',
                               'ja' => 'Japanese',
                               'kn' => 'Kannada',
                               'kk' => 'Kazakh',
                               'kok' => 'Konkani',
                               'ko' => 'Korean',
                               'kz' => 'Kyrgyz',
                               'lv' => 'Latvian',
                               'lt' => 'Lithuanian',
                               'ms' => 'Malay',
                               'ml' => 'Malayalam',
                               'mt' => 'Maltese',
                               'mr' => 'Marathi',
                               'mn' => 'Mongolian (Cyrillic)',
                               'ne' => 'Nepali (India)',
                               'nb-no' => 'Norwegian (Bokmal)',
                               'nn-no' => 'Norwegian (Nynorsk)',
                               'no' => 'Norwegian (Bokmal)',
                               'or' => 'Oriya',
                               'pl' => 'Polish',
                               'pt-br' => 'Portuguese (Brazil)',
                               'pt' => 'Portuguese (Portugal)',
                               'pa' => 'Punjabi',
                               'rm' => 'Rhaeto-Romanic',
                               'ro-md' => 'Romanian (Moldova)',
                               'ro' => 'Romanian',
                               'ru-md' => 'Russian (Moldova)',
                               'ru-ru' => 'Russian',
                               'ru' => 'Russian',
                               'sa' => 'Sanskrit',
                               'sr' => 'Serbian',
                               'sk' => 'Slovak',
                               'ls' => 'Slovenian',
                               'sb' => 'Sorbian',
                               'es-ar' => 'Spanish (Argentina)',
                               'es-bo' => 'Spanish (Bolivia)',
                               'es-cl' => 'Spanish (Chile)',
                               'es-co' => 'Spanish (Colombia)',
                               'es-cr' => 'Spanish (Costa Rica)',
                               'es-do' => 'Spanish (Dominican Republic)',
                               'es-ec' => 'Spanish (Ecuador)',
                               'es-sv' => 'Spanish (El Salvador)',
                               'es-gt' => 'Spanish (Guatemala)',
                               'es-hn' => 'Spanish (Honduras)',
                               'es-mx' => 'Spanish (Mexico)',
                               'es-ni' => 'Spanish (Nicaragua)',
                               'es-pa' => 'Spanish (Panama)',
                               'es-py' => 'Spanish (Paraguay)',
                               'es-pe' => 'Spanish (Peru)',
                               'es-pr' => 'Spanish (Puerto Rico)',
                               'es-us' => 'Spanish (United States)',
                               'es-uy' => 'Spanish (Uruguay)',
                               'es-ve' => 'Spanish (Venezuela)',
                               'es' => 'Spanish (Traditional Sort)',
                               'sx' => 'Sutu',
                               'sw' => 'Swahili',
                               'sv-fi' => 'Swedish (Finland)',
                               'sv' => 'Swedish',
                               'syr' => 'Syriac',
                               'ta' => 'Tamil',
                               'tt' => 'Tatar',
                               'te' => 'Telugu',
                               'th' => 'Thai',
                               'ts' => 'Tsonga',
                               'tn' => 'Tswana',
                               'tr' => 'Turkish',
                               'uk' => 'Ukrainian',
                               'ur' => 'Urdu',
                               'uz' => 'Uzbek',
                               'vi' => 'Vietnamese',
                               'xh' => 'Xhosa',
                               'yi' => 'Yiddish',
                               'zu' => 'Zulu');

    private function __construct($locale = null, Cache\Cache $cache = null)
    {
      $this->a = \aleph::getInstance();
      $this->cache = $cache ?: $this->a->getCache();
      $this->setLocale($locale ?: (substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, strpos($_SERVER['HTTP_ACCEPT_LANGUAGE'], ',')) ?: 'en'));
    }

    private function __clone(){}

    public static function getInstance($locale = null, Cache\Cache $cache = null)
    {
      if (self::$instance === null) self::$instance = new self($locale, $cache);
      return self::$instance;
    }
    
    public function getCache()
    {
      return $this->cache;
    }
    
    public function setCache(Cache\Cache $cache)
    {
      $this->cache = $cache;
    }

    public function getLocale()
    {
      return $this->locale;
    }

    public function setLocale($locale)
    {
      $tmp = explode('-', $locale);
      $this->locale = array('full' => $locale, 'short' => $tmp[0]);
    }

    public function setLocalizationFile($file)
    {
      $this->localizationFile = $file;
    }

    public function getLocalizationFile()
    {
      return $this->localizationFile;
    }

    public function getLanguage()
    {
      if (isset($this->languages[$this->locale['full']])) return $this->languages[$this->locale['full']];
      return isset($this->languages[$this->locale['short']]) ? $this->languages[$this->locale['short']] : false;
    }

    public function loadFromXML($file)
    {
      $dom = new \DOMDocument('1.0', 'utf-8');
      $dom->load($file);
      $dom->schemaValidate(__DIR__ . '/translations.xsd');
      $dom = simplexml_import_dom($dom);
      $this->localizationFile = $file;
      foreach ($dom->xpath('/Localization/Phrase') as $phrase)
      {
        $data = array();
        foreach ($phrase->Translate as $translate)
        {
          $lang = (string)$translate['Language'];
          $data[$lang]['direction'] = (string)$translate['Direction'];
          if (count($translate->Case) == 0)
          {
            $this->parseTemplate($data[$lang], $translate);
          }
          else
          {
            $data[$lang]['cases'] = array(); $k = 0;
            foreach ($translate->Case as $case)
            {
              $data[$lang]['cases'][$k] = array();
              $this->parseTemplate($data[$lang]['cases'][$k], $case);
              $rule = (string)$case->Rule;
              if (!$this->checkRule($rule, $data[$lang]['cases'][$k]['vars'])) throw new Core\Exception($this, 'ERR_TLT_1', $rule);
              $data[$lang]['cases'][$k++]['rule'] = $rule;
            }
            $this->parseTemplate($data[$lang], $translate->Otherwise);
          }
        }
        $this->cache->set($this->getCacheKey((string)$phrase->Key), $data, $this->cache->getVaultLifeTime(), '--localization');
      }
    }

    public function loadFromDB($alias)
    {

    }

    public function text($ID, array $params = null, $locale = null)
    {
      $cacheID = $this->getCacheKey($ID);
      if ($this->cache->isExpired($cacheID)) $this->loadFromXML($this->localizationFile);
      $data = $this->cache->get($cacheID);
      if (!is_array($data)) throw new Core\Exception($this, 'ERR_TLT_5', $ID);
      if ($locale === null)
      {
        if (isset($data[$this->locale['full']])) $locale = $this->locale['full'];
        else if (isset($data[$this->locale['short']])) $locale = $this->locale['short'];
        else throw new Core\Exception($this, 'ERR_TLT_4', $this->locale['full']);
      }
      else if (empty($data[$locale])) throw new Core\Exception($this, 'ERR_TLT_4', $locale);
      if (isset($data[$locale]['cases']))
      {
        foreach ((array)$data[$locale]['cases'] as $case)
        {
          foreach ($case['vars'] as $var) ${$var} = $params[$var];
          eval($case['rule']);
          if (!$flag) continue;
          eval($case['template']);
          return $tmp;
        }
      }
      foreach ($data[$locale]['vars'] as $var) ${$var} = $params[$var];
      eval($data[$locale]['template']);
      return $tmp;
    }

    private function checkRule(&$rule, array $vars)
    {
      $tokens = token_get_all('<?php ' . $rule);
      $availables = array(305, 307, 371, 315, 283, 278, 279);
      unset($tokens[0]); $code = '';
      foreach ($tokens as $token)
      {
        if (!in_array($token[0], $availables) && $token != '(' && $token != ')' && $token != ',' && $token != '-' && $token != '+') return false;
        if ($token[0] == 307)
        {
          if ($token[1] == 'preg_match') $code .= $token[1];
          else if (in_array($token[1], $vars)) $code .= '$' . $token[1];
          else throw new Core\Exception($this, 'ERR_TLT_2', $rule, $token[1]);
        }
        else $code .= !empty($token[1]) ? $token[1] : $token;
      }
      if (empty($code)) throw new Core\Exception($this, 'ERR_TLT_3');
      $rule = '$flag = (' . $code . ');';
      return true;
    }

    private function parseTemplate(array &$data, \SimpleXMLElement $template)
    {
      $ld = (string)$template['leftDelimiter'] ?: '[{';
      $rd = (string)$template['rightDelimiter'] ?: '}]';
      $data['vars'] = array();
      $tmp = isset($template->Template) ? (string)$template->Template : (string)$template;
      foreach (explode($ld, $tmp) as $chunk)
      {
        $chunk = explode($rd, $chunk);
        if (count($chunk) < 2) continue;
        $data['vars'][] = $chunk[0];
        $tmp = str_replace($ld . $chunk[0] . $rd, '$' . $chunk[0], $tmp);
      }
      $data['template'] = '$tmp = "' . str_replace('"', '\"', $tmp) . '";';
    }

    private function getCacheKey($ID)
    {
      return $this->localizationFile . $ID . \Aleph::getSiteUniqueID();
    }
  }
}

namespace
{
  function t($ID, array $params = null, $locale = null)
  {
    return \Aleph\Utils\Translate::getInstance()->text($ID, $params, $locale);
  }
}

?>