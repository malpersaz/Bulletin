#!/usr/bin/env python3
import os
import re

# Target modules to analyze
MODULES = [
    'mod_news',
    'mod_announcements',
    'mod_page',
    'mod_publication',
    'mod_video',
    'mod_video_embed'
]

BASE_DIR = '/home/malpersaz/Desktop/projects/htdocs/AF/Rojname'

# Table 1 columns map to check in schema and templates
T1_COLUMNS = [
    'lang_select',
    'add_image',
    'add_gallery',
    'add_file',
    'add_video_embed',
    'add_video',
    'add_history',
    'add_name',
    'add_status',
    'add_slider',
    'add_summary',
    'add_content',
    'add_seo',
    'entegration_acl',
    'add_deck',
    'add_category',
    'add_show_on_homepage'
]

# Table 2 columns map
T2_COLUMNS = [
    'controller',
    'datatable',
    'i18n',
    'acl',
    'sqlite',
    'mobile',
    'twig'
]

def analyze_module(module_name):
    # Initialize results
    t1 = {col: 'white' for col in T1_COLUMNS}
    t2 = {col: 'white' for col in T2_COLUMNS}
    issues = []

    controller_path = os.path.join(BASE_DIR, 'mod', module_name, 'index.php')
    sql_path = os.path.join(BASE_DIR, 'mod', module_name, f'{module_name}.sql')
    if not os.path.exists(sql_path):
        # Fallback to old path or default to project sql folder if exists
        sql_path = os.path.join(BASE_DIR, 'installer', module_name, f'{module_name}.sql')
    
    # Read files for checks
    controller_content = ""
    if os.path.exists(controller_path):
        with open(controller_path, 'r', errors='ignore') as f:
            controller_content = f.read()

    sql_content = ""
    if os.path.exists(sql_path):
        with open(sql_path, 'r', errors='ignore') as f:
            sql_content = f.read()

    # Template checks
    admin_tpl_dir = os.path.join(BASE_DIR, 'templates/admin', module_name)
    templates_exist = os.path.exists(admin_tpl_dir)
    
    add_twig_content = ""
    edit_twig_content = ""
    list_twig_content = ""
    if templates_exist:
        add_twig_path = os.path.join(admin_tpl_dir, 'add.twig')
        edit_twig_path = os.path.join(admin_tpl_dir, 'edit.twig')
        list_twig_path = os.path.join(admin_tpl_dir, 'list.twig')
        if os.path.exists(add_twig_path):
            with open(add_twig_path, 'r', errors='ignore') as f:
                add_twig_content = f.read()
        if os.path.exists(edit_twig_path):
            with open(edit_twig_path, 'r', errors='ignore') as f:
                edit_twig_content = f.read()
        if os.path.exists(list_twig_path):
            with open(list_twig_path, 'r', errors='ignore') as f:
                list_twig_content = f.read()

    # --- TABLE 2 CHECKS (Deep Code) ---
    # 1. Controller inheritance
    if "extends BaseModuleController" in controller_content:
        t2['controller'] = 'green'
    else:
        t2['controller'] = 'red'
        issues.append(f"{module_name}: Controller index.php does not extend BaseModuleController.")

    # 2. DataTable Integration
    if "getDatatableColumns" in controller_content or "DataTableService" in controller_content:
        t2['datatable'] = 'green'
    else:
        t2['datatable'] = 'red'
        issues.append(f"{module_name}: DataTableService / getDatatableColumns() integration is missing.")

    # 3. i18n Integrity
    lang_dir = os.path.join(BASE_DIR, 'mod', module_name, 'lang')
    if os.path.exists(lang_dir):
        lang_files = os.listdir(lang_dir)
        required_langs = ['en.php', 'ku.php', 'tr.php']
        missing_langs = [l for l in required_langs if l not in lang_files]
        if not missing_langs:
            t2['i18n'] = 'green'
        else:
            t2['i18n'] = 'red'
            issues.append(f"{module_name}: Missing core language files in lang/ directory: {missing_langs}")
    else:
        t2['i18n'] = 'red'
        issues.append(f"{module_name}: Language (lang/) directory is missing entirely.")

    # 4. ACL Check
    if "hasPermission" in controller_content or "Roll::hasPermission" in controller_content:
        t2['acl'] = 'green'
    else:
        t2['acl'] = 'red'
        issues.append(f"{module_name}: ACL check (hasPermission) is missing in controller actions.")

    # 5. SQLite compatibility
    mysql_only_funcs = ['YEAR(', 'MONTH(', 'NOW()', 'RAND(']
    has_mysql_only = any(func in controller_content for func in mysql_only_funcs)
    # Check if there is model content
    model_dir = os.path.join(BASE_DIR, 'mod', module_name, 'model')
    if os.path.exists(model_dir):
        for f_name in os.listdir(model_dir):
            if f_name.endswith('.php'):
                with open(os.path.join(model_dir, f_name), 'r', errors='ignore') as mf:
                    m_content = mf.read()
                    if any(func in m_content for func in mysql_only_funcs):
                        has_mysql_only = True
    
    if has_mysql_only:
        # Check if adapter check is present
        if "getAdapterType" in controller_content or "PDO_SQLITE" in controller_content:
            t2['sqlite'] = 'green'
        else:
            t2['sqlite'] = 'red'
            issues.append(f"{module_name}: MySQL-specific functions used in SQL queries without SQLite adapter fallback.")
    else:
        t2['sqlite'] = 'green'

    # 6. Responsive Mobile UI
    # If the layout uses selection-card or wizard templates, it's optimized for phone
    if "selection-card" in add_twig_content or "wizard_content" in add_twig_content or "wizard_page" in add_twig_content:
        t2['mobile'] = 'green'
    else:
        t2['mobile'] = 'red'
        issues.append(f"{module_name}: Add/edit forms are not modernized/responsive for phone viewport (missing selection-card or wizard overlay).")

    # 7. Twig View Modernization
    if templates_exist and all(f.endswith('.twig') for f in os.listdir(admin_tpl_dir) if os.path.isfile(os.path.join(admin_tpl_dir, f))):
        # Check if templates contain raw PHP
        all_twig = True
        for f in os.listdir(admin_tpl_dir):
            if f.endswith('.twig'):
                with open(os.path.join(admin_tpl_dir, f), 'r', errors='ignore') as tf:
                    t_cont = tf.read()
                    if '<?php' in t_cont or '?>' in t_cont:
                        all_twig = False
        if all_twig:
            t2['twig'] = 'green'
        else:
            t2['twig'] = 'red'
            issues.append(f"{module_name}: Twig files contain legacy inline PHP code.")
    else:
        t2['twig'] = 'red'
        issues.append(f"{module_name}: Twig template directory is legacy or missing.")


    # --- TABLE 1 CHECKS (Features) ---
    # Determine columns based on SQL and Template features
    has_column = lambda col: col in sql_content or f"`{col}`" in sql_content
    
    # Lang Select
    if has_column('lang'):
        if "wizard_content" in add_twig_content or "wizard_page" in add_twig_content or "lang_lang" in add_twig_content:
            t1['lang_select'] = 'green'
        else:
            t1['lang_select'] = 'red'
    else:
        t1['lang_select'] = 'white'

    # Image
    if has_column('image'):
        if "image_fields" in controller_content or "image_uploader.twig" in add_twig_content or "image_uploader.twig" in edit_twig_content or "image_uploader.twig" in list_twig_content:
            t1['add_image'] = 'green'
        else:
            t1['add_image'] = 'red'
    else:
        t1['add_image'] = 'white'

    # Gallery
    if has_column('gallery_id'):
        if "has_gallery = true" in controller_content:
            t1['add_gallery'] = 'green'
        else:
            t1['add_gallery'] = 'red'
    else:
        t1['add_gallery'] = 'white'

    # File
    if has_column('file_id') or has_column('document_id'):
        if "has_files = true" in controller_content:
            t1['add_file'] = 'green'
        else:
            t1['add_file'] = 'red'
    else:
        t1['add_file'] = 'white'

    # Video Embed / Video
    is_video_embed_applicable = (
        module_name == 'mod_video_embed' or 
        has_column('video_embed_id') or 
        has_column('video_embed') or 
        has_column('video') or
        "toolbarVideo" in list_twig_content
    )
    if is_video_embed_applicable:
        t1['add_video_embed'] = 'green' if ("toolbarVideo" in list_twig_content and ("ajax_get_current_media" in controller_content or "BaseModuleController" in controller_content)) else 'red'
    else:
        t1['add_video_embed'] = 'white'

    is_video_applicable = (
        module_name == 'mod_video' or 
        has_column('video') or
        "toolbarVideo" in list_twig_content
    )
    if is_video_applicable:
        t1['add_video'] = 'green' if ("toolbarVideo" in list_twig_content and ("ajax_get_current_media" in controller_content or "BaseModuleController" in controller_content)) else 'red'
    else:
        t1['add_video'] = 'white'

    # History
    if has_column('created_at'):
        has_input = (
            "created_at" in add_twig_content or 
            "created_at" in edit_twig_content or
            "wizard_content" in add_twig_content
        )
        if has_input:
            uses_modern = (
                'type="datetime-local"' in add_twig_content or 
                'type="datetime-local"' in edit_twig_content or
                "wizard_content" in add_twig_content
            )
            t1['add_history'] = 'green' if uses_modern else 'red'
        else:
            t1['add_history'] = 'red'
    else:
        t1['add_history'] = 'white'

    # Name
    if has_column('name') or has_column('title'):
        if "counter" in add_twig_content or "counter" in edit_twig_content or "wizard_content" in add_twig_content:
            t1['add_name'] = 'green'
        else:
            t1['add_name'] = 'red'
    else:
        t1['add_name'] = 'white'

    # Status
    if has_column('status'):
        if "selection-card" in add_twig_content or "selection-card" in edit_twig_content or "wizard_content" in add_twig_content:
            t1['add_status'] = 'green'
        else:
            t1['add_status'] = 'red'
    else:
        t1['add_status'] = 'white'

    # Slider
    is_slider_applicable = (
        has_column('slider') or
        "toolbarSlider" in list_twig_content
    )
    if is_slider_applicable:
        t1['add_slider'] = 'green' if ("toolbarSlider" in list_twig_content and ("toggle_hero_slider" in controller_content or "BaseModuleController" in controller_content)) else 'red'
    else:
        t1['add_slider'] = 'white'

    # Summary
    if has_column('summary') or has_column('deck'):
        if "counter" in add_twig_content or "counter" in edit_twig_content or "wizard_content" in add_twig_content:
            t1['add_summary'] = 'green'
        else:
            t1['add_summary'] = 'red'
    else:
        t1['add_summary'] = 'white'

    # Content
    if has_column('content'):
        t1['add_content'] = 'green' if ("BaseModuleController" in controller_content or "wizard_content" in add_twig_content) else 'red'
    else:
        t1['add_content'] = 'white'

    # SEO
    if has_column('seo') or "mod_seo" in controller_content:
        t1['add_seo'] = 'green' if "wizard_content" in add_twig_content else 'red'
    else:
        t1['add_seo'] = 'white'

    # Integration ACL
    if has_column('acl_id') or "mod_acl" in controller_content:
        t1['entegration_acl'] = 'green' if ("BaseModuleController" in controller_content or "acl_label" in add_twig_content) else 'red'
    else:
        t1['entegration_acl'] = 'white'

    # Deck
    if has_column('deck'):
        t1['add_deck'] = 'green' if "wizard_content" in add_twig_content else 'red'
    else:
        t1['add_deck'] = 'white'

    # Category
    if has_column('category_id'):
        t1['add_category'] = 'green' if "wizard_content" in add_twig_content else 'red'
    else:
        t1['add_category'] = 'white'

    # Show on homepage
    is_homepage_applicable = (
        has_column('show_on_homepage') or 
        has_column('homepage') or
        "show_on_homepage" in add_twig_content or
        "show_on_homepage" in edit_twig_content or
        "toolbarHomepage" in list_twig_content
    )
    if is_homepage_applicable:
        t1['add_show_on_homepage'] = 'green' if (
            ("selection-card" in add_twig_content or "selection-card" in edit_twig_content) or
            ("toolbarHomepage" in list_twig_content) or
            ("BaseModuleController" in controller_content)
        ) else 'red'
    else:
        t1['add_show_on_homepage'] = 'white'

    return t1, t2, issues

