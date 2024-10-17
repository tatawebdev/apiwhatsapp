<?php

namespace Models;

class Connection
{
    private static $instance = null; // Armazena a instância única da conexão
    protected $mysqli;

    // Construtor privado para impedir criação direta de novas instâncias
    private function __construct($host, $dbname, $username, $password)
    {
        $this->mysqli = new \mysqli($host, $username, $password, $dbname);
        if ($this->mysqli->connect_error) {
            die("Conexão falhou: " . $this->mysqli->connect_error);
        }
        $this->mysqli->set_charset("utf8");
    }

    
    // Função para retornar a instância única
    public static function getInstance($host, $dbname, $username, $password)
    {
        if (self::$instance === null) {
            self::$instance = new self($host, $dbname, $username, $password);
        }
        return self::$instance;
    }

    // Métodos para executar queries, buscar dados e fechar a conexão
    public function query($sql, $params = [], $types = "")
    {
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            die("Erro ao preparar a consulta: " . $this->mysqli->error);
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        return $stmt;
    }

    public function fetchAssoc($sql, $params = [], $types = "")
    {
        $stmt = $this->query($sql, $params, $types);
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        return $data;
    }

    public function fetchAll($sql, $params = [], $types = "")
    {
        $stmt = $this->query($sql, $params, $types);
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    }

    public function close()
    {
        $this->mysqli->close();
        self::$instance = null; // Reseta a instância
    }
}
