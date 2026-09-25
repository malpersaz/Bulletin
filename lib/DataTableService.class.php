<?php

/**
 * DataTableService - Centralized DataTable JSON generation
 * Eliminates massive duplication in datatable_json() methods (400+ lines per module)
 * 
 * Amed Framework
 * @author Refactored for better maintainability
 */

class DataTableService
{
    private $module_name;
    private $model;
    private $lang;
    private $site;
    private $session;

    /**
     * Generate DataTable JSON response
     * 
     * @param string $module_name The module name (e.g., 'mod_announcements')
     * @param array $columns Array of column names
     * @param array $options Additional options and configurations
     * @return string JSON response
     */
    public function generateJson($module_name, $columns = [], $options = [])
    {
        $this->module_name = $module_name;
        $this->lang = Load::lang($module_name);
        $this->site = Registry::get('site_config');
        $this->session = Load::lib('Session');

        // Load the model
        $model_name = isset($options['model_name']) ? $options['model_name'] : $module_name;
        $this->model = Load::model($model_name);

        // Set default columns if none provided
        if (empty($columns)) {
            $columns = ['id'];
        }

        // Extract actual column names for SQL select (remove formatters)
        $sql_columns = [];
        foreach ($columns as $column) {
            $parsed = $this->parseColumnDefinition($column);
            $sql_columns[] = $parsed['name'];
        }

        // Select columns
        $this->model->select($sql_columns);

        // Set defaults
        $limit = 50;
        $offset = 0;
        $order = $sql_columns[0];
        $sort = 'desc';

        // Handle ordering
        if (Req::is_Post('order')) {
            $o = Req::Post_Safe('order');
            if (isset($o[0]['column']) && ($o[0]['column'] > 0) && isset($sql_columns[$o[0]['column'] - 1])) {
                $order = $sql_columns[$o[0]['column'] - 1];
            }
            if (isset($o[0]['dir']) && in_array($o[0]['dir'], ['asc', 'desc'])) {
                $sort = $o[0]['dir'];
            }
        }
        $this->model->order($order, $sort);

        // Build WHERE conditions
        $wheres = $this->buildWhereConditions($sql_columns, $options);

        if (count($wheres) > 0) {
            $wheres = "(" . implode(") AND (", $wheres) . ")";
            $this->model->addWhere($wheres);
        }

        // Handle pagination
        if (Req::is_Post('length') && intval(Req::Post_Safe('length'))) {
            $limit = intval(Req::Post_Safe('length'));
        }

        if (Req::is_Post('start') && intval(Req::Post_Safe('start'))) {
            $offset = intval(Req::Post_Safe('start'));
        }

        $getCount = $this->model->getCount();
        if ($limit > 0) {
            $this->model->limit($limit, $offset);
        }


        // Execute query
        $this->model->gR();
        $data = [];

        // Generate data rows
        if ($getCount > 0) {
            foreach ($this->model->toArray() as $content) {
                $row = $this->buildDataRow($content, $columns, $options);
                $data[] = $row;
            }
        }

        // Get total count and status stats
        $model_class = $model_name . '_model';
        $total_model = new $model_class();
        if ((!isset($options['skip_user_filter']) || !$options['skip_user_filter']) && (Role::hasPermission($this->module_name, 'moderate_edit') !== true && Role::hasPermission($this->module_name, 'moderate_delete') !== true)) {
            $total_model->addWhere("user_id='" . $this->session->get('id') . "'");
        }
        $total_count = $total_model->getCount();

        $draft_model = new $model_class();
        $draft_count = 0;
        if ($this->tableHasColumn($model_name, 'status')) {
            try {
                $draft_model->addWhere("status='n'");
                if ((!isset($options['skip_user_filter']) || !$options['skip_user_filter']) && (Role::hasPermission($this->module_name, 'moderate_edit') !== true && Role::hasPermission($this->module_name, 'moderate_delete') !== true)) {
                    $draft_model->addWhere("user_id='" . $this->session->get('id') . "'");
                }
                $draft_count = $draft_model->getCount();
            } catch (Exception $e) {
                $draft_count = 0;
            }
        }
        $published_count = max(0, $total_count - $draft_count);

        $suffix = ucfirst(str_replace('mod_', '', $this->module_name));
        $suffix_lower = str_replace('mod_', '', $this->module_name);

        // Return JSON response
        return json_encode([
            'data' => $data,
            'recordsFiltered' => $getCount,
            'recordsTotal' => $total_count,
            'total' => $total_count,
            'published' => $published_count,
            'draft' => $draft_count,
            'total' . $suffix => $total_count,
            'published' . $suffix => $published_count,
            'draft' . $suffix => $draft_count,
            'total_' . $suffix_lower => $total_count,
            'published_' . $suffix_lower => $published_count,
            'draft_' . $suffix_lower => $draft_count,
        ]);
    }

