<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SessionManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/_test/protected-page', fn () => response('protected'))
            ->middleware('check.session:admin');
    }

    public function test_authenticated_pages_cannot_be_cached_by_the_browser(): void
    {
        $response = $this->withSession(['user' => $this->adminSession()])
            ->get('/_test/protected-page');

        $response->assertOk();
        $response->assertHeader('Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store, private');
        $response->assertHeader('Pragma', 'no-cache');
        $response->assertHeader('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');
    }

    public function test_logout_invalidates_the_session_and_back_navigation_cannot_reopen_dashboard(): void
    {
        $logout = $this->withSession(['user' => $this->adminSession()])
            ->post('/logout');

        $logout->assertRedirect(route('login'));
        $logout->assertHeader('Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store, private');
        $this->assertNull(session('user'));

        $this->get('/_test/protected-page')->assertRedirect(route('login'));
    }

    public function test_session_status_cannot_report_logged_out_browser_history_as_authenticated(): void
    {
        $this->get('/session/status')
            ->assertUnauthorized()
            ->assertJson(['authenticated' => false])
            ->assertHeader('Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store, private');

        $this->withSession(['user' => $this->adminSession()])
            ->get('/session/status')
            ->assertOk()
            ->assertJson(['authenticated' => true]);
    }

    public function test_login_page_is_not_cached_and_replaces_its_history_entry_after_login(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store, private')
            ->assertSee('window.location.replace(data.redirect)', false);
    }

    private function adminSession(): array
    {
        return [
            'id' => 1,
            'name' => 'Session Test Admin',
            'email' => 'session-test@example.com',
            'role' => 'admin',
        ];
    }
}
