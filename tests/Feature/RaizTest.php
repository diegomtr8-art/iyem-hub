<?php

namespace Tests\Feature;

use Tests\TestCase;

class RaizTest extends TestCase
{
    public function test_la_raiz_redirige_al_inicio_de_sesion(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_el_health_check_responde(): void
    {
        $this->get('/up')->assertOk();
    }
}
