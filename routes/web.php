<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\WebController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\QuotaController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\InterestController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\AccountMovementController;
use App\Http\Controllers\AdvisorController;


Route::get('optimize', function () {
	Artisan::call('optimize:clear');
});


Route::get('login', [AuthController::class, 'login'])->name('auth.login');
Route::post('login', [AuthController::class, 'check'])->name('auth.check');
Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');


Route::middleware('auth')->group(function () {

	Route::get('/', [WebController::class, 'index']);
	Route::get('reports/portfolio-daily/excel', [WebController::class, 'portfolioDailyExcel'])->name('reports.portfolio-daily.excel');
	Route::get('reports/portfolio-daily/clients', [WebController::class, 'reportClients'])->name('reports.portfolio-daily.clients');

	Route::get('api/reniec', [WebController::class, 'apiReniec'])->name('api.reniec');
	Route::get('api/provinces', [WebController::class, 'apiProvinces'])->name('api.provinces');
	Route::get('api/districts', [WebController::class, 'apiDistricts'])->name('api.districts');
	Route::get('api/indicator-detail', [WebController::class, 'indicatorDetail'])->name('api.indicator-detail');

	Route::get('clients/images', [ClientController::class, 'images'])->name('clients.images');
	Route::post('clients/images', [ClientController::class, 'uploadImage'])->name('clients.uploadImage');
	Route::delete('clients/images/{id}', [ClientController::class, 'deleteImage'])->name('clients.deleteImage');
	Route::get('clients/quotas', [ClientController::class, 'quotas'])->name('clients.quotas');
	Route::get('clients/contracts', [ClientController::class, 'contracts'])->name('clients.contracts');
	Route::get('clients/details', [ClientController::class, 'details'])->name('clients.details');
	Route::get('clients/check', [ClientController::class, 'check'])->name('clients.check');
	Route::get('clients/api', [ClientController::class, 'api'])->name('clients.api');
	Route::get('clients/inactive/excel', [ClientController::class, 'inactiveExcel'])->name('clients.inactive.excel');
	Route::get('clients/inactive', [ClientController::class, 'inactive'])->name('clients.inactive');
	Route::get('clients/excel', [ClientController::class, 'excel'])->name('clients.excel');
	Route::put('clients/update', [ClientController::class, 'update'])->name('clients.update');
	Route::get('clients', [ClientController::class, 'index'])->name('clients.index');

	Route::get('contracts/api', [ContractController::class, 'api'])->name('contracts.api');
	Route::put('contracts/{contract}/approve', [ContractController::class, 'approve'])->name('contracts.approve');
	Route::get('contracts/ending', [ContractController::class, 'ending'])->name('contracts.ending');
	Route::get('contracts/ending/excel', [ContractController::class, 'endingExcel'])->name('contracts.ending.excel');
	Route::get('contracts/sentinel/excel', [ContractController::class, 'sentinelExcel'])->name('contracts.sentinel.excel');
	Route::get('contracts/import/template', [ContractController::class, 'importTemplate'])->name('contracts.import.template');
	Route::post('contracts/import', [ContractController::class, 'importStore'])->name('contracts.import.store');
	Route::get('contracts/{contract}/pdf', [ContractController::class, 'pdf'])->name('contracts.pdf');
	Route::get('contracts/{contract}/pdfPersonal', [ContractController::class, 'pdfPersonal'])->name('contracts.pdfPersonal');
	Route::get('contracts/excel', [ContractController::class, 'excel'])->name('contracts.excel');
	Route::resource('contracts', ContractController::class);

	Route::get('quotas/api', [QuotaController::class, 'api'])->name('quotas.api');

	Route::get('payments/charges', [PaymentController::class, 'charges'])->name('payments.charges');
	Route::get('contracts/charges/excel', [PaymentController::class, 'chargesExcel'])->name('payments.charges.excel');
	Route::get('payments/dues/excel', [PaymentController::class, 'duesExcel'])->name('payments.dues.excel');
	Route::get('payments/dues', [PaymentController::class, 'dues'])->name('payments.dues');
	Route::get('payments/multiple', [PaymentController::class, 'multiple'])->name('payments.multiple');
	Route::get('payments/excel', [PaymentController::class, 'excel'])->name('payments.excel');
	Route::get('payments/{payment}/image', [PaymentController::class, 'image'])->name('payments.image');
	Route::resource('payments', PaymentController::class);

	Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
	Route::post('settings', [SettingController::class, 'update'])->name('settings.update');

	Route::get('expenses/excel', [ExpenseController::class, 'excel'])->name('expenses.excel');
	Route::get('expenses/index_cash', [ExpenseController::class, 'index_cash'])->name('expenses.index_cash');
	Route::get('expenses/excel_cash', [ExpenseController::class, 'excel_cash'])->name('expenses.excel_cash');
	Route::resource('expenses', ExpenseController::class);

	// Super Admin Dashboard Routes
	Route::middleware('role:superadmin')->prefix('superadmin')->name('superadmin.')->group(function () {
		Route::get('import/template', [\App\Http\Controllers\SuperAdmin\DataImportController::class, 'template'])->name('import.template');
		Route::get('companies', [\App\Http\Controllers\SuperAdmin\CompanyController::class, 'index'])->name('companies.index');
		Route::get('companies/create', [\App\Http\Controllers\SuperAdmin\CompanyController::class, 'create'])->name('companies.create');
		Route::post('companies', [\App\Http\Controllers\SuperAdmin\CompanyController::class, 'store'])->name('companies.store');
		Route::get('companies/{company}/edit', [\App\Http\Controllers\SuperAdmin\CompanyController::class, 'edit'])->name('companies.edit');
		Route::put('companies/{company}', [\App\Http\Controllers\SuperAdmin\CompanyController::class, 'update'])->name('companies.update');
		Route::post('companies/{company}/toggle-permission', [\App\Http\Controllers\SuperAdmin\CompanyController::class, 'togglePermission'])->name('companies.toggle-permission');
		Route::put('companies/{company}/toggle-status', [\App\Http\Controllers\SuperAdmin\CompanyController::class, 'toggleStatus'])->name('companies.toggle-status');
		Route::get('companies/{company}/import', [\App\Http\Controllers\SuperAdmin\DataImportController::class, 'show'])->name('companies.import');
		Route::post('companies/{company}/import', [\App\Http\Controllers\SuperAdmin\DataImportController::class, 'store'])->name('companies.import.store');

		Route::get('users', [\App\Http\Controllers\SuperAdmin\UserController::class, 'index'])->name('users.index');
		Route::get('users/create', [\App\Http\Controllers\SuperAdmin\UserController::class, 'create'])->name('users.create');
		Route::post('users', [\App\Http\Controllers\SuperAdmin\UserController::class, 'store'])->name('users.store');
		Route::get('users/{id}/edit', [\App\Http\Controllers\SuperAdmin\UserController::class, 'edit'])->name('users.edit');
		Route::put('users/{id}', [\App\Http\Controllers\SuperAdmin\UserController::class, 'update'])->name('users.update');
		Route::put('users/{id}/toggle-status', [\App\Http\Controllers\SuperAdmin\UserController::class, 'toggleStatus'])->name('users.toggle-status');
	});
});

