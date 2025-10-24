<?php
/**
 * 마이그레이션 실행 스크립트
 *
 * 사용법: php run_migration.php migration_add_cascade.sql
 */

// CLI에서만 실행 가능
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line.');
}

// 인자 확인
if ($argc < 2) {
    die("Usage: php run_migration.php <migration_file.sql>\n");
}

$migrationFile = $argv[1];

// 파일 존재 확인
if (!file_exists($migrationFile)) {
    die("Error: Migration file '{$migrationFile}' not found.\n");
}

// 데이터베이스 설정 (환경 변수에서 가져옴)
$dbConfig = [
    'host'     => getenv('DB_HOST') ?: 'localhost',
    'username' => getenv('DB_USERNAME') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'database' => getenv('DB_DATABASE') ?: 'ci'
];

echo "Connecting to database...\n";

// 데이터베이스 연결
$mysqli = new mysqli(
    $dbConfig['host'],
    $dbConfig['username'],
    $dbConfig['password'],
    $dbConfig['database']
);

// 문자셋 설정
$mysqli->set_charset('utf8mb4');

// 연결 확인
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error . "\n");
}

echo "Connected successfully.\n";
echo "Running migration: {$migrationFile}\n";

// 트랜잭션 시작
$mysqli->begin_transaction();

try {
    // SQL 파일 읽기
    $sql = file_get_contents($migrationFile);

    // SQL 실행 (multi_query로 여러 쿼리 실행)
    if ($mysqli->multi_query($sql)) {
        do {
            // 결과 저장
            if ($result = $mysqli->store_result()) {
                $result->free();
            }

            // 에러 확인
            if ($mysqli->errno) {
                throw new Exception($mysqli->error);
            }
        } while ($mysqli->next_result());

        $mysqli->commit();
        echo "Migration completed successfully!\n";
    } else {
        throw new Exception($mysqli->error);
    }
} catch (Exception $e) {
    $mysqli->rollback();
    echo "Error executing migration: " . $e->getMessage() . "\n";
    exit(1);
}

$mysqli->close();