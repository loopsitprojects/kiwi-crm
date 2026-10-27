<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EstimateController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\PettyCashController;

Route::get('login', [AuthController::class, 'showLogin'])->name('login');
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:15,1')->name('login.post');
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

// Password Reset Routes (Email OTP)
Route::get('forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('forgot-password', [AuthController::class, 'sendOtp'])->middleware('throttle:6,1')->name('password.email');
Route::get('reset-password/otp', [AuthController::class, 'showOtpForm'])->name('password.otp');
Route::post('reset-password/otp', [AuthController::class, 'resetPasswordWithOtp'])->middleware('throttle:10,1')->name('password.update-otp');

// Dynamic PWA Manifest & Service Worker Routes for Hostinger / LiteSpeed
Route::get('manifest.json', function () {
    return response()->json([
        'name' => 'Creative Kiwi',
        'short_name' => 'Creative Kiwi',
        'description' => 'Creative Kiwi CRM, Petty Cash & Invoicing Management System',
        'id' => url('/'),
        'start_url' => url('/'),
        'scope' => url('/'),
        'display' => 'standalone',
        'orientation' => 'any',
        'background_color' => '#15803d',
        'theme_color' => '#15803d',
        'prefer_related_applications' => false,
        'icons' => [
            [
                'src' => url('images/pwa-icon-192.png'),
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any'
            ],
            [
                'src' => url('images/pwa-icon-512.png'),
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any'
            ],
            [
                'src' => url('images/pwa-icon-192.png'),
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'maskable'
            ],
            [
                'src' => url('images/pwa-icon-512.png'),
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'maskable'
            ]
        ]
    ])->header('Content-Type', 'application/manifest+json');
});

Route::get('serviceworker.js', function () {
    $sw = <<<JS
var staticCacheName = "pwa-v" + new Date().getTime();

self.addEventListener("install", function (event) {
    self.skipWaiting();
});

self.addEventListener("activate", function (event) {
    event.waitUntil(
        caches.keys().then(function (cacheNames) {
            return Promise.all(
                cacheNames.map(function (cacheName) {
                    if (cacheName !== staticCacheName) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

self.addEventListener("fetch", function (event) {
    if (event.request.method !== 'GET') return;
    if (!event.request.url.startsWith('http')) return;

    event.respondWith(
        fetch(event.request)
            .then(function (response) {
                if (!response || response.status !== 200 || response.type !== 'basic') {
                    return response;
                }
                var responseToCache = response.clone();
                caches.open(staticCacheName).then(function (cache) {
                    cache.put(event.request, responseToCache);
                });
                return response;
            })
            .catch(function () {
                return caches.match(event.request);
            })
    );
});
JS;
    return response($sw, 200)->header('Content-Type', 'application/javascript');
});

// Dynamic Square PWA Icon Route
Route::get('images/pwa-icon-{size}.png', function ($size) {
    $size = in_array((int)$size, [192, 512]) ? (int)$size : 192;
    $logoPath = public_path('images/logo_loops_light.png');
    if (!file_exists($logoPath)) {
        return response('', 404);
    }
    
    $src = imagecreatefrompng($logoPath);
    // Non-transparent bounding box in logo_loops_light.png: x: 52..949 (897w), y: 82..419 (337h)
    $cropX = 52;
    $cropY = 82;
    $cropW = 897;
    $cropH = 337;

    $dst = imagecreatetruecolor($size, $size);
    // Brand kiwi green #15803d
    $kiwiGreen = imagecolorallocate($dst, 21, 128, 61);
    imagefill($dst, 0, 0, $kiwiGreen);

    // Safe zone width is ~70% of canvas size (360px for 512, 135px for 192)
    $targetW = (int)round($size * (360 / 512));
    $targetH = (int)round($cropH * ($targetW / $cropW));
    $posX = (int)round(($size - $targetW) / 2);
    $posY = (int)round(($size - $targetH) / 2);

    imagealphablending($dst, true);
    imagecopyresampled($dst, $src, $posX, $posY, $cropX, $cropY, $targetW, $targetH, $cropW, $cropH);

    ob_start();
    imagepng($dst, null, 9);
    $imageData = ob_get_clean();

    return response($imageData, 200)
        ->header('Content-Type', 'image/png')
        ->header('Cache-Control', 'public, max-age=604800');
});

Route::get('maintenance', function () {
    $mode = (int) \App\Models\Setting::get('maintenance_mode', 0);
    if ($mode === 0) {
        return redirect()->route('login');
    }
    return response()->view('errors.maintenance', ['mode' => $mode], 503);
})->name('maintenance');

Route::get('/petty-cash/{pettyCash}/download', [PettyCashController::class, 'downloadVoucher'])->name('petty-cash.download');
Route::get('/v/{token}', [PettyCashController::class, 'downloadVoucherSecure'])->name('petty-cash.download-secure');
Route::get('/petty-cash/proofs/{proof}', [PettyCashController::class, 'showProof'])->name('petty-cash.proofs.show');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/change-password', [AuthController::class, 'changePassword'])->name('password.change');
    Route::post('/notifications/mark-as-read', function () {
        auth()->user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    })->name('notifications.markAsRead');
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    // Petty Cash Routes (Accessible to Staff, HOD, Management, Finance Admin)
    Route::get('/petty-cash', [PettyCashController::class, 'index'])->name('petty-cash.index');
    Route::post('/petty-cash', [PettyCashController::class, 'store'])->name('petty-cash.store');
    Route::get('/petty-cash/{pettyCash}', [PettyCashController::class, 'show'])->name('petty-cash.show');
    Route::put('/petty-cash/{pettyCash}', [PettyCashController::class, 'update'])->name('petty-cash.update');
    Route::delete('/petty-cash/{pettyCash}', [PettyCashController::class, 'destroy'])->name('petty-cash.destroy');
    Route::post('/petty-cash/{pettyCash}/hod-approve', [PettyCashController::class, 'hodApprove'])->name('petty-cash.hodApprove');
    Route::get('/petty-cash/{id}/hod-approve', function ($id) { return redirect()->route('petty-cash.index', ['hod_approve_id' => $id, 'scope' => 'approvals']); });
    Route::post('/petty-cash/{pettyCash}/hod-reject', [PettyCashController::class, 'hodReject'])->name('petty-cash.hodReject');
    Route::get('/petty-cash/{id}/hod-reject', function ($id) { return redirect()->route('petty-cash.index', ['hod_reject_id' => $id, 'scope' => 'approvals']); });
    Route::post('/petty-cash/{pettyCash}/admin-approve', [PettyCashController::class, 'adminApprove'])->name('petty-cash.adminApprove');
    Route::get('/petty-cash/{id}/admin-approve', function ($id) { return redirect()->route('petty-cash.index', ['approve_id' => $id, 'scope' => 'approvals']); });
    Route::post('/petty-cash/{pettyCash}/admin-reject', [PettyCashController::class, 'adminReject'])->name('petty-cash.adminReject');
    Route::get('/petty-cash/{id}/admin-reject', function ($id) { return redirect()->route('petty-cash.index', ['reject_id' => $id, 'scope' => 'approvals']); });
    Route::post('/petty-cash/{pettyCash}/send-to-management', [PettyCashController::class, 'sendToManagement'])->name('petty-cash.sendToManagement');
    Route::post('/petty-cash/{pettyCash}/management-approve', [PettyCashController::class, 'managementApprove'])->name('petty-cash.managementApprove');
    Route::get('/petty-cash/{id}/management-approve', function ($id) { return redirect()->route('petty-cash.index', ['mgmt_approve_id' => $id, 'scope' => 'approvals']); });
    Route::post('/petty-cash/{pettyCash}/management-reject', [PettyCashController::class, 'managementReject'])->name('petty-cash.managementReject');
    Route::get('/petty-cash/{id}/management-reject', function ($id) { return redirect()->route('petty-cash.index', ['mgmt_reject_id' => $id, 'scope' => 'approvals']); });
    Route::post('/petty-cash/{pettyCash}/settle', [PettyCashController::class, 'settleIOU'])->name('petty-cash.settle');
    Route::get('/petty-cash/{id}/settle', function ($id) { return redirect()->route('petty-cash.index', ['settle_id' => $id, 'scope' => 'approvals']); });
    Route::post('/petty-cash/{pettyCash}/reappeal', [PettyCashController::class, 'reappeal'])->name('petty-cash.reappeal');
    Route::get('/petty-cash/{id}/reappeal', function ($id) { return redirect()->route('petty-cash.index', ['reappeal_id' => $id]); });
    Route::post('/petty-cash/{pettyCash}/remind-iou', [PettyCashController::class, 'sendIouReminder'])->name('petty-cash.remind-iou');
    Route::post('/petty-cash/{pettyCash}/notify-sign', [PettyCashController::class, 'notifySignVoucher'])->name('petty-cash.notify-sign');

    // CRM Routes (Protected from direct Staff role access)
    Route::middleware(['prevent.staff'])->group(function () {
        Route::get('/dashboard/export', [DashboardController::class, 'exportCsv'])->name('dashboard.export');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'exportCsv'])->name('reports.export');

        Route::resource('customers', CustomerController::class);

        // Deals
        Route::get('/deals', [DealController::class, 'index'])->name('deals.index');
        Route::post('/deals', [DealController::class, 'store'])->name('deals.store');
        Route::put('/deals/{deal}', [DealController::class, 'update'])->name('deals.update');
        Route::get('/deals/{deal}/delete', [DealController::class, 'destroy'])->name('deals.destroy.get');
        Route::delete('/deals/{deal}', [DealController::class, 'destroy'])->name('deals.destroy');
        Route::post('/deals/{deal}/stage', [DealController::class, 'updateStage'])->name('deals.updateStage');
        Route::post('/deals/{deal}/create-estimate', [DealController::class, 'createEstimate'])->name('deals.createEstimate');
        Route::post('/deals/{deal}/create-invoice', [DealController::class, 'createInvoice'])->name('deals.createInvoice');

        // Jobs
        Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');
        Route::post('/jobs/{deal}/send-webhook', [JobController::class, 'sendWebhook'])->name('jobs.send-webhook');

        Route::post('estimates/{estimate}/accept', [EstimateController::class, 'markAsAccepted'])->name('estimates.accept');
        Route::post('estimates/{estimate}/reject', [EstimateController::class, 'markAsRejected'])->name('estimates.reject');
        Route::post('estimates/{estimate}/duplicate', [EstimateController::class, 'duplicate'])->name('estimates.duplicate');
        Route::post('estimates/{estimate}/convert', [
            EstimateController::class,
            'convertToInvoice'
        ])->name('estimates.convert');
        Route::post('estimates/{estimate}/status', [EstimateController::class, 'updateStatus'])->name('estimates.updateStatus');
        Route::resource('estimates', EstimateController::class);

        Route::get('invoices/ready', [InvoiceController::class, 'ready'])->name('invoices.ready');
        Route::get('invoices/invoiced', [InvoiceController::class, 'invoiced'])->name('invoices.invoiced');
        Route::get('invoices/rejected', [InvoiceController::class, 'rejected'])->name('invoices.rejected');
        Route::get('invoices/proforma', [InvoiceController::class, 'proforma'])->name('invoices.proforma');
        Route::post('invoices/{invoice}/status', [InvoiceController::class, 'updateStatus'])->name('invoices.updateStatus');
        Route::get('invoices/{invoice}/payments', [InvoiceController::class, 'getPayments'])->name('invoices.payments.index');
        Route::post('invoices/{invoice}/payments', [InvoiceController::class, 'storePayment'])->name('invoices.payments.store');
        Route::delete('invoices/{invoice}/payments/{payment}', [InvoiceController::class, 'destroyPayment'])->name('invoices.payments.destroy');
        Route::post('invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
        Route::post('invoices/{invoice}/revert', [InvoiceController::class, 'revert'])->middleware('role:IT Admin')->name('invoices.revert');
        Route::post('estimates/{estimate}/revert-invoice', [InvoiceController::class, 'revertEstimateInvoice'])->middleware('role:IT Admin')->name('estimates.revertInvoice');
        Route::resource('invoices', InvoiceController::class);
        Route::resource('temp-invoices', \App\Http\Controllers\TempInvoiceController::class)->only(['edit', 'update']);
        Route::post('temp-invoices/{tempInvoice}/revert', [InvoiceController::class, 'revertToPending'])->name('temp-invoices.revert');

        // Finance Admin, Management, IT Admin & Super Admin Routes
        Route::middleware(['role:Finance Admin,Management,IT Admin'])->group(function () {
            Route::get('activities', [ActivityController::class, 'index'])->name('activities.index');
            Route::get('users/download-sample', [UserController::class, 'downloadSample'])->name('users.download-sample');
            Route::post('users/import', [UserController::class, 'import'])->name('users.import');
            Route::resource('users', UserController::class);

            // Customer Requests
            Route::get('customers/requests/{request}', [CustomerController::class, 'reviewRequest'])->name('customers.requests.review');
            Route::post('customers/requests/{request}/approve', [CustomerController::class, 'approveRequest'])->name('customers.requests.approve');
            Route::post('customers/requests/{request}/reject', [CustomerController::class, 'rejectRequest'])->name('customers.requests.reject');

            // Settings
            Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
            Route::post('settings/general', [SettingController::class, 'updateGeneral'])->name('settings.updateGeneral');
            Route::post('settings/tax', [SettingController::class, 'updateTax'])->name('settings.updateTax');
            Route::post('settings/department-targets', [SettingController::class, 'updateDepartmentTargets'])->name('settings.updateDepartmentTargets');
            Route::post('settings/user-targets', [SettingController::class, 'updateUserTargets'])->name('settings.updateUserTargets');
            Route::post('settings/targets/periods', [SettingController::class, 'storeTargetPeriod'])->name('settings.storeTargetPeriod');
            Route::delete('settings/targets/periods', [SettingController::class, 'destroyTargetPeriod'])->name('settings.destroyTargetPeriod');
            Route::post('settings/targets/delete-period', [SettingController::class, 'deleteTargetPeriod'])->name('settings.deleteTargetPeriod');
            Route::post('settings/targets/delete-single', [SettingController::class, 'deleteSingleTarget'])->name('settings.deleteSingleTarget');
            Route::post('settings/targets/add-users', [SettingController::class, 'addUsersToTargetPeriod'])->name('settings.addUsersToTargetPeriod');
            Route::post('settings/targets/add-departments', [SettingController::class, 'addDepartmentsToTargetPeriod'])->name('settings.addDepartmentsToTargetPeriod');
            Route::post('settings/maintenance', [SettingController::class, 'updateMaintenance'])->name('settings.updateMaintenance');
            Route::post('settings/notifications', [SettingController::class, 'updateNotifications'])->name('settings.updateNotifications');
            
            Route::post('settings/managers', [SettingController::class, 'storeManager'])->name('settings.storeManager');
            Route::get('settings/managers/{manager}/delete', [SettingController::class, 'destroyManager'])->name('settings.destroyManager.get');
            Route::delete('settings/managers/{manager}', [SettingController::class, 'destroyManager'])->name('settings.destroyManager');
            Route::put('settings/managers/{manager}', [SettingController::class, 'updateManager'])->name('settings.updateManager');
            Route::post('settings/terms', [SettingController::class, 'storeTerm'])->name('settings.storeTerm');
            Route::get('settings/terms/{term}/delete', [SettingController::class, 'destroyTerm'])->name('settings.destroyTerm.get');
            Route::delete('settings/terms/{term}', [SettingController::class, 'destroyTerm'])->name('settings.destroyTerm');
            Route::put('settings/terms/{term}', [SettingController::class, 'updateTerm'])->name('settings.updateTerm');
            Route::post('settings/currencies', [SettingController::class, 'storeCurrency'])->name('settings.storeCurrency');
            Route::get('settings/currencies/{currency}/delete', [SettingController::class, 'destroyCurrency'])->name('settings.destroyCurrency.get');
            Route::delete('settings/currencies/{currency}', [SettingController::class, 'destroyCurrency'])->name('settings.destroyCurrency');
            Route::put('settings/currencies/{currency}', [SettingController::class, 'updateCurrency'])->name('settings.updateCurrency');

            Route::post('settings/expense-categories', [SettingController::class, 'storeExpenseCategory'])->name('settings.storeExpenseCategory');
            Route::get('settings/expense-categories/{expenseCategory}/delete', [SettingController::class, 'destroyExpenseCategory'])->name('settings.destroyExpenseCategory.get');
            Route::delete('settings/expense-categories/{expenseCategory}', [SettingController::class, 'destroyExpenseCategory'])->name('settings.destroyExpenseCategory');
            Route::put('settings/expense-categories/{expenseCategory}', [SettingController::class, 'updateExpenseCategory'])->name('settings.updateExpenseCategory');

            Route::post('settings/departments', [SettingController::class, 'storeDepartment'])->name('settings.storeDepartment');
            Route::put('settings/departments/{department}', [SettingController::class, 'updateDepartment'])->name('settings.updateDepartment');
            Route::delete('settings/departments/{department}', [SettingController::class, 'destroyDepartment'])->name('settings.destroyDepartment');

            // Workflow Webhook Settings
            Route::post('settings/webhook', [SettingController::class, 'updateWebhook'])->name('settings.webhook.update');
            Route::post('settings/webhook/test', [SettingController::class, 'testWorkflowWebhook'])->name('settings.webhook.test');

            // Seasonal Theme (Halloween) Settings
            Route::post('settings/halloween-theme', [SettingController::class, 'updateHalloweenTheme'])->name('settings.halloween.update');
            Route::post('settings/halloween-theme/toggle', [SettingController::class, 'toggleHalloweenTheme'])->name('settings.halloween.toggle');
        });

    });
});

// Direct route to serve uploads in shared-hosting / subfolder deployments (e.g. Hostinger LiteSpeed where /uploads or /public/uploads rewrites to index.php)
$serveUploadFile = function ($path) {
    // Strip any leading slashes, public/, uploads/
    $cleanPath = preg_replace('#^(public/)?(uploads/)?#i', '', ltrim($path, '/'));
    $decodedPath = urldecode($cleanPath);
    
    $checkPaths = array_unique(array_filter([$cleanPath, $decodedPath]));
    $possiblePaths = [];
    foreach ($checkPaths as $p) {
        $possiblePaths[] = public_path('uploads/' . $p);
        $possiblePaths[] = public_path($p);
        $possiblePaths[] = base_path('public/uploads/' . $p);
        $possiblePaths[] = base_path('public/' . $p);
        $possiblePaths[] = base_path('uploads/' . $p);
        $possiblePaths[] = base_path($p);
        $possiblePaths[] = storage_path('app/public/' . $p);
        $possiblePaths[] = storage_path('app/public/uploads/' . $p);
        $possiblePaths[] = storage_path('app/' . $p);
    }

    foreach ($possiblePaths as $file) {
        if (file_exists($file) && is_file($file)) {
            $mime = mime_content_type($file) ?: 'application/octet-stream';
            return response()->file($file, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="' . basename($file) . '"',
                'Cache-Control' => 'public, max-age=604800',
            ]);
        }
    }
    abort(404, 'Upload file not found.');
};

Route::get('uploads/{path}', $serveUploadFile)->where('path', '.*');
Route::get('public/uploads/{path}', $serveUploadFile)->where('path', '.*');

// Secure diagnostic & maintenance endpoint for Hostinger deployment troubleshooting
Route::get('system-diagnose', function (\Illuminate\Http\Request $request) {
    if ($request->query('key') !== 'loops-pc-fix') {
        abort(403, 'Unauthorized');
    }

    $results = [];

    // 1. Run migrations
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $results['migrate'] = trim(\Illuminate\Support\Facades\Artisan::output());
    } catch (\Throwable $e) {
        $results['migrate_error'] = $e->getMessage();
    }

    // 2. Clear view cache, config cache, route cache, app cache
    try {
        \Illuminate\Support\Facades\Artisan::call('view:clear');
        $results['view_clear'] = trim(\Illuminate\Support\Facades\Artisan::output());
    } catch (\Throwable $e) {
        $results['view_clear_error'] = $e->getMessage();
    }

    try {
        \Illuminate\Support\Facades\Artisan::call('route:clear');
        $results['route_clear'] = trim(\Illuminate\Support\Facades\Artisan::output());
    } catch (\Throwable $e) {
        $results['route_clear_error'] = $e->getMessage();
    }

    try {
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        $results['config_clear'] = trim(\Illuminate\Support\Facades\Artisan::output());
    } catch (\Throwable $e) {
        $results['config_clear_error'] = $e->getMessage();
    }

    try {
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        $results['cache_clear'] = trim(\Illuminate\Support\Facades\Artisan::output());
    } catch (\Throwable $e) {
        $results['cache_clear_error'] = $e->getMessage();
    }

    // Ensure uploads/petty_cash_proofs directories exist and are writable
    $uploadDirs = [
        public_path('uploads/petty_cash_proofs'),
        base_path('public/uploads/petty_cash_proofs'),
        base_path('uploads/petty_cash_proofs'),
    ];
    $results['upload_dirs'] = [];
    foreach ($uploadDirs as $dir) {
        if (!file_exists($dir)) {
            @mkdir($dir, 0777, true);
        }
        $results['upload_dirs'][$dir] = [
            'exists' => file_exists($dir),
            'writable' => is_writable($dir),
        ];
    }

    // 3. Test render petty-cash index for each user
    $results['test_renders'] = [];
    $users = \App\Models\User::all();
    foreach ($users as $u) {
        try {
            auth()->login($u);
            $req = \Illuminate\Http\Request::create('/petty-cash', 'GET');
            $controller = app(\App\Http\Controllers\PettyCashController::class);
            view()->share('errors', new \Illuminate\Support\ViewErrorBag());
            $response = $controller->index($req);
            $html = $response->render();
            $info = 'SUCCESS (' . strlen($html) . ' bytes)';
            if ($u->role === 'Management') {
                $info .= ' | hasAdminApprove: ' . (str_contains($html, 'openAdminApproveModal') ? 'YES' : 'NO') .
                         ' | hasToManagement: ' . (str_contains($html, 'openSendToManagementModal') ? 'YES' : 'NO');
            }
            $results['test_renders'][$u->name . ' (role: ' . $u->role . ', id: ' . $u->id . ')'] = $info;
        } catch (\Throwable $e) {
            $results['test_renders'][$u->name . ' (role: ' . $u->role . ', id: ' . $u->id . ')'] = [
                'ERROR' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => array_slice(explode("\n", $e->getTraceAsString()), 0, 10),
            ];
        }
    }

    // Git commit info
    try {
        $headFile = base_path('.git/HEAD');
        if (file_exists($headFile)) {
            $ref = trim(file_get_contents($headFile));
            if (str_starts_with($ref, 'ref: ')) {
                $refPath = base_path('.git/' . substr($ref, 5));
                $commit = file_exists($refPath) ? trim(file_get_contents($refPath)) : $ref;
            } else {
                $commit = $ref;
            }
            $results['git_commit'] = $commit;
        }
    } catch (\Throwable $e) {
        $results['git_commit'] = 'unknown';
    }

    // 4. Retrieve recent logs from storage/logs/laravel.log
    $logPath = storage_path('logs/laravel.log');
    if (file_exists($logPath)) {
        $lines = file($logPath);
        $results['recent_logs'] = array_slice($lines, -80);
    } else {
        $results['recent_logs'] = 'No log file found at ' . $logPath;
    }

    return response()->json($results, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
});