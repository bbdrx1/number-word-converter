<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ConverterController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Default Laravel welcome route (you can remove this)
Route::get('/welcome', function () {
    return view('welcome');
});

// Main converter routes
Route::get('/', [ConverterController::class, 'index'])->name('converter.index');
Route::post('/convert', [ConverterController::class, 'convert'])->name('converter.convert');

// API routes for AJAX requests (bonus feature)
Route::prefix('api')->group(function () {
    Route::post('/convert', [ConverterController::class, 'apiConvert'])->name('api.convert');
    Route::get('/currencies', [ConverterController::class, 'getCurrencies'])->name('api.currencies');
});

// Additional utility routes
Route::get('/test', function () {
    return response()->json([
        'message' => 'Laravel Number-Word Converter API is working!',
        'timestamp' => now(),
        'version' => '1.0.0'
    ]);
})->name('test');