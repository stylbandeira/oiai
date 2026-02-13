<?php

namespace App\Services;

use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Log;

class NFCeHtmlParserService
{
    private $crawler;

    public function parse(string $html): array
    {
        $this->crawler = new Crawler($html);

        return [
            'dados_nota' => $this->getDadosNota(),
            'emitente' => $this->getEmitente(),
            'destinatario' => $this->getDestinatario(),
            'produtos' => $this->getProdutos(),
            'totais' => $this->getTotais(),
            'pagamento' => $this->getPagamento(),
            'informacoes' => $this->getInformacoesAdicionais()
        ];
    }

    /**
     * Extrai dados básicos da nota da primeira aba
     */
    private function getDadosNota(): array
    {
        $dados = [
            'chave_acesso' => '',
            'modelo' => '',
            'serie' => '',
            'numero' => '',
            'data_emissao' => '',
            'valor_total' => 0,
            'protocolo' => '',
            'status' => ''
        ];

        try {
            // Pega a chave de acesso do input hidden
            $chaveInput = $this->crawler->filter('#ChaveAcessoDfe')->first();
            if ($chaveInput->count() > 0) {
                $dados['chave_acesso'] = $chaveInput->attr('value') ?? '';
            }

            // Pega os dados da tabela principal
            $this->crawler->filter('#nfe table.box tr')->each(function ($row) use (&$dados) {
                $row->filter('td')->each(function ($cell) use (&$dados) {
                    $label = $cell->filter('label')->count() ? trim($cell->filter('label')->text()) : '';
                    $value = $cell->filter('span')->count() ? trim($cell->filter('span')->text()) : '';

                    if ($label === 'Modelo') $dados['modelo'] = $value;
                    if ($label === 'Série') $dados['serie'] = $value;
                    if ($label === 'Número') $dados['numero'] = $value;
                    if ($label === 'Data de Emissão') $dados['data_emissao'] = $value;
                    if ($label === 'Valor Total da Nota Fiscal  ') $dados['valor_total'] = $this->parseFloat($value);
                });
            });

            // Pega status e protocolo
            $this->crawler->filter('#nfe fieldset:contains("Situação Atual")')->each(function ($fieldset) use (&$dados) {
                $legend = $fieldset->filter('legend')->text();
                if (preg_match('/Situação Atual:\s*(.+?)(?:\s*\(|$)/', $legend, $matches)) {
                    $dados['status'] = trim($matches[1]);
                }

                $protocolo = $fieldset->filter('table.box tr')->eq(1)->filter('td')->eq(1)->filter('span');
                if ($protocolo->count()) {
                    $dados['protocolo'] = trim($protocolo->text());
                }
            });
        } catch (\Exception $e) {
            Log::warning('Erro ao extrair dados da nota', ['error' => $e->getMessage()]);
        }

        return $dados;
    }

    /**
     * Extrai dados do emitente
     */
    private function getEmitente(): array
    {
        $emitente = [
            'cnpj' => '',
            'razao_social' => '',
            'ie' => '',
            'uf' => '',
            'endereco' => '',
            'bairro' => '',
            'municipio' => '',
            'cep' => ''
        ];

        try {
            // Pega da primeira aba
            $this->crawler->filter('#nfe fieldset:contains("Emitente")')->each(function ($fieldset) use (&$emitente) {
                $fieldset->filter('table.box tr td')->each(function ($cell) use (&$emitente) {
                    $label = $cell->filter('label')->count() ? trim($cell->filter('label')->text()) : '';
                    $value = $cell->filter('span')->count() ? trim($cell->filter('span')->text()) : '';

                    if ($label === 'CNPJ') $emitente['cnpj'] = preg_replace('/[^0-9]/', '', $value);
                    if ($label === 'Nome / Razão Social') $emitente['razao_social'] = $value;
                    if ($label === 'Inscrição Estadual') $emitente['ie'] = $value;
                    if ($label === 'UF') $emitente['uf'] = $value;
                });
            });

            // Complementa com dados da aba específica
            $this->crawler->filter('#emitente table.box tr td')->each(function ($cell) use (&$emitente) {
                $label = $cell->filter('label')->count() ? trim($cell->filter('label')->text()) : '';
                $value = $cell->filter('span')->count() ? trim($cell->filter('span')->text()) : '';

                if ($label === 'Endereço') $emitente['endereco'] = $value;
                if ($label === 'Bairro / Distrito') $emitente['bairro'] = $value;
                if ($label === 'Município') {
                    if (preg_match('/\d+\s*-\s*(.+)/', $value, $matches)) {
                        $emitente['municipio'] = $matches[1];
                    } else {
                        $emitente['municipio'] = $value;
                    }
                }
                if ($label === 'CEP') $emitente['cep'] = $value;
            });
        } catch (\Exception $e) {
            Log::warning('Erro ao extrair emitente', ['error' => $e->getMessage()]);
        }

        return $emitente;
    }

