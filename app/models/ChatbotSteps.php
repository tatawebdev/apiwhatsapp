<?php

class ChatbotSteps extends Model
{
    protected static $table = 'chatbot_steps'; // Nome da tabela
    protected static $select = []; // Nome da tabela

    /**
     * Obtém todos os passos.
     *
     * @return array
     */
    public static function getAllSteps()
    {
        return self::all(); // Usa o método all da classe Model
    }

    /**
     * Obtém um passo pelo ID.
     *
     * @param int $id
     * @return array|null
     */
    /**
     * Obtém um passo pelo ID, incluindo as opções relacionadas.
     *
     * @param int $id
     * @return array|null
     */
    public static function getStepById($id)
    {
        // Obtém o passo pelo ID
        $step = self::find($id);

        if ($step) {
            // Consulta as opções relacionadas ao passo (id_step) na tabela chatbot_options
            $options = self::query("SELECT id, id_step, resposta_opcional, id_step_proximo 
                                    FROM chatbot_options 
                                    WHERE id_step = ?", [$id])
                ->fetchAll(PDO::FETCH_ASSOC);

            // Adiciona as opções ao array do passo
            $step->selected['options'] = $options;
        }

        return $step;
    }

    /**
     * Adiciona um novo passo.
     *
     * @param array $data
     * @return int ID do novo passo inserido.
     */
    public static function addStep($data)
    {
        self::insert($data); // Usa o método insert da classe Model
        return self::$db->lastInsertId(); // Retorna o ID do último registro inserido
    }

    /**
     * Atualiza um passo existente.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function updateStep($id, $data)
    {
        return self::update($data, $id); // Usa o método update da classe Model
    }
}
