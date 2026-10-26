<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Tests\TestCase;

class GlobalErrorHandlingTest extends TestCase
{
    public function test_custom_error_pages_exist(): void
    {
        $this->assertFileExists(resource_path('views/errors/500.blade.php'));
        $this->assertFileExists(resource_path('views/errors/404.blade.php'));
        $this->assertFileExists(resource_path('views/errors/403.blade.php'));
        $this->assertFileExists(resource_path('views/errors/419.blade.php'));
        $this->assertFileExists(resource_path('views/errors/db.blade.php'));
    }

    public function test_404_view_renders(): void
    {
        $view = view('errors.404')->render();
        $this->assertStringContainsString('404', $view);
        $this->assertStringContainsString('Page Not Found', $view);
    }

    public function test_500_view_renders(): void
    {
        $view = view('errors.500')->render();
        $this->assertStringContainsString('500', $view);
        $this->assertStringContainsString('Server Error', $view);
    }

    public function test_db_view_renders(): void
    {
        $view = view('errors.db')->render();
        $this->assertStringContainsString('Database Offline', $view);
        $this->assertStringContainsString('Retry Connection', $view);
    }
}
