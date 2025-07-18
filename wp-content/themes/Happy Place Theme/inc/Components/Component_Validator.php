<?php
/**
 * Component Validator
 * 
 * Validates component props against defined rules
 *
 * @package HappyPlace\Components\Props
 * @since 2.0.0
 */

namespace HappyPlace\Components\Props;

class Component_Validator {
    
    /**
     * Validation rules
     * @var array
     */
    private $rules;
    
    /**
     * Constructor
     *
     * @param array $prop_definitions
     */
    public function __construct($prop_definitions) {
        $this->rules = $prop_definitions;
    }
    
    /**
     * Validate props against rules
     *
     * @param array $props
     * @return bool
     * @throws Component_Validation_Exception
     */
    public function validate($props) {
        $errors = [];
        
        // Check each rule
        foreach ($this->rules as $prop => $rules) {
            $this->validate_prop($prop, $props, $rules, $errors);
        }
        
        // If there are errors, throw exception
        if (!empty($errors)) {
            throw new Component_Validation_Exception(
                'Component validation failed: ' . implode(', ', $errors),
                $errors
            );
        }
        
        return true;
    }
    
    /**
     * Validate individual prop
     *
     * @param string $prop
     * @param array $props
     * @param array $rules
     * @param array &$errors
     */
    private function validate_prop($prop, $props, $rules, &$errors) {
        $value = $props[$prop] ?? null;
        
        // Check if required
        if (!empty($rules['required']) && $this->is_empty($value)) {
            $errors[] = "Required prop '{$prop}' is missing or empty";
            return;
        }
        
        // Skip validation if value is empty and not required
        if ($this->is_empty($value) && empty($rules['required'])) {
            return;
        }
        
        // Validate type
        if (!empty($rules['type'])) {
            $this->validate_type($prop, $value, $rules['type'], $errors);
        }
        
        // Validate enum values
        if (!empty($rules['enum'])) {
            $this->validate_enum($prop, $value, $rules['enum'], $errors);
        }
        
        // Validate array items enum
        if (!empty($rules['enum_items']) && is_array($value)) {
            $this->validate_enum_items($prop, $value, $rules['enum_items'], $errors);
        }
        
        // Validate string length
        if (is_string($value)) {
            $this->validate_string_length($prop, $value, $rules, $errors);
        }
        
        // Validate array length
        if (is_array($value)) {
            $this->validate_array_length($prop, $value, $rules, $errors);
        }
        
        // Validate numeric range
        if (is_numeric($value)) {
            $this->validate_numeric_range($prop, $value, $rules, $errors);
        }
        
        // Custom validation
        if (!empty($rules['custom']) && is_callable($rules['custom'])) {
            $this->validate_custom($prop, $value, $rules['custom'], $errors);
        }
    }
    
