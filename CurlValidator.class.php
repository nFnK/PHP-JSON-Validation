<?php

    // dependency check
    if (class_exists('Curler') === false) {
        throw new Exception(
            '*Curler* class required. Please see ' .
            'https://github.com/onassar/PHP-Curler'
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
         * _cacheCurler
         * 
         * Stores Curler references in request memory incase there is a want to
         * reuse them.
         * 
         * @access protected
         * @static
         * @param  Curler $url
         * @param  String $url
         * @param  String $type
         * @return void
         */
        protected static function _cacheCurler($curler, $url, $type)
        {
            $GLOBALS['curlers'][$url][$type] = $curler;
        }

        /**
         * _getCurler
         * 
         * Wrapper for accessing curlers, incase they were cached in the
         * validation process (or elsewhere).
         * 
         * @access protected
         * @static
         * @param  String $url
         * @param  String $type
         * @return Curler|false
         */
        protected static function _getCurler($url, $type)
        {
            if (isset($GLOBALS['curlers'][$url][$type])) {
                return $GLOBALS['curlers'][$url][$type];
            }
            return false;
        }

        /**
         * urlContentIsNotEmpty
         * 
         * @access public
         * @static
         * @param  String $url
         * @return Boolean
         */
        public static function urlContentIsNotEmpty($url)
        {
            // "get" for content
            $curler = self::_getCurler($url, 'get');
            if ($curler === false) {
                $curler = (new Curler());
                $curler->setLimit(1024);
                $curler->setTimeout(5);
                self::_cacheCurler($curler, $url, 'get');
                $curler->get($url);
            }
            $response = $curler->getResponse();
            return !empty($response);
        }

        /**
         * urlContentSizeIsLessThan
         * 
         * @access public
         * @static
         * @param  String $url
         * @param  Integer $maxKilobytes
         * @return Boolean
         */
        public static function urlContentSizeIsLessThan($url, $maxKilobytes)
        {
            // "get" for content; check info for download size
            $curler = self::_getCurler($url, 'get');
            if ($curler === false) {
                $curler = (new Curler());
                $curler->setLimit(1024);
                $curler->setTimeout(5);
                self::_cacheCurler($curler, $url, 'get');
                $curler->get($url);
            }
            $info = $curler->getInfo();
            $contentSizeInBytes = (int) $info['size_download'];
            return $contentSizeInBytes > 0
                && $contentSizeInBytes < $maxKilobytes * 1024;
        }

        /**
         * urlContentTypeIsHtml
         * 
         * @access public
         * @static
         * @param  String $url
         * @return Boolean
         */
        public static function urlContentTypeIsHtml($url)
        {
            // "head" for content type
            $curler = self::_getCurler($url, 'head');
            if ($curler === false) {
                $curler = (new Curler());
                $curler->setLimit(1024);
                $curler->setTimeout(5);
                self::_cacheCurler($curler, $url, 'head');
                $curler->head($url);
            }
            $info = $curler->getInfo();
            return isset($info['content_type'])
                && strstr($info['content_type'], 'text/html') !== false;
        }

        /**
         * urlStatusCode
         * 
         * @access public
         * @static
         * @param  String $url
         * @param  Array $allowedStatusCodes (default: array(200))
         * @return Boolean
         */
        public static function urlStatusCode($url, $allowedStatusCodes = array(200))
        {
            // "head" for content type
            $curler = self::_getCurler($url, 'head');
            if ($curler === false) {
                $curler = (new Curler());
                $curler->setLimit(1024);
                $curler->setTimeout(5);
                self::_cacheCurler($curler, $url, 'head');
                $curler->head($url);
            }
            $info = $curler->getInfo();
            return isset($info['http_code'])
                && in_array($info['http_code'], $allowedStatusCodes);
        }
    }