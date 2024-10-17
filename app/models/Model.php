<?php


class Model
{
    protected static $db; // Conexão com o banco de dados
    protected static $table; // Nome da tabela associada
    protected static $primaryKey = 'id'; // Chave primária padrão
    public  $selected = [];

    // Construtor para inicializar a conexão com o banco de dados
    public static function init($host = null, $dbname = null, $username = null, $password = null)
    {
        // Define os valores padrão usando as variáveis de ambiente
        $host = $host ?? $_ENV['DB_HOST'];
        $dbname = $dbname ?? $_ENV['DB_NAME'];
        $username = $username ?? $_ENV['DB_USER'];
        $password = $password ?? $_ENV['DB_PASSWORD'];

        // Inicializa a conexão PDO com os valores corretos
        self::$db = new \PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        self::$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        self::$db->exec("SET NAMES utf8");
    }



    // Método para executar uma query
    protected static function query($sql, $params = [])
    {
        $stmt = self::$db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    // Método para buscar todos os registros
    public static function all($columns = '*')
    {
        return self::query("SELECT $columns FROM " . static::$table)->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Método para buscar um registro pelo ID
    public static function find($id, $columns = '*')
    {

        $data = self::query("SELECT $columns FROM " . static::$table . " WHERE " . static::$primaryKey . " = ?", [$id])->fetch(\PDO::FETCH_ASSOC);
        $obj = new static($data);
        if ($data) {
            foreach ($data as $key => $value) {
                $obj->selected[$key] = $value;
            }
        }
        return $obj;
    }

    // Método para inserir um novo registro
    public static function insert($data)
    {
        $columns = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_fill(0, count($data), '?'));
        self::query("INSERT INTO " . static::$table . " ($columns) VALUES ($placeholders)", array_values($data));
    }

    // Método para atualizar um registro
    public static function update($data, $id)
    {
        $set = [];
        foreach ($data as $column => $value) {
            $set[] = "$column = ?";
        }
        $set = implode(", ", $set);
        self::query("UPDATE " . static::$table . " SET $set WHERE " . static::$primaryKey . " = ?", array_merge(array_values($data), [$id]));
    }

    // Método para deletar um registro
    public static function delete($id)
    {
        self::query("DELETE FROM " . static::$table . " WHERE " . static::$primaryKey . " = ?", [$id]);
    }
}
