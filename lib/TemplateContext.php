<?php

class TemplateContext
{
    private $data = [];

    // Assign a single value (replaces previous value if any)
    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    // Add a value to an array list under the same key
    public function add(string $key, $value): void
    {
        if (!isset($this->data[$key])) {
            $this->data[$key] = [];
        } elseif (!is_array($this->data[$key])) {
            // If a single value was assigned before, convert it to array
            $this->data[$key] = [$this->data[$key]];
        }

        $this->data[$key][] = $value;
    }

    // Retrieve value by key
    public function get(string $key)
    {
        return $this->data[$key] ?? null;
    }

    // Return entire context
    public function all(): array
    {
        return $this->data;
    }

    public function delete($key)
    {
        unset($this->data[$key]);
    }
}
