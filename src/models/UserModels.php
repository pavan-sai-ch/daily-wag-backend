<?php 
class UserModels {
    private $pdo;
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    public function createUser($first_name, $last_name, $email, $passwprd_hash, $role = 'user') {
        $sql = "INSERT INTO USERS(first_name,, last_name, email, password_hash, role) VALUES (first_name, last_name, email, password_hash, role)";
        $stmt =$this->pdo->prepare($sql);
        $stmt->execute();
    }   
    public function getUserByEmail($email) {
        $sql = "SELECT * FROM USERS WHERE email = email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }  
    public function getUserById($user_id){
        $sql = "SELECT* FROM users where user_id = user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);  
    }
}
?>