    /**
     * Build WHERE conditions from search parameters
     * 
     * @param array $columns Available columns
     * @param array $options Configuration options
     * @return array WHERE conditions
     */
    private function buildWhereConditions($columns, $options = [])
    {
        $wheres = [];

        // Handle text search
        if (Req::is_Post('search') && count(Req::Post_Safe('search')) > 0) {
            foreach (Req::Post_Safe('search') as $k => $v) {
                if (in_array($k, $columns) && $v != '') {
                    $wheres[] = $k . " LIKE '%" . Req::Clear($v) . "%'";
                }
            }
        }

        // Handle radio/select search
        if (Req::is_Post('search_radio') && count(Req::Post_Safe('search_radio')) > 0) {
            foreach (Req::Post_Safe('search_radio') as $k => $v) {
                if (in_array($k, $columns) && $v != '') {
                    $wheres[] = $k . "='" . Req::Clear($v) . "'";
                }
            }
        }

        // Handle date range search
        if (Req::is_Post('search_created_at') && count(Req::Post_Safe('search_created_at')) > 0) {
            foreach (Req::Post_Safe('search_created_at') as $k => $v) {
                if (in_array($k, $columns) && $v != '') {
                    if (isset($v['from']) && isset($v['to']) && $this->_valid_date($v['from']) && $this->_valid_date($v['to'])) {
                        $wheres[] = $k . " BETWEEN '" . $v['from'] . "' AND '" . $v['to'] . "'";
                    } elseif (isset($v['from']) && $this->_valid_date($v['from'])) {
                        $wheres[] = $k . " > '" . $v['from'] . "'";
                    } elseif (isset($v['to']) && $this->_valid_date($v['to'])) {
                        $wheres[] = $k . " < '" . $v['to'] . "'";
                    }
                }
            }
        }

        // Apply user permission filter
        if ((!isset($options['skip_user_filter']) || !$options['skip_user_filter']) && (Role::hasPermission($this->module_name, 'moderate_edit') !== true && Role::hasPermission($this->module_name, 'moderate_delete') !== true)) {
            $wheres[] = "user_id='" . $this->session->get('id') . "'";
        }

        return $wheres;
    }

    /**
     * Check whether a table has a specific column (adapter-aware).
     *
     * @param string $table Table name
     * @param string $column Column name
     * @return bool
     */
    private function tableHasColumn($table, $column)
    {
        $db = Load::lib('Database');
        try {
            if (strpos(strtoupper((string) $db->getAdapterType()), 'SQLITE') !== false) {
                $cols = $db->fetch_array("PRAGMA table_info(`{$table}`)")->toArray();
            } else {
                $cols = $db->fetch_array("SHOW COLUMNS FROM `{$table}`")->toArray();
            }
        } catch (Exception $e) {
            return false;
        }
        foreach ((array) $cols as $col) {
            $name = isset($col['name']) ? $col['name'] : (isset($col['Field']) ? $col['Field'] : null);
            if ($name === $column) {
                return true;
            }
        }
        return false;
    }

