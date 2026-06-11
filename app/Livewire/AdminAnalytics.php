<?php

namespace App\Livewire;

use Livewire\Component;

class AdminAnalytics extends Component
{
    public function render()
    {
        return view('livewire.admin-analytics')
            ->layout('layouts.app');
    }
}
