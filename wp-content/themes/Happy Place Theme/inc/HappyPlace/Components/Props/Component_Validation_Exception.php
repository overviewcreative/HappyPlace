<?php
/**
 * Component Validation Exception
 *
 * @package HappyPlace\Components\Props
 * @since 2.0.0
 */

namespace HappyPlace\Components\Props;

use Exception;

class Component_Validation_Exception extends Exception {
    
    /**
     * Validation errors
     * @var array
     */
    protected $validation_errors = [];
    
    /**
     * Constructor
     *
     * @param string $message
     * @param array $validation_errors
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct($message = "", $validation_errors = [], $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->validation_errors = $validation_errors;
    }
    
    /**
     * Get validation errors
     *
     * @return array
     */
    public function getValidationErrors() {
        return $this->validation_errors;
    }
    
    /**
     * Set validation errors
     *
     * @param array $errors
     */
    public function setValidationErrors($errors) {
        $this->validation_errors = $errors;
    }
    
    /**
     * Add validation error
     *
     * @param string $error
     */
    public function addValidationError($error) {
        $this->validation_errors[] = $error;
    }
    
    /**
     * Check if has validation errors
     *
     * @return bool
     */
    public function hasValidationErrors() {
        return !empty($this->validation_errors);
    }
    
    /**
     * Get formatted error message
     *
     * @return string
     */
    public function getFormattedMessage() {
        $message = $this->getMessage();
        
        if ($this->hasValidationErrors()) {
            $message .= "\nValidation Errors:\n";
            foreach ($this->validation_errors as $error) {
                $message .= "- " . $error . "\n";
            }
        }
        
        return $message;
    }
}
