<?php
class User {
    private $db;
    private $id;
    private $email;
    private $company_name;
    
    public function __construct($db) {
        $this->db = $db->getConnection();
    }
    
    public function register($data) {
        $stmt = $this->db->prepare("
            INSERT INTO users (email, password, company_name) 
            VALUES (?, ?, ?)
        ");
        
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        return $stmt->execute([$data['email'], $password, $data['company_name']]);
    }
    
    public function login($data) {
        $stmt = $this->db->prepare("SELECT id, password FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($data['password'], $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            return true;
        }
        return false;
    }
    
    public function getProfile($user_id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    }
    
    public function updateProfile($user_id, $data) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET company_name = ?, address = ?, phone = ?, siret = ?, website = ? 
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['company_name'],
            $data['address'],
            $data['phone'],
            $data['siret'],
            $data['website'],
            $user_id
        ]);
    }
}
?>