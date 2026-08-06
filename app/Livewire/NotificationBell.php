<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationBell extends Component
{
    public bool $isOpen = false;

    public function toggleDropdown(): void
    {
        $this->isOpen = !$this->isOpen;
    }

    public function closeDropdown(): void
    {
        $this->isOpen = false;
    }

    public function markAsRead(string $notificationId): void
    {
        $user = Auth::user();
        if ($user) {
            $user->notifications()->where('id', $notificationId)->first()?->markAsRead();
        }
    }

    public function markAllAsRead(): void
    {
        $user = Auth::user();
        if ($user) {
            $user->unreadNotifications->markAsRead();
        }
    }

    public function deleteNotification(string $notificationId): void
    {
        $user = Auth::user();
        if ($user) {
            $user->notifications()->where('id', $notificationId)->first()?->delete();
        }
    }

    public function render()
    {
        $user = Auth::user();
        $unreadCount = $user ? $user->unreadNotifications()->count() : 0;
        $notifications = $user ? $user->notifications()->take(10)->get() : collect();

        return view('livewire.notification-bell', [
            'unreadCount'   => $unreadCount,
            'notifications' => $notifications,
        ]);
    }
}
