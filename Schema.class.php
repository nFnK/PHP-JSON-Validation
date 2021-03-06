<?php

    /**
     * Schema
     * 
     * General object that encapsulates a JSON-converted validation schema for
     * processing.
     * 
     * @todo    load schema upon instantiation, and validate rules (required
     *          properties/attributes)
     * @example https://github.com/onassar/PHP-JSON-Validation/tree/master/example
     * @link    https://github.com/onassar/PHP-JSON-Validation
     * @author  Oliver Nassar <onassar@gmail.com>
     */
    class Schema
    {
        /**
         * _allowPhpInSchemas
         * 
         * @var     boolean (default: false)
         * @access  protected
         */
        protected $_allowPhpInSchemas = false;

        /**
         * _path
         * 
         * Path to the schema json file
         * 
         * @var     string
         * @access  protected
         */
        protected $_path;

        /**
         * _method
         * 
         * The method that ought to be called for rules retrieval (changing of
         * this property is currently only used/useful by the <SmartSchema>
         * class).
         * 
         * @var     string
         * @access  protected
         */
        protected $_method = 'getRules';

        /**
         * __construct
         * 
         * @access  public
         * @param   string $path
         * @param   boolean $allowPhpInSchemas (default: false)
         * @return  void
         */
        public function __construct($path, $allowPhpInSchemas = false)
        {
            $this->_path = $path;
            $this->_allowPhpInSchemas = $allowPhpInSchemas;
        }

        /**
         * _loadDynamicRules
         * 
         * @access  protected
         * @param   array $rules
         * @return  array
         */
        protected function _loadDynamicRules(array $rules)
        {
            /**
             * Check for a rules property as a string, treat it as though it's
             * a path to another schema, and retrieve it's rules.
             * 
             * This allows for the DRY practice to be applied to the validation
             * schemas.
             */
            foreach ($rules as &$rule) {
                if (isset($rule['rules']) === true) {
                    if (is_string($rule['rules']) === true) {
                        $directoryPath = dirname($this->_path);
                        $raw = $this->_loadSchema(
                            ($directoryPath) . '/' . ($rule['rules'])
                        );
                        $decoded = json_decode($raw, true);
                        $rule['rules'] = $this->_loadDynamicRules($decoded);
                    } else {
                        $rule['rules'] = $this->_loadDynamicRules(
                            $rule['rules']
                        );
                    }
                }
            }
            unset($rule);// See: http://php.net/manual/en/language.references.unset.php
            return $rules;
        }

        /**
         * _loadSchema
         * 
         * @access  protected
         * @param   string $path
         * @return  string
         */
        protected function _loadSchema($path)
        {
            if ($this->_allowPhpInSchemas === true) {
                ob_start();
                include $path;
                $_response = ob_get_contents();
                ob_end_clean();
                return $_response;
            }
            return file_get_contents($path);
        }

        /**
         * _validateSchemaForPropertyLimitations
         * 
         * Validates rules recursively against both a `blocking` and `funnel`
         * property being set to `true`.
         * 
         * @access  public
         * @param   array $rules
         * @return  void
         */
        protected function _validateSchemaForPropertyLimitations(array $rules)
        {
            foreach ($rules as $rule) {
                if (
                    isset($rule['blocking']) === true
                    && $rule['blocking'] === true
                    && isset($rule['funnel']) === true
                    && $rule['funnel'] = true
                ) {
                    throw new Exception(
                        'Blocking and funnel cannot both be set.'
                    );
                }
                if (isset($rule['rules']) === true) {
                    $this->_validateSchemaForPropertyLimitations(
                        $rule['rules']
                    );
                }
            }
        }

        /**
         * getMethod
         * 
         * @access  public
         * @return  string
         */
        public function getMethod()
        {
            return $this->_method;
        }

        /**
         * getPath
         * 
         * @access  public
         * @return  string
         */
        public function getPath()
        {
            return $this->_path;
        }

        /**
         * getRules
         * 
         * @note    Since replacement for dynamical rules (eg. rules that don't
         *          have an array specified, rather a relative path to another
         *          schema) are done within this method, and class that extends
         *          `Schema` and overrides the `_method` property ought to
         *          ensure it uses the `getRules` method before filtering any
         *          rules.
         *          If not, you'll have to ensure the recursive loading of
         *          dynamic rules are done manually.
         * @access  public
         * @return  array
         */
        public function getRules()
        {
            // grab and return schema contents
            $raw = $this->_loadSchema($this->_path);
            $decoded = json_decode($raw, true);

            // json is formatted invalidly; otherwise return the decoded schema
            if ($decoded === null) {
                throw new Exception('Invalidly formatted json');
            }

            // modify potential sub-rules; validate rules
            $decoded = $this->_loadDynamicRules($decoded);
            $this->_validateSchemaForPropertyLimitations($decoded);

            // return rules (aka. schema)
            return $decoded;
        }

        /**
         * setMethod
         * 
         * @access  public
         * @param   string $method
         * @return  void
         */
        public function setMethod($method)
        {
            $this->_method = $method;
        }
    }
