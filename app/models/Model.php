<?php
/**
 * CampusLend Base Model
 * Provides clean PDO CRUD helpers and prepared statements
 */

namespace App\Models;

use App\Config\Database;
use PDO;
use Exception;

abstract class Model
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';

    public static function pdo(): PDO
    {
        return Database::getConnection();
    }

    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $res = self::query($sql, $params)->fetch();
        return $res ?: null;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function fetchColumn(string $sql, array $params = []): mixed
    {
        return self::query($sql, $params)->fetchColumn();
    }

    public static function find(int $id): ?array
    {
        $sql = "SELECT * FROM " . static::$table . " WHERE " . static::$primaryKey . " = :id LIMIT 1";
        return self::fetch($sql, [':id' => $id]);
    }

    public static function findBy(string $column, mixed $value): ?array
    {
        $sql = "SELECT * FROM " . static::$table . " WHERE `{$column}` = :val LIMIT 1";
        return self::fetch($sql, [':val' => $value]);
    }

    public static function all(string $orderBy = 'id DESC'): array
    {
        $sql = "SELECT * FROM " . static::$table . " ORDER BY " . $orderBy;
        return self::fetchAll($sql);
    }

    public static function insert(array $data): int
    {
        $pdo = self::pdo();
        $fields = array_keys($data);
        $placeholders = array_map(fn($f) => ":{$f}", $fields);

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            static::$table,
            implode(', ', array_map(fn($f) => "`{$f}`", $fields)),
            implode(', ', $placeholders)
        );

        $params = [];
        foreach ($data as $key => $val) {
            $params[":{$key}"] = $val;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $fields = [];
        $params = [':id' => $id];

        foreach ($data as $key => $val) {
            $fields[] = "`{$key}` = :{$key}";
            $params[":{$key}"] = $val;
        }

        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s = :id",
            static::$table,
            implode(', ', $fields),
            static::$primaryKey
        );

        $stmt = self::pdo()->prepare($sql);
        return $stmt->execute($params);
    }

    public static function delete(int $id): bool
    {
        $sql = sprintf("DELETE FROM %s WHERE %s = :id", static::$table, static::$primaryKey);
        $stmt = self::pdo()->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public static function count(string $where = '', array $params = []): int
    {
        $sql = "SELECT COUNT(*) FROM " . static::$table;
        if (!empty($where)) {
            $sql .= " WHERE " . $where;
        }
        return (int)self::fetchColumn($sql, $params);
    }
}
