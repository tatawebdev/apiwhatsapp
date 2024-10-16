<?php

namespace Models;

class Connection
{
    protected $mysqli;

    public function __construct($host, $dbname, $username, $password)
    {
        $this->mysqli = new \mysqli($host, $username, $password, $dbname);
        if ($this->mysqli->connect_error) {
            die("Conexão falhou: " . $this->mysqli->connect_error);
        }
        $this->mysqli->set_charset("utf8");
    }

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
    }
}

