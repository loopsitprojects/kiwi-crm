<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Models\User;
use App\Notifications\InvoiceStatusPendingNotification;
use Illuminate\Support\Facades\Notification;

class CheckPendingInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:check-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for invoices pending status change for more than 30 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for pending invoices...');

        // Find unpaid invoices older than 30 days
        $invoices = Invoice::where('status', 'unpaid')
            ->where('created_at', '<=', now()->subDays(30))
            ->get();

        if ($invoices->isEmpty()) {
            $this->info('No pending invoices found.');
            return;
        }

        foreach ($invoices as $invoice) {
            try {
                $this->info("Processing Invoice #{$invoice->invoice_number}");

                // target Users
                $recipients = collect();

                // 1. Finance Admin, Super Admin & IT Admin
                $superAdmins = User::whereIn('role', ['Finance Admin', 'Super Admin', 'IT Admin'])->get();
                $recipients = $recipients->merge($superAdmins);

                // 2. Management
                $management = User::whereIn('role', ['Management', 'Manager'])->get();
                $recipients = $recipients->merge($management);

                // 3. Project Members (Deal -> Team Members)
                if ($invoice->estimate && $invoice->estimate->deal) {
                    $teamMembers = $invoice->estimate->deal->teamMembers;
                    $recipients = $recipients->merge($teamMembers);

                    // Also the deal owner
                    if ($invoice->estimate->deal->user) {
                        $recipients->push($invoice->estimate->deal->user);
                    }
                }

                // Unique users only to avoid duplicate emails
                $recipients = $recipients->unique('id');

                // Send Notification
                Notification::send($recipients, new InvoiceStatusPendingNotification($invoice));
            } catch (\Throwable $e) {
                $this->error("Failed to notify for Invoice #{$invoice->invoice_number}: " . $e->getMessage());
                \Illuminate\Support\Facades\Log::error("Invoice #{$invoice->invoice_number} reminder failed: " . $e->getMessage());
            }
        }

        $this->info('Pending invoices check completed.');
    }
}
