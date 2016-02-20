<?php
namespace Halstack;

use Halstack\NestedCategory;

class NestedCategoryDAO
{
    const TABLE = 'category';
    const MODEL = 'Halstack\NestedCategory';

    private $table = '';

    public function __construct($connection)
    {
        $this->connection = $connection;
        $this->table = $this->connection->quoteIdentifier(self::TABLE);
    }

    /**
     * Should be run once
     */
    public function initDatabaseModel()
    {
        $schema = 'CREATE TABLE IF NOT EXISTS '.$this->table.' (id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT, name VARCHAR(128) NOT NULL, lft INTEGER, rght INTEGER, created_at DATETIME, updated_at DATETIME);';
        $stmt = $this->connection->prepare($schema);
        $stmt->execute();

        $sql = 'SELECT COUNT(*) AS c FROM ' . $this->table;
        $stmt = $this->connection->executeQuery($sql);
        $isEmpty = 0 === (int) $stmt->fetch()['c'];

        if ($isEmpty) {
                $category = new NestedCategory;
                $category->name = 'root';
                $category->lft = 1;
                $category->rght = 2;
                $this->insert($category);
        }
    }

    public function findById($id)
    {
        $sql = 'SELECT * FROM ' . $this->table.' WHERE id = :id';
        $params = array('id' => $id);
        $stmt = $this->connection->executeQuery($sql, $params);
        $collection = $stmt->fetchAll(\PDO::FETCH_CLASS, self::MODEL);

        if (empty($collection)) {
            return null;
        }

        return array_shift($collection);
    }

    public function findAll()
    {
        $sql = 'SELECT * FROM ' . $this->table;
        $stmt = $this->connection->executeQuery($sql);
        $categoryCollection = $stmt->fetchAll(\PDO::FETCH_CLASS, self::MODEL);
        return $categoryCollection;
    }

    public function append(NestedCategory $parent, NestedCategory $category)
    {
        $this->connection->beginTransaction();

        $category->lft = $parent->rght;
        $category->rght = $category->lft + 1;

        $params = array('rght' => $parent->rght);
        $sql = 'UPDATE '.$this->table.' SET rght = rght + 2 WHERE rght >= :rght';
        $stmt = $this->connection->executeQuery($sql, $params);

        $params = array('rght' => $parent->rght);
        $sql = 'UPDATE '.$this->table.' SET lft = lft + 2 WHERE lft >= :rght';
        $stmt = $this->connection->executeQuery($sql, $params);

        $this->insert($category);

        $this->connection->commit();
    }

    public function insertLeft(NestedCategory $nextCategory, NestedCategory $category)
    {
        $this->connection->beginTransaction();

        $category->lft = $nextCategory->lft;
        $category->rght = $category->lft + 1;

        $params = array('lft' => $category->lft);
        $sql = 'UPDATE '.$this->table.' SET rght = rght + 2 WHERE rght >= :lft';
        $stmt = $this->connection->executeQuery($sql, $params);

        $params = array('lft' => $category->lft);
        $sql = 'UPDATE '.$this->table.' SET lft = lft + 2 WHERE lft >= :lft';
        $stmt = $this->connection->executeQuery($sql, $params);

        $this->insert($category);

        $this->connection->commit();
    }

    public function insert(NestedCategory $category)
    {
        $sql = 'INSERT INTO '.$this->table.'(name, lft, rght)  VALUES(:name, :lft, :rght)';
        $params = array(
            'name' => $category->name,
            'lft' => $category->lft,
            'rght' => $category->rght,
        );
        $stmt = $this->connection->executeQuery($sql, $params);
    }

    public function getTree()
    {
        $sql = 'SELECT COUNT(parent.name) - 1 AS depth, node.id, node.name
                FROM '.$this->table.' AS node,
                        '.$this->table.' AS parent
                WHERE node.lft BETWEEN parent.lft AND parent.rght
                GROUP BY node.name
                ORDER BY node.lft';

        $stmt = $this->connection->executeQuery($sql);
        return $stmt->fetchAll(\PDO::FETCH_CLASS);
    }

    public function deleteSubtree(NestedCategory $category)
    {
        $this->connection->beginTransaction();

        $sql = 'DELETE FROM '.$this->table.'WHERE lft BETWEEN :lft AND :rght';
        $params = array(
            'lft' => $category->lft,
            'rght' => $category->rght,
        );
        $stmt = $this->connection->executeQuery($sql, $params);

        $shift = $category->rght - $category->lft +1;
        $params = array(
            'shift' => $shift,
            'rght' => $category->rght,
        );

        $sql = 'UPDATE '.$this->table.' SET lft = lft - :shift WHERE lft > :rght';
        $stmt = $this->connection->executeQuery($sql, $params);
        $sql = 'UPDATE '.$this->table.' SET rght = rght - :shift WHERE rght > :rght';
        $stmt = $this->connection->executeQuery($sql, $params);

        $this->connection->commit();
    }

}
