<?php

namespace ConfigCommon;

use Config\Site;

/**
 * @author Pardeep Kumar
 * Parent config class; will have all the common
 * configurations.
 */
class Config
{

    public function __construct()
    {
        static::init();
        self::intialize();
    }

    /**
     * Certification Status
     * @return array
     */
    public static function getCertificationStatus()
    {
        return array(
            '0' => 'Not Certified',
            '1' => 'Certified',
            '2' => 'Pending',
            '3' => 'Dealer Request',
            '4' => 'Refurbishment',
            '5' => 'Done and waiting for approval',
            '6' => 'Rejected',
            '7' => 'Expired'
        );
    }


    public static function isNewTemplateApplicable($theme = null)
    {
        return false;
    }
    
    /**
     * get your template name
     * 
     * @return string
     */
    public static function getMyTheme()
    {
        $paramArray = (\Config\Site::getAllParams());
        return ($paramArray['hash'])?'Flexi':'';
    }
    
    public static function getFileSystemStorageOptions()
    {
        return array(
            'namespace'           => 'dealers',
            'namespace_separator' => '_',
            'dir_level'           => 0,
            'ttl'                 => \Helper\TimeHelper::DAY,
            'cache_dir'           => ROOT . DS . 'cache',
            'dir_permission'      => 0777,
            'file_locking'        => true,
            'file_permission'     => 0600,
            'key_pattern'         => '/^[a-z0-9_\+\-\.\/]*$/Di'
        );
    }
    
    public static function getMemcachedStorageOptions()
    {
        throw new \Exception('Memcached options not defined');
    }
    
    public static function getRedisConnections()
    {
        throw new \Exception('Redis Connection not defined');
    }
    
    public static function getSmsApiUrl()
    {
        return 'http://www.gaadi.com/api_send_sms.php';
    }
    
    public static function getMinified()
    {
        return false; // we have to work on this
    }
    
    public static function getMinifyOnLocal()
    {
        return false;
    }
    
    /**
     * Change the revision whenever you make any change to css file
     * @return int return the current version of css
     */
    public static function getCssRevision()
    {
        return 16;
    }
     public static function minifyJs()
     {
         return false;
     }
     public static function minifyCss()
     {
         return false;
     }
         /**
     }
     * Change the revision whenever you make any change to js file
     * @return int return the current version of js
     */
    public static function getJsRevision()
    {
        return 3;
    }
    
    public static function enableOutputCache()
    {
        return false; 
    }
    
    public static function intialize()
    {
        $domain             = Site::getHost();
        if (Site::getHost())
        {
            $dealer = new \Storage\Dealer\Dealer();
            $dealerDetails = $dealer->setIn('domain', [$domain, 'www.' . $domain, str_ireplace("www.", "", $domain)])->get()->resultOne();
            if (!empty($dealerDetails) && $dealerDetails['id'] != '48' && $dealerDetails['id'] != '50')
            {
                if (in_array($dealerDetails['status'],[1, 3, 4]))
                {
                    $definedDomain = $dealerDetails['domain'];
                    if ($dealerDetails['listing_type'] == 'Trial')
                    {
                        $listedDate       = $dealerDetails['date_time'];
                        $duration         = $dealerDetails['duration'];
                        $validTimeToLogin = strtotime($listedDate . "+ " . $duration . " days");
                        if ($validTimeToLogin <= time())
                        {
                            echo "<div style='height:150px;text-align:center;font-weight:bold;padding-top:50px;background-color:gray;'>Duration over. Please contact admin.</div>";
                            die;
                        }
                    }
                    if ($definedDomain != $domain)
                    {
                        header('location:http://' . $definedDomain . $_SERVER['REQUEST_URI']);
                    }
                }
            }
        }
    }
    
    public static function getOnRoadPriceExternalResponseUrl()
    {
        return 'http://alpha.gaadi.com/orp_external_response.php';
    }
  public static function getUsedCarEvaluationResponseUrl()
    {
        return 'http://www.gaadi.com/UsedCarEstimatedPriceAPI.php';
    }

    public static function getUsGaadiUrl()
    {
        return '';
    }
    public static function getKnowlarityUrlApiCallInGaadi()
    {
        return '';
    }
    
    public static function getSellCarApiKey()
    {
        return 'msdhydsa5b43';
    }
    
    public static function getSellCarApiUrl()
    {
        return "http://usedcarsin.in/api/Api_GetModelParent.php";
    }
    
    public static function mailTo()
    {
        return 'ankit.vishwakarma@gaadi.com';
    }
    
