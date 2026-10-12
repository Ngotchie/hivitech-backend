<?php

namespace Tests\Unit;

use App\Models\User;
use Filament\Panel;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserPanelAccessTest extends TestCase
{
    #[Test]
    public function configured_administrator_can_access_the_admin_panel(): void
    {
        config()->set('hivitech.filament_admin_emails', ['christian.ngotchie@hivi-tech.com']);

        $user = new User(['email' => 'christian.ngotchie@hivi-tech.com']);

        $this->assertTrue($user->canAccessPanel(Panel::make()->id('admin')));
    }

    #[Test]
    public function another_user_cannot_access_the_admin_panel(): void
    {
        config()->set('hivitech.filament_admin_emails', ['christian.ngotchie@hivi-tech.com']);

        $user = new User(['email' => 'another@example.com']);

        $this->assertFalse($user->canAccessPanel(Panel::make()->id('admin')));
    }
}