    /**
     * Build a data row for DataTable
     * 
     * @param array $content Record data
     * @param array $columns Column definitions
     * @param array $options Configuration options
     * @return array Formatted row data
     */
    private function buildDataRow($content, $columns, $options = [])
    {
        $row = [];

        // Add checkbox column
        $row[] = '<input type="checkbox" name="id[]" value="' . $content['id'] . '">';

        // Add data columns
        foreach ($columns as $column) {
            // Parse column definition if it contains formatter
            $parsed_column = $this->parseColumnDefinition($column);
            $column_name = $parsed_column['name'];
            $formatter = $parsed_column['formatter'];

            $value = isset($content[$column_name]) ? $content[$column_name] : '';
            $row[] = $this->formatColumnValue($column_name, $value, $content, $options, $formatter);
        }

        // Add action buttons
        $row[] = $this->buildActionButtons($content, $options);

        return $row;
    }

    /**
     * Format column value based on column type
     * 
     * @param string $column Column name
     * @param mixed $value Column value
     * @param array $record Full record data
     * @param array $options Configuration options
     * @param string|null $formatter Formatter type from column definition
     * @return string Formatted value
     */
    private function formatColumnValue($column, $value, $record, $options = [], $formatter = null)
    {
        // If formatter is specified in column definition, use it
        if ($formatter) {
            return $this->applyFormatter($formatter, $value, $record, $column);
        }

        // Check if custom formatter is provided in options
        if (isset($options['formatters'][$column])) {
            $formatter = $options['formatters'][$column];
            if (is_callable($formatter)) {
                return $formatter($value, $record);
            }
        }

        return htmlspecialchars($value);
    }


    /**
     * Get localized status text based on site language
     * 
     * @param string $status_key Status key
     * @return string Localized status text
     */
    private function getLocalizedStatusText($status_key)
    {
        $normalizedKey = strtolower(str_replace([' ', '-'], '_', (string) $status_key));
        if (isset($this->lang['datatable']['status'][$normalizedKey])) {
            return $this->lang['datatable']['status'][$normalizedKey];
        }
        if (isset($this->lang['common']['status'][$normalizedKey])) {
            return $this->lang['common']['status'][$normalizedKey];
        }

        return $status_key;
    }

    /**
     * Get comprehensive status configuration for any status field
     * 
     * @param string $type Status type (status, deck, etc.)
     * @param string $value Status value
     * @return array|null [css_class, label] or null if no fallback
     */
    private function getFallbackStatusConfig($type, $value)
    {
        // Universal status mappings that work for any field
        $universal_mappings = [
            // Success states (green)
            'y' => ['success', $this->getLocalizedStatusText('Active')],
            'a' => ['success', $this->getLocalizedStatusText('Active')],
            'y' => ['success', $this->getLocalizedStatusText('Yes')],
            '1' => ['success', $this->getLocalizedStatusText('Active')],
            'true' => ['success', $this->getLocalizedStatusText('Yes')],
            'approved' => ['success', $this->getLocalizedStatusText('Approved')],
            'published' => ['success', $this->getLocalizedStatusText('Published')],
            'active' => ['success', $this->getLocalizedStatusText('Active')],
            'enabled' => ['success', $this->getLocalizedStatusText('Enabled')],
            'online' => ['success', $this->getLocalizedStatusText('Online')],
            'completed' => ['success', $this->getLocalizedStatusText('Completed')],
            'success' => ['success', $this->getLocalizedStatusText('Success')],

            // Warning states (orange)
            'n' => ['warning', $this->getLocalizedStatusText('No')],
            'p' => ['warning', $this->getLocalizedStatusText('Pending')],
            'n' => ['warning', $this->getLocalizedStatusText('No')],
            '0' => ['warning', $this->getLocalizedStatusText('Inactive')],
            'false' => ['warning', $this->getLocalizedStatusText('No')],
            'pending' => ['warning', $this->getLocalizedStatusText('Pending')],
            'draft' => ['warning', $this->getLocalizedStatusText('Draft')],
            'inactive' => ['warning', $this->getLocalizedStatusText('Inactive')],
            'disabled' => ['warning', $this->getLocalizedStatusText('Disabled')],
            'offline' => ['warning', $this->getLocalizedStatusText('Offline')],
            'waiting' => ['warning', $this->getLocalizedStatusText('Waiting')],
            'processing' => ['warning', $this->getLocalizedStatusText('Processing')],

            // Danger states (red)
            'rejected' => ['danger', $this->getLocalizedStatusText('Rejected')],
            'deleted' => ['danger', $this->getLocalizedStatusText('Deleted')],
            'error' => ['danger', $this->getLocalizedStatusText('Error')],
            'failed' => ['danger', $this->getLocalizedStatusText('Failed')],
            'cancelled' => ['danger', $this->getLocalizedStatusText('Cancelled')],
            'expired' => ['danger', $this->getLocalizedStatusText('Expired')],
            'blocked' => ['danger', $this->getLocalizedStatusText('Blocked')],
            'suspended' => ['danger', $this->getLocalizedStatusText('Suspended')],

            // Info states (blue)
            'info' => ['info', $this->getLocalizedStatusText('Info')],
            'new' => ['info', $this->getLocalizedStatusText('New')],
            'updated' => ['info', $this->getLocalizedStatusText('Updated')],

            // Language codes
            'tr' => ['success', $this->getLocalizedStatusText('Turkish')],
            'en' => ['warning', $this->getLocalizedStatusText('English')],
            'ku' => ['info', $this->getLocalizedStatusText('Kurdish')],
        ];

        $value_lower = strtolower($value);

        // Check universal mappings
        if (isset($universal_mappings[$value_lower])) {
            return $universal_mappings[$value_lower];
        }

        return null;
    }

