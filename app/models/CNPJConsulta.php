<?php

require_once PATH  . 'autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\TemplateProcessor;

class CNPJConsulta
{
    private $cnpj;
    private $apiUrls;

    public function __construct($cnpj, $apiUrls = [])
    {
        $this->cnpj = $cnpj;
        $this->apiUrls = array_merge([
            'publica' => "https://publica.cnpj.ws/cnpj/{$cnpj}",
            'open' => "https://open.cnpja.com/office/{$cnpj}"
        ], $apiUrls);
    }

    /**
     * Obtém os dados da empresa usando a API da CNPJA.
     *
     * @return array
     */
    public function getCompanyData()
    {
        return $this->fetchData($this->apiUrls['open']);
    }

    /**
     * Obtém os dados da empresa usando a API Pública do CNPJ.
     *
     * @return array
     */
    public function getPublicaCompanyData()
    {
        return $this->fetchData($this->apiUrls['publica']);
    }

    /**
     * Faz a requisição para a URL fornecida e retorna os dados em formato de array.
     *
     * @param string $url
     * @return array
     */
    private function fetchData($url)
    {
        $response = @file_get_contents($url);
        if ($response === FALSE) {
            // Lida com o erro (pode lançar uma exceção ou retornar um erro)
            return ['error' => 'Erro ao buscar dados da API.'];
        }
        return json_decode($response, true);
    }
}

/**
 * Formata o CNPJ para o formato padrão.
 *
 * @param string $cnpj
 * @return string
 */
function formatCNPJ($cnpj)
{
    // Remove caracteres não numéricos
    $cnpj = preg_replace('/\D/', '', $cnpj);

    // Verifica se o CNPJ tem 14 dígitos
    if (strlen($cnpj) !== 14) {
        return $cnpj; // Retorna o original se não tiver 14 dígitos
    }

    // Formata o CNPJ
    return substr($cnpj, 0, 2) . '.' .
        substr($cnpj, 2, 3) . '.' .
        substr($cnpj, 5, 3) . '/' .
        substr($cnpj, 8, 4) . '-' .
        substr($cnpj, 12, 2);
}



// Função para gerar o documento da empresa
function generateCompanyDocument($cnpj)
{
    $cnpjConsulta = new CNPJConsulta($cnpj);
    $company1Data = $cnpjConsulta->getPublicaCompanyData();
    $company2Data = $cnpjConsulta->getCompanyData();

    // Caminho para o arquivo template.docx
    $templateFile =  PATH_APP . 'docs/template.docx';

    // Carregar o template
    $templateProcessor = new TemplateProcessor($templateFile);

    // Substitui os placeholders com os dados desejados
    $templateProcessor->setValue('NOME_EMPRESARIAL', $company2Data['company']['name'] ?? 'N/A');
    $templateProcessor->setValue('NOME_FANTASIA', $company1Data['razao_social'] ?? 'N/A');
    $templateProcessor->setValue('CNPJ', formatCNPJ($company2Data['taxId'] ?? ''));
    $templateProcessor->setValue('DATA_ABERTURA', $company2Data['founded'] ?? 'N/A');
    $templateProcessor->setValue('DATA_CADASTRAL', $company2Data['statusDate'] ?? 'N/A');
    $templateProcessor->setValue('DESCRICAO_NATUREZA', $company2Data['company']['nature']['text'] ?? 'N/A');
    $templateProcessor->setValue('SITUACAO_CADASTRAL', $company2Data['status']['text'] ?? 'N/A');
    $templateProcessor->setValue('DETALHES_CADASTRAL', $company2Data['company']['size']['text'] ?? 'N/A');
    $templateProcessor->setValue('LOGRADOURO', $company2Data['address']['street'] ?? 'N/A');
    $templateProcessor->setValue('NUM', $company2Data['address']['number'] ?? 'N/A');
    $templateProcessor->setValue('COMPLEMENTO', $company2Data['address']['details'] ?? '');
    $templateProcessor->setValue('CEP', $company2Data['address']['zip'] ?? 'N/A');
    $templateProcessor->setValue('BAIRRO', $company2Data['address']['district'] ?? 'N/A');
    $templateProcessor->setValue('MUNICIPIO', $company2Data['address']['city'] ?? 'N/A');
    $templateProcessor->setValue('UF', $company2Data['address']['state'] ?? 'N/A');
    $templateProcessor->setValue('ATIVIDADE_PRINCIPAL', $company2Data['mainActivity']['text'] ?? 'N/A');

    // Mensagens para o template
    $simplesMessage = $company2Data['company']['simples']['optant']
        ? "A empresa é optante pelo Simples Nacional desde " . formatDateBR($company2Data['company']['simples']['since']) . "."
        : "A empresa não é optante pelo Simples Nacional.";

    $simeiMessage = $company2Data['company']['simei']['optant']
        ? "A empresa é optante pelo Simei desde " . formatDateBR($company2Data['company']['simei']['since']) . "."
        : "A empresa não é optante pelo Simei.";

    // Substituir os placeholders no template
    $templateProcessor->setValue('SIMPLES_STATUS', $simplesMessage);
    $templateProcessor->setValue('SIMEI_STATUS', $simeiMessage);
    $templateProcessor->setValue('PORTE', $company2Data['company']['size']['text'] ?? 'N/A');

    $inscricoes = $company1Data['estabelecimento']['inscricoes_estaduais'] ?? [];
    $listaInscricoes = [];

    foreach ($inscricoes as $inscricao) {
        $numeroInscricao = $inscricao['inscricao_estadual'];
        $status = $inscricao['ativo'] ? 'Ativo' : 'Inativo';
        $estado = $inscricao['estado']['nome'];
        $listaInscricoes[] = "Inscrição: $numeroInscricao ($status) - Estado: $estado";
    }

    $templateProcessor->setValue('INSCRICAO_ESTADUAL', implode("\n", $listaInscricoes));

    // Verificar sócios
    if (!empty($company2Data['company']['members'])) {
        $socios = array_map(function ($member) {
            $date = DateTime::createFromFormat('Y-m-d', $member['since']);
            $formattedDate = $date ? $date->format('d/m/Y') : $member['since'];
            return $member['person']['name'] . " - " . $member['role']['text'] . " (Desde: " . $formattedDate . ")";
        }, $company2Data['company']['members']);

        $sociosString = implode("\n", $socios);
        $templateProcessor->setValue('SOCIOS', $sociosString);
    } else {
        $templateProcessor->setValue('SOCIOS', 'Não há sócios registrados.');
    }

    // Suponha que você tenha um array de atividades secundárias
    $atividadesSecundarias = array_map(function ($activity) {
        return $activity['text'];
    }, $company2Data['sideActivities'] ?? []);

    $atividadesSecundariasString = implode("\n", $atividadesSecundarias);
    $templateProcessor->setValue('ATIVIDADE_SECUNDARIA', $atividadesSecundariasString);

    // Salvar o arquivo gerado
    $generatedFile = PATH_APP . "docs/consultas_cnpj/$cnpj.docx";
    $templateProcessor->saveAs($generatedFile);


    return [
        'filePath' => $generatedFile,
        'data' => $company1Data, // Retorna os dados também
        'data2' => $company2Data // Retorna os dados também
    ];


}

// Função para formatar a data no formato brasileiro
function formatDateBR($date)
{
    return date("d/m/Y", strtotime($date));
}
