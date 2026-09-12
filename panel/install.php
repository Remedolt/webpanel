<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

header('Content-Type: text/html; charset=UTF-8');

if (PHP_VERSION_ID < 70100) {
    echo 'Bu panel PHP 7.1 veya üzeri ister. Sunucu sürümü: ' . PHP_VERSION;
    exit;
}

define('CMS_SKIP_CONNECT', true);

$httpsOn = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ((isset($_SERVER['SERVER_PORT']) ? (string) $_SERVER['SERVER_PORT'] : '') === '443');

if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params(array(
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $httpsOn,
        'httponly' => true,
        'samesite' => 'Lax',
    ));
} else {
    session_set_cookie_params(0, '/', '', $httpsOn, true);
}
session_start();

$error = '';
$loaded = false;
$loadError = '';

try {
    require __DIR__ . '/includes/db.php';
    $loaded = true;
} catch (Throwable $t) {
    $loadError = $t->getMessage() . ' @ ' . $t->getFile() . ':' . $t->getLine();
}

if ($loaded && isset($pdo) && $pdo instanceof PDO) {
    header('Location: index.php');
    exit;
}

if ($loaded && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (function_exists('csrf_verify')) {
        csrf_verify();
    }
    $dbHost = isset($_POST['db_host']) ? trim((string) $_POST['db_host']) : 'localhost';
    $dbName = isset($_POST['db_name']) ? trim((string) $_POST['db_name']) : '';
    $dbUser = isset($_POST['db_user']) ? trim((string) $_POST['db_user']) : '';
    $dbPass = isset($_POST['db_pass']) ? (string) $_POST['db_pass'] : '';

    if ($dbHost === '' || $dbName === '' || $dbUser === '') {
        $error = 'Sunucu, veritabanı adı ve kullanıcı adı zorunludur.';
    } else {
        try {
            $dsn = 'mysql:host=' . $dbHost . ';dbname=' . $dbName . ';charset=utf8mb4';
            $opts = isset($pdoOptions) ? $pdoOptions : array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            );
            $test = new PDO($dsn, $dbUser, $dbPass, $opts);
            $config = "<?php\n"
                . '$dbHost = ' . var_export($dbHost, true) . ";\n"
                . '$dbName = ' . var_export($dbName, true) . ";\n"
                . '$dbUser = ' . var_export($dbUser, true) . ";\n"
                . '$dbPass = ' . var_export($dbPass, true) . ";\n";
            $cfg = isset($configFile) ? $configFile : (__DIR__ . '/includes/config.php');
            $written = @file_put_contents($cfg, $config, LOCK_EX);
            if ($written === false) {
                $error = 'includes/config.php yazılamadı. FTP ile includes klasörüne yazma izni verin (755/775).';
            } else {
                $schemaPaths = array(
                    __DIR__ . '/schema.sql',
                    dirname(__DIR__) . '/schema.sql',
                );
                foreach ($schemaPaths as $schemaPath) {
                    if (is_file($schemaPath)) {
                        $sql = (string) file_get_contents($schemaPath);
                        $sql = preg_replace('/^\s*--.*$/m', '', $sql);
                        foreach (explode(';', $sql) as $statement) {
                            $statement = trim($statement);
                            if ($statement !== '') {
                                $test->exec($statement);
                            }
                        }
                        break;
                    }
                }
                if (function_exists('cms_seed')) {
                    cms_seed($test);
                }
                header('Location: index.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Bağlantı başarısız: ' . $e->getMessage();
        } catch (Throwable $e) {
            $error = 'Kurulum hatası: ' . $e->getMessage();
        }
    }
}

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$hostVal = isset($_POST['db_host']) ? (string) $_POST['db_host'] : 'localhost';
$nameVal = isset($_POST['db_name']) ? (string) $_POST['db_name'] : '';
$userVal = isset($_POST['db_user']) ? (string) $_POST['db_user'] : '';
$token = isset($_SESSION['csrf_token']) && is_string($_SESSION['csrf_token'])
    ? $_SESSION['csrf_token']
    : bin2hex(random_bytes(16));
$_SESSION['csrf_token'] = $token;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kurulum</title>
    <style>
        body { margin:0; font-family: Arial, sans-serif; background:#f1f5f9; color:#0f172a; }
        .wrap { max-width: 480px; margin: 48px auto; background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:28px; }
        h1 { margin:0 0 8px; font-size:22px; }
        p { margin:0 0 16px; color:#64748b; font-size:14px; }
        label { display:block; font-size:13px; font-weight:700; margin:12px 0 6px; }
        input { width:100%; box-sizing:border-box; padding:10px; border:1px solid #cbd5e1; border-radius:6px; font-size:14px; }
        button { margin-top:16px; width:100%; background:#2271b1; color:#fff; border:0; border-radius:6px; padding:12px; font-size:14px; font-weight:700; cursor:pointer; }
        .err { background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; padding:10px; border-radius:6px; font-size:13px; margin-bottom:12px; }
        .ok { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; padding:10px; border-radius:6px; font-size:13px; margin-bottom:12px; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Veritabanı kurulumu</h1>
    <p>cPanel → MySQL Veritabanları bilgilerini girin. PHP <?php echo h(PHP_VERSION); ?></p>
    <?php if ($loadError !== ''): ?>
        <div class="err"><?php echo h($loadError); ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="err"><?php echo h($error); ?></div>
    <?php endif; ?>
    <?php if ($loaded): ?>
        <div class="ok">Dosyalar yüklendi. Formu doldurup bağlanın.</div>
    <?php endif; ?>
    <form method="post" action="install.php">
        <input type="hidden" name="csrf_token" value="<?php echo h($token); ?>">
        <label for="db_host">Sunucu</label>
        <input id="db_host" name="db_host" value="<?php echo h($hostVal); ?>">
        <label for="db_name">Veritabanı adı</label>
        <input id="db_name" name="db_name" required value="<?php echo h($nameVal); ?>" placeholder="cpanelkullanici_cms">
        <label for="db_user">Kullanıcı adı</label>
        <input id="db_user" name="db_user" required value="<?php echo h($userVal); ?>" placeholder="cpanelkullanici_cms">
        <label for="db_pass">Şifre</label>
        <input id="db_pass" name="db_pass" type="password">
        <button type="submit">Bağlan ve kur</button>
    </form>
</div>
</body>
</html>
