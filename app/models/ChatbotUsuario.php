<?php

include_once __DIR__ . '/Model.php';


class ChatbotUsuario extends Model
{
    protected static $table = 'chatbot_usuario'; // Define a tabela associada
    protected $id; // ID do usuário
    protected $telefone; // Telefone do usuário

    // Construtor
    public function __construct($id = null, $telefone = null)
    {
        $this->id = $id;
        $this->telefone = $telefone;
    }

    // Getters
    public function getId()
    {
        return $this->id;
    }

    public function getTelefone()
    {
        return $this->telefone;
    }

    // Setters
    public function setTelefone($telefone)
    {
        $this->telefone = $telefone;
    }

    // Método para salvar (inserir ou atualizar)
    public function save()
    {
        $data = ['telefone' => $this->telefone];

        if ($this->id) {
            // Se o ID estiver definido, atualiza
            self::update($data, $this->id);
        } else {
            // Caso contrário, insere um novo registro
            self::insert($data);
            $this->id = self::$db->lastInsertId(); // Atualiza o ID após a inserção
        }
    }

    // Método estático para buscar todos os usuários com telefone
    public static function getAllUsers()
    {
        return self::query("SELECT `id`, `telefone` FROM " . static::$table)->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Método para buscar um usuário pelo ID
    public static function find($id, $columns = '*') // Altera para incluir o parâmetro $columns
    {
        $data = self::query("SELECT $columns FROM " . static::$table . " WHERE id = ?", [$id])->fetch(\PDO::FETCH_ASSOC);
        if ($data) {
            return new self($data['id'], $data['telefone']); // Retorna uma instância da classe
        }
        return null; // Retorna null se não encontrar
    }

    public static function addUserIfNotExists($telefone)
    {
        // Tenta recuperar o usuário pelo telefone
        $user = self::query("SELECT * FROM " . static::$table . " WHERE telefone = ?", [$telefone])->fetch(\PDO::FETCH_ASSOC);

        // Se não existir, insere um novo usuário
        if (!$user) {
            self::insert(['telefone' => $telefone]);
            $user = self::query("SELECT * FROM " . static::$table . " WHERE telefone = ?", [$telefone])->fetch(\PDO::FETCH_ASSOC);
        }

        return [
            'id' => $user['id'],
            'telefone' => $user['telefone']
        ];
    }
}
