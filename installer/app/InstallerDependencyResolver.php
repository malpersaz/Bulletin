<?php

class InstallerDependencyResolver
{
    public function resolve(array $selectedModules, InstallerModuleCatalog $catalog)
    {
        $selection = $this->buildSelection($selectedModules, $catalog);
        return $selection['resolved'];
    }

    public function buildSelection(array $requestedModules, InstallerModuleCatalog $catalog)
    {
        $modules = $catalog->getModules();
        $requested = $catalog->filterRequestedModules($requestedModules);
        $requestedMap = array_fill_keys($requested, true);
        $selectedMap = array_fill_keys($catalog->getRequiredModules(), true);
        $requiredBy = array();

        foreach ($requested as $module) {
            $this->addModuleWithDependencies($module, $selectedMap, $modules, $requiredBy, null);
        }

        $resolved = array();
        $moduleStates = array();
        $groupedOptional = array();
        $requiredModules = array();

        foreach ($catalog->getInstallOrder() as $module) {
            if (!isset($modules[$module])) {
                continue;
            }

            $selected = isset($selectedMap[$module]);
            if ($selected) {
                $resolved[] = $module;
            }

            $state = array(
                'selected' => $selected,
                'requested' => isset($requestedMap[$module]),
                'required' => !empty($modules[$module]['required']),
                'default_selected' => !empty($modules[$module]['default_selected']),
                'project_specific' => !empty($modules[$module]['project_specific']),
                'supports_sample_content' => !empty($modules[$module]['supports_sample_content']),
                'group' => isset($modules[$module]['group']) ? $modules[$module]['group'] : 'core_system',
                'dependencies' => isset($modules[$module]['dependencies']) ? $modules[$module]['dependencies'] : array(),
                'required_by' => isset($requiredBy[$module]) ? array_values(array_unique($requiredBy[$module])) : array(),
            );

            $state['auto_selected'] = $state['selected'] && !$state['requested'] && !$state['required'];
            $state['locked'] = $state['required'] || !empty($state['required_by']);
            $moduleStates[$module] = $state;

            if ($state['required']) {
                $requiredModules[] = $module;
                continue;
            }

            $group = $state['group'];
            if (!isset($groupedOptional[$group])) {
                $groupedOptional[$group] = array();
            }
            $groupedOptional[$group][] = $module;
        }

        return array(
            'requested' => $requested,
            'resolved' => $resolved,
            'required_modules' => $requiredModules,
            'optional_groups' => $groupedOptional,
            'module_states' => $moduleStates,
            'summary' => $catalog->getSummary($resolved),
        );
    }

    private function addModuleWithDependencies($module, array &$selectedMap, array $modules, array &$requiredBy, $parentModule)
    {
        if (!isset($modules[$module])) {
            return;
        }

        $selectedMap[$module] = true;

        if ($parentModule !== null) {
            if (!isset($requiredBy[$module])) {
                $requiredBy[$module] = array();
            }
            $requiredBy[$module][] = $parentModule;
        }

        foreach ($modules[$module]['dependencies'] as $dependency) {
            $this->addModuleWithDependencies($dependency, $selectedMap, $modules, $requiredBy, $module);
        }
    }
}
