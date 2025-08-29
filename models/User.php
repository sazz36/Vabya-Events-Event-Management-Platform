<?php
require_once 'BaseModel.php';

class User extends BaseModel {
    public function __construct() {
        parent::__construct('users');
    }

    public function create($name, $email, $password, $role = 'attendee') {
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $sql = "INSERT INTO users (name, email, password_hash, role) 
                VALUES (:name, :email, :password_hash, :role)";
        $params = [
            ':name' => $name,
            ':email' => $email,
            ':password_hash' => $passwordHash,
            ':role' => $role
        ];
        $this->executeQuery($sql, $params);
        return $this->db->lastInsertId();
    }

    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->executeQuery($sql, [':email' => $email]);
        return $stmt->fetch();
    }

    public function verifyPassword($email, $password) {
        $user = $this->findByEmail($email);
        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }
        return false;
    }

    public function updatePassword($userId, $newPassword) {
        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $sql = "UPDATE users SET password_hash = :password_hash WHERE id = :user_id";
        return $this->executeQuery($sql, [
            ':password_hash' => $passwordHash,
            ':user_id' => $userId
        ])->rowCount();
    }

    public function updateProfile($userId, $name, $email) {
        $sql = "UPDATE users SET name = :name, email = :email WHERE id = :user_id";
        return $this->executeQuery($sql, [
            ':name' => $name,
            ':email' => $email,
            ':user_id' => $userId
        ])->rowCount();
    }

    public function setResetToken($email, $token, $expires) {
        $sql = "UPDATE users SET reset_token = :token, reset_token_expires = :expires WHERE email = :email";
        return $this->executeQuery($sql, [
            ':token' => $token,
            ':expires' => $expires,
            ':email' => $email
        ])->rowCount();
    }

    public function findByResetToken($token) {
        $sql = "SELECT * FROM users WHERE reset_token = :token AND reset_token_expires > NOW()";
        $stmt = $this->executeQuery($sql, [':token' => $token]);
        return $stmt->fetch();
    }

    public function clearResetToken($userId) {
        $sql = "UPDATE users SET reset_token = NULL, reset_token_expires = NULL WHERE id = :user_id";
        return $this->executeQuery($sql, [':user_id' => $userId])->rowCount();
    }
}