    public static function getThemesRefactored()
    {
        return [
                'Gaadi_Template001',
                'Gaadi_Template002',
                'Gaadi_Template003',
                'Gaadi_Template004',
                'Gaadi_Template005',
                'Gaadi_Template006',
                'Premium_Template',
                'Flexi'
            ];
    }
    
    /**
     * list of the all the dealer ids to be migrated to the new theme
     * 
     * @return array 
     */
    public static function migrateDealersToNewThemes()
    {
        return [];
    }
    
    public static function isNewThemeApplicable()
    {
        $input = \Config\Site::getHost();

        // in case scheme relative URI is passed, e.g., //www.google.com/
        $input = trim($input, '/');

        // If scheme not included, prepend it
        if (!preg_match('#^http(s)?://#', $input))
        {
            $input = 'http://' . $input;
        }

        $urlParts = parse_url($input);

        // remove www
        $domain = preg_replace('/^www\./', '', $urlParts['host']);
        
        $dealer = new \Storage\Dealer\Dealer();
        $template= $dealer->getTemplateName($domain);
        
        return in_array($template['template_name'], self::getThemesRefactored());
    }
    
    public static function loadNewTemplateSystem()
    {
        if (static::isNewThemeApplicable())
        {
            if (isset($_COOKIE['themesNew']))
            {

                if ($_COOKIE['themesNew'] == 'true')
                {
                    return true;
                }
                else
                {
                    return false;
                }
            }
            else
            {
                return true;
            }
        }
        return false;
    }

    public static function getMimes()
    {
            static $_mimes;

            if (empty($_mimes))
            {
                if (file_exists(ROOT. DS. 'config/mimes.php'))
                {
                    $_mimes = require(ROOT. DS. 'config/mimes.php');
                }
                else
                {
                    $_mimes = array();
                }
            }
            return $_mimes;
    }

    public static function getCharSet()
    {
        return 'UTF-8';
    }


    /*
    |--------------------------------------------------------------------------
    | Cross Site Request Forgery
    |--------------------------------------------------------------------------
    | Enables a CSRF cookie token to be set. When set to TRUE, token will be
    | checked on a submitted form. If you are accepting user data, it is strongly
    | recommended CSRF protection be enabled.
    |
    | 'csrf_token_name' = The token name
    | 'csrf_cookie_name' = The cookie name
    | 'csrf_expire' = The number in seconds the token should expire.
    | 'csrf_regenerate' = Regenerate token on every submission
    | 'csrf_exclude_uris' = Array of URIs which ignore CSRF checks
    */
    public static function getCsrfProtection($key = null)
    {
        $config['csrf_protection'] = FALSE;
        $config['csrf_token_name'] = 'csrf_test_name';
        $config['csrf_cookie_name'] = 'csrf_cookie_name';
        $config['csrf_expire'] = 7200;
        $config['csrf_regenerate'] = TRUE;
        $config['csrf_exclude_uris'] = array();

        return !empty($key) && isset($config[$key]) ? $config[$key] : $config;
    }

    /*
    |--------------------------------------------------------------------------
    | Cookie Related Variables
    |--------------------------------------------------------------------------
    |
    | 'cookie_prefix'   = Set a cookie name prefix if you need to avoid collisions
    | 'cookie_domain'   = Set to .your-domain.com for site-wide cookies
    | 'cookie_path'     = Typically will be a forward slash
    | 'cookie_secure'   = Cookie will only be set if a secure HTTPS connection exists.
    | 'cookie_httponly' = Cookie will only be accessible via HTTP(S) (no javascript)
    |
    | Note: These settings (with the exception of 'cookie_prefix' and
    |       'cookie_httponly') will also affect sessions.
    |
    */
    public static function getCookie($key = null)
    {
        $config['cookie_prefix'] = '';
        $config['cookie_domain'] = '';
        $config['cookie_path'] = '/';
        $config['cookie_secure'] = FALSE;
        $config['cookie_httponly'] = FALSE;

        return !empty($key) && isset($config[$key]) ? $config[$key] : $config;
    }


    /**
     * path where the file has to be uploaded
     * @return string
     */
    public static function getUploadPath()
    {
        return '';
    }

    /**
     * url to render the file
     * @return string
     */
    public static function getImageCDNUrl()
    {
        return '';
    }
    
    public static function getOtpUrl() 
    {
        return 'http://www.gaadi.com/ajax/verify_otp_code.php';
    }
    
    public static function getOneSignalAppID()
    {
        return "16b59b4e-a2d2-4cd1-a06f-366af4d75eef";
    }
    
    public static function captureApiTraffic()
    {
        return true;
    }
}