    /**
     * Parse column definition to extract column name and formatter
     * Supports formats like:
     * - "id" (simple column)
     * - "id|checkbox" (column with formatter)
     * - "image|image" (image column)
     * - "status|label" (status label)
     * - "name|bold" (bold text)
     * 
     * @param string $column_definition Column definition
     * @return array ['name' => string, 'formatter' => string|null]
     */
    private function parseColumnDefinition($column_definition)
    {
        if (strpos($column_definition, '|') !== false) {
            $parts = explode('|', $column_definition, 2);
            return [
                'name' => trim($parts[0]),
                'formatter' => trim($parts[1])
            ];
        }

        return [
            'name' => $column_definition,
            'formatter' => null
        ];
    }

    /**
     * Apply specific formatter to a value
     * 
     * @param string $formatter Formatter type
     * @param mixed $value Column value
     * @param array $record Full record data
     * @param string $column Column name
     * @return string Formatted value
     */
    private function applyFormatter($formatter, $value, $record, $column)
    {
        switch ($formatter) {
            case 'checkbox':
                return '<input type="checkbox" name="' . $column . '[]" value="' . $value . '">';

            case 'image':
                if (!empty($value)) {
                    return '<img src="' . $this->site['iaddress'] . $this->module_name . '/thumb/' . $value . '" style="max-width: 50px; max-height: 50px;">';
                }
                return '<span class="text-muted">No image</span>';

            case 'bold':
                return '<strong>' . htmlspecialchars($value) . '</strong>';

            case 'italic':
                return '<em>' . htmlspecialchars($value) . '</em>';

            case 'date':
                if (!empty($value) && $value !== '0000-00-00 00:00:00') {
                    return date('d.m.Y', strtotime($value));
                }
                return '-';

            case 'datetime':
                if (!empty($value) && $value !== '0000-00-00 00:00:00') {
                    return date('d.m.Y H:i', strtotime($value));
                }
                return '-';

            case 'time':
                if (!empty($value)) {
                    return date('H:i', strtotime($value));
                }
                return '-';

            case 'number':
                return number_format($value);

            case 'currency':
                $currencySymbol = Registry::has('currency_symbol') ? ' ' . Registry::get('currency_symbol') : '';
                return number_format((float)$value, 2) . $currencySymbol;

            case 'truncate':
                return $this->truncateText($value, 50);

            case 'link':
                if (!empty($value)) {
                    return '<a href="' . htmlspecialchars($value) . '" target="_blank">' . htmlspecialchars($value) . '</a>';
                }
                return '';

            case 'email':
                if (!empty($value)) {
                    return '<a href="mailto:' . htmlspecialchars($value) . '">' . htmlspecialchars($value) . '</a>';
                }
                return '';

            case 'badge':
                return $this->formatBadgeColumn($value, $column);

            default:
                return htmlspecialchars($value);
        }
    }

