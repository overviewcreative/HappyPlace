<?php

namespace HappyPlace\Integration;

/**
 * Integration Exception
 *
 * Custom exception class for integration-related errors
 *
 * @package HappyPlace\Integration
 * @since 2.0.0
 */
class Integration_Exception extends \Exception {
    
    /**
     * Integration type that caused the exception
     * @var string
     */
    protected $integration_type;
    
    /**
     * Additional context data
     * @var array
     */
    protected $context;
    
    /**
     * Constructor
     * 
     * @param string $message Exception message
     * @param int $code Exception code
     * @param \Exception $previous Previous exception
     * @param string $integration_type Integration type
     * @param array $context Additional context
     */
    public function __construct($message = '', $code = 0, \Exception $previous = null, $integration_type = '', $context = []) {
        parent::__construct($message, $code, $previous);
        
        $this->integration_type = $integration_type;
        $this->context = $context;
    }
    
    /**
     * Get integration type
     * 
     * @return string
     */
    public function getIntegrationType() {
        return $this->integration_type;
    }
    
    /**
     * Get context data
     * 
     * @return array
     */
    public function getContext() {
        return $this->context;
    }
    
    /**
     * Get formatted error for logging
     * 
     * @return array
     */
    public function getFormattedError() {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'integration_type' => $this->integration_type,
            'context' => $this->context,
            'trace' => $this->getTraceAsString()
        ];
    }
}
