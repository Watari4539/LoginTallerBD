<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $configPath = __DIR__ . '/config.local.php';
    if (!is_file($configPath)) {
        throw new RuntimeException(
            'Falta config.local.php. Copia config.local.php.example, completa los valores y vuelve a intentar.'
        );
    }

    $config = require $configPath;
    foreach (['host', 'port', 'database', 'username', 'password', 'aes_key_hex'] as $key) {
        if (!array_key_exists($key, $config) || $config[$key] === '') {
            throw new RuntimeException("Falta configurar el valor '{$key}' en config.local.php.");
        }
    }

    $aesKeyHex = (string) $config['aes_key_hex'];
    if (!preg_match('/\A[0-9a-fA-F]{64}\z/', $aesKeyHex)) {
        throw new RuntimeException('aes_key_hex debe contener exactamente 64 caracteres hexadecimales.');
    }

    $sslCa = (string) ($config['ssl_ca'] ?? '');
    if ($sslCa === '' || !is_file($sslCa)) {
        throw new RuntimeException('No se encontró el certificado raíz TLS configurado en ssl_ca.');
    }
    if (defined('Pdo\\Mysql::ATTR_SSL_CA')) {
        $sslCaAttribute = constant('Pdo\\Mysql::ATTR_SSL_CA');
    } elseif (defined('PDO::MYSQL_ATTR_SSL_CA')) {
        $sslCaAttribute = constant('PDO::MYSQL_ATTR_SSL_CA');
    } else {
        throw new RuntimeException('La extensión PDO MySQL debe estar habilitada para usar la conexión TLS.');
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $config['host'],
        (int) $config['port'],
        $config['database']
    );

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $options[$sslCaAttribute] = $sslCa;
    if (defined('Pdo\\Mysql::ATTR_SSL_VERIFY_SERVER_CERT')) {
        $options[constant('Pdo\\Mysql::ATTR_SSL_VERIFY_SERVER_CERT')] = true;
    } elseif (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
        $options[constant('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')] = true;
    }

    $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
    $pdo->exec("SET SESSION block_encryption_mode = 'aes-256-cbc'");

    return $pdo;
}

function aesKey(): string
{
    $configPath = __DIR__ . '/config.local.php';
    if (!is_file($configPath)) {
        throw new RuntimeException('Configura config.local.php antes de usar el backend.');
    }

    $config = require $configPath;
    $keyHex = (string) ($config['aes_key_hex'] ?? '');
    if (!preg_match('/\A[0-9a-fA-F]{64}\z/', $keyHex)) {
        throw new RuntimeException('aes_key_hex debe contener exactamente 64 caracteres hexadecimales.');
    }

    return hex2bin($keyHex);
}
