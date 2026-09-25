<?php

/**
 * Amed Framework
 * Description: Core Role and Permission Verification Class
 */
class Role
{
    /**
     * Check if current session user has specified permission for a module
     *
     * @param string $mod
     * @param string $permission
     * @return bool
     */
    static function hasPermission($mod, $permission)
    {
        if (empty($_SESSION['permissions'])) {
            return False;
        }
        if (!empty($_SESSION['permissions'][$mod][$permission])) {
            return True;
        }
        return False;
    }
}
