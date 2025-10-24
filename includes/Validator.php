<?php
class Validator {
    private $errors = [];
    private $data;
    private $sanitizedData = [];
    private static $specialChars = ['<', '>', '"', "'", '&', '(', ')', '{', '}', '[', ']'];
    
    public function __construct($data) {
        $this->data = $data;
    }
    
    public function required($field, $message = null) {
        if (!isset($this->data[$field]) || 
            (is_string($this->data[$field]) && trim($this->data[$field]) === '') || 
            (is_array($this->data[$field]) && empty($this->data[$field]))) {
            $this->errors[$field] = $message ?? ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
        return $this;
    }
    
    public function email($field, $message = null) {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field] = $message ?? 'Invalid email format';
            }
        }
        return $this;
    }
    
    public function minLength($field, $length, $message = null) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->errors[$field] = $message ?? ucfirst($field) . " must be at least $length characters";
        }
        return $this;
    }
    
    public function maxLength($field, $length, $message = null) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $length) {
            $this->errors[$field] = $message ?? ucfirst($field) . " must not exceed $length characters";
        }
        return $this;
    }
    
    public function match($field1, $field2, $message = null) {
        if (isset($this->data[$field1]) && isset($this->data[$field2])) {
            if ($this->data[$field1] !== $this->data[$field2]) {
                $this->errors[$field2] = $message ?? "$field1 and $field2 must match";
            }
        }
        return $this;
    }
    
    public function numeric($field, $message = null) {
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field] = $message ?? ucfirst($field) . ' must be a number';
        }
        return $this;
    }
    
    public function date($field, $format = 'Y-m-d', $message = null) {
        if (isset($this->data[$field])) {
            $d = DateTime::createFromFormat($format, $this->data[$field]);
            if (!$d || $d->format($format) !== $this->data[$field]) {
                $this->errors[$field] = $message ?? 'Invalid date format';
            }
        }
        return $this;
    }
    
    public function in($field, $values, $message = null) {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $values)) {
            $this->errors[$field] = $message ?? ucfirst($field) . ' has an invalid value';
        }
        return $this;
    }
    
    public function custom($field, $callback, $message = null) {
        if (isset($this->data[$field])) {
            if (!$callback($this->data[$field])) {
                $this->errors[$field] = $message ?? ucfirst($field) . ' is invalid';
            }
        }
        return $this;
    }
    
    public function fails() {
        return !empty($this->errors);
    }
    
    public function getErrors() {
        return $this->errors;
    }
    
    public function validate() {
        if ($this->fails()) {
            ApiResponse::validationError($this->errors);
        }
        return true;
    }
}