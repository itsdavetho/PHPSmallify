<?php
/**
 * PHPSmallify -- Make your PHP code "small""
 *
 * PHP version 5
 *
 * Copyright (c) 2013, Orpheus
 * All rights reserved.
 * Redistribution and use in source and binary forms,
 * with or without modification, are permitted provided
 * that the following conditions are met:
 * Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution.
 * Neither the name of the <ORGANIZATION> nor the names of its contributors
 * may be used to endorse or promote products derived from this software without
 * specific prior written permission.
 * 
 * @category  Utility
 * @package   Orpheus\PHPSmallify
 * @author    Orpheus <lolidunno@live.co.uk>
 * @copyright 2013-2013 Orpheus
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version   GIT: $Id$
 * @link      https://github.com/xxOrpheus/PHPSmallify
 */

namespace Orpheus;

/**
 * PHPSmallify -- Make your PHP code "small""
 * 
 * @category  Utility
 * @package   Orpheus\PHPSmallify
 * @author    Orpheus <lolidunno@live.co.uk>
 * @copyright 2013-2013 Orpheus
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version   Release: 1.1.5
 * @link      https://github.com/xxOrpheus/PHPSmallify
 */

class PHPSmallify {
    protected $reserved_variables = array('_GET', '_POST', '_COOKIE', '_SESSION', '_SERVER', 'GLOBALS', '_FILES', '_REQUEST', '_ENV', 'php_errormsg', 'HTTP_RAW_POST_DATA', 'http_response_header', 'argv', 'argc', 'this');
    
    protected $reserved_methods = array('__construct', '__destruct', '__call', '__callStatic', '__get', '__set', '__isset', '__unset', '__sleep', '__wakeup', '__toString', '__invoke', '__set_state', '__clone');
    
    protected $php_code = null, $new_php_code = null, $php_code_size;
    protected $variables = array(), $functions = array();
    
    /**
     * 
     * The constructor
     * 
     * @param string $file        The file to load
     * @param string $code[=null] The PHP code to load.
     * 
     **/
    public function __construct($file = null, $code = null) {
        if ($file !== null && $code === null) {
            $this->loadFile($file);
        } else if ($code !== null) {
            $this->php_code = $code;
            $this->php_code_size = strlen($this->php_code);
        }
    }
    
    /**
     * 
     * Load PHP code from a file
     * 
     * @param string $file The path to the file
     * 
     **/
    public function loadFile($file) {
        if (is_file($file)) {
            $this->php_code = file_get_contents($file);
            $this->php_code_size = strlen($this->php_code);
        } else {
            throw new \Exception(__METHOD__ . ': "' . $file . '" does not exist.');
        }
    }
    
    /**
     * 
     * Is it a valid label? From http://www.php.net/manual/en/language.functions.php
     *
     * @param string $in The label
     *
     * @return boolean
     *
     */
    public function validPHP($in) {
        return preg_match('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', $in);
    }
    
    /**
     *
     * Random encoder - Encodes string with random functions.
     *
     * @return array[result, encoders_used]
     *
     */
    public function randomEncode($str, $loops = 4) {
        $newStr = $str;
        $encoders = array('base64_encode' => 'base64_decode', 'str_rot13' => 'str_rot13');
        $decoders = array();
        for($i = 0; $i < $loops; $i++) {
            $encoder = array_rand($encoders);
            $newStr = $encoder($newStr);
            $decoder = $encoders[$encoder];
            $decoders[] = $decoder;
        }
        $decodeString = '';
        foreach($decoders as $decoder) {
            $decodeString .= $decoder . '(';
        }
        $decodeString .= '"' . $newStr . '"';
        $decodeString .= str_repeat(')', $loops);
        return $decodeString;
    }