    /**
     * Format badge column with automatic status detection
     * 
     * @param mixed $value Column value
     * @param string $column Column name for context
     * @return string Formatted badge
     */
    private function formatBadgeColumn($value, $column)
    {
        // Try to get status configuration
        $status_config = $this->getFallbackStatusConfig($column, $value);

        if ($status_config) {
            return '<span class="label label-' . $status_config[0] . '">' . $status_config[1] . '</span>';
        }

        // Default badge
        return '<span class="label label-primary">' . htmlspecialchars($value) . '</span>';
    }

    /**
     * Truncate text to specified length
     * 
     * @param string $text Text to truncate
     * @param int $length Maximum length
     * @return string Truncated text
     */
    private function truncateText($text, $length = 50)
    {
        if (strlen($text) <= $length) {
            return htmlspecialchars($text);
        }

        return htmlspecialchars(substr($text, 0, $length)) . '...';
    }

    /**
     * Build action buttons for a record
     * 
     * @param array $record Record data
     * @param array $options Configuration options
     * @return string Action buttons HTML
     */
    private function buildActionButtons($record, $options = [])
    {
        $buttons = '';
        $table_name = $this->module_name . '_table_ajax';

        // Edit button
        if (Role::hasPermission($this->module_name, 'edit')) {
            $buttons .= '<button onclick="' . $table_name . '.akm.processEdit(' . $record['id'] . ');return false;" class="btn default btn-xs purple">';
            $buttons .= '<i class="fa fa-edit"></i> ' . $this->lang[$this->module_name]['button']['edit'] . '</button>';
        }

        // Delete button
        if (Role::hasPermission($this->module_name, 'delete')) {
            $buttons .= '<button onclick="' . $table_name . '.akm.processDelete(\'' . $record['id'] . '\'); return false;" class="btn default btn-xs red">';
            $buttons .= '<i class="fa fa-trash-o"></i> ' . $this->lang[$this->module_name]['button']['delete'] . '</button>';
        }

        // Gallery button
        if (Role::hasPermission($this->module_name, 'gallery')) {
            $buttons .= '<button onclick="' . $table_name . '.akm.processGallery(\'' . $record['id'] . '\'); return false;" class="btn default btn-xs blue">';
            $buttons .= '<i class="fa fa-file-image-o"></i> ' . $this->lang[$this->module_name]['button']['gallery'] . '</button>';
        }

        // Files button
        if (Role::hasPermission($this->module_name, 'file')) {
            $buttons .= '<button onclick="' . $table_name . '.akm.processFile(\'' . $record['id'] . '\'); return false;" class="btn default btn-xs green">';
            $buttons .= '<i class="fa fa-file-zip-o"></i> ' . $this->lang[$this->module_name]['button']['file'] . '</button>';
        }

        // Custom buttons
        if (isset($options['custom_buttons'])) {
            foreach ($options['custom_buttons'] as $button) {
                if (is_callable($button)) {
                    $buttons .= $button($record);
                }
            }
        }

        return $buttons;
    }

    /**
     * Date validation helper
     * 
     * @param string $data Date string
     * @return bool Valid date
     */
    private function _valid_date($data = '')
    {
        if ($data == '') {
            return false;
        }
        return preg_match('@(\d{4})-(\d{2})-(\d{2})$@', $data);
    }
}
