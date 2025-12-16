<?php

namespace Core;

use PDO;
use App\Database;

/**
 * Minimal QueryBuilder 
 */
class Query {

  /**
   * Active PDO connection shared by queries.
   * @var PDO
   */
  protected PDO $db;

  /**
   * Target table for the current query.
   * @var string
   */
  private string $table = '';

  /**
   * Collected WHERE clauses for the next statement.
   * @var array
   */
  private array $wheres = [];

  /**
   * Create a new query builder for the given table.
   *
   * @param string $table
   */
  public function __construct(string $table) {
    $this->db = Database::getConnection();
    $this->table = $table;
  }

  /**
   * Factory helper to start a query for a table.
   *
   * @param string $name
   * @return self
   */
  public static function table(string $name) : self {
    return new Query($name);
  }

  /**
   * Insert a new row and return the inserted id.
   *
   * @param array<string,mixed> $data
   * @return int
   */
  public function insert(array $data) {
    $placeholders = array_map(fn($key) => ':' . $key, array_keys($data));

    $sql = 'INSERT INTO ' . $this->table . ' (';
    $sql .= implode(', ', array_keys($data)); 
    $sql .= ') VALUES (';
    $sql .= implode(', ', $placeholders);
    $sql .= ')';    
    $stmt = $this->db->prepare($sql);
    foreach ($data as $key => $value) {
      $stmt->bindValue(':' . $key, $value);
    }
    $stmt->execute();

    $this->wheres = [];
    return (int)$this->db->lastInsertId();
  }

  /**
   * Update rows matching current WHERE clauses.
   *
   * @param array<string,mixed> $data
   * @return int
   * @throws \RuntimeException
   */
  public function update(array $data) : int {
    if (empty($this->wheres)) {
      throw new \RuntimeException('Refusing to UPDATE without WHERE clause.');
    }

    $setClauses = array_map(fn($key) => $key . ' = :' . $key, array_keys($data));

    $sql = 'UPDATE ' . $this->table . ' SET ';
    $sql .= implode(', ', $setClauses) . ' ';
    $sql .= $this->buildWhereClause();

    $stmt = $this->db->prepare($sql);

    foreach ($data as $key => $value) {
      $stmt->bindValue(':' . $key, $value);
    }

    foreach ($this->wheres as $where) {
      $stmt->bindValue(':' . $where['column'], $where['value']);
    }
    $stmt->execute();
    
    $this->wheres = [];
    return $stmt->rowCount();
  }

  /**
   * Add a WHERE condition to the query.
   *
   * @param string $column
   * @param string $operator
   * @param mixed $value
   * @return self
   */
  public function where(string $column, string $operator, $value ) : self {
    $this->wheres[] = [
      'column' => $column, 
      'value' => $value, 
      'operator' => $operator
    ];
    
    return $this;
  }

  /**
   * Fetch the first matching row or null.
   *
   * @return array|null
   */
  public function first() : ?array {
    $sql = 'SELECT * FROM ' . $this->table . ' ' . $this->buildWhereClause() . ' LIMIT 1';
    $stmt = $this->db->prepare($sql);
    foreach ($this->wheres as $where) {
      $stmt->bindValue(':' . $where['column'], $where['value']);
    }
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $this->wheres = [];

    return $result ?: null;
  }

  /**
   * Fetch all matching rows.
   *
   * @return array
   */
  public function get() : array {
    $sql = 'SELECT * FROM ' . $this->table . ' ' . $this->buildWhereClause();
    $stmt = $this->db->prepare($sql);
    foreach ($this->wheres as $where) {
      $stmt->bindValue(':' . $where['column'], $where['value']);
    }
    $stmt->execute();
    $this->wheres = [];

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Build the WHERE clause for the current query.
   *
   * @return string
   */
  private function buildWhereClause() : string {
    if (empty($this->wheres)) {
      return '';
    }

    $clauses = array_map(function($where) {
      return $where['column'] . ' ' . $where['operator'] . ' :' . $where['column'];
    }, $this->wheres);

    return 'WHERE ' . implode(' AND ', $clauses);
  }

  /**
   * Expose the underlying PDO connection.
   *
   * @return PDO
   */
  protected function pdo(): PDO
  {
    return $this->db;
  }

  /**
   * Fetch a paginated list of rows ordered by id.
   *
   * @param int $limit
   * @param int $offset
   * @return array
   */
  public function all(int $limit = 50, int $offset = 0): array
  {
    $limit  = max(1, $limit);
    $offset = max(0, $offset);

    $sql = 'SELECT * FROM ' . $this->table .
           ' ORDER BY id ' .
           ' LIMIT ' . $limit .
           ' OFFSET ' . $offset;

    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}
