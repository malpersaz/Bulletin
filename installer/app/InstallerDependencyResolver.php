<?php

class InstallerDependencyResolver
{
    public function resolve(array $selectedModules, InstallerModuleCatalog $catalog)
    {
        $resolved = array();
        $selectedMap = array_fill_keys($selectedModules, true);
        $modules = $catalog->getModules();

        foreach ($selectedModules as $module) {
            $this->addModuleWithDependencies($module, $selectedMap, $modules);
        }

        foreach ($catalog->getInstallOrder() as $module) {
            if (isset($selectedMap[$module])) {
                $resolved[] = $module;
            }
        }

        return $resolved;
    }

    private function addModuleWithDependencies($module, array &$selectedMap, array $modules)
    {
        if (!isset($modules[$module])) {
            return;
        }

        $selectedMap[$module] = true;
        foreach ($modules[$module]['dependencies'] as $dependency) {
            if (!isset($selectedMap[$dependency])) {
                $this->addModuleWithDependencies($dependency, $selectedMap, $modules);
            }
        }
    }
}
