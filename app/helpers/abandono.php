<?php
function checkAbandono()
{

    $config = ENV;
    $chatbot = new Chatbot($config['DB_HOST'], $config['DB_USER'], $config['DB_NAME'], $config['DB_PASSWORD']);
    $atendimentosemaberto = $chatbot->getAtendimentosAntigosEmAndamento();

    foreach ($atendimentosemaberto as $atendimento) {
        $atendimento['assuntoEmail'] = 'Chatbot do Whatsapp (Abandono)';
        $chatbot->enviarEmailAtendimento($atendimento);
        $chatbot->marcarAtendimentoComoAbandono($atendimento['id']);
    }
}
