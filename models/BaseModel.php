<?php
require_once __DIR__ . '/../Config/db.php';

abstract class BaseModel {
    protected $db;
    protected $table;

    public function __construct($table) {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->table = $table;
    }

    protected function executeQuery($sql, $params = []) {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            throw $e;
        }
    }

    public function find($id) {
        // Use correct primary key for users table
        $primaryKey = ($this->table === 'users') ? 'user_id' : 'id';
        $sql = "SELECT * FROM {$this->table} WHERE $primaryKey = :id";
        $stmt = $this->executeQuery($sql, [':id' => $id]);
        return $stmt->fetch();
    }

    public function findAll() {
        $sql = "SELECT * FROM {$this->table}";
        $stmt = $this->executeQuery($sql);
        return $stmt->fetchAll();
    }

    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        return $this->executeQuery($sql, [':id' => $id])->rowCount();
    }
}