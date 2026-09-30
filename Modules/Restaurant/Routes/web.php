<?php
	use Modules\Restaurant\Http\Controllers\AuthController;
	use Modules\Restaurant\Http\Controllers\CustomerController;
	use Modules\Restaurant\Http\Controllers\RestaurantSettingController;
	use Modules\Restaurant\Http\Controllers\ReservationController;
	use Modules\Restaurant\Http\Controllers\FloorController;
	use Modules\Restaurant\Http\Controllers\KitchenController;
	use Modules\Restaurant\Http\Controllers\MenuTypeController;
	use Modules\Restaurant\Http\Controllers\ModifierGroupController;
	use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
	use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;


	// 1. Determine if this is a SaaS Landlord environment
	$isSaaS = (bool) config('database.connections.saleprosaas_landlord');

	// 2. Build Tenancy middleware array dynamically
	$tenancyMiddleware = $isSaaS ? [InitializeTenancyByDomain::class, PreventAccessFromCentralDomains::class] : [];

	// ==========================================
	// ADMIN/COMMON ROUTES
	// ==========================================
	$middlewares = array_merge(
		$tenancyMiddleware,
		['common', 'auth', 'active', 'warehouse.access']
	);

	Route::prefix('restaurant')->name('restaurant.')->middleware($middlewares)->group(function () {

		// Route::prefix('setting')->group(function () {
		// 	Route::get('restaurant-setting', [RestaurantSettingController::class, 'index'])->name('setting.restaurant');
		// 	Route::post('restaurant-setting/update', [RestaurantSettingController::class, 'update'])->name('setting.restaurant.update');
		// });

		Route::prefix('floor')->group(function () {
			Route::get('/', [FloorController::class, 'index'])->name('floor.index');
			Route::post('/store', [FloorController::class, 'store'])->name('floor.store');
			Route::get('/edit/{id}', [FloorController::class, 'edit'])->name('floor.edit');
			Route::post('/update', [FloorController::class, 'update'])->name('floor.update');
			Route::get('/delete/{id}', [FloorController::class, 'destroy'])->name('floor.destroy');

			Route::get('/plan/{id}', [FloorController::class, 'floorplan'])->name('floor.plan');
			Route::post('/plan/update', [FloorController::class, 'updateFloorplan'])->name('floorplan.update');
		});

		Route::prefix('reservation')->group(function () {
			Route::get('/', [ReservationController::class, 'index'])->name('reservation.index');
			Route::post('/store', [ReservationController::class, 'store'])->name('reservation.store');
			Route::get('/edit/{id}', [ReservationController::class, 'edit'])->name('reservation.edit');
			Route::post('/update', [ReservationController::class, 'update'])->name('reservation.update');
			Route::get('/delete/{id}', [ReservationController::class, 'destroy'])->name('reservation.destroy');

			Route::post('/check', [ReservationController::class, 'check'])->name('reservation.check');

		});

		Route::prefix('kitchen')->group(function () {
			Route::get('/', [KitchenController::class, 'index'])->name('kitchen.index');
			Route::post('/store', [KitchenController::class, 'store'])->name('kitchen.store');
			Route::get('/edit/{id}', [KitchenController::class, 'edit'])->name('kitchen.edit');
			Route::post('/update', [KitchenController::class, 'update'])->name('kitchen.update');
			Route::get('/delete/{id}', [KitchenController::class, 'destroy'])->name('kitchen.destroy');
			Route::get('/dashboard', [KitchenController::class, 'dashboard'])->name('kitchen.dashboard');
			Route::get('/dashboard/data', [KitchenController::class, 'dashboardData'])->name('kitchen.dashboard.data');
			Route::post('/mark-preparing/{id}', [KitchenController::class, 'markItemPreparing'])->name('sale.status.preparing');
			Route::post('/mark-cooked/{id}', [KitchenController::class, 'markCooked'])->name('sale.status.cooked');
			Route::post('/mark-served/{id}', [KitchenController::class, 'markServed'])->name('sale.status.served');
		});

		Route::prefix('menu-type')->group(function () {
			Route::get('/', [MenuTypeController::class, 'index'])->name('menutype.index');
			Route::post('/store', [MenuTypeController::class, 'store'])->name('menutype.store');
			Route::get('/edit/{id}', [MenuTypeController::class, 'edit'])->name('menutype.edit');
			Route::post('/update', [MenuTypeController::class, 'update'])->name('menutype.update');
			Route::get('/delete/{id}', [MenuTypeController::class, 'destroy'])->name('menutype.destroy');
		});

		// ── Modifier Groups & Modifiers ────────────────────────────────────────────
		Route::prefix('modifier-group')->name('modifier-group.')->group(function () {
			// Modifier Groups CRUD
			Route::get('/', [ModifierGroupController::class, 'index'])->name('index');
			Route::get('/data', [ModifierGroupController::class, 'getData'])->name('data');
			Route::get('/product-search', [ModifierGroupController::class, 'productSearch'])->name('product-search');
			Route::post('/store', [ModifierGroupController::class, 'store'])->name('store');
			Route::get('/edit/{id}', [ModifierGroupController::class, 'edit'])->name('edit');
			Route::post('/update', [ModifierGroupController::class, 'update'])->name('update');
			Route::get('/delete/{id}', [ModifierGroupController::class, 'destroy'])->name('destroy');

			// Modifiers within a group
			Route::get('/{groupId}/modifiers', [ModifierGroupController::class, 'modifiers'])->name('modifiers');
			Route::post('/{groupId}/modifiers/store', [ModifierGroupController::class, 'storeModifier'])->name('modifier.store');
			Route::post('/{groupId}/modifiers/store-ajax', [ModifierGroupController::class, 'storeModifierAjax'])->name('modifier.store-ajax');
			Route::get('/{groupId}/modifiers/edit/{modifierId}', [ModifierGroupController::class, 'editModifier'])->name('modifier.edit');
			Route::post('/{groupId}/modifiers/update', [ModifierGroupController::class, 'updateModifier'])->name('modifier.update');
			Route::get('/{groupId}/modifiers/delete/{modifierId}', [ModifierGroupController::class, 'destroyModifier'])->name('modifier.destroy');

			// Product assignments
			Route::get('/{groupId}/products', [ModifierGroupController::class, 'products'])->name('products');
			Route::post('/assign-product', [ModifierGroupController::class, 'assignProduct'])->name('assign-product');
			Route::post('/unassign-product', [ModifierGroupController::class, 'unassignProduct'])->name('unassign-product');
			Route::get('/product-config', [ModifierGroupController::class, 'getProductModifierConfig'])->name('product-config');
		});

	});
