<?php
require_once __DIR__ . '/Database.php';
class Game {
    public static function all() {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query('SELECT * FROM games ORDER BY id DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function search($keyword) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT * FROM games WHERE name LIKE ? ORDER BY id DESC');
        $stmt->execute(['%' . $keyword . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getByType($type, $limit = 6) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT * FROM games WHERE type = ? ORDER BY id DESC LIMIT ?');
        $stmt->bindValue(1, $type);
        $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 