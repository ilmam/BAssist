<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The home page needs a signed-in user: a guest is sent to the login page.
     */
    public function test_home_sends_a_guest_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }
}
