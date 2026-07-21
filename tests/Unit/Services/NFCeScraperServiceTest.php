<?php

namespace Tests\Unit\Services;

use App\Services\NFCe\SaoPauloNFCeProvider;
use App\Services\NFCeScraperService;
use Tests\TestCase;

class NFCeScraperServiceTest extends TestCase
{
    public function test_returns_error_when_no_state_provider_supports_input(): void
    {
        $service = new NFCeScraperService([]);

        $result = $service->scrapeFromQRCode('99999999999999999999999999999999999999999999');

        $this->assertSame('error', $result['status']);
        $this->assertSame('Não existe provider cadastrado para a UF informada.', $result['error']);
    }

    public function test_sao_paulo_provider_owns_detection_and_url_creation(): void
    {
        $accessKey = '35260747508411094703651070005749261615098569';
        $provider = new SaoPauloNFCeProvider();

        $this->assertTrue($provider->supports($accessKey));
        $this->assertFalse($provider->supports('26260747508411094703651070005749261615098569'));
        $this->assertSame(
            'https://www.nfce.fazenda.sp.gov.br/NFCeConsultaPublica/Paginas/ConsultaQRCode.aspx?p='.$accessKey.'%7C3%7C1',
            $provider->buildUrl($accessKey),
        );
    }

    public function test_parses_sao_paulo_public_consultation_html(): void
    {
        $html = <<<'HTML'
        <div class="txtCenter">
          <div class="txtTopo">CIA BRASILEIRA DE DISTRIBUICAO</div>
          <div class="text">CNPJ: 47.508.411/0947-03</div>
          <div class="text">RUA PAMPLONA, 000816, BELA VISTA, SAO PAULO, SP</div>
        </div>
        <table id="tabResult"><tr><td>
          <span class="txtTit">PAO FOR PANCO 500G</span>
          <span class="RCod">(Código: 1161717)</span>
          <span class="Rqtd"><strong>Qtde.:</strong>1</span>
          <span class="RUN"><strong>UN:</strong>UN</span>
          <span class="RvlUnit"><strong>Vl. Unit.:</strong>11,49</span>
        </td><td><span class="valor">11,49</span></td></tr></table>
        <div id="totalNota">
          <div id="linhaTotal"><label>Valor total R$:</label><span class="totalNumb">11,49</span></div>
          <div id="linhaTotal"><label>Valor a pagar R$:</label><span class="totalNumb">11,49</span></div>
          <div id="linhaTotal"><label class="tx">Cartão de Crédito</label><span class="totalNumb">11,49</span></div>
        </div>
        <div id="infos">Número: 574926 Série: 107 Emissão: 20/07/2026 09:34:30
          Protocolo de Autorização: 135264899771704 20/07/2026 09:34:33
          <span class="chave">3526 0747 5084 1109 4703 6510 7000 5749 2616 1509 8569</span>
        </div>
        HTML;

        $provider = new SaoPauloNFCeProvider();
        $result = $provider->parseHtml($html, 'https://www.nfce.fazenda.sp.gov.br/');

        $this->assertSame('success', $result['status']);
        $this->assertSame('35260747508411094703651070005749261615098569', $result['data']['chave_acesso']);
        $this->assertSame('CIA BRASILEIRA DE DISTRIBUICAO', $result['data']['emitente']['razao_social']);
        $this->assertSame('PAO FOR PANCO 500G', $result['data']['produtos'][0]['descricao']);
        $this->assertSame(11.49, $result['data']['produtos'][0]['valor_unitario']);
        $this->assertSame('20/07/2026 09:34:33', $result['data']['protocolo']['data_recebimento']);
    }
}