def update_html_table():
    html_path = os.path.join(BASE_DIR, 'docs/module_modernization_integrated.html')
    if not os.path.exists(html_path):
        print(f"Error: {html_path} does not exist.")
        return

    with open(html_path, 'r', errors='ignore') as f:
        lines = f.readlines()

    all_issues = []
    
    # Run analysis for each module
    audit_results = {}
    for mod in MODULES:
        t1, t2, issues = analyze_module(mod)
        audit_results[mod] = (t1, t2)
        all_issues.extend(issues)

    updated_lines = []
    current_module = None
    in_table_1 = True # Start with Table 1

    for line in lines:
        # Detect table headers to switch mode
        if 'class="table-deep-analysis"' in line:
            in_table_1 = False
        
        # Detect table rows
        # E.g., <!-- mod_news -->
        m_mod = re.search(r'<!--\s*(mod_[a-z0-9_]+)\s*-->', line)
        if m_mod:
            current_module = m_mod.group(1)
            updated_lines.append(line)
            continue
            
        # Detect row end to clear current module
        if '</tr>' in line:
            updated_lines.append(line)
            current_module = None
            continue

        if current_module and current_module in audit_results:
            t1, t2 = audit_results[current_module]
            # Match input radios using the explicit list of modules to avoid greediness bugs
            sorted_modules = sorted(MODULES, key=len, reverse=True)
            m_radio = re.search(r'name="status_(' + '|'.join(sorted_modules) + r')_([a-z0-9_]+)"', line)
            if m_radio:
                mod_name = m_radio.group(1)
                feat_name = m_radio.group(2)
                
                # Check status
                status = 'white'
                if in_table_1:
                    lookup_feat = feat_name
                    if lookup_feat == 'show_on_homepage':
                        lookup_feat = 'add_show_on_homepage'
                    status = t1.get(lookup_feat, 'white')
                else:
                    # In Table 2 (deep analysis), features are named controller, datatable, i18n, acl, sqlite, mobile, twig
                    status = t2.get(feat_name, 'white')
                
                # Clean up existing checked attribute
                cleaned_line = line.replace(' checked=""', '').replace(' checked="checked"', '').replace(' checked', '')
                
                # Determine which radio class (cb-white, cb-red, cb-green) should be checked
                target_color = f"cb-{status}"
                if target_color in cleaned_line:
                    # Add checked to the matched radio button line
                    # Look for the closing bracket of the input and insert checked
                    updated_line = re.sub(r'(class="status-cb\s+cb-' + status + r'"\s*)', r'\1checked ', cleaned_line)
                    updated_lines.append(updated_line)
                else:
                    updated_lines.append(cleaned_line)
            else:
                updated_lines.append(line)
        else:
            updated_lines.append(line)

    # Now let's handle the English issues section at the bottom of the HTML page
    html_content = "".join(updated_lines)
    
    # Strip any existing issues section first to avoid duplicates
    html_content = re.sub(r'<!--\s*ISSUES_START\s*-->.*<!--\s*ISSUES_END\s*-->', '', html_content, flags=re.DOTALL)
    
    # Generate list of issues in English
    issues_html = "\n<!-- ISSUES_START -->\n<div class=\"container\" style=\"margin-top: 40px; padding: 20px; border-left: 5px solid #dc2626; background-color: #fef2f2; border-radius: 8px;\">\n"
    issues_html += "    <h2 style=\"color: #b91c1c; font-size: 18px; margin-top: 0; margin-bottom: 12px;\">Identified Modernization & Compatibility Issues</h2>\n"
    issues_html += "    <ul style=\"margin: 0; padding-left: 20px; font-size: 13px; line-height: 1.6; color: #7f1d1d;\">\n"
    for iss in all_issues:
        issues_html += f"        <li>{iss}</li>\n"
    issues_html += "    </ul>\n"
    issues_html += "</div>\n<!-- ISSUES_END -->\n"

    # Insert before </body>
    if "</body>" in html_content:
        html_content = html_content.replace("</body>", f"{issues_html}</body>")
    else:
        html_content += issues_html

    with open(html_path, 'w') as f:
        f.write(html_content)

    print("Successfully audited modules and updated module_modernization_integrated.html!")

if __name__ == '__main__':
    update_html_table()
