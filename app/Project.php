<?php
class Project {
    public static function create($data) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("
            INSERT INTO projects 
            (name, description, client_id, contractor_id, budget, status) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['client_id'],
            $data['contractor_id'],
            $data['budget'],
            $data['status'] ?? 'draft'
        ]);
    }

    public static function update($id, $data) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("
            UPDATE projects 
            SET name = ?, description = ?, client_id = ?, 
                contractor_id = ?, budget = ?, status = ? 
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['client_id'],
            $data['contractor_id'],
            $data['budget'],
            $data['status'],
            $id
        ]);
    }

    public static function delete($id) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function getById($id) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function getAll() {
        $pdo = Database::connect();
        return $pdo->query("SELECT * FROM projects")->fetchAll();
    }

    public static function getByClient($clientId) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE client_id = ?");
        $stmt->execute([$clientId]);
        return $stmt->fetchAll();
    }

    public static function getByContractor($contractorId) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE contractor_id = ?");
        $stmt->execute([$contractorId]);
        return $stmt->fetchAll();
    }
}