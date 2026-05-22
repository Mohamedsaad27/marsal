<?php

namespace Tests\Unit\Auth;

use App\Modules\Auth\Domain\Services\WhatsAppService;
use App\Modules\Users\Infrastructure\Database\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WhatsAppServiceTest extends TestCase
{
    private WhatsAppService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WhatsAppService();
    }

    #[DataProvider('phoneNormalizationProvider')]
    public function test_normalize_phone_for_wa_me(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->service->normalizePhoneForWaMe($input));
    }

    public static function phoneNormalizationProvider(): array
    {
        return [
            ['01010865241', '201010865241'],
            ['+201010865241', '201010865241'],
            ['201010865241', '201010865241'],
        ];
    }

    public function test_build_welcome_link_contains_wa_me_and_user_phone(): void
    {
        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '01099998888',
        ]);

        $url = $this->service->buildWelcomeLink($user, 'TempPass@123');

        $this->assertStringStartsWith('https://wa.me/201099998888?text=', $url);
        $this->assertStringContainsString(rawurlencode('test@example.com'), $url);
    }
}