    /**
     * Check if value is empty
     *
     * @param mixed $value
     * @return bool
     */
    private function is_empty($value) {
        if ($value === null || $value === '') {
            return true;
        }
        
        if (is_array($value) && empty($value)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Validate type
     *
     * @param string $prop
     * @param mixed $value
     * @param string $expected_type
     * @param array &$errors
     */
    private function validate_type($prop, $value, $expected_type, &$errors) {
        $actual_type = gettype($value);
        
        $type_map = [
            'string' => 'string',
            'array' => 'array',
            'object' => 'object',
            'boolean' => 'boolean',
            'bool' => 'boolean',
            'integer' => 'integer',
            'int' => 'integer',
            'float' => 'double',
            'double' => 'double',
            'number' => ['integer', 'double']
        ];
        
        $valid_types = $type_map[$expected_type] ?? $expected_type;
        
        if (is_array($valid_types)) {
            if (!in_array($actual_type, $valid_types)) {
                $errors[] = "Prop '{$prop}' must be one of types: " . implode(', ', $valid_types) . ", {$actual_type} given";
            }
        } else {
            if ($actual_type !== $valid_types) {
                $errors[] = "Prop '{$prop}' must be {$expected_type}, {$actual_type} given";
            }
        }
    }
    
    /**
     * Validate enum values
     *
     * @param string $prop
     * @param mixed $value
     * @param array $enum_values
     * @param array &$errors
     */
    private function validate_enum($prop, $value, $enum_values, &$errors) {
        if (!in_array($value, $enum_values, true)) {
            $allowed = implode(', ', $enum_values);
            $errors[] = "Prop '{$prop}' must be one of: {$allowed}. '{$value}' given";
        }
    }
    
    /**
     * Validate enum items for arrays
     *
     * @param string $prop
     * @param array $value
     * @param array $enum_values
     * @param array &$errors
     */
    private function validate_enum_items($prop, $value, $enum_values, &$errors) {
        foreach ($value as $index => $item) {
            if (!in_array($item, $enum_values, true)) {
                $allowed = implode(', ', $enum_values);
                $errors[] = "Prop '{$prop}[{$index}]' must be one of: {$allowed}. '{$item}' given";
            }
        }
    }
    
    /**
     * Validate string length
     *
     * @param string $prop
     * @param string $value
     * @param array $rules
     * @param array &$errors
     */
    private function validate_string_length($prop, $value, $rules, &$errors) {
        $length = strlen($value);
        
        if (!empty($rules['min_length']) && $length < $rules['min_length']) {
            $errors[] = "Prop '{$prop}' must be at least {$rules['min_length']} characters. {$length} given";
        }
        
        if (!empty($rules['max_length']) && $length > $rules['max_length']) {
            $errors[] = "Prop '{$prop}' must be no more than {$rules['max_length']} characters. {$length} given";
        }
    }
    
    /**
     * Validate array length
     *
     * @param string $prop
     * @param array $value
     * @param array $rules
     * @param array &$errors
     */
    private function validate_array_length($prop, $value, $rules, &$errors) {
        $length = count($value);
        
        if (!empty($rules['min_items']) && $length < $rules['min_items']) {
            $errors[] = "Prop '{$prop}' must have at least {$rules['min_items']} items. {$length} given";
        }
        
        if (!empty($rules['max_items']) && $length > $rules['max_items']) {
            $errors[] = "Prop '{$prop}' must have no more than {$rules['max_items']} items. {$length} given";
        }
    }
    
    /**
     * Validate numeric range
     *
     * @param string $prop
     * @param numeric $value
     * @param array $rules
     * @param array &$errors
     */
    private function validate_numeric_range($prop, $value, $rules, &$errors) {
        if (!empty($rules['min']) && $value < $rules['min']) {
            $errors[] = "Prop '{$prop}' must be at least {$rules['min']}. {$value} given";
        }
        
        if (!empty($rules['max']) && $value > $rules['max']) {
            $errors[] = "Prop '{$prop}' must be no more than {$rules['max']}. {$value} given";
        }
    }
    
    /**
     * Validate using custom function
     *
     * @param string $prop
     * @param mixed $value
     * @param callable $validator
     * @param array &$errors
     */
    private function validate_custom($prop, $value, $validator, &$errors) {
        $result = call_user_func($validator, $value, $prop);
        
        if ($result !== true) {
            $error_message = is_string($result) ? $result : "Custom validation failed for prop '{$prop}'";
            $errors[] = $error_message;
        }
    }
    
    /**
     * Validate all props and return detailed results
     *
     * @param array $props
     * @return array
     */
    public function validate_detailed($props) {
        $results = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'prop_results' => []
        ];
        
        foreach ($this->rules as $prop => $rules) {
            $prop_result = $this->validate_prop_detailed($prop, $props, $rules);
            $results['prop_results'][$prop] = $prop_result;
            
            if (!$prop_result['valid']) {
                $results['valid'] = false;
                $results['errors'] = array_merge($results['errors'], $prop_result['errors']);
            }
            
            if (!empty($prop_result['warnings'])) {
                $results['warnings'] = array_merge($results['warnings'], $prop_result['warnings']);
            }
        }
        
        return $results;
    }
    
    /**
     * Validate individual prop with detailed results
     *
     * @param string $prop
     * @param array $props
     * @param array $rules
     * @return array
     */
    private function validate_prop_detailed($prop, $props, $rules) {
        $result = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'value' => $props[$prop] ?? null
        ];
        
        $this->validate_prop($prop, $props, $rules, $result['errors']);
        
        if (!empty($result['errors'])) {
            $result['valid'] = false;
        }
        
        return $result;
    }
}
