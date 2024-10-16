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
        $interacaoExistente = self::query("SELECT id, id_flow FROM " . static::$table . " WHERE id_usuario = ?", [$id_usuario])->fetch(PDO::FETCH_ASSOC);

        if ($interacaoExistente) {
            // Passo 3: Verificar se o fluxo atual mudou
            $fluxoAtual = self::query("SELECT id FROM chatbot_flows WHERE atual = 1")->fetch(PDO::FETCH_ASSOC);

            if ($fluxoAtual) {
                $novoIdFlow = $fluxoAtual['id'];

                // Se o id_flow mudou, atualiza a interação
                if ($interacaoExistente['id_flow'] !== $novoIdFlow) {
                    // Chama getFlowAndStep() para obter novos valores de id_flow e id_step
                    $flowAndStep = ChatbotInteracoesUsuario::getFlowAndStep();
                    $idFlow = $flowAndStep['id_flow'];
                    $idStep = $flowAndStep['id_step'];

                    self::update([
                        'id_flow' => $idFlow,
                        'id_step' => $idStep,
                        'ultima_interacao' => date('Y-m-d H:i:s')
                    ], $interacaoExistente['id']);
                } else {
                    // Apenas atualiza a última interação, sem mudar o fluxo ou passo
                    self::update([
                        'ultima_interacao' => date('Y-m-d H:i:s')
                    ], $interacaoExistente['id']);
                }

                // Retorna a interação atualizada
                return self::query("SELECT * FROM " . static::$table . " WHERE id = ?", [$interacaoExistente['id']])->fetch(PDO::FETCH_ASSOC);
            }
        } else {
            // Se não houver interação existente, obtemos o fluxo e passo
            $flowAndStep = ChatbotInteracoesUsuario::getFlowAndStep();
            $idFlow = $flowAndStep['id_flow'];
            $idStep = $flowAndStep['id_step'];

            self::insert([
                'id_usuario' => $id_usuario,
                'id_flow' => $idFlow,
                'id_step' => $idStep,
                'primeira_interacao' => date('Y-m-d H:i:s'),
                'ultima_interacao' => date('Y-m-d H:i:s')
            ]);

            // Retorna a nova interação inserida
            return self::query("SELECT * FROM " . static::$table . " WHERE id_usuario = ? ORDER BY id DESC LIMIT 1", [$id_usuario])->fetch(PDO::FETCH_ASSOC);
        }
    }

    public static function getFlowAndStep()
    {
        $flowQuery = "SELECT id FROM chatbot_flows WHERE atual = 1 LIMIT 1";
        $flowResult = self::query($flowQuery)->fetch(PDO::FETCH_ASSOC);

        $idFlow = $flowResult ? $flowResult['id'] : 1;

        $stepQuery = "SELECT id FROM chatbot_steps WHERE id_flow = ? LIMIT 1";
        $stepResult = self::query($stepQuery, [$idFlow])->fetch(PDO::FETCH_ASSOC);

        $idStep = $stepResult ? $stepResult['id'] : 1;

        return [
            'id_flow' => $idFlow,
            'id_step' => $idStep
        ];
    }

    public static function updateStepById($id, $newIdStep)
    {
        $interacao = self::query("SELECT id FROM " . static::$table . " WHERE id = ?", [$id])->fetch(PDO::FETCH_ASSOC);

        if (!$interacao) {
            throw new Exception("Interação não encontrada com o ID: $id");
        }

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
