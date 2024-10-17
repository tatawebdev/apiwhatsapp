<?php
include_once __DIR__ . '/Model.php';
class PalavrasProibidas extends Model
{
    protected static $table = 'palavras_proibidas'; // Nome da tabela

    // Método para obter todas as palavras proibidas
    public static function getAllPalavras()
    {
        $sql = "SELECT `id`, `palavra` FROM " . static::$table;
        return self::query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function existsPalavra($palavra)
    {
        $sql = "SELECT id FROM " . static::$table . " WHERE palavra = ?";
        $result = self::query($sql, [$palavra])->fetch(PDO::FETCH_ASSOC);

        return $result !== false; // Retorna true se a palavra existir, caso contrário, false
    }

    public static function getAllPalavrasOnly()
    {
        $sql = "SELECT `palavra` FROM " . static::$table;
        $result = self::query($sql)->fetchAll(PDO::FETCH_COLUMN); // Obtém apenas a coluna 'palavra'
        return $result; // Retorna uma lista de palavras
    }

    

    // Método para adicionar uma nova palavra proibida
    public static function addPalavra($palavra)
    {
        // Verifica se a palavra já existe
        $existing = self::query("SELECT id FROM " . static::$table . " WHERE palavra = ?", [$palavra])->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            throw new Exception("A palavra '$palavra' já está proibida.");
        }

        // Adiciona a nova palavra proibida
        self::insert(['palavra' => $palavra]);

        return self::query("SELECT * FROM " . static::$table . " WHERE palavra = ?", [$palavra])->fetch(PDO::FETCH_ASSOC);
    }

    // Método para remover uma palavra proibida
    public static function removePalavra($id)
    {
        // Verifica se a palavra existe
        $existing = self::query("SELECT id FROM " . static::$table . " WHERE id = ?", [$id])->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            throw new Exception("Palavra não encontrada com o ID: $id");
        }

        // Remove a palavra proibida
        self::delete($id);

        return true; // Retorna true se a remoção foi bem-sucedida
    }
}