    /**
     *
     * Make it "smallified"
     *
     * @param boolean $stripComments   Should we remove all comments?
     * @param boolean $stripWhiteSpace Should we remove whitespace?
     *
     * @return boolean 
     *
     */
    public function smallify($stripComments = true, $stripWhiteSpace = true, $changeVariables = true, $encodeStrings = false, $finalObfuscate = false) {
        if ($this->php_code == null) {
            throw new \Exception(__METHOD__ . ': Need to load PHP code first.');
        }
        $this->php_code = mb_convert_encoding($this->php_code, 'UTF-8');
        
        $tokens = token_get_all($this->php_code);
        $this->new_php_code = null;
        
        $chars = range('a', 'z');
        $countChars = count($chars);
        $usedVariables = array();
        $replacedVariables = array();
        $usedFunctions = array();
        $replacedFunction = array();
        $i = 0;
        $ignoreBlock = false;
        foreach ($tokens as $key => $token) {
            if (!is_array($token)) {
                $this->new_php_code .= $token;
                continue;
            }
            if (($token[0] == T_VARIABLE || (isset($tokens[$key - 2]) && $tokens[$key - 2][0] == T_VARIABLE && $tokens[$key - 2][1] == '$this' && isset($tokens[$key - 1]) && $tokens[$key - 1][0] = T_OBJECT_OPERATOR && $tokens[$key + 1] != '(')) && !in_array(substr($token[1], 1), $this->reserved_variables)) {
                if ((isset($tokens[$key - 2]) && $tokens[$key - 2][0] == T_VARIABLE) && (isset($tokens[$key - 1]) && $tokens[$key - 1][0] == T_OBJECT_OPERATOR)) {
                    $prefix = '';
                } else {
                    $prefix = '$';
                }
                if ($changeVariables && !$ignoreBlock) {
                    if (substr($token[1], 0, 1) == '$') {
                        $token[1] = substr($token[1], 1);
                    }
                    if (isset($replacedVariables[$token[1]])) {
                        $token[1] = $prefix . $replacedVariables[$token[1]];
                    } else {
                        $oldVariable = $token[1];
                        $token[1] = $chars[$i];
                        while (in_array($token[1], $usedVariables)) {
                            $token[1] .= $chars[$i];
                        }
                        $usedVariables[] = $token[1];
                        $replacedVariables[$oldVariable] = $token[1];
                        $token[1] = $prefix . $token[1];
                        $i++;
                    }
                }
            }
                
            if ($encodeStrings && !$ignoreBlock) {
                if($token[0] == T_CONSTANT_ENCAPSED_STRING) {
                    $str = substr($token[1], 1);
                    $str = substr($str, 0, -1);
                    $str = $this->randomEncode($str);
                    $token[1] = $str;
                }
            }
            if($token[0] == T_COMMENT || $token[0] == T_DOC_COMMENT) {
                $comment = trim($token[1]);
                if($comment == '//smallify-ignore' || $comment == '/*smallify-ignore*/') {
                    $ignoreBlock = true;
                }
                if($comment == '//smallify-ignore-end' || $comment == '/*smallify-ignore-end*/') {
                    $ignoreBlock = false;
                }
            }            
            if ($stripComments && ($token[0] == T_COMMENT || $token[0] == T_DOC_COMMENT)) {
                continue;
            }
            
            if ($stripWhiteSpace && $token[0] == T_WHITESPACE) {
                if (isset($tokens[$key - 1]) && isset($tokens[$key + 1]) && is_array($tokens[$key - 1]) && is_array($tokens[$key + 1]) && $this->validPHP($tokens[$key - 1][1]) && $this->validPHP($tokens[$key + 1][1])) {
                    $this->new_php_code .= ' ';
                }
                continue;
            }
            
            $this->new_php_code .= $token[1];
            if ($i >= $countChars - 1) {
                $i = 0;
            }
        }
        if($finalObfuscate) {
            $this->new_php_code = $this->randomEncode($this->new_php_code);
        }
        $compression_ratio = strlen($this->new_php_code) / $this->php_code_size;
        $space_savings = 1 - (strlen($this->new_php_code) / $this->php_code_size);
        
        return array(
            'smallified' => $this->new_php_code,
            'initial_size' => $this->php_code_size,
            'new_size' => strlen($this->new_php_code),
            'compression_ratio' => $compression_ratio,
            'space_savings' => $space_savings * 100
        );
    }
}
