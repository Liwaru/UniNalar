<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_landing_page_is_public_and_uses_the_existing_login_route(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('id="transparansi"', false)
            ->assertSee('data-bs-target="#mainNavigation"', false)
            ->assertSee('class="collapse navbar-collapse"', false)
            ->assertViewIs('landing.index')
            ->assertSee('Kenali Risiko Lebih Dini.', false)
            ->assertSee('Pahami kondisi akademik mahasiswa melalui analisis data yang transparan', false)
            ->assertSee(route('login'), false)
            ->assertSee('Data simulasi')
            ->assertSee('riskTrendChart')
            ->assertSee('Distribusi risiko')
            ->assertDontSee('DEMO00001');
        $this->assertGuest();
    }

    public function test_primary_navigation_targets_each_unique_landing_section(): void
    {
        $response = $this->get('/');
        $html = $response->getContent();

        $response->assertOk()
            ->assertSee('href="#beranda"', false)
            ->assertSee('href="#fitur"', false)
            ->assertSee('href="#cara-kerja"', false)
            ->assertSee('href="#tentang"', false);

        $response->assertSee('TENTANG UNINALAR')
            ->assertSee('Memahami Risiko Akademik,', false)
            ->assertSee('Berbasis Data')
            ->assertSee('Transparan dan Dapat Dijelaskan')
            ->assertSee('Mendukung Pendampingan')
            ->assertSee('class="about-section section-space" id="tentang"', false)
            ->assertSee('<a href="#tentang">Tentang</a>', false);

        $this->assertLessThan(strpos($html, 'id="tentang"'), strpos($html, 'EXPLAINABLE AI'));
        $this->assertLessThan(strpos($html, 'id="cta"'), strpos($html, 'id="tentang"'));

        preg_match_all('/\sid="(beranda|fitur|cara-kerja|tentang)"/', $html, $matches);
        $sectionIds = array_count_values($matches[1]);

        $this->assertSame([
            'beranda' => 1,
            'fitur' => 1,
            'cara-kerja' => 1,
            'tentang' => 1,
        ], $sectionIds);
    }
}
