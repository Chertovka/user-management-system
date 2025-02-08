<?php

include 'controllers/Database.php';

class Users
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * @param $email
     * @return bool
     */
    public function checkUniquenessEmail($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        try {
            $sql = "SELECT 1 FROM users WHERE email = :email";
            $query = $this->db->pdo->prepare($sql);
            $query->bindValue(':email', $email, );
            $query->execute();

            $result = $query->fetchColumn();

            return ($result !== false && $result !== null);

        } catch (PDOException $e) {
            error_log("Ошибка при проверке email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @param $data
     * @return array
     */
    public function addNewUser($data)
    {
        $name = isset($data['name']) ? $data['name'] : '';
        $email = isset($data['email']) ? $data['email'] : '';
        $groupIds = isset($data['groupId']) ? $data['groupId'] : [];

        $checkEmail = $this->checkUniquenessEmail($email);

        $error = '';
        if ($checkEmail) {
            $error = "Email уже существует. Используйте другой email!";
        }

        if (!empty($error)) {
            return [
                'success' => false,
                'message' => $error
            ];
        }

        try {
            $pdo = $this->db->pdo;
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->beginTransaction();

            $sql = "INSERT INTO users(name, email, created_at, updated_at) VALUES(:name, :email, NOW(), NOW())";
            $query = $pdo->prepare($sql);

            $query->bindValue(':name', $name);
            $query->bindValue(':email', $email);

            $query->execute();

            $newUserId = $pdo->lastInsertId();

            $sqlUserGroupInsert = "INSERT INTO user_group(user_id, group_id) VALUES(:user_id, :group_id)";
            $queryUserGroupInsert = $pdo->prepare($sqlUserGroupInsert);

            foreach ($groupIds as $groupId) {
                $queryUserGroupInsert->bindValue(':user_id', $newUserId, PDO::PARAM_INT);
                $queryUserGroupInsert->bindValue(':group_id', $groupId, PDO::PARAM_INT);
                $queryUserGroupInsert->execute();
            }

            $pdo->commit();

            return [
                'success' => true,
                'message' => "Пользователь успешно создан!"
            ];
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return [
                'success' => false,
                'message' => "Ошибка при создании пользователя: " . $e->getMessage()
            ];
        }
    }

    /**
     * @param $email
     * @param $userId
     * @return bool
     */
    public function isEmailBelongsToUser($email, $userId)
    {
        try {
            $sql = "SELECT id FROM users WHERE email = :email AND id = :id";
            $query = $this->db->pdo->prepare($sql);
            $query->bindValue(':email', $email, PDO::PARAM_STR);
            $query->bindValue(':id', $userId, PDO::PARAM_INT);
            $query->execute();

            $user = $query->fetch(PDO::FETCH_ASSOC);

            return ($user !== false);
        } catch (PDOException $e) {
            error_log("Ошибка при проверке email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @param $userId
     * @param $data
     * @return string|void
     */
    public function updateUser($userId, $data)
    {
        $name = $data['name'];
        $email = $data['email'];
        $groupIds = isset($data['groupId']) ? $data['groupId'] : [];

        $checkEmail = $this->checkUniquenessEmail($email);

        $error = '';

        if ($checkEmail && !$this->isEmailBelongsToUser($email, $userId)) {
            $error = "Данный email уже существует. Используйте другой!";
        }

        if (!empty($error)) {
            return $error;
        }

        try {
            $pdo = $this->db->pdo;
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->beginTransaction();

            $sql = "UPDATE users SET name = :name, email = :email, updated_at = NOW()";

            $params = [
                ':name' => $name,
                ':email' => $email
            ];

            $sql .= " WHERE id = :id";
            $params[':id'] = $userId;

            $query = $pdo->prepare($sql);

            foreach ($params as $key => $value) {
                $query->bindValue($key, $value, (is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR));
            }
            $query->execute();

            $sqlUserGroupDelete = "DELETE FROM user_group WHERE user_id = :user_id";
            $queryUserGroupDelete = $pdo->prepare($sqlUserGroupDelete);
            $queryUserGroupDelete->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $queryUserGroupDelete->execute();

            $sqlUserGroupInsert = "INSERT INTO user_group (user_id, group_id) VALUES (:user_id, :group_id)";
            $queryUserGroupInsert = $pdo->prepare($sqlUserGroupInsert);

            foreach ($groupIds as $groupId) {
                $queryUserGroupInsert->bindValue(':user_id', $userId, PDO::PARAM_INT);
                $queryUserGroupInsert->bindValue(':group_id', $groupId, PDO::PARAM_INT);
                $queryUserGroupInsert->execute();
            }

            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Ошибка при обновлении пользователя: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @param $remove
     * @return array
     */
    public function deleteUser($remove)
    {
        if (!is_numeric($remove) || intval($remove) <= 0) {
            return [
                'success' => false,
                'message' => "Некорректный id пользователя!"
            ];
        }

        $userId = intval($remove);

        try {
            $pdo = $this->db->pdo;
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->beginTransaction();

            $checkSql = "SELECT COUNT(*) FROM users WHERE id = :id";
            $checkStmt = $this->db->pdo->prepare($checkSql);
            $checkStmt->bindValue(':id', $userId, PDO::PARAM_INT);
            $checkStmt->execute();

            if ($checkStmt->fetchColumn() == 0) {
                return [
                    'success' => false,
                    'message' => "Пользователь с id " . $userId . " не найден!"
                ];
            }

            $sql = "DELETE FROM users WHERE id = :id";
            $query = $this->db->pdo->prepare($sql);
            $query->bindValue(':id', $userId, PDO::PARAM_INT);
            $result = $query->execute();

            if ($result) {
                $pdo->commit();
                return [
                    'success' => true,
                    'message' => "Пользователь успешно удален!"
                ];
            } else {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'message' => "Не удалось удалить пользователя!"
                ];
            }

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return [
                'success' => false,
                'message' => "Ошибка при удалении пользователя: " . $e->getMessage()
            ];
        }
    }

    /**
     * @param $page
     * @param $perPage
     * @return array
     */
    public function selectAllUser()
    {
        try {
            $sql = "SELECT users.id, users.name, users.email, users.created_at, GROUP_CONCAT(`groups`.name SEPARATOR ', ') AS users_groups
                    FROM users
                    LEFT JOIN user_group ON users.id = user_group.user_id
                    LEFT JOIN `groups` ON user_group.group_id = `groups`.id
                    GROUP BY users.id
                    ORDER BY users.created_at DESC;
                    ";
            $query = $this->db->pdo->prepare($sql);
            $query->execute();
            $users = $query->fetchAll(PDO::FETCH_OBJ);

            return [
                'users' => $users,
            ];
        } catch (PDOException $e) {
            error_log("Ошибка при получении пользователей: " . $e->getMessage());
            return [
                'users' => [],
            ];
        }
    }
}