<?php
// List of files to update
$files = [
    'get_bag_items.php',
    'get_categories.php',
    'get_department_id.php',
    'get_employees.php',
    'get_item_descriptions_with_category.php',
    'get_item_description.php',
    'get_repair_logs.php',
    'login.php',
    'update_item_description.php',
    'get_item_descriptions.php',
    'get_item_info.php',
    'get_employee_names.php',
    'get_category_by_item_name.php',
    'add_students_from_csv.php',
    'add_new_item.php',
    'add_item_description.php',
    'add_employees_from_csv.php',
    'add_employee.php',
    'add_claimed.php',
    'add_category.php',
    'get_departments.php'
];

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Replace direct mysqli connections
    $content = preg_replace(
        '/\$conn\s*=\s*new\s+mysqli\s*\(\s*["\']localhost["\']\s*,\s*["\']root["\']\s*,\s*["\']["\']\s*,\s*["\']nexus_ims_db_dummy["\']\s*\)\s*;/',
        'require_once \'db_functions.php\';

// Get database connection
$conn = get_database_connection();',
        $content
    );
    
    // Replace PDO connections with variable declarations
    $content = preg_replace(
        '/(?:\$servername|\$host)\s*=\s*["\']localhost["\'];\s*\$username\s*=\s*["\']root["\'];\s*\$password\s*=\s*["\']["\']\s*;\s*\$(?:database|dbname|db)\s*=\s*["\']nexus_ims_db_dummy["\']\s*;/',
        'require_once \'db_functions.php\';

// Get database connection
$conn = get_pdo_connection();',
        $content
    );
    
    // Replace single variable declarations
    $content = preg_replace(
        '/\$(?:database|dbname|db)\s*=\s*["\']nexus_ims_db_dummy["\']\s*;/',
        '',
        $content
    );
    
    // Add require_once if it doesn't exist
    if (strpos($content, 'require_once \'db_functions.php\';') === false) {
        $content = preg_replace(
            '/<\?php/',
            "<?php\nrequire_once 'db_functions.php';",
            $content
        );
    }
    
    // Replace any remaining connection creation code
    $content = preg_replace(
        '/\$conn\s*=\s*new\s+(?:mysqli|PDO)\s*\([^)]+\)\s*;/',
        '$conn = get_database_connection();',
        $content
    );
    
    file_put_contents($file, $content);
}

echo "Database connections updated successfully!\n";
?> 