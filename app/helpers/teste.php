<?php

function testarChatbot($celular = null, $etapa = 1, $tipo_documento = 'cpf')
{
    $config = ENV; // Certifique-se de que a variável ENV está definida

    $chatbot = new Chatbot($config['DB_HOST'], $config['DB_USER'], $config['DB_NAME'], $config['DB_PASSWORD']);

    switch ($etapa) {
        case 1:
            // Teste 1: Primeira mensagem do usuário
            echo "Teste 1:\n";
            $data1 = [
                'celular' => $celular,
                'message_etapa' => $etapa,
                'message_tipo_documento' => $tipo_documento,
                'message' => 'Olá!',
                'message_id' => '1',
                'event_type' => 'message_text',
            ];
            $chatbot->processarEntrada($data1);
            echo "\n";
            break;

        case 2:
            // Teste 2: Nome válido
            echo "Teste 2:\n";
            $data2 = [
                'celular' => $celular,
                'message_etapa' => $etapa,
                'message_tipo_documento' => $tipo_documento,
                'message' => 'Jeremias de souza',
                'message_id' => '2',
                'event_type' => 'message_text',
            ];
            $chatbot->processarEntrada($data2);
            echo "\n";
            break;

        case 3:
            // Teste 3: Tipo de pessoa inválido
            echo "Teste 3:\n";
            $data3 = [
                'celular' => $celular,
                'message_etapa' => $etapa,
                'message_tipo_documento' => $tipo_documento,
                'message' => 'Dívidas Tributárias',
                'message_id' => '3',
                'event_type' => 'interactive',
            ];
            $chatbot->processarEntrada($data3);
            echo "\n";
            break;

        case 4:
            // Teste 4: Tipo de pessoa válido
            echo "Teste 4:\n";
            $data4 = [
                'celular' => $celular,
                'message_etapa' => $etapa,
                'message_tipo_documento' => $tipo_documento,
                'message' => 'Teste automatizado, Teste automatizado , Teste automatizado  ',
                'message_id' => '4',
                'event_type' => 'message_text',
            ];
            $chatbot->processarEntrada($data4);
            echo "\n";
            break;

        case 5:
            if ($tipo_documento == 'cpf') {
                $pessoa = 'Pessoa Física';
            } else {
                $pessoa = 'Pessoa Jurídica';
            }
            // Teste 5: CPF ou CNPJ
            echo "Teste 5:\n";
            $data5 = [
                'celular' => $celular,
                'message_etapa' => $etapa,
                'message_tipo_documento' => $tipo_documento,
                'message' => $pessoa,
                'message_id' => '5',
                'event_type' => 'message_button',
            ];
            $chatbot->processarEntrada($data5);
            echo "\n";
            break;
        case 6:

            if ($tipo_documento == 'cpf') {
                $doc = '44940226832';
            } else {
                $doc = '57078276000100';
            }

            // Teste 5: CPF ou CNPJ
            echo "Teste 5:\n";
            $data5 = [
                'celular' => $celular,
                'message_etapa' => $etapa,
                'message_tipo_documento' => $tipo_documento,
                'message' => $doc,
                'message_id' => '5',
                'event_type' => 'message_text',
            ];
            $chatbot->processarEntrada($data5);
            echo "\n";
            break;

        case 7:
            // Teste 6: Finalizar atendimento
            echo "Teste 7:\n";
            $data6 = [
                'celular' => $celular,
                'message_etapa' => $etapa,
                'message_tipo_documento' => $tipo_documento,
                'message' => 'Baixa',
                'message_id' => '6',
                'event_type' => 'message_button',
            ];
            $chatbot->processarEntrada($data6);
            echo "\n";
            break;

        default:
            echo "Etapa não reconhecida.\n";
            break;
    }
    $chatbot->atualizarEstado($etapa + 1, $tipo_documento);
}