Route::middleware('role:admin')->group(function () {
	Route::get('advisors/api', [AdvisorController::class, 'api'])->name('advisors.api');
	Route::put('advisors/drop/{id}', [AdvisorController::class, 'drop'])->name('advisors.drop');
	Route::put('advisors/up/{id}', [AdvisorController::class, 'up'])->name('advisors.up');
	Route::resource('advisors', AdvisorController::class)->except(['create', 'show']);

	Route::put('sellers/drop/{id}', [SellerController::class, 'drop'])->name('sellers.drop');
	Route::put('sellers/up/{id}', [SellerController::class, 'up'])->name('sellers.up');

	Route::get('sellers/{seller}/contracts/excel', [SellerController::class, 'contractsExcel'])->name('sellers.contracts.excel');
	Route::get('sellers/{seller}/overdue-contracts/excel', [SellerController::class, 'overdueContractsExcel'])->name('sellers.overdue-contracts.excel');
	Route::get('sellers/{seller}/contracts', [SellerController::class, 'contracts'])->name('sellers.contracts');
	Route::get('sellers/{seller}/overdue-contracts', [SellerController::class, 'overdueContracts'])->name('sellers.overdue-contracts');
	Route::resource('sellers', SellerController::class);

	Route::resource('transfers', TransferController::class);
	Route::resource('account-movements', AccountMovementController::class)->except(['show', 'create']);

	Route::get('interests/monthly', [InterestController::class, 'index'])->name('interests.monthly');

	Route::post('config/insurance', [ConfigController::class, 'insurance'])->name('config.insurance');

	Route::get('goals', [GoalController::class, 'index'])->name('goals.index');
	Route::post('goals', [GoalController::class, 'store'])->name('goals.store');
});
