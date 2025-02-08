<?php

require_once 'controllers/Users.php';

$user = new Users();

// Добавить пользователя
echo "Добавление пользователей:";

$addFirstUser = [
    'name' => 'Петр Петров',
    'email' => 'petrov.p13@gmail.com',
    'groupId' => [2, 4]
];

$resultAfterAddFirstUser = $user->addNewUser($addFirstUser);
if ($resultAfterAddFirstUser) {
    echo "Пользователь успешно добавлен!";
} else {
    echo "Ошибка при создании пользователя!";
}

$addSecondUser = [
    'name' => 'Алиса Пальчикова',
    'email' => 'palchikova.a00@gmail.com',
    'groupId' => [1]
];

$resultAfterAddSecondUser = $user->addNewUser($addSecondUser);
if ($resultAfterAddSecondUser) {
    echo "Пользователь успешно добавлен!";
} else {
    echo "Ошибка при создании пользователя!";
}

// Получить список пользователей
echo "Список пользователей:";

$allUsers = $user->selectAllUser();

if ($allUsers) {
    echo "<table>";
    echo "<thead><tr><th>ID</th><th>Имя</th><th>Email</th><th>Группы</th></tr></thead>";
    echo "<tbody>";
    foreach ($allUsers['users'] as $userItem) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($userItem->id) . "</td>";
        echo "<td>" . htmlspecialchars($userItem->name) . "</td>";
        echo "<td>" . htmlspecialchars($userItem->email) . "</td>";
        echo "<td>" . htmlspecialchars($userItem->users_groups) . "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
} else {
    echo "Не удалось получить пользователей!";
}
echo "<hr>";

//Обновить информацию о пользователе
echo "Обновить информацию о пользователе:";

$userIdToUpdate = 2;

$updateUserData = [
    'name' => 'Алиса Пальчикова',
    'email' => 'palchikova.a00@gmail.com',
    'groupId' => [5]
];

$resultAfterUpdateUser = $user->updateUser($userIdToUpdate, $updateUserData);
if ($resultAfterUpdateUser) {
    echo "Пользователь успешно обновлен!";
} else {
    echo "Ошибка при обновлении пользователя.";
}

//Удалить пользователя
echo "Удалить пользователя:";

$userIdToDelete = 1;

$resultAfterDeleteUser = $user->deleteUser($userIdToDelete);
if ($resultAfterDeleteUser) {
    echo "Пользователь успешно удален!";
} else {
    echo "Произошла ошибка при удалении пользователя!";
}

// Вывод списка пользователей после удаления
echo "Вывод списка пользователей после удаления:";

$allUsersAfterDelete = $user->selectAllUser();

if ($allUsersAfterDelete) {
    echo "<table>";
    echo "<thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Groups</th></tr></thead>";
    echo "<tbody>";
    foreach ($allUsersAfterDelete['users'] as $userAfterDelete) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($userAfterDelete->id) . "</td>";
        echo "<td>" . htmlspecialchars($userAfterDelete->name) . "</td>";
        echo "<td>" . htmlspecialchars($userAfterDelete->email) . "</td>";
        echo "<td>" . htmlspecialchars($userAfterDelete->users_groups) . "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
} else {
    echo "Не удалось получить пользователей!";
}