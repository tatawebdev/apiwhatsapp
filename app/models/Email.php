<?php


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// define('EMAIL_PRINCIPAL', 'suporte@tataweb.com.br');



require_once PATH_LIBS . 'php-mailer/Exception.php';
require_once PATH_LIBS . 'php-mailer/PHPMailer.php';
require_once PATH_LIBS . 'php-mailer/SMTP.php';
include_once __DIR__ . '/CNPJConsulta.php';


class Email
{
    public $dados;
    public $mensagem;
    public $filePath =  null;
    function send($dados)
    {
        $this->dados = $dados;
        $mail = new PHPMailer();

        $mail->IsSMTP();
        $mail->SMTPAuth = true;
        $mail->Port = 587;
        $mail->SMTPSecure = false;
        $mail->SMTPAutoTLS = false;
        $mail->Host     = HOST_EMAIL;
        $mail->Username = USERNAME_EMAIL;
        $mail->Password = PASSWORD_EMAIL;
        $mail->From     = EMAIL_PRINCIPAL;
        $mail->AddBCC('suporte@tataweb.com.br');
        $mail->FromName = mb_convert_encoding("Cassia Souza", "ISO-8859-1", "UTF-8");
        $mail->AddAddress(EMAIL_PRINCIPAL);
        $mail->WordWrap = 50;
        $mail->IsHTML(true);
        $mail->Body = mb_convert_encoding($this->corpoEmail(), "ISO-8859-1", "UTF-8");
        $mail->Subject = mb_convert_encoding($this->dados['assunto'] .  " - " . $this->dados['assuntoUsuario'], "ISO-8859-1", "UTF-8");

        if ($this->filePath) {
            $mail->addAttachment($this->filePath);
        }
        if ($mail->Send()) {
            $this->mensagem = "Sua mensagem foi enviada com sucesso. Entraremos em contato em breve.";
            return true;
        } else {
            $this->mensagem = $mail->ErrorInfo;

            return false;
        }
    }

