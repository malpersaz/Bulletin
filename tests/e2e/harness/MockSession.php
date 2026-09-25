<?php

namespace E2E\Harness;

class MockSession
{
    private array $data = [];

    public function __construct(array $initial = [])
    {
        $this->data = $initial;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $curr = $this->data;
        foreach ($keys as $k) {
            if (!is_array($curr) || !array_key_exists($k, $curr)) {
                return $default;
            }
            $curr = $curr[$k];
        }
        return $curr;
    }

    public function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $curr = &$this->data;
        foreach ($keys as $k) {
            if (!isset($curr[$k]) || !is_array($curr[$k])) {
                $curr[$k] = [];
            }
            $curr = &$curr[$k];
        }
        $curr = $value;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function remove(string $key): void
    {
        $keys = explode('.', $key);
        $curr = &$this->data;
        $count = count($keys);
        for ($i = 0; $i < $count - 1; $i++) {
            $k = $keys[$i];
            if (!isset($curr[$k]) || !is_array($curr[$k])) {
                return;
            }
            $curr = &$curr[$k];
        }
        unset($curr[$keys[$count - 1]]);
    }

    public function clear(): void
    {
        $this->data = [];
    }

    public function all(): array
    {
        return $this->data;
    }

    public function setAdminUser(int $userId = 1, string $username = 'admin', int $groupId = 1): void
    {
        $this->set('user_id', $userId);
        $this->set('username', $username);
        $this->set('group_id', $groupId);
        $this->set('is_admin', true);
        $this->set('csrf_token', bin2hex(random_bytes(16)));

        // Default superadmin permissions
        $permissions = [
            'mod_content_category' => ['list' => 1, 'add' => 1, 'edit' => 1, 'delete' => 1, 'moderate_edit' => 1, 'moderate_delete' => 1],
            'mod_content' => ['list' => 1, 'add' => 1, 'edit' => 1, 'delete' => 1, 'toggle_hero_slider' => 1],
            'mod_faq' => ['list' => 1, 'add' => 1, 'edit' => 1, 'delete' => 1, 'reorder' => 1],
            'mod_newsletter' => ['list' => 1, 'delete' => 1, 'export' => 1],
            'mod_seo' => ['list' => 1, 'edit' => 1, 'sitemap' => 1],
            'mod_settings' => ['edit' => 1, 'legal_info' => 1, 'site_status' => 1, 'theme_logo' => 1],
        ];

        foreach ($permissions as $mod => $actions) {
            foreach ($actions as $action => $val) {
                $this->set("permissions.{$mod}.{$action}", $val);
            }
        }
    }

    public function hasPermission(string $module, string $action): bool
    {
        return (bool)$this->get("permissions.{$module}.{$action}", false);
    }
}
