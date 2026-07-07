<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$users = App\Models\User::where('is_active', true)->limit(5)->get(['id','name','email']);
foreach ($users as $u) {
    echo "{$u->id} - {$u->name} - {$u->email}\n";
}
unlink(__FILE__);
