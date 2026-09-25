<?php

include(__DIR__ . '/Valid_Core.php');

class Validator
{
    public $valid;
    private $errors = [];

    public function __construct()
    {
        $this->valid = new Valid_Core();
    }

    public function add($data)
    {
        $arg = $data['arg'] ?? '';
        $func = $data['func'] ?? ($data['function'] ?? ($data['fonk'] ?? ''));
        $name = $data['name'] ?? '';
        $message = $data['message'] ?? ($data['mesaj'] ?? '');

        if ($this->execute($this->valid, $func, $data) === false) {
            $this->errors[] = $message;
        }
    }

    public function __call($name, $arguments)
    {
        if ($name === 'ekle' && isset($arguments[0])) {
            return $this->add($arguments[0]);
        }
        throw new BadMethodCallException("Method {$name} does not exist on Validator.");
    }

    private function execute($class, $method, $data)
    {
        $method = "valid_" . $method;
        if (method_exists($class, $method)) {
            return (bool) $class->$method($data);
        }
        exit(Helper::red_text(Req::Clear($method) . ' Validator Function Not Found'));
    }

    public function check()
    {
        return count($this->errors) > 0;
    }

    public function list_errors()
    {
        return $this->errors;
    }
}