<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__, 2) . '/app/autoload.php';
\PCMS\Support\Env::load(dirname(__DIR__, 2) . '/.env');

use PCMS\Support\Database;

$name = trim((string)getenv('PCMS_ADMIN_NAME'));
$email = strtolower(trim((string)getenv('PCMS_ADMIN_EMAIL')));
$password = (string)getenv('PCMS_ADMIN_PASSWORD');
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
    fwrite(STDERR, "Set PCMS_ADMIN_NAME, PCMS_ADMIN_EMAIL and a password of at least 12 characters.\n");
    exit(1);
}

try {
    Database::transaction(function () use ($name, $email, $password): void {
        if ((int)Database::scalar("SELECT COUNT(*) FROM user_role ur JOIN role r ON r.role_id=ur.role_id WHERE r.role_code='ADMIN'") > 0) {
            throw new RuntimeException('An administrator already exists.');
        }
        $id = (int)Database::scalar("SELECT nextval('seq_app_user')");
        Database::execute("INSERT INTO app_user(user_id,user_name,email,password_hash,account_status) VALUES(:id,:name,:email,:hash,'ACTIVE')",
            ['id'=>$id,'name'=>$name,'email'=>$email,'hash'=>password_hash($password, PASSWORD_DEFAULT)]);
        if (Database::execute("INSERT INTO user_role(user_id,role_id) SELECT :id,role_id FROM role WHERE role_code='ADMIN'", ['id'=>$id]) !== 1) {
            throw new RuntimeException('Run 02_seed.sql before creating the administrator.');
        }
    });
    echo "Administrator created. Remove PCMS_ADMIN_PASSWORD from the environment.\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
}
