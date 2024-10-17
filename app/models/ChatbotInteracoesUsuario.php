<?php
include_once __DIR__ . '/Model.php';

class ChatbotInteracoesUsuario extends Model
{
    protected static $table = 'chatbot_interacoes_usuario'; // Nome da tabela
    private static $table_usuario = 'chatbot_usuario'; // Nome da tabela do usuário

    public function getInteractionsWithUsers()
    {
        $sql = "
            SELECT 
                ciu.id AS interacao_id,
                ciu.id_usuario,
                ciu.id_flow,
                ciu.id_step,
                ciu.resposta,
                ciu.primeira_interacao,
                ciu.ultima_interacao,
                cu.telefone,
                cu.data_criacao
            FROM " . static::$table . " AS ciu
            LEFT JOIN " . static::$table_usuario . " AS cu ON ciu.id_usuario = cu.id
        ";

        return self::query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function upsertInteraction($telefone)
    {
        // Passo 1: Obter o ID do usuário pelo telefone
        $usuario = self::query("SELECT id FROM " . static::$table_usuario . " WHERE telefone = ?", [$telefone])->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            throw new Exception("Usuário não encontrado com o telefone: $telefone");
        }

        $id_usuario = $usuario['id'];

        // Passo 2: Verificar se a interação já existe
        $interacaoExistente = self::query("SELECT id FROM " . static::$table . " WHERE id_usuario = ?", [$id_usuario])->fetch(PDO::FETCH_ASSOC);

        if ($interacaoExistente) {
            // Passo 3: Atualizar a interação existente
            self::update([
                'ultima_interacao' => date('Y-m-d H:i:s')
            ], $interacaoExistente['id']);

            // Retorna a interação atualizada
            return self::query("SELECT * FROM " . static::$table . " WHERE id = ?", [$interacaoExistente['id']])->fetch(PDO::FETCH_ASSOC);
        } else {
            // Passo 4: Inserir uma nova interação
            self::insert([
                'id_usuario' => $id_usuario,
                'id_flow' => 1,
                'id_step' => 1,
                'primeira_interacao' => date('Y-m-d H:i:s'),
                'ultima_interacao' => date('Y-m-d H:i:s')
            ]);

            // Retorna a nova interação inserida
            return self::query("SELECT * FROM " . static::$table . " WHERE id_usuario = ? ORDER BY id DESC LIMIT 1", [$id_usuario])->fetch(PDO::FETCH_ASSOC);
        }
    }

public static function updateStepById($id, $newIdStep)
    {
        // Verifica se a interação existe antes de atualizar
        $interacao = self::query("SELECT id FROM " . static::$table . " WHERE id = ?", [$id])->fetch(PDO::FETCH_ASSOC);

        if (!$interacao) {
            throw new Exception("Interação não encontrada com o ID: $id");
        }

        // Atualiza o id_step da interação
        self::update([
            'id_step' => $newIdStep
        ], $id);

        return self::query("SELECT * FROM " . static::$table . " WHERE id = ?", [$id])->fetch(PDO::FETCH_ASSOC);
    }

    public static function addUserAndInteraction($telefone)
    {
        // Adiciona o usuário, se não existir
        $usuario = ChatbotUsuario::addUserIfNotExists($telefone);
        $interacao = self::upsertInteraction($telefone);

        return [
            'usuario_id' => $usuario['id'],
            'telefone' => $usuario['telefone'],
            'interacoes_id' => $interacao['id'],
            'id_flow' => $interacao['id_flow'],
            'id_step' => $interacao['id_step']
        ];
    }
}
