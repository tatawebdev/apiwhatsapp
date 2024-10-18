<?php

class ChatbotOptions extends Model
{
    protected static $table = 'chatbot_options'; // Nome da tabela associada

    /**
     * Obtém todos os registros da tabela chatbot_options.
     *
     * @return array
     */
    public static function getAllOptions()
    {
        return self::all('id, id_step, resposta_opcional, id_step_proximo, tipo_interacao, titulo_interacao, descricao_interacao');
    }

    /**
     * Obtém um registro da tabela chatbot_options pelo ID.
     *
     * @param int $id
     * @return object|null
     */
    public static function getOptionById($id)
    {
        return self::find($id, 'id, id_step, resposta_opcional, id_step_proximo, tipo_interacao, titulo_interacao, descricao_interacao');
    }

    /**
     * Adiciona um novo registro na tabela chatbot_options.
     *
     * @param array $data
     * @return int ID do novo registro inserido.
     */
    public static function addOption($data)
    {
        self::insert($data); // Insere os dados no banco
        return self::$db->lastInsertId(); // Retorna o ID do último registro inserido
    }

    /**
     * Atualiza um registro existente na tabela chatbot_options.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function updateOption($id, $data)
    {
        return self::update($data, $id); // Atualiza os dados com base no ID
    }

    /**
     * Deleta um registro da tabela chatbot_options pelo ID.
     *
     * @param int $id
     * @return bool
     */
    public static function deleteOption($id)
    {
        return self::delete($id); // Deleta o registro com base no ID
    }

    /**
     * Retorna botões interativos para uma mensagem do WhatsApp com base no id_step.
     *
     * @param int $id_step ID do passo no chatbot_steps.
     * @return array Retorna um array de botões.
     */
    public static function getBotoesMensagemWhatsApp($id_step)
    {
        // Consulta as opções de botões no banco de dados
        $sql = "SELECT titulo_interacao 
                FROM chatbot_options 
                WHERE id_step = :id_step";

        $stmt = self::$db->prepare($sql);
        $stmt->execute(['id_step' => $id_step]);
        $opcoes = $stmt->fetchAll();

        // Verifica se há opções
        if (empty($opcoes)) {
            return []; // Não há botões para retornar
        }

        // Monta os botões
        $buttons = [];
        foreach ($opcoes as $key => $opcao) {
            $buttons[] = [
                'type' => 'reply',
                'reply' => [
                    'id' => 'button_' . $key,
                    'title' => $opcao['titulo_interacao']
                ]
            ];
        }

        return $buttons; // Retorna os botões formatados
    }

    /**
     * Retorna uma lista interativa para uma mensagem do WhatsApp com base no id_step.
     *
     * @param int $id_step ID do passo no chatbot_steps.
     * @return array Retorna um array de seções e itens.
     */
    public static function getListaInterativaWhatsApp($id_step)
    {
        // Consulta as opções da lista interativa no banco de dados
        $sql = "SELECT titulo_interacao, descricao_interacao, secao_titulo 
                FROM chatbot_options 
                WHERE id_step = :id_step";

        $stmt = self::$db->prepare($sql);
        $stmt->execute(['id_step' => $id_step]);
        $opcoes = $stmt->fetchAll();

        // Verifica se há opções
        if (empty($opcoes)) {
            return []; // Não há listas para retornar
        }

        // Monta as seções e itens
        $secoes = [];
        foreach ($opcoes as $opcao) {
            $secoes[] = [
                'secao_titulo' => $opcao['secao_titulo'],
                'titulo' => $opcao['titulo_interacao'],
                'descricao' => $opcao['descricao_interacao']
            ];
        }

        return $secoes; // Retorna as seções formatadas
    }
}
