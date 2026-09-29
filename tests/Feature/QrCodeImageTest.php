<?php

namespace Tests\Feature;

use App\Models\QRCode;
use App\Models\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrCodeImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_menu_qr_image_is_generated_for_a_table(): void
    {
        $table = Table::factory()->create();
        $qrCode = QRCode::factory()->create(['table_id' => $table->id]);

        $this->get('/qr/menu/'.$qrCode->code)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_the_staff_login_qr_image_is_generated(): void
    {
        $this->get('/qr/personal')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_an_unknown_code_cannot_generate_a_menu_qr(): void
    {
        $this->get('/qr/menu/codigo-inexistente')
            ->assertNotFound();
    }
}
