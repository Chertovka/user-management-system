<?php

include "config/database.php";

class  Database
{
    /**
     * @var PDO
     */
    public $pdo;

    public function __construct()
    {
        if (!isset($this->pdo)) {
            try {
                $link = new PDO('mysql:host=' . DB_HOST . '; dbname=' . DB_NAME, DB_USER, DB_PASS);
                $link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $link->exec("SET CHARACTER SET utf8");
                $this->pdo = $link;
            } catch (PDOException $e) {
                die("Ошибка подключения..." . $e->getMessage());
            }
        }
    }
}
