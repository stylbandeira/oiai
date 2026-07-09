<?php

namespace Tests\Feature\Integration\InvoiceController;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceProcessInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private const VALID_ACCESS_KEY = '26260712345678000195650010000012341000012345';

    /**
     * @dataProvider invalidPayloadsProvider
     */
    public function test_validation(array $payload, string $field): void
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)
            ->postJson('/api/invoice/process', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrorFor($field);
    }

    public function test_process_invoice_only_stores_access_key_for_later_processing(): void
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)
            ->postJson('/api/invoice/process', [
                'invoice_code' => self::VALID_ACCESS_KEY,
            ]);

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'message' => 'NFCe cadastrada para processamento',
            ])
            ->assertJsonPath('invoice.access_key', self::VALID_ACCESS_KEY)
            ->assertJsonPath('invoice.user_id', $client->id)
            ->assertJsonPath('invoice.pending', true)
            ->assertJsonPath('invoice.invoice_data', null);

        $this->assertDatabaseHas('invoice', [
            'user_id' => $client->id,
            'access_key' => self::VALID_ACCESS_KEY,
            'invoice_data' => null,
            'pending' => true,
        ]);
    }

    public function test_invoice_access_key_is_extracted_from_qr_code_data(): void
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)
            ->postJson('/api/invoice/process', [
                'qr_code_data' => 'https://nfce.sefaz.pe.gov.br/nfce/consulta?p=' . self::VALID_ACCESS_KEY . '|2|1|1|hash',
            ]);

        $response
            ->assertStatus(200)
            ->assertJsonPath('invoice.access_key', self::VALID_ACCESS_KEY)
            ->assertJsonPath('invoice.user_id', $client->id)
            ->assertJsonPath('invoice.pending', true);

        $this->assertDatabaseHas('invoice', [
            'user_id' => $client->id,
            'access_key' => self::VALID_ACCESS_KEY,
            'invoice_data' => null,
            'pending' => true,
        ]);
    }

    public function test_unsupported_access_key_area_returns_bad_request(): void
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)
            ->postJson('/api/invoice/process', [
                'invoice_code' => '35260712345678000195650010000012341000012345',
            ]);

        $response
            ->assertStatus(400)
            ->assertJsonFragment([
                'message' => 'Código de NFCe ainda não suportado.',
            ]);

        $this->assertDatabaseCount('invoice', 0);
    }

    public function invalidPayloadsProvider(): array
    {
        return [
            [[], 'qr_code_data'],
            [['qr_code_data' => ['not-string']], 'qr_code_data'],
            [['qr_code_data' => 'INVALID-QR'], 'qr_code_data'],
            [['invoice_code' => ['not-string']], 'invoice_code'],
            [['invoice_code' => '123'], 'invoice_code'],
        ];
    }
}
