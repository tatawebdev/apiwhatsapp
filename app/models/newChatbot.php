<?php

use Models\Connection;
use WhatsApp\InteractiveMessages;
use WhatsApp\Message;



include_once __DIR__ . '/Connection.php';

class newChatbot
{
    protected $connection; // Instância de conexão será armazenada aqui
    public $numeroUsuario;
    public $mensagemUsuario;
    public $event_type;
    public $last_message_id;




    public function processarEntrada($data)
    {
        echo '<pre>';

        if (empty($data['celular']) || empty($data['message']))
            return;

        $tipo_documento = '';
        $this->numeroUsuario = $data['celular'];
        $this->mensagemUsuario = $data['message'];
        $this->last_message_id = $data['message_id'];
        $this->event_type = $data['event_type'];

        $telefone = $this->numeroUsuario; // Telefone do usuário


        $usuario = ChatbotInteracoesUsuario::addUserAndInteraction($telefone);
        extract($usuario);
        dd($usuario_id);

        $step = new ChatbotSteps;
        $step = $step->getStepById($id_step)->selected;
        extract($step);

        var_dump($step);


        if (!empty($nome_da_funcao)) {
            if (function_exists($nome_da_funcao)) {
                $step['step_id'] =  $step['id'];
                unset($step['id']);
                $params = array_merge($step, $usuario);
                $params['data'] = $data;

                $resultado_validacao = call_user_func($nome_da_funcao, $params);
                if ($resultado_validacao['result']) {
                    ChatbotInteracoesUsuario::updateStepById($params['interacoes_id'], $params['id_step_proximo']);
                }else{
                    return $resultado_validacao['message'];
                }


                // Processa o resultado da validação
            }
        }
        // array(4) {
        //     ["usuario_id"]=>
        //     int(8)
        //     ["telefone"]=>
        //     string(13) "5511951936777"
        //     ["id_flow"]=>
        //     int(1)
        //     ["id_step"]=>
        //     int(1)
        //   }



        return [$pergunta];
    }


    public function obterEstado()
    {
        $sql = "SELECT id_step as etapa, type_documento as tipo_documento FROM interacoes WHERE numero_usuario = ? ORDER BY ultima_interacao DESC LIMIT 1";
        return $this->connection->fetchAssoc($sql, [$this->numeroUsuario], 's') ?? ['etapa' => null, 'tipo_documento' => null];
    }
}