    function formatarNumero($numero)
    {
        // Usando preg_replace para formatar o número no padrão +55 (11) 95193-6777
        return preg_replace('/(\d{2})(\d{2})(\d{5})(\d{4})/', '+$1 ($2) $3-$4', $numero);
    }
    function corpoEmail()
    {
        $numeroFormatado = $this->formatarNumero($this->dados['numero_usuario']);
        // Verificar e formatar documento
        $tipoPessoa = $this->dados['tipo_pessoa'];
        $documento = $this->dados['documento'];
        $this->filePath = null;
        $informacoes = [];
        $informacoesCNPJConsulta = [];

        // Formatar CNPJ ou CPF conforme o tipo de pessoa
        if (strtolower($tipoPessoa) == 'pessoa jurídica') {
            // Formatar CNPJ (xx.xxx.xxx/xxxx-xx)
            $documento = preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $documento);

            $dadosAll = generateCompanyDocument($this->dados['documento']);;
            $informacoes = [
                ['Nome', $this->dados['nome'] ?? 'N/A'],
                ['Whatsapp', $numeroFormatado ?? 'N/A'],
                ['Tipo de Pessoa', $this->dados['tipo_pessoa'] ?? 'N/A'],
                ['Documento', $documento ?? 'N/A'],
                ['Assunto', $this->dados['assuntoUsuario'] ?? 'N/A'],
                ['Detalhes', $this->dados['detalhes'] ?? 'N/A'],
                ['Urgência', $this->dados['urgencia'] ?? 'N/A'],
            ];
            if ($dadosAll) {
                $this->filePath = $dadosAll['filePath'];
                $dados = $dadosAll['data'];
                $dados2 = $dadosAll['data2'];

                $informacoesCNPJConsulta = [
                    ['Razão social', $dados['razao_social'] ?? 'N/A'],
                    ['Nome fantasia', $dados2['nome_fantasia'] ?? 'N/A'],
                    ['Situação cadastral', $dados['estabelecimento']['situacao_cadastral'] ?? 'N/A'],
                    ['Porte (RFB)', $dados['porte']['descricao'] ?? 'N/A'],
                    ['Data de abertura', isset($dados['estabelecimento']['data_inicio_atividade']) ? date('d/m/Y', strtotime($dados['estabelecimento']['data_inicio_atividade'])) : 'N/A'],
                    ['Natureza jurídica', ($dados['natureza_juridica']['id'] ?? 'N/A') . ' - ' . ($dados['natureza_juridica']['descricao'] ?? 'N/A')],
                    ['Capital social', 'R$ ' . number_format((float)($dados['capital_social'] ?? 0), 2, ',', '.')],
                    ['Última atualização', isset($dados2['statusDate']) ? date('d/m/Y', strtotime($dados2['statusDate'])) : 'N/A'],
                    ['MEI', $dados['simples']['mei'] ?? ''],
                    ['Simples', isset($dados2['company']['simples']['optant']) ? ($dados2['company']['simples']['optant'] ? 'Sim' : 'Não') : 'N/A'],
                    ['E-mail', $dados['estabelecimento']['email'] ?? 'N/A'],
                    ['Telefone', ($dados['estabelecimento']['ddd1'] ?? '') . ' ' . ($dados['estabelecimento']['telefone1'] ?? 'N/A')]
                ];
            }
        } else {
            // Formatar CPF (xxx.xxx.xxx-xx)
            $documento = preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $documento);
            $informacoes = [
                ['Nome', $this->dados['nome'] ?? 'N/A'],
                ['Whatsapp', $numeroFormatado ?? 'N/A'],
                ['Tipo de Pessoa', $this->dados['tipo_pessoa'] ?? 'N/A'],
                ['Assunto', $this->dados['assuntoUsuario'] ?? 'N/A'],
                ['Detalhes', $this->dados['detalhes'] ?? 'N/A'],
                ['Urgência', $this->dados['urgencia'] ?? 'N/A'],
            ];
        }

        // Dados a serem incluídos no e-mail

        // Montando a tabela em HTML
        $tabela = '<table style="width: 100%; border-collapse: collapse;">';
        $tabela .= '<tr style="background-color: #001f56; color: #FFF;">';
        $tabela .= '<th style="padding: 10px; border: 1px solid #ccc;">Campo</th>';
        $tabela .= '<th style="padding: 10px; border: 1px solid #ccc;">Valor</th>';
        $tabela .= '</tr>';

        foreach ($informacoes as $linha) {
            $tabela .= '<tr>';
            $tabela .= '<td style="padding: 10px; border: 1px solid #ccc;">' . htmlspecialchars($linha[0]) . '</td>';
            $tabela .= '<td style="padding: 10px; border: 1px solid #ccc;">' . htmlspecialchars($linha[1]) . '</td>';
            $tabela .= '</tr>';
        }

        // Verifica se houve consulta ao CNPJ e adiciona o texto
        if (!empty($informacoesCNPJConsulta)) {
            $tabela .= '<tr style="background-color: #f0f0f0; color: #000;">';
            $tabela .= '<td colspan="2" style="padding: 10px; border: 1px solid #ccc; text-align: center;"><strong>Consulta do CNPJ</strong></td>';
            $tabela .= '</tr>';

            foreach ($informacoesCNPJConsulta as $linhaCNPJ) {
                $tabela .= '<tr>';
                $tabela .= '<td style="padding: 10px; border: 1px solid #ccc;">' . htmlspecialchars($linhaCNPJ[0]) . '</td>';
                $tabela .= '<td style="padding: 10px; border: 1px solid #ccc;">' . htmlspecialchars($linhaCNPJ[1]) . '</td>';
                $tabela .= '</tr>';
            }
        }

        $tabela .= '</table>';

        return ("<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//PT' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
        <html xmlns='http://www.w3.org/1999/xhtml' style='-webkit-text-size-adjust:none;'>
            <head>
                <meta charset='utf-8'/>
                <meta name='HandheldFriendly' content='true'/>
                <meta name='viewport' content='width=device-width; initial-scale=0.666667; maximum-scale=0.666667; user-scalable=0'/>
                <meta name='viewport' content='width=device-width'/>
                <title>" . $this->dados['assunto'] . "</title>
            </head>
            <body style='padding:25px 0 75px 0; background-color:#DFDFDF; margin:0 auto; width:100%; height:100%; font-family:Helvetica,Arial,sans-serif;'>
                <table border='0' cellspacing='0' align='center' cellpadding='0' style='border-collapse:collapse; margin:50px 0 0 0;' width='100%' height='100%' bgcolor='#DFDFDF'>
                    <tbody>
                        <tr>
                            <td>
                                <table align='center' width='600' bgcolor='#FFF'>
                                    <tr>
                                        <td style='background-color:#001f56; text-align:center;'>
                                            <h1 style='margin:20px 0; text-align:center; color:#FFF;'>" . $this->dados['assunto'] . "</h1>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style='background-color:#FFF; text-align:left; padding:15px;font-size:14px;'>
                                            " . $tabela . "
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </body>
        </html>");
    }
}
