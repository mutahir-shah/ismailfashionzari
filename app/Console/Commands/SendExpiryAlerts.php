<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;

class SendExpiryAlerts extends Command
{
    protected $signature = 'notifications:expiry-check {days=30}';
    protected $description = 'Scan inventory rows and alert admins about products expiring within X days.';

    public function handle()
    {
        $days = (int) $this->argument('days');
        $thresholdDate = Carbon::now()->addDays($days)->toDateString();
        $today = Carbon::now()->toDateString();

        // Finding products expiring between today and X days out
        $expiringProducts = Product::whereNotNull('expiry_date')
            ->where('expiry_date', '>=', $today)
            ->where('expiry_date', '<=', $thresholdDate)
            ->get();

        if ($expiringProducts->isEmpty()) {
            $this->info('No expiring stock items detected.');
            return;
        }

        $summaryLines = [];
        foreach ($expiringProducts as $product) {
            $summaryLines[] = "{$product->name} (Exp: {$product->expiry_date})";
        }

        app(NotificationService::class)->dispatch('expiry_alert', [
            'product'     => implode(', ', $summaryLines),
            'admin_users' => User::where('role_id', '<=', 2)->get(),
        ]);

        $this->info('Expiry warning messages dispatched to admins.');
    }
}
