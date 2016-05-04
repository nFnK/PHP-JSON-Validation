<?php

    // dependency check
    if (class_exists('Curler') === false) {
        throw new Exception(
            '*Curler* class required. Please see ' .
            'https://github.com/onassar/PHP-Curler'
        );
    }

    // dependency check
    if (class_exists('RequestCache') === false) {
        throw new Exception(
            '*RequestCache* class required. Please see ' .
            'https://github.com/onassar/PHP-RequestCache'
        );
    }

    /**
     * CurlValidator
     * 
     * @author   Oliver Nassar <onassar@gmail.com>
     * @abstract
     */
    abstract class CurlValidator
    {
        /**
         * _makeRequestToUrl
         * 
         * @access public
         * @static
         * @param  string $url
         * @return void
         */
        protected static function _makeRequestToUrl($url)
        {
            $curler = RequestCache::simpleRead('curler');
            $urlContent = $curler->getResponse();
            if (is_null($urlContent)) {
                $curler->get($url);
            }
        }

        /**
         * _getUrlInfo
         * 
         * @access public
         * @static
         * @param  string $url
         * @return array
         */
        protected static function _getUrlInfo($url)
        {
            self::_makeRequestToUrl($url);
            $curler = RequestCache::simpleRead('curler');
            return $curler->getInfo();
        }

        /**
         * _getUrlContent
         * 
         * @access public
         * @static
         * @param  string $url
         * @return string
         */
        protected static function _getUrlContent($url)
        {
            self::_makeRequestToUrl($url);
            $curler = RequestCache::simpleRead('curler');
            return $curler->getResponse();
        }

        /**
         * numberOfRedirectsIsLessThan
         * 
         * @access public
         * @static
         * @param  string $url
         * @param  integer $redirectLimit
         * @return boolean
         */
        public static function numberOfRedirectsIsLessThan($url, $redirectLimit)
        {
            $urlInfo = self::_getUrlInfo($url);
            return isset($urlInfo['redirect_count'])
                && (int) $urlInfo['redirect_count'] < (int) $redirectLimit;
        }

        /**
         * urlContainsHeadTag
         * 
         * @access public
         * @static
         * @param  string $url
         * @return boolean
         */
        public static function urlContainsHeadTag($url)
        {
            $urlContent = self::_getUrlContent($url);
            return strstr(strtolower($urlContent), '<head') !== false;
        }

        /**
         * urlCharsetIsDefined
         * 
         * @access public
         * @static
         * @param  string $url
         * @return boolean
         */
        public static function urlCharsetIsDefined($url)
        {
            self::_makeRequestToUrl($url);
            $curler = RequestCache::simpleRead('curler');
            $charset = $curler->getCharset();
            return $charset !== false;
        }

        /**
         * urlCharsetIsSupported
         * 
         * @access public
         * @static
         * @param  string $url
         * @return boolean
         */
        public static function urlCharsetIsSupported($url)
        {
            self::_makeRequestToUrl($url);
            $curler = RequestCache::simpleRead('curler');
            $charset = $curler->getCharset();
            $charsetIsSupported = StringValidator::inList(
                $charset,
                array(
                    'utf-8',
                    'ascii',
                    'iso-8859-1',
                    'iso-8859-15',
                    'euc-kr',
                    'euc-jp',
                    'windows-1252'
                )
            );
            if ($charsetIsSupported === true) {
                return true;
            }
            $urlContent = self::_getUrlContent($url);
            return mb_check_encoding($urlContent, 'UTF-8');
        }

        /**
         * urlContentIsNotEmpty
         * 
         * @access public
         * @static
         * @param  string $url
         * @return boolean
         */
        public static function urlContentIsNotEmpty($url)
        {
            $urlContent = self::_getUrlContent($url);
            return !empty($urlContent);
        }

        /**
         * urlContentSizeIsLessThan
         * 
         * Try/catch here in case the Curler writeCallback method bails on the
         * internal Curler filesize requirement. The application-level file
         * size limit check is done below using `strlen`.
         * 
         * @access public
         * @static
         * @param  string $url
         * @param  integer $maxBytes
         * @return boolean
         */
        public static function urlContentSizeIsLessThan($url, $maxBytes)
        {
            try {
                $urlContent = self::_getUrlContent($url);
                return strlen($urlContent) < $maxBytes;
            } catch (Exception $exception) {
                return false;
            }
        }

        /**
         * urlContentTypeIsHtml
         * 
         * @access public
         * @static
         * @param  string $url
         * @return boolean
         */
        public static function urlContentTypeIsHtml($url)
        {
            $urlInfo = self::_getUrlInfo($url);
            return isset($urlInfo['content_type'])
                &&
                (
                    strstr(strtolower($urlInfo['content_type']), 'text/html') !== false
                    // || strstr(strtolower($urlInfo['content_type']), 'text/xml') !== false
                );
        }

        /**
         * urlContentTypeIsImage
         * 
         * @access public
         * @static
         * @param  string $url
         * @return boolean
         */
        public static function urlContentTypeIsImage($url)
        {
            $urlInfo = self::_getUrlInfo($url);
            $allowable = array(
                'image/png',
                'image/jpg',
                'image/jpeg',
                'image/gif'
            );
            return isset($urlInfo['content_type'])
                && in_array($urlInfo['content_type'], $allowable);
        }

        /**
         * urlRepondsWithinMaxSeconds
         * 
         * This is a test to ensure that the page responds within an allotted
         * time.
         * 
         * @access public
         * @static
         * @param  string $url
         * @param  integer $maxNumberOfSeconds
         * @return boolean
         */
        public static function urlRepondsWithinMaxSeconds($url, $maxNumberOfSeconds)
        {
            $urlInfo = self::_getUrlInfo($url);
            return isset($urlInfo['total_time'])
                && (float) $urlInfo['total_time'] < (float) $maxNumberOfSeconds;
        }

        /**
         * urlStatusCodeIsValid
         * 
         * @access public
         * @static
         * @param  string $url
         * @param  array $allowedStatusCodes (default: array(200))
         * @return boolean
         */
        public static function urlStatusCodeIsValid(
            $url, array $allowedStatusCodes = array(200)
        ) {
            $urlInfo = self::_getUrlInfo($url);
            return isset($urlInfo['http_code'])
                && in_array($urlInfo['http_code'], $allowedStatusCodes);
        }
    }
