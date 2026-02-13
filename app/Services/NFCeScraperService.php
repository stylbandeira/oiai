<?php
// app/Services/NFCeScraperService.php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Log;

class NFCeScraperService
{
    private $client;
    private $xmlParser;

    public function __construct(NFCeXMLParserService $xmlParser)
    {
        $this->client = new Client([
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ],
            'verify' => false,
        ]);

        $this->xmlParser = $xmlParser;
    }

    /**
     * Resolve URL encurtada para URL real
     */
    private function resolveShortUrl($shortUrl)
    {
        try {
            $response = $this->client->get($shortUrl, [
                'allow_redirects' => false // Não seguir redirecionamentos automaticamente
            ]);

            // Verifica se tem cabeçalho de localização
            if ($response->hasHeader('Location')) {
                $location = $response->getHeader('Location')[0];
                return $location;
            }

            // Se não tiver header Location, tenta encontrar no corpo
            $body = (string) $response->getBody();
            if (preg_match('/href=["\']([^"\']+)["\']/', $body, $matches)) {
                $location = $matches[1];
                return $location;
            }
        } catch (\Exception $e) {
            Log::warning('Erro ao resolver URL encurtada', ['url' => $shortUrl, 'error' => $e->getMessage()]);
        }

        return null;
    }


    private function getRealNFCeUrl($qrData)
    {
        try {
            // Se já for uma URL completa da SEFAZ, usa direto
            if (str_contains($qrData, 'sefaz') && str_contains($qrData, 'nfce')) {
                return $qrData;
            }

            // Se for URL encurtada, resolve
            if (filter_var($qrData, FILTER_VALIDATE_URL)) {
                $resolvedUrl = $this->resolveShortUrl($qrData);
                if ($resolvedUrl && str_contains($resolvedUrl, 'sefaz')) {
                    return $resolvedUrl;
                }
                return $qrData; // Fallback para a URL original
            }

            // Se for parâmetro p, monta URL (Pernambuco como exemplo)
            if (str_contains($qrData, '|')) {
                // Extrair estado da chave (primeiros 2 dígitos)
                $chave = explode('|', $qrData)[0];
                $estado = substr($chave, 0, 2);

                // URLs por estado
                $urlsPorEstado = [
                    '26' => 'https://nfce.sefaz.pe.gov.br/nfce/consulta', // PE
                    '35' => 'https://www.nfce.fazenda.sp.gov.br/consulta', // SP
                    '31' => 'https://nfce.fazenda.mg.gov.br/portalnfce', // MG
                    '53' => 'https://www.fazenda.df.gov.br/nfce', // DF
                    '41' => 'https://www.nfce.fazenda.pr.gov.br/consulta', // PR
                    '43' => 'https://www.sefaz.rs.gov.br/NFCE/NFCE-COM.aspx', // RS
                    '32' => 'https://nfe.es.gov.br/consulta', // ES
                    // Adicione outros estados conforme necessário
                ];

                $baseUrl = $urlsPorEstado[$estado] ?? 'https://nfce.sefaz.pe.gov.br/nfce/consulta';
                return $baseUrl . "?p=" . urlencode($qrData);
            }

            // Se for apenas chave, monta com valores padrão
            if (preg_match('/^\d{44}$/', $qrData)) {
                $estado = substr($qrData, 0, 2);
                $urlsPorEstado = [
                    '26' => 'https://nfce.sefaz.pe.gov.br/nfce/consulta', // PE
                    '35' => 'https://www.nfce.fazenda.sp.gov.br/consulta', // SP
                    '31' => 'https://nfce.fazenda.mg.gov.br/portalnfce', // MG
                    '53' => 'https://www.fazenda.df.gov.br/nfce', // DF
                    '41' => 'https://www.nfce.fazenda.pr.gov.br/consulta', // PR
                    '43' => 'https://www.sefaz.rs.gov.br/NFCE/NFCE-COM.aspx', // RS
                    '32' => 'https://nfe.es.gov.br/consulta', // ES
                    // Adicione outros estados conforme necessário
                ];
                $baseUrl = $urlsPorEstado[$estado] ?? 'https://nfce.sefaz.pe.gov.br/nfce/consulta';
                return $baseUrl . "?p=" . $qrData . "|2|1|1|117A7509F59D4D306ED989C0E5689A01E7A011E9";
            }

            return null;
        } catch (\Exception $e) {
            Log::warning('Erro ao construir URL', ['qr_data' => $qrData, 'error' => $e->getMessage()]);
            // Fallback genérico
            return "https://nfce.sefaz.pe.gov.br/nfce/consulta?p=" . urlencode($qrData);
        }
    }

    public function scrapeFromQRCode($qrData)
    {
        try {
            // Obter URL
            $url = $this->getRealNFCeUrl($qrData);

            if (!$url) {
                return [
                    'status' => 'error',
                    'error' => 'Não foi possível obter URL válida',
                    'qr_data' => $qrData
                ];
            }


            // Fazer requisição
            $response = $this->client->get($url, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept' => 'application/xml,text/xml,text/html,application/xhtml+xml,*/*;q=0.8',
                ],
                'timeout' => 30,
                'verify' => false,
            ]);

            $content = $response->getBody()->getContents();

            // Verificar se é XML (mais preciso)
            $isXML = $this->isXMLContent($content);

            if ($isXML) {
                // É XML - usar parser de XML
                $result = $this->xmlParser->parseXML($content);

                if ($result['status'] === 'success') {
                    return [
                        'status' => 'success',
                        'message' => 'NFCe processada com sucesso (via XML)',
                        'url_consulta' => $url,
                        'tipo' => 'xml',
                        'data' => $result['data'],
                        'produtos_count' => count($result['data']['produtos']),
                        'valor_total' => $result['data']['nota']['valor_total'] ?? 0,
                    ];
                } else {
                    return $result;
                }
            } else {
                // É HTML - usar scraper antigo (fallback)
                return $this->scrapeFromHTML($content, $url);
            }
        } catch (\Exception $e) {
            Log::error('Erro ao scrapear NFCe:', [
                'error' => $e->getMessage(),
                'qr_data' => $qrData,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'qr_data' => $qrData
            ];
        }
    }

    /**
     * Detecta se o conteúdo é XML
     */
    private function isXMLContent($content)
    {
        // Remove espaços no início
        $content = ltrim($content);

        // Verifica marcadores comuns de XML
        $xmlIndicators = [
            '<?xml' => 0,
            '<nfeProc' => 0,
            '<NFe' => 0,
            '<infNFe' => 0,
        ];

        foreach ($xmlIndicators as $indicator => $position) {
            if (stripos($content, $indicator) === 0 || stripos($content, $indicator) !== false) {
                return true;
            }
        }

        // Verifica se tem muitas tags XML
        $xmlTagCount = substr_count($content, '<');
        $closingTagCount = substr_count($content, '>');

        if ($xmlTagCount > 10 && $closingTagCount > 10 && $xmlTagCount === $closingTagCount) {
            return true;
        }

        return false;
    }

    private function scrapeFromHTML($html, $url)
    {
        try {

            $crawler = new Crawler($html);

            // Verificar se a página tem conteúdo válido
            $pageText = $crawler->text();
            if (strlen($pageText) < 100) {
                throw new Exception('Página HTML muito curta ou vazia');
            }

            // Extrair dados usando os métodos corrigidos
            $dados = [
                'emitente' => $this->extrairEmitente($crawler),
                'destinatario' => $this->extrairDestinatario($crawler),
                'nota' => $this->extrairDadosNota($crawler),
                'produtos' => $this->extrairProdutos($crawler),
                'pagamento' => $this->extrairPagamento($crawler),
                'total' => $this->extrairTotal($crawler),
                'informacoes_adicionais' => $this->extrairInformacoesComplementares($crawler),
            ];

            return $dados;
        } catch (\Exception $e) {

            throw $e;
        }
    }

    private function extrairEmitente(Crawler $crawler)
    {
        $emitente = [
            'razao_social' => '',
            'cnpj' => '',
            'endereco' => '',
            'bairro' => '',
            'municipio' => '',
            'uf' => '',
            'cep' => '',
            'telefone' => '',
            'ie' => '',
        ];

        try {
            // Método 1: Procurar por div com classe específica
            $selectors = [
                'div.txtCenter',
                'div.text-center',
                'div.emitente',
                'div:contains("CNPJ")',
                'div:contains("IE:")',
            ];

            foreach ($selectors as $selector) {
                if ($crawler->filter($selector)->count() > 0) {
                    $text = $crawler->filter($selector)->text();
                    $emitente = $this->parseEmitenteFromText($text, $emitente);
                    if (!empty($emitente['razao_social']) && !empty($emitente['cnpj'])) {
                        return $emitente;
                    }
                }
            }

            // Método 2: Procurar em toda a página
            $pageText = $crawler->text();
            $emitente = $this->parseEmitenteFromText($pageText, $emitente);
        } catch (\Exception $e) {
            Log::warning('Erro ao extrair emitente', ['error' => $e->getMessage()]);
        }

        return $emitente;
    }

    private function parseEmitenteFromText($text, $emitente)
    {
        // Extrair CNPJ
        if (preg_match('/CNPJ:\s*([\d.\/\-]+)/i', $text, $matches)) {
            $emitente['cnpj'] = trim($matches[1]);
        }

        // Extrair IE
        if (preg_match('/IE:\s*([\d.\/\-]+)/i', $text, $matches)) {
            $emitente['ie'] = trim($matches[1]);
        }

        // Extrair endereço completo
        $lines = explode("\n", $text);
        foreach ($lines as $i => $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Razão social geralmente é a primeira linha significativa
            if (
                empty($emitente['razao_social']) &&
                !str_contains($line, 'CNPJ') &&
                !str_contains($line, 'IE') &&
                !str_contains($line, 'End:') &&
                !str_contains($line, 'Fone:') &&
                !str_contains($line, 'CEP:') &&
                strlen($line) > 5
            ) {
                $emitente['razao_social'] = $line;
            }

            // Endereço
            if (str_contains($line, 'End:')) {
                $emitente['endereco'] = str_replace('End:', '', $line);
            }

            // Bairro/Município/UF
            if (preg_match('/(.+)\s+-\s+(.+)\/([A-Z]{2})/', $line, $matches)) {
                $emitente['bairro'] = trim($matches[1]);
                $emitente['municipio'] = trim($matches[2]);
                $emitente['uf'] = trim($matches[3]);
            }

            // CEP
            if (preg_match('/CEP:\s*(\d{5}-\d{3})/i', $line, $matches)) {
                $emitente['cep'] = $matches[1];
            }

            // Telefone
            if (preg_match('/Fone:\s*(.+)/i', $line, $matches)) {
                $emitente['telefone'] = trim($matches[1]);
            }
        }

        return $emitente;
    }

    private function extrairDestinatario(Crawler $crawler)
    {
        $destinatario = [
            'nome' => 'CONSUMIDOR FINAL',
            'cpf_cnpj' => '',
            'endereco' => '',
        ];

        try {
            $pageText = $crawler->text();

            // Procurar CPF (###.###.###-##)
            if (preg_match('/(\d{3}\.\d{3}\.\d{3}-\d{2})/', $pageText, $matches)) {
                $destinatario['cpf_cnpj'] = $matches[1];
            }
            // Procurar CNPJ (##.###.###/####-##)
            elseif (preg_match('/(\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2})/', $pageText, $matches)) {
                $destinatario['cpf_cnpj'] = $matches[1];
            }
        } catch (\Exception $e) {
            Log::warning('Erro ao extrair destinatário', ['error' => $e->getMessage()]);
        }

        return $destinatario;
    }

    private function extrairDadosNota(Crawler $crawler)
    {
        $nota = [
            'numero' => '',
            'serie' => '',
            'chave_acesso' => '',
            'data_emissao' => '',
            'data_entrada_saida' => '',
            'modelo' => 'NFC-e',
            'natureza_operacao' => '',
            'protocolo' => '',
            'valor_total' => 0,
            'valor_desconto' => 0,
            'valor_frete' => 0,
            'valor_seguro' => 0,
            'valor_outras_despesas' => 0,
        ];

        try {
            $pageText = $crawler->text();

            // Extrair número da nota
            if (preg_match('/Nº[:\s]*([\d\-\.\/]+)/i', $pageText, $matches)) {
                $nota['numero'] = trim($matches[1]);
            } elseif (preg_match('/Número[:\s]*([\d\-\.\/]+)/i', $pageText, $matches)) {
                $nota['numero'] = trim($matches[1]);
            }

            // Extrair série
            if (preg_match('/Série[:\s]*([\d\-\.\/]+)/i', $pageText, $matches)) {
                $nota['serie'] = trim($matches[1]);
            }

            // Extrair data de emissão
            if (preg_match('/Data de Emissão[:\s]*([\d\/:\s]+)/i', $pageText, $matches)) {
                $nota['data_emissao'] = trim($matches[1]);
            }

            // Extrair valor total
            if (preg_match('/Valor Total[:\s]*R?\$\s*([\d,\.]+)/i', $pageText, $matches)) {
                $nota['valor_total'] = $this->parseValorMonetario($matches[1]);
            }

            // Tentar extrair chave de acesso
            if (preg_match('/\d{44}/', $pageText, $matches)) {
                $nota['chave_acesso'] = $matches[0];
            }
        } catch (\Exception $e) {
            Log::warning('Erro ao extrair dados da nota', ['error' => $e->getMessage()]);
        }

        return $nota;
    }

    private function extrairProdutos(Crawler $crawler)
    {
        $produtos = [];

        try {
            // Tenta encontrar a tabela de produtos
            $table = $this->findProductsTable($crawler);

            if ($table->count() > 0) {
                $this->extractProductsFromTable($table, $produtos);
            } else {
                // Se não encontrou tabela específica, tenta extrair de qualquer tabela
                $this->extractProductsFromAllTables($crawler, $produtos);
            }
        } catch (\Exception $e) {
            Log::warning('Erro ao extrair produtos', ['error' => $e->getMessage()]);
        }

        return $produtos;
    }

    private function findProductsTable(Crawler $crawler)
    {
        // Tenta seletores específicos para tabela de produtos
        $selectors = [
            'table[class*="prod"]',
            'table[id*="prod"]',
            'table:contains("Código")',
            'table:contains("Descrição")',
            'table:contains("Qtde")',
            'table:contains("UN")',
            'table:contains("Vl Unit")',
            'table:contains("Vl Total")',
        ];

        foreach ($selectors as $selector) {
            $table = $crawler->filter($selector)->first();
            if ($table->count() > 0) {
                return $table;
            }
        }

        return new Crawler();
    }

    private function extractProductsFromTable(Crawler $table, array &$produtos)
    {
        $table->filter('tr')->each(function (Crawler $row) use (&$produtos) {
            $cells = $row->filter('td');

            // Precisa ter pelo menos 3 células para ser um produto
            if ($cells->count() >= 3) {
                $rowText = $row->text();

                // Ignora linhas que são cabeçalho ou totais
                $ignoreKeywords = ['código', 'descrição', 'quantidade', 'unidade', 'total', 'subtotal'];
                $isHeader = false;

                foreach ($ignoreKeywords as $keyword) {
                    if (stripos($rowText, $keyword) !== false) {
                        $isHeader = true;
                        break;
                    }
                }

                if (!$isHeader) {
                    $produto = $this->extractProductFromRow($row);
                    if ($produto && (!empty($produto['descricao']) || !empty($produto['codigo']))) {
                        $produtos[] = $produto;
                    }
                }
            }
        });
    }

    private function extractProductFromRow(Crawler $row)
    {
        $cells = $row->filter('td');
        $cellCount = $cells->count();

        $produto = [
            'codigo' => '',
            'descricao' => '',
            'quantidade' => 0,
            'unidade' => '',
            'valor_unitario' => 0,
            'valor_total' => 0,
        ];

        // Padrão mais comum: código, descrição, quantidade, unidade, vl unit, vl total
        if ($cellCount >= 6) {
            $produto['codigo'] = $this->limparTexto($cells->eq(0)->text());
            $produto['descricao'] = $this->limparTexto($cells->eq(1)->text());
            $produto['quantidade'] = $this->parseFloat($cells->eq(2)->text());
            $produto['unidade'] = $this->limparTexto($cells->eq(3)->text());
            $produto['valor_unitario'] = $this->parseValorMonetario($cells->eq(4)->text());
            $produto['valor_total'] = $this->parseValorMonetario($cells->eq(5)->text());
        }
        // Padrão alternativo: descrição, quantidade, unidade, valor
        elseif ($cellCount >= 4) {
            $produto['descricao'] = $this->limparTexto($cells->eq(0)->text());
            $produto['quantidade'] = $this->parseFloat($cells->eq(1)->text());
            $produto['unidade'] = $this->limparTexto($cells->eq(2)->text());

            // Última célula pode ser valor total
            $lastCellText = $cells->eq($cellCount - 1)->text();
            if (str_contains($lastCellText, 'R$')) {
                $produto['valor_total'] = $this->parseValorMonetario($lastCellText);
                if ($produto['quantidade'] > 0) {
                    $produto['valor_unitario'] = $produto['valor_total'] / $produto['quantidade'];
                }
            }
        }

        return $produto;
    }

    private function extractProductsFromAllTables(Crawler $crawler, array &$produtos)
    {
        $crawler->filter('table')->each(function (Crawler $table) use (&$produtos) {
            // Verifica se esta tabela pode conter produtos
            $tableText = $table->text();
            $hasProductIndicators =
                stripos($tableText, 'un') !== false ||
                stripos($tableText, 'kg') !== false ||
                stripos($tableText, 'R$') !== false;

            if ($hasProductIndicators) {
                $this->extractProductsFromTable($table, $produtos);
            }
        });
    }

    private function extrairPagamento(Crawler $crawler)
    {
        $pagamentos = [];

        try {
            // Procurar em toda a página por padrões de pagamento
            $pageText = $crawler->text();

            // Procura por padrões como: "Dinheiro R$ 50,00" ou "Cartão de Crédito R$ 100,00"
            $patterns = [
                '/(Dinheiro|Cartão\s+(?:de\s+)?(?:Crédito|Débito)|PIX|Vale|Cheque|Outros)[\s:]+R?\$\s*([\d.,]+)/i',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match_all($pattern, $pageText, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        if (count($match) >= 3) {
                            $forma = $this->limparTexto($match[1]);
                            $valor = $this->parseValorMonetario($match[2]);

                            if ($valor > 0.01 && !empty($forma)) {
                                $pagamentos[] = [
                                    'forma' => $forma,
                                    'valor' => $valor,
                                ];
                            }
                        }
                    }
                }
            }

            // Se não encontrou, procurar em tabelas
            if (empty($pagamentos)) {
                $crawler->filter('table')->each(function (Crawler $table) use (&$pagamentos) {
                    $rows = $table->filter('tr');
                    $rows->each(function (Crawler $row) use (&$pagamentos) {
                        $cells = $row->filter('td');
                        if ($cells->count() === 2) {
                            $text1 = $this->limparTexto($cells->eq(0)->text());
                            $text2 = $this->limparTexto($cells->eq(1)->text());

                            // Verifica se parece ser um pagamento
                            $isPayment = (
                                (stripos($text2, 'R$') !== false || is_numeric(str_replace(',', '.', $text2))) &&
                                !stripos($text1, 'total') &&
                                !stripos($text1, 'troco') &&
                                !stripos($text1, 'subtotal')
                            );

                            if ($isPayment) {
                                $pagamentos[] = [
                                    'forma' => $text1,
                                    'valor' => $this->parseValorMonetario($text2),
                                ];
                            }
                        }
                    });
                });
            }
        } catch (\Exception $e) {
            Log::warning('Erro ao extrair pagamento', ['error' => $e->getMessage()]);
        }

        return $pagamentos;
    }

    private function extrairTotal(Crawler $crawler)
    {
        $total = [
            'valor_produtos' => 0,
            'valor_desconto' => 0,
            'valor_frete' => 0,
            'valor_total' => 0,
        ];

        try {
            $pageText = $crawler->text();

            // Extrair valor total
            if (preg_match('/Valor Total[:\s]*R?\$\s*([\d,\.]+)/i', $pageText, $matches)) {
                $total['valor_total'] = $this->parseValorMonetario($matches[1]);
            }

            // Extrair desconto
            if (preg_match('/Desconto[:\s]*R?\$\s*([\d,\.]+)/i', $pageText, $matches)) {
                $total['valor_desconto'] = $this->parseValorMonetario($matches[1]);
            }
        } catch (\Exception $e) {
            Log::warning('Erro ao extrair total', ['error' => $e->getMessage()]);
        }

        return $total;
    }

    private function extrairInformacoesComplementares(Crawler $crawler)
    {
        $info = [
            'observacoes' => '',
            'informacao_fisco' => '',
            'informacao_contribuinte' => '',
        ];

        try {
            $pageText = $crawler->text();

            // Procura por padrões de informações complementares
            $patterns = [
                'observacoes' => '/Observações[:\s]*\n*(.+?)(?=\n{2}|$)/is',
                'fisco' => '/Informação\s+(?:ao\s+)?Fisco[:\s]*\n*(.+?)(?=\n{2}|$)/i',
                'contribuinte' => '/Informação\s+(?:ao\s+)?Contribuinte[:\s]*\n*(.+?)(?=\n{2}|$)/i',
            ];

            foreach ($patterns as $key => $pattern) {
                if (preg_match($pattern, $pageText, $matches)) {
                    $info[$key === 'observacoes' ? 'observacoes' : ($key === 'fisco' ? 'informacao_fisco' : 'informacao_contribuinte')]
                        = $this->limparTexto($matches[1]);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Erro ao extrair info complementares', ['error' => $e->getMessage()]);
        }

        return $info;
    }

    // ============ MÉTODOS AUXILIARES ============

    private function limparTexto($text)
    {
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    private function parseValorMonetario($text)
    {
        $text = $this->limparTexto($text);

        // Remove tudo que não é número, ponto, vírgula
        $text = preg_replace('/[^\d,\.]/', '', $text);

        // Converte formato brasileiro para float
        if (strpos($text, ',') !== false && strpos($text, '.') !== false) {
            // Tem ambos: 1.234,56 -> remove ponto de milhar, converte vírgula decimal
            $text = str_replace('.', '', $text);
            $text = str_replace(',', '.', $text);
        } elseif (strpos($text, ',') !== false) {
            // Só tem vírgula: 1234,56
            if (substr_count($text, ',') === 1) {
                $text = str_replace(',', '.', $text);
            }
        }

        return floatval($text);
    }

    private function parseFloat($text)
    {
        $text = $this->limparTexto($text);
        $text = str_replace(',', '.', $text);
        return floatval($text);
    }
}
