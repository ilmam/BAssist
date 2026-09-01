<?php

namespace Tests\Unit;

use App\Support\ModalUrl;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ModalUrlTest extends TestCase
{
    #[Test]
    public function canonical_path_maps_overlay_routes_to_full_pages(): void
    {
        $this->assertSame('business_needs/5', ModalUrl::canonicalPath('business_needs/modal/5/view'));
        $this->assertSame('business_needs/5/edit', ModalUrl::canonicalPath('business_needs/modal/5/edit'));
        $this->assertSame('business_needs/create', ModalUrl::canonicalPath('business_needs/modal/create'));
        $this->assertSame('business_needs/create', ModalUrl::canonicalPath('business_needs/modal/quick-create'));
        $this->assertSame('business_needs/5', ModalUrl::canonicalPath('business_needs/modal/5/delete'));
        $this->assertSame('business_needs/5', ModalUrl::canonicalPath('/business_needs/modal/5'));
        $this->assertNull(ModalUrl::canonicalPath('features/modal/5/raw'));
        $this->assertNull(ModalUrl::canonicalPath('business_needs/5'));
    }

    #[Test]
    public function canonical_url_keeps_query_string(): void
    {
        $request = Request::create('http://localhost/business_needs/modal/5/view?project_id=3');

        $url = ModalUrl::canonicalUrl($request);

        $this->assertNotNull($url);
        $this->assertStringContainsString('/business_needs/5?project_id=3', $url);
    }
}
