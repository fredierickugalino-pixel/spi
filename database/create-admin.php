<?php
// Run only from a terminal: php database/create-admin.php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/functions.php';
function ask(string $prompt): string { fwrite(STDOUT,$prompt); return trim((string)fgets(STDIN)); }
$username=ask('Admin username: ');$email=ask('Admin email (optional): ');fwrite(STDOUT,'Admin password (hidden input is not available in standard PHP CLI): ');$password=trim((string)fgets(STDIN));
if($username===''||!password_is_valid($password)||($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))){fwrite(STDERR,"Username and valid optional email are required. Password must be at least 8 characters and include lowercase, uppercase, and a special character.\n");exit(1);}
$emailDb=$email!==''?$email:null;$hash=password_hash($password,PASSWORD_DEFAULT);$role='admin';$q=$conn->prepare('INSERT INTO users(username,email,password,role) VALUES(?,?,?,?)');$q->bind_param('ssss',$username,$emailDb,$hash,$role);$q->execute();fwrite(STDOUT,"Administrator created. Remove or protect this script after setup.\n");
