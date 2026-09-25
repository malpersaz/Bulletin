<?php

class BaseModuleValidator
{
    protected $lang;
    protected $validation_lang;
    protected $module_name;
    protected $rules = [];
    protected $errors = [];
    protected $current_lang;
    
    public function __construct($module_name)
    {
        $this->module_name = $module_name;
        $this->lang = Load::lang($module_name);
        $this->current_lang = Registry::get('lang');
        $this->loadValidationLanguage();
        $this->setupRules();
    }
    
    protected function loadValidationLanguage()
    {
        $validation_lang_file = root_dir . 'lang' . DS . 'validator' . DS . $this->current_lang . '.php';
        
        if (file_exists($validation_lang_file)) {
            include $validation_lang_file;
            $this->validation_lang = isset($lang['validation']) ? $lang['validation'] : [];
        } else {
            // Fallback to English if language file doesn't exist
            $fallback_file = root_dir . 'lang' . DS . 'validator' . DS . 'en.php';
            if (file_exists($fallback_file)) {
                include $fallback_file;
                $this->validation_lang = isset($lang['validation']) ? $lang['validation'] : [];
            } else {
                $this->validation_lang = [];
            }
        }
    }
    
    protected function setupRules()
    {
        // Override this method in child classes to define validation rules
    }
    
    public function check($id = null)
    {
        $this->errors = [];

        // Use the existing Validator system
        $validator = Load::lib('Validator');

        foreach ($this->rules as $field => $fieldRules) {
            foreach ($fieldRules as $rule => $params) {
                $this->validateField($validator, $field, $rule, $params);
            }
        }

        if ($validator->check()) {
            $this->errors = $validator->list_errors();
            return true; // Has errors
        }

        return false; // No errors
    }
    
    protected function validateField($validator, $field, $rule, $params)
    {
        $fieldLabel = $this->getFieldLabel($field);
        
        switch ($rule) {
            case 'required':
                $message = $this->getValidationMessage('required', $fieldLabel);
                $validator->add([
                    'name' => $field,
                    'func' => 'required',
                    'arg' => '',
                    'message' => $message
                ]);
                break;
                
            case 'length':
                $message = $this->getValidationMessage('length', $fieldLabel, $params['min'], $params['max']);
                $validator->add([
                    'name' => $field,
                    'func' => 'length',
                    'arg' => [
                        'min' => $params['min'],
                        'max' => $params['max']
                    ],
                    'message' => $message
                ]);
                break;
                
            case 'in':
                $message = $this->getValidationMessage('in', $fieldLabel, implode(', ', $params));
                $validator->add([
                    'name' => $field,
                    'func' => 'array',
                    'arg' => $params,
                    'message' => $message
                ]);
                break;
                
            case 'email':
                $message = $this->getValidationMessage('email', $fieldLabel);
                $validator->add([
                    'name' => $field,
                    'func' => 'email',
                    'arg' => '',
                    'message' => $message
                ]);
                break;
                
            case 'date':
                $message = $this->getValidationMessage('date', $fieldLabel);
                $validator->add([
                    'name' => $field,
                    'func' => 'date',
                    'arg' => '',
                    'message' => $message
                ]);
                break;
                
            case 'numeric':
                $message = $this->getValidationMessage('numeric', $fieldLabel);
                $validator->add([
                    'name' => $field,
                    'func' => 'num',
                    'arg' => '',
                    'message' => $message
                ]);
                break;
                
            case 'alpha':
                $message = $this->getValidationMessage('alpha', $fieldLabel);
                $validator->add([
                    'name' => $field,
                    'func' => 'char',
                    'arg' => '',
                    'message' => $message
                ]);
                break;
                
            case 'alphaNum':
                $message = $this->getValidationMessage('alphaNum', $fieldLabel);
                $validator->add([
                    'name' => $field,
                    'func' => 'alphanum',
                    'arg' => '',
                    'message' => $message
                ]);
                break;
        }
    }
    
    protected function getFieldLabel($field)
    {
        // Try to get the field label from language files
        if (isset($this->lang[$this->module_name]['admin']['columns'][$field])) {
            return $this->lang[$this->module_name]['admin']['columns'][$field];
        }
        
        return ucfirst($field);
    }
    
    protected function getValidationMessage($rule, ...$params)
    {
        // Use centralized validation language
        if (isset($this->validation_lang[$rule])) {
            return sprintf($this->validation_lang[$rule], ...$params);
        }
        
        // Fallback to default English messages if validation language not available
        $defaultMessages = [
            'required' => '%s field is required',
            'length' => '%s must be between %s and %s characters',
            'email' => '%s must be a valid email address',
            'date' => '%s must be a valid date',
            'numeric' => '%s must be a numeric value',
            'alpha' => '%s must contain only letters',
            'alphaNum' => '%s must contain only letters and numbers',
            'in' => '%s must be one of: %s',
            'unique' => '%s must be unique',
            'min' => '%s must be at least %s',
            'max' => '%s can be at most %s'
        ];
        
        if (isset($defaultMessages[$rule])) {
            return sprintf($defaultMessages[$rule], ...$params);
        }
        
        // Ultimate fallback
        return $params[0] . ' field is invalid';
    }
    
    public function getErrors()
    {
        return $this->errors;
    }
}