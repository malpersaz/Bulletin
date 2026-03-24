<?php

class BaseModuleValidator
{
    protected $lang;
    protected $validation_lang;
    protected $module_name;
    protected $rules = [];
    protected $hatalar = [];
    protected $current_lang;
    
    public function __construct($module_name)
    {
        $this->module_name = $module_name;
        $this->lang = Load::lang($module_name);
        $this->current_lang = Kayit::getir('lang');
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
            // Fallback to Turkish if language file doesn't exist
            $fallback_file = root_dir . 'lang' . DS . 'validator' . DS . 'tr.php';
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
        $this->hatalar = [];
        
        // Use the existing Validator system
        $validator = Load::lib('Validator');
        
        foreach ($this->rules as $field => $fieldRules) {
            foreach ($fieldRules as $rule => $params) {
                $this->validateField($validator, $field, $rule, $params);
            }
        }
        
        if ($validator->check()) {
            $this->hatalar = $validator->list_errors();
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
                    'fonk' => 'required',
                    'arg' => '',
                    'mesaj' => $message
                ]);
                break;
                
            case 'length':
                $message = $this->getValidationMessage('length', $fieldLabel, $params['min'], $params['max']);
                $validator->add([
                    'name' => $field,
                    'fonk' => 'length',
                    'arg' => [
                        'min' => $params['min'],
                        'max' => $params['max']
                    ],
                    'mesaj' => $message
                ]);
                break;
                
            case 'in':
                $message = $this->getValidationMessage('in', $fieldLabel, implode(', ', $params));
                $validator->add([
                    'name' => $field,
                    'fonk' => 'array',
                    'arg' => $params,
                    'mesaj' => $message
                ]);
                break;
                
            case 'email':
                $message = $this->getValidationMessage('email', $fieldLabel);
                $validator->add([
                    'name' => $field,
                    'fonk' => 'email',
                    'arg' => '',
                    'mesaj' => $message
                ]);
                break;
                
            case 'date':
                $message = $this->getValidationMessage('date', $fieldLabel);
                $validator->add([
                    'name' => $field,
                    'fonk' => 'date',
                    'arg' => '',
                    'mesaj' => $message
                ]);
                break;
                
            case 'numeric':
                $message = $this->getValidationMessage('numeric', $fieldLabel);
                $validator->add([
                    'name' => $field,
                    'fonk' => 'num',
                    'arg' => '',
                    'mesaj' => $message
                ]);
                break;
                
            case 'alpha':
                $message = $this->getValidationMessage('alpha', $fieldLabel);
                $validator->add([
                    'name' => $field,
                    'fonk' => 'char',
                    'arg' => '',
                    'mesaj' => $message
                ]);
                break;
                
            case 'alphaNum':
                $message = $this->getValidationMessage('alphaNum', $fieldLabel);
                $validator->add([
                    'name' => $field,
                    'fonk' => 'alphanum',
                    'arg' => '',
                    'mesaj' => $message
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
        
        // Fallback to default messages if validation language not available
        $defaultMessages = [
            'required' => '%s alanı gereklidir',
            'length' => '%s %s ile %s karakter arasında olmalıdır',
            'email' => '%s geçerli bir e-posta adresi olmalıdır',
            'date' => '%s geçerli bir tarih olmalıdır',
            'numeric' => '%s sayısal bir değer olmalıdır',
            'alpha' => '%s sadece harf içerebilir',
            'alphaNum' => '%s sadece harf ve rakam içerebilir',
            'in' => '%s şu değerlerden biri olmalıdır: %s',
            'unique' => '%s benzersiz olmalıdır',
            'min' => '%s en az %s olmalıdır',
            'max' => '%s en fazla %s olabilir'
        ];
        
        if (isset($defaultMessages[$rule])) {
            return sprintf($defaultMessages[$rule], ...$params);
        }
        
        // Ultimate fallback
        return $params[0] . ' alanı geçersiz';
    }
    
    public function getErrors()
    {
        return $this->hatalar;
    }
}