    /**
     * Extrai dados do destinatário
     */
    private function getDestinatario(): array
    {
        $destinatario = [
            'cpf_cnpj' => '',
            'nome' => '',
            'consumidor_final' => ''
        ];

        try {
            $this->crawler->filter('#nfe fieldset:contains("Destinatário")')->each(function ($fieldset) use (&$destinatario) {
                $fieldset->filter('table.box tr td')->each(function ($cell) use (&$destinatario) {
                    $label = $cell->filter('label')->count() ? trim($cell->filter('label')->text()) : '';
                    $value = $cell->filter('span')->count() ? trim($cell->filter('span')->text()) : '';

                    if ($label === 'CPF' || $label === 'CNPJ') {
                        $destinatario['cpf_cnpj'] = preg_replace('/[^0-9]/', '', $value);
                    }
                    if ($label === 'Nome / Razão Social') $destinatario['nome'] = $value;
                    if ($label === 'Consumidor final') $destinatario['consumidor_final'] = $value;
                });
            });
        } catch (\Exception $e) {
            Log::warning('Erro ao extrair destinatário', ['error' => $e->getMessage()]);
        }

        return $destinatario;
    }

    private function getProdutos(): array
    {
        $produtos = [];

        try {
            $this->crawler->filter('#produtosServicos .toggle')->each(function ($row, $index) use (&$produtos) {
                $produto = [
                    'codigo' => '',
                    'descricao' => '',
                    'ncm' => '',
                    'cest' => '',
                    'cfop' => '',
                    'ean' => '',
                    'ean_tributavel' => '',
                    'quantidade' => 0,
                    'unidade' => '',
                    'valor_unitario' => 0,
                    'valor_total' => 0
                ];

                // Dados básicos da linha principal
                $cells = $row->filter('td');
                if ($cells->count() >= 5) {
                    $produto['descricao'] = trim($cells->eq(1)->filter('span')->text());
                    $produto['quantidade'] = $this->parseFloat($cells->eq(2)->filter('span')->text());
                    $produto['unidade'] = trim($cells->eq(3)->filter('span')->text());
                    $produto['valor_total'] = $this->parseFloat($cells->eq(4)->filter('span')->text());
                }

                // Pega o bloco de detalhes
                $detalhes = $this->crawler->filter('#produtosServicos .toggable')->eq($index);

                if ($detalhes->count()) {
                    // Pega todo o texto do bloco de detalhes
                    $texto = $detalhes->text();

                    // Extrai código do produto
                    if (preg_match('/Código do Produto\s*(\d+)/', $texto, $matches)) {
                        $produto['codigo'] = $matches[1];
                    }

                    // Extrai NCM
                    if (preg_match('/Código NCM\s*(\d{8})/', $texto, $matches)) {
                        $produto['ncm'] = $matches[1];
                    }

                    // Extrai CEST (ignora 0000000)
                    if (preg_match('/Código CEST\s*(\d{7})/', $texto, $matches)) {
                        if ($matches[1] !== '0000000') {
                            $produto['cest'] = $matches[1];
                        }
                    }

                    // Extrai CFOP
                    if (preg_match('/CFOP\s*(\d{4})/', $texto, $matches)) {
                        $produto['cfop'] = $matches[1];
                    }

                    // Extrai EAN Comercial
                    if (preg_match('/Código EAN Comercial\s*(\d+|\w+)/', $texto, $matches)) {
                        if ($matches[1] !== 'SEM GTIN') {
                            $produto['ean'] = $matches[1];
                        }
                    }

                    // Extrai EAN Tributável
                    if (preg_match('/Código EAN Tributável\s*(\d+|\w+)/', $texto, $matches)) {
                        if ($matches[1] !== 'SEM GTIN') {
                            $produto['ean_tributavel'] = $matches[1];
                        }
                    }

                    // Extrai valor unitário
                    if (preg_match('/Valor unitário de comercialização\s*([\d\.,]+)/', $texto, $matches)) {
                        $produto['valor_unitario'] = $this->parseFloat($matches[1]);
                    }
                }

                if (!empty($produto['descricao'])) {
                    $produtos[] = $produto;
                }
            });
        } catch (\Exception $e) {
            Log::warning('Erro ao extrair produtos', ['error' => $e->getMessage()]);
        }

        return $produtos;
    }

    /**
     * Extrai totais da nota
     */
    private function getTotais(): array
    {
        $totais = [
            'base_calculo_icms' => 0,
            'valor_icms' => 0,
            'valor_produtos' => 0,
            'valor_desconto' => 0,
            'valor_frete' => 0,
            'valor_total' => 0,
            'valor_tributos' => 0
        ];

        try {
            $this->crawler->filter('#totais table.box tr td')->each(function ($cell) use (&$totais) {
                $label = $cell->filter('label')->count() ? trim($cell->filter('label')->text()) : '';
                $value = $cell->filter('span')->count() ? trim($cell->filter('span')->text()) : '';

                if ($label === 'Base de Cálculo ICMS') $totais['base_calculo_icms'] = $this->parseFloat($value);
                if ($label === 'Valor do ICMS') $totais['valor_icms'] = $this->parseFloat($value);
                if ($label === 'Valor Total dos Produtos') $totais['valor_produtos'] = $this->parseFloat($value);
                if ($label === 'Valor Total dos Descontos') $totais['valor_desconto'] = $this->parseFloat($value);
                if ($label === 'Valor do Frete') $totais['valor_frete'] = $this->parseFloat($value);
                if ($label === 'Valor Total da NFe') $totais['valor_total'] = $this->parseFloat($value);
                if ($label === 'Valor Aproximado dos Tributos') $totais['valor_tributos'] = $this->parseFloat($value);
            });
        } catch (\Exception $e) {
            Log::warning('Erro ao extrair totais', ['error' => $e->getMessage()]);
        }

        return $totais;
    }

    /**
     * Extrai forma de pagamento
     */
    private function getPagamento(): array
    {
        $pagamento = [
            'forma' => '',
            'valor' => 0,
            'bandeira' => '',
            'cnpj_credenciadora' => ''
        ];

        try {
            $this->crawler->filter('#cobranca .toggle tr td span')->each(function ($span, $index) use (&$pagamento) {
                $texto = trim($span->text());

                // Pega a forma de pagamento (segunda célula)
                if ($index === 1) $pagamento['forma'] = $texto;
                // Pega o valor (quarta célula)
                if ($index === 3) $pagamento['valor'] = $this->parseFloat($texto);
            });

            // Detalhes do pagamento
            $this->crawler->filter('#cobranca .toggable table.box tr td')->each(function ($cell) use (&$pagamento) {
                $label = $cell->filter('label')->count() ? trim($cell->filter('label')->text()) : '';
                $value = $cell->filter('span')->count() ? trim($cell->filter('span')->text()) : '';

                if ($label === 'Bandeira da operadora') $pagamento['bandeira'] = $value;
                if ($label === 'CNPJ da Credenciadora') $pagamento['cnpj_credenciadora'] = preg_replace('/[^0-9]/', '', $value);
            });
        } catch (\Exception $e) {
            Log::warning('Erro ao extrair pagamento', ['error' => $e->getMessage()]);
        }

        return $pagamento;
    }

    /**
     * Extrai informações adicionais
     */
    private function getInformacoesAdicionais(): array
    {
        $info = [
            'informacoes_complementares' => '',
            'qr_code' => '',
            'url_consulta' => ''
        ];

        try {
            $this->crawler->filter('#informacoes_adicionais table.box tr td')->each(function ($cell) use (&$info) {
                $label = $cell->filter('label')->count() ? trim($cell->filter('label')->text()) : '';
                $value = $cell->filter('span')->count() ? trim($cell->filter('span')->text()) : '';

                if ($label === 'Descrição') {
                    $info['informacoes_complementares'] = $value;
                }
                if ($label === 'QR-Code') {
                    $info['qr_code'] = $value;
                }
                if ($label === 'URL NFC-e') {
                    $info['url_consulta'] = $value;
                }
            });
        } catch (\Exception $e) {
            Log::warning('Erro ao extrair informações adicionais', ['error' => $e->getMessage()]);
        }

        return $info;
    }

    /**
     * Converte string monetária para float
     */
    private function parseFloat(string $value): float
    {
        $value = trim($value);
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
        return floatval($value);
    }
}
