@extends('backend.layout.main')
@section('content')

	@php
		$permissions_data = include(resource_path('views/backend/role/permission_data.php'));
		$module_permissions = [
			'repair_module' => 'Repair',
			'manufacturing_module' => 'Manufacturing',
			'restaurant_module' => 'Restaurant',
			'tailoring_module' => 'Tailoring',
			'social_commerce_module' => 'SocialCommerce',
			'project_module' => 'Project',
			'ai_assistant_module' => 'AIAssistant',
		];
		$module_present = collect($module_permissions)
			->mapWithKeys(fn ($moduleName, $groupKey) => [$groupKey => is_dir(base_path("Modules/{$moduleName}"))])
			->all();
	@endphp

	<error-message key="not_permitted" />

	<section class="forms">
		<div class="container-fluid">
			<div class="row">
				<div class="col-md-12">
					<div class="card">
						<div class="card-header d-flex align-items-center">
							<h4>{{__('db.Group Permission')}}</h4>
						</div>
						<form action="{{ route('role.setPermission') }}" method="POST">
							@csrf
							<div class="card-body">
								<input type="hidden" name="role_id" value="{{$lims_role_data->id}}" />
								<ul class="nav nav-tabs" id="permissionTabs" role="tablist">
									<li class="nav-item">
										<a class="nav-link active" id="core-tab" data-toggle="tab" href="#core" role="tab"
											aria-controls="core" aria-selected="true"><i class="fa fa-cubes"></i> Core</a>
									</li>
									<li class="nav-item">
										<a class="nav-link" id="modules-tab" data-toggle="tab" href="#modules" role="tab"
											aria-controls="modules" aria-selected="false"><i class="fa fa-puzzle-piece"></i>
											Modules</a>
									</li>
									<li class="nav-item">
										<a class="nav-link" id="reports-tab" data-toggle="tab" href="#reports" role="tab"
											aria-controls="reports" aria-selected="false"><i class="fa fa-line-chart"></i>
											Reports</a>
									</li>
									<li class="nav-item">
										<a class="nav-link" id="settings-tab" data-toggle="tab" href="#settings" role="tab"
											aria-controls="settings" aria-selected="false"><i class="fa fa-cogs"></i> System
											& Settings</a>
									</li>
								</ul>
								<div class="tab-content" id="permissionTabsContent">
									<div class="tab-pane fade show active" id="core" role="tabpanel"
										aria-labelledby="core-tab">
										<div class="table-responsive">
											<table class="table table-bordered permission-table mt-3">
												<thead>
													<tr>
														<th rowspan="2" class="text-center">{{__('db.name')}}</th>
														<th colspan="6" class="text-center">
															<div class="custom-control custom-switch">
																<input type="checkbox"
																	class="custom-control-input section-select-all"
																	id="select_all_core">
																<label class="custom-control-label"
																	for="select_all_core">{{__('db.Select All')}}</label>
															</div>
														</th>
													</tr>
													<tr>
														<th class="text-center">{{__('db.List')}}</th>
														<th class="text-center">{{__('db.View')}}</th>
														<th class="text-center">{{__('db.add')}}</th>
														<th class="text-center">{{__('db.edit')}}</th>
														<th class="text-center">{{__('db.delete')}}</th>
														<th class="text-center">{{__('db.import')}}</th>
													</tr>
												</thead>
												<tbody>
													@php
														$permissions = ['index', 'view', 'add', 'edit', 'delete', 'import'];
													@endphp
													<tr>
														<td>{{ __('db.category')}}</td>
														@foreach ($permissions as $perm)
															@php $key = "categories-{$perm}"; @endphp
															<td class="text-center">
																<div class="">
																	<input type="checkbox" value="1" id="{{ $key }}"
																		name="{{ $key }}" {{ in_array($key, $all_permission) ? 'checked' : '' }} readonly>
																	<label for="{{ $key }}"></label>
																</div>
															</td>
														@endforeach
													</tr>

													<tr>
														<td>{{ __('db.product') }}</td>
														@foreach ($permissions as $perm)
															@php $key = "products-{$perm}"; @endphp
															<td class="text-center">
																<div class="">
																	<input type="checkbox" value="1" id="{{ $key }}"
																		name="{{ $key }}" {{ in_array($key, $all_permission) ? 'checked' : '' }}>
																	<label for="{{ $key }}"></label>
																</div>
															</td>
														@endforeach
													</tr>

													<tr>
														<td>{{ __('db.Purchase') }}</td>
														@foreach ($permissions as $perm)
															@php $key = "purchases-{$perm}"; @endphp
															<td class="text-center">
																<div class="">
																	<input type="checkbox" value="1" id="{{ $key }}"
																		name="{{ $key }}" {{ in_array($key, $all_permission) ? 'checked' : '' }}>
																	<label for="{{ $key }}"></label>
																</div>
															</td>
														@endforeach
													</tr>


													<tr>
														<td>{{__('db.Purchase Payment')}}</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("purchase-payment-index", $all_permission))
																		<input type="checkbox" value="1"
																			id="purchase-payment-index"
																			name="purchase-payment-index" checked />
																	@else
																		<input type="checkbox" value="1"
																			id="purchase-payment-index"
																			name="purchase-payment-index">
																	@endif
																	<label for="purchase-payment-index"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("purchase-payment-view", $all_permission))
																		<input type="checkbox" value="1"
																			id="purchase-payment-view"
																			name="purchase-payment-view" checked />
																	@else
																		<input type="checkbox" value="1"
																			id="purchase-payment-view"
																			name="purchase-payment-view">
																	@endif
																	<label for="purchase-payment-view"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("purchase-payment-add", $all_permission))
																		<input type="checkbox" value="1"
																			id="purchase-payment-add"
																			name="purchase-payment-add" checked />
																	@else
																		<input type="checkbox" value="1"
																			id="purchase-payment-add"
																			name="purchase-payment-add">
																	@endif
																	<label for="purchase-payment-add"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("purchase-payment-edit", $all_permission))
																		<input type="checkbox" value="1"
																			id="purchase-payment-edit"
																			name="purchase-payment-edit" checked>
																	@else
																		<input type="checkbox" value="1"
																			id="purchase-payment-edit"
																			name="purchase-payment-edit">
																	@endif
																	<label for="purchase-payment-edit"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("purchase-payment-delete", $all_permission))
																		<input type="checkbox" value="1"
																			id="purchase-payment-delete"
																			name="purchase-payment-delete" checked>
																	@else
																		<input type="checkbox" value="1"
																			id="purchase-payment-delete"
																			name="purchase-payment-delete">
																	@endif
																	<label for="purchase-payment-delete"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
															</div>
														</td>
													</tr>

													<tr>
														<td>{{ __('db.Sale') }}</td>
														@foreach ($permissions as $perm)
															@php $key = "sales-{$perm}"; @endphp
															<td class="text-center">
																<div class="">
																	<input type="checkbox" value="1" id="{{ $key }}"
																		name="{{ $key }}" {{ in_array($key, $all_permission) ? 'checked' : '' }}>
																	<label for="{{ $key }}"></label>
																</div>
															</td>
														@endforeach
													</tr>


													<tr>
														<td>{{__('db.Sale Payment')}}</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("sale-payment-index", $all_permission))
																		<input type="checkbox" value="1" id="sale-payment-index"
																			name="sale-payment-index" checked />
																	@else
																		<input type="checkbox" value="1" id="sale-payment-index"
																			name="sale-payment-index">
																	@endif
																	<label for="sale-payment-index"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("sale-payment-view", $all_permission))
																		<input type="checkbox" value="1" id="sale-payment-view"
																			name="sale-payment-view" checked />
																	@else
																		<input type="checkbox" value="1" id="sale-payment-view"
																			name="sale-payment-view">
																	@endif
																	<label for="sale-payment-view"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("sale-payment-add", $all_permission))
																		<input type="checkbox" value="1" id="sale-payment-add"
																			name="sale-payment-add" checked />
																	@else
																		<input type="checkbox" value="1" id="sale-payment-add"
																			name="sale-payment-add">
																	@endif
																	<label for="sale-payment-add"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("sale-payment-edit", $all_permission))
																		<input type="checkbox" value="1" id="sale-payment-edit"
																			name="sale-payment-edit" checked>
																	@else
																		<input type="checkbox" value="1" id="sale-payment-edit"
																			name="sale-payment-edit">
																	@endif
																	<label for="sale-payment-edit"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("sale-payment-delete", $all_permission))
																		<input type="checkbox" value="1"
																			id="sale-payment-delete" name="sale-payment-delete"
																			checked>
																	@else
																		<input type="checkbox" value="1"
																			id="sale-payment-delete" name="sale-payment-delete">
																	@endif
																	<label for="sale-payment-delete"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
															</div>
														</td>
													</tr>

													<tr class="expense-row">
														<td>{{__('db.Expense')}}</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("expenses-index", $all_permission))
																		<input type="checkbox" value="1" id="expenses-index"
																			name="expenses-index" checked />
																	@else
																		<input type="checkbox" value="1" id="expenses-index"
																			name="expenses-index">
																	@endif
																	<label for="expenses-index"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("expenses-view", $all_permission))
																		<input type="checkbox" value="1" id="expenses-view"
																			name="expenses-view" checked />
																	@else
																		<input type="checkbox" value="1" id="expenses-view"
																			name="expenses-view">
																	@endif
																	<label for="expenses-view"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("expenses-add", $all_permission))
																		<input type="checkbox" value="1" id="expenses-add"
																			name="expenses-add" checked />
																	@else
																		<input type="checkbox" value="1" id="expenses-add"
																			name="expenses-add">
																	@endif
																	<label for="expenses-add"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("expenses-edit", $all_permission))
																		<input type="checkbox" value="1" id="expenses-edit"
																			name="expenses-edit" checked>
																	@else
																		<input type="checkbox" value="1" id="expenses-edit"
																			name="expenses-edit">
																	@endif
																	<label for="expenses-edit"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("expenses-delete", $all_permission))
																		<input type="checkbox" value="1" id="expenses-delete"
																			name="expenses-delete" checked>
																	@else
																		<input type="checkbox" value="1" id="expenses-delete"
																			name="expenses-delete">
																	@endif
																	<label for="expenses-delete"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
															</div>
														</td>
													</tr>
													<tr class="income-row">
														<td>{{__('db.Income')}}</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("incomes-index", $all_permission))
																		<input type="checkbox" value="1" id="incomes-index"
																			name="incomes-index" checked />
																	@else
																		<input type="checkbox" value="1" id="incomes-index"
																			name="incomes-index">
																	@endif
																	<label for="incomes-index"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("incomes-view", $all_permission))
																		<input type="checkbox" value="1" id="incomes-view"
																			name="incomes-view" checked />
																	@else
																		<input type="checkbox" value="1" id="incomes-view"
																			name="incomes-view">
																	@endif
																	<label for="incomes-view"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("incomes-add", $all_permission))
																		<input type="checkbox" value="1" id="incomes-add"
																			name="incomes-add" checked />
																	@else
																		<input type="checkbox" value="1" id="incomes-add"
																			name="incomes-add">
																	@endif
																	<label for="incomes-add"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("incomes-edit", $all_permission))
																		<input type="checkbox" value="1" id="incomes-edit"
																			name="incomes-edit" checked>
																	@else
																		<input type="checkbox" value="1" id="incomes-edit"
																			name="incomes-edit">
																	@endif
																	<label for="incomes-edit"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("incomes-delete", $all_permission))
																		<input type="checkbox" value="1" id="incomes-delete"
																			name="incomes-delete" checked>
																	@else
																		<input type="checkbox" value="1" id="incomes-delete"
																			name="incomes-delete">
																	@endif
																	<label for="incomes-delete"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
															</div>
														</td>
													</tr>
													<tr class="quotation-row">
														<td>{{__('db.quotation')}}</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("quotes-index", $all_permission))
																		<input type="checkbox" value="1" id="quotes-index"
																			name="quotes-index" checked>
																	@else
																		<input type="checkbox" value="1" id="quotes-index"
																			name="quotes-index">
																	@endif
																	<label for="quotes-index"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("quotes-view", $all_permission))
																		<input type="checkbox" value="1" id="quotes-view"
																			name="quotes-view" checked>
																	@else
																		<input type="checkbox" value="1" id="quotes-view"
																			name="quotes-view">
																	@endif
																	<label for="quotes-view"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("quotes-add", $all_permission))
																		<input type="checkbox" value="1" id="quotes-add"
																			name="quotes-add" checked>
																	@else
																		<input type="checkbox" value="1" id="quotes-add"
																			name="quotes-add">
																	@endif
																	<label for="quotes-add"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("quotes-edit", $all_permission))
																		<input type="checkbox" value="1" id="quotes-edit"
																			name="quotes-edit" checked>
																	@else
																		<input type="checkbox" value="1" id="quotes-edit"
																			name="quotes-edit">
																	@endif
																	<label for="quotes-edit"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("quotes-delete", $all_permission))
																		<input type="checkbox" value="1" id="quotes-delete"
																			name="quotes-delete" checked>
																	@else
																		<input type="checkbox" value="1" id="quotes-delete"
																			name="quotes-delete">
																	@endif
																	<label for="quotes-delete"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
															</div>
														</td>
													</tr>

													<tr class="transfer-row">
														<td>{{ __('db.Transfer') }}</td>
														@foreach ($permissions as $perm)
															@php $key = "transfers-{$perm}"; @endphp
															<td class="text-center">
																<div class="">
																	<input type="checkbox" value="1" id="{{ $key }}"
																		name="{{ $key }}" {{ in_array($key, $all_permission) ? 'checked' : '' }}>
																	<label for="{{ $key }}"></label>
																</div>
															</td>
														@endforeach
													</tr>


													<tr class="sale-return-row">
														<td>{{__('db.Sale Return')}}</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("returns-index", $all_permission))
																		<input type="checkbox" value="1" id="returns-index"
																			name="returns-index" checked>
																	@else
																		<input type="checkbox" value="1" id="returns-index"
																			name="returns-index">
																	@endif
																	<label for="returns-index"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("returns-view", $all_permission))
																		<input type="checkbox" value="1" id="returns-view"
																			name="returns-view" checked>
																	@else
																		<input type="checkbox" value="1" id="returns-view"
																			name="returns-view">
																	@endif
																	<label for="returns-view"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("returns-add", $all_permission))
																		<input type="checkbox" value="1" id="returns-add"
																			name="returns-add" checked>
																	@else
																		<input type="checkbox" value="1" id="returns-add"
																			name="returns-add">
																	@endif
																	<label for="returns-add"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("returns-edit", $all_permission))
																		<input type="checkbox" value="1" id="returns-edit"
																			name="returns-edit" checked>
																	@else
																		<input type="checkbox" value="1" id="returns-edit"
																			name="returns-edit">
																	@endif
																	<label for="returns-edit"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("returns-delete", $all_permission))
																		<input type="checkbox" value="1" id="returns-delete"
																			name="returns-delete" checked>
																	@else
																		<input type="checkbox" value="1" id="returns-delete"
																			name="returns-delete">
																	@endif
																	<label for="returns-delete"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
															</div>
														</td>
													</tr>

													<tr class="sale-exchange-row">
														<td>{{__('db.Sale Exchange')}}</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("exchange-index", $all_permission))
																		<input type="checkbox" value="1" id="exchange-index"
																			name="exchange-index" checked>
																	@else
																		<input type="checkbox" value="1" id="exchange-index"
																			name="exchange-index">
																	@endif
																	<label for="exchange-index"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("exchange-view", $all_permission))
																		<input type="checkbox" value="1" id="exchange-view"
																			name="exchange-view" checked>
																	@else
																		<input type="checkbox" value="1" id="exchange-view"
																			name="exchange-view">
																	@endif
																	<label for="exchange-view"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("exchange-add", $all_permission))
																		<input type="checkbox" value="1" id="exchange-add"
																			name="exchange-add" checked>
																	@else
																		<input type="checkbox" value="1" id="exchange-add"
																			name="exchange-add">
																	@endif
																	<label for="exchange-add"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("exchange-edit", $all_permission))
																		<input type="checkbox" value="1" id="exchange-edit"
																			name="exchange-edit" checked>
																	@else
																		<input type="checkbox" value="1" id="exchange-edit"
																			name="exchange-edit">
																	@endif
																	<label for="exchange-edit"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("exchange-delete", $all_permission))
																		<input type="checkbox" value="1" id="exchange-delete"
																			name="exchange-delete" checked>
																	@else
																		<input type="checkbox" value="1" id="exchange-delete"
																			name="exchange-delete">
																	@endif
																	<label for="exchange-delete"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
															</div>
														</td>
													</tr>

													<tr class="purchase-return-row">
														<td>{{__('db.Purchase Return')}}</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("purchase-return-index", $all_permission))
																		<input type="checkbox" value="1"
																			id="purchase-return-index"
																			name="purchase-return-index" checked>
																	@else
																		<input type="checkbox" value="1"
																			id="purchase-return-index"
																			name="purchase-return-index">
																	@endif
																	<label for="purchase-return-index"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("purchase-return-view", $all_permission))
																		<input type="checkbox" value="1"
																			id="purchase-return-view"
																			name="purchase-return-view" checked>
																	@else
																		<input type="checkbox" value="1"
																			id="purchase-return-view"
																			name="purchase-return-view">
																	@endif
																	<label for="purchase-return-view"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("purchase-return-add", $all_permission))
																		<input type="checkbox" value="1"
																			id="purchase-return-add" name="purchase-return-add"
																			checked>
																	@else
																		<input type="checkbox" value="1"
																			id="purchase-return-add" name="purchase-return-add">
																	@endif
																	<label for="purchase-return-add"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("purchase-return-edit", $all_permission))
																		<input type="checkbox" value="1"
																			id="purchase-return-edit"
																			name="purchase-return-edit" checked>
																	@else
																		<input type="checkbox" value="1"
																			id="purchase-return-edit"
																			name="purchase-return-edit">
																	@endif
																	<label for="purchase-return-edit"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("purchase-return-delete", $all_permission))
																		<input type="checkbox" value="1"
																			id="purchase-return-delete"
																			name="purchase-return-delete" checked>
																	@else
																		<input type="checkbox" value="1"
																			id="purchase-return-delete"
																			name="purchase-return-delete">
																	@endif
																	<label for="purchase-return-delete"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
															</div>
														</td>
													</tr>
													<tr class="employee-row">
														<td>{{__('db.Employee')}}</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("employees-index", $all_permission))
																		<input type="checkbox" value="1" id="employees-index"
																			name="employees-index" checked>
																	@else
																		<input type="checkbox" value="1" id="employees-index"
																			name="employees-index">
																	@endif
																	<label for="employees-index"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("employees-view", $all_permission))
																		<input type="checkbox" value="1" id="employees-view"
																			name="employees-view" checked>
																	@else
																		<input type="checkbox" value="1" id="employees-view"
																			name="employees-view">
																	@endif
																	<label for="employees-view"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("employees-add", $all_permission))
																		<input type="checkbox" value="1" id="employees-add"
																			name="employees-add" checked>
																	@else
																		<input type="checkbox" value="1" id="employees-add"
																			name="employees-add">
																	@endif
																	<label for="employees-add"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("employees-edit", $all_permission))
																		<input type="checkbox" value="1" id="employees-edit"
																			name="employees-edit" checked>
																	@else
																		<input type="checkbox" value="1" id="employees-edit"
																			name="employees-edit">
																	@endif
																	<label for="employees-edit"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("employees-delete", $all_permission))
																		<input type="checkbox" value="1" id="employees-delete"
																			name="employees-delete" checked>
																	@else
																		<input type="checkbox" value="1" id="employees-delete"
																			name="employees-delete">
																	@endif
																	<label for="employees-delete"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
															</div>
														</td>
													</tr>
													<tr>
														<td>{{__('db.User')}}</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("users-index", $all_permission))
																		<input type="checkbox" value="1" id="users-index"
																			name="users-index" checked>
																	@else
																		<input type="checkbox" value="1" id="users-index"
																			name="users-index">
																	@endif
																	<label for="users-index"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("users-view", $all_permission))
																		<input type="checkbox" value="1" id="users-view"
																			name="users-view" checked>
																	@else
																		<input type="checkbox" value="1" id="users-view"
																			name="users-view">
																	@endif
																	<label for="users-view"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("users-add", $all_permission))
																		<input type="checkbox" value="1" id="users-add"
																			name="users-add" checked>
																	@else
																		<input type="checkbox" value="1" id="users-add"
																			name="users-add">
																	@endif
																	<label for="users-add"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("users-edit", $all_permission))
																		<input type="checkbox" value="1" id="users-edit"
																			name="users-edit" checked>
																	@else
																		<input type="checkbox" value="1" id="users-edit"
																			name="users-edit">
																	@endif
																	<label for="users-edit"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
																<div class="">
																	@if(in_array("users-delete", $all_permission))
																		<input type="checkbox" value="1" id="users-delete"
																			name="users-delete" checked>
																	@else
																		<input type="checkbox" value="1" id="users-delete"
																			name="users-delete">
																	@endif
																	<label for="users-delete"></label>
																</div>
															</div>
														</td>
														<td class="text-center">
															<div class="">
															</div>
														</td>
													</tr>

													<tr>
														<td>{{ __('db.customer') }}</td>
														@foreach ($permissions as $perm)
															@php $key = "customers-{$perm}"; @endphp
															<td class="text-center">
																<div class="">
																	<input type="checkbox" value="1" id="{{ $key }}"
																		name="{{ $key }}" {{ in_array($key, $all_permission) ? 'checked' : '' }}>
																	<label for="{{ $key }}"></label>
																</div>
															</td>
														@endforeach
													</tr>

													<tr>
														<td>{{ __('db.Biller') }}</td>
														@foreach ($permissions as $perm)
															@php $key = "billers-{$perm}"; @endphp
															<td class="text-center">
																<div class="">
																	<input type="checkbox" value="1" id="{{ $key }}"
																		name="{{ $key }}" {{ in_array($key, $all_permission) ? 'checked' : '' }}>
																	<label for="{{ $key }}"></label>
																</div>
															</td>
														@endforeach
													</tr>

													<!-- @php
										$permissions = ['index', 'view', '', 'edit', 'delete'];
									@endphp
									<tr>
										<td>whatsapp</td>
										@foreach ($permissions as $perm)
											@if ($perm !== '')
												@php $key = "whatsapp-{$perm}"; @endphp
												<td class="text-center">
													<div class="">
														<input type="checkbox"
															value="1"
															id="{{ $key }}"
															name="{{ $key }}"
															{{ in_array($key, $all_permission) ? 'checked' : '' }}>
														<label for="{{ $key }}"></label>
													</div>
												</td>
											@else
												<td class="text-center"></td>
											@endif
										@endforeach
									</tr> -->
													@php
														$permissions = ['index', 'view', 'add', 'edit', 'delete'];
													@endphp
													<tr>
														<td>{{ __('db.Supplier') }}</td>
														@foreach ($permissions as $perm)
															@if ($perm !== '')
																@php $key = "suppliers-{$perm}"; @endphp
																<td class="text-center">
																	<div class="">
																		<input type="checkbox" value="1" id="{{ $key }}"
																			name="{{ $key }}" {{ in_array($key, $all_permission) ? 'checked' : '' }}>
																		<label for="{{ $key }}"></label>
																	</div>
																</td>
															@else
																<td class="text-center"></td>
															@endif
														@endforeach
													</tr>

													{{-- import sidebar permissions data --}}
													@php
														$sidebar_permissions = $permissions_data['sidebar_permissions'];
													@endphp
												</tbody>
												<!-- ENDCORE -->
											</table>
										</div>
									</div>
									<div class="tab-pane fade" id="modules" role="tabpanel" aria-labelledby="modules-tab">
										<div class="table-responsive">
											<table class="table table-bordered permission-table mt-3">
												<thead>
													<tr>
														<th rowspan="2" class="text-center">{{__('db.name')}}</th>
														<th colspan="6" class="text-center">
															<div class="custom-control custom-switch">
																<input type="checkbox"
																	class="custom-control-input section-select-all"
																	id="select_all_modules">
																<label class="custom-control-label"
																	for="select_all_modules">{{__('db.Select All')}}</label>
															</div>
														</th>
													</tr>
													<tr>
													</tr>
												</thead>
												<tbody>
													<tr>
														<td>{{__('db.Sidebar')}}</td>
														<td class="report-permissions" colspan="5">
															@foreach($sidebar_permissions as $key => $label)
																<span>
																	<div>
																		<div class="">
																			@if(in_array($key, $all_permission))
																				<input type="checkbox" value="1" id={{$key}}
																					name={{$key}} checked>
																			@else
																				<input type="checkbox" value="1" id={{$key}}
																					name={{$key}}>
																			@endif
																			<label for={{$key}} class="padding05">{{__('db.' . $label)}} &nbsp;&nbsp;</label>
																		</div>
																	</div>
																</span>
															@endforeach
														</td>
													</tr>


													{{-- REPAIR MODULE PERMISSIONS --}}
													@if($module_present['repair_module'])
													@php $repair_module = $permissions_data['repair_module'] @endphp
													<tr class="repair-row">
														<td>{{ __('db.Repair') }}</td>
														<td class="report-permissions" colspan="5">
															@foreach($repair_module as $key => $label)
																<span>
																	<div aria-checked="false" aria-disabled="false">
																		<div class="custom-control custom-switch permission-switch">
															@if(in_array($key, $all_permission))
																<input class="custom-control-input" type="checkbox" value="{{ $key }}" id="{{ $key }}"
																	name="permissions[]" checked>
															@else
																<input class="custom-control-input" type="checkbox" value="{{ $key }}" id="{{ $key }}"
																	name="permissions[]">
																			@endif
																			<label for={{$key}} class="custom-control-label padding05">{{__('db.' . $label)}} &nbsp;&nbsp;</label>
																		</div>
																	</div>
																</span>
															@endforeach
														</td>
													</tr>
													@endif

													{{-- Manufacturing MODULE PERMISSIONS --}}
													@if($module_present['manufacturing_module'])
													@php $manufacturing_module = $permissions_data['manufacturing_module'] @endphp
													<tr class="manufacturing-row">
														<td>{{ __('db.Manufacturing') }}</td>
														<td class="report-permissions" colspan="5">
															@foreach($manufacturing_module as $key => $label)
																<span>
																	<div aria-checked="false" aria-disabled="false">
																		<div class="custom-control custom-switch permission-switch">
																			@if(in_array($key, $all_permission))
																				<input class="custom-control-input" type="checkbox" value="1" id={{$key}}
																					name={{$key}} checked>
																			@else
																				<input class="custom-control-input" type="checkbox" value="1" id={{$key}}
																					name={{$key}}>
																			@endif
																			<label for={{$key}} class="padding05">{{__('db.' . $label)}} &nbsp;&nbsp;</label>
																		</div>
																	</div>
																</span>
															@endforeach
														</td>
													</tr>
													@endif

													{{-- Restaurant MODULE PERMISSIONS --}}
													@if($module_present['restaurant_module'])
													@php $restaurant_module = $permissions_data['restaurant_module'] @endphp
													<tr class="restaurant-row">
														<td>{{ __('db.restaurant') }}</td>
														<td class="report-permissions" colspan="5">
															@foreach($restaurant_module as $key => $label)
																<span>
																	<div aria-checked="false" aria-disabled="false">
																		<div class="custom-control custom-switch permission-switch">
																			@if(in_array($key, $all_permission))
																				<input class="custom-control-input" type="checkbox" value="1" id={{$key}}
																					name={{$key}} checked>
																			@else
																				<input class="custom-control-input" type="checkbox" value="1" id={{$key}}
																					name={{$key}}>
																			@endif
																			<label for={{$key}} class="custom-control-label padding05">{{ __('db.' . $label) }} &nbsp;&nbsp;</label>
																		</div>
																	</div>
																</span>
															@endforeach
														</td>
													</tr>
													@endif

													{{-- Tailoring MODULE PERMISSIONS --}}
													@if($module_present['tailoring_module'])
													@php $tailoring_module = $permissions_data['tailoring_module'] @endphp
													<tr class="tailoring-row">
														<td>{{ __('db.tailoring') }}</td>
														<td class="report-permissions" colspan="5">
															@foreach($tailoring_module as $key => $label)
																<span>
																	<div aria-checked="false" aria-disabled="false">
																		<div class="custom-control custom-switch permission-switch">
																						@if(in_array($key, $all_permission))
																							<input class="custom-control-input" type="checkbox" value="{{ $key }}" id="{{ $key }}"
																								name="permissions[]" checked>
																						@else
																							<input class="custom-control-input" type="checkbox" value="{{ $key }}" id="{{ $key }}"
																								name="permissions[]">
																			@endif
																			<label for={{$key}} class="custom-control-label padding05">{{ __('db.' . $label) }} &nbsp;&nbsp;</label>
																		</div>
																	</div>
																</span>
															@endforeach
														</td>
													</tr>
													@endif

													{{-- Social Commerce MODULE PERMISSIONS --}}
													@if($module_present['social_commerce_module'])
													@php $social_commerce_module = $permissions_data['social_commerce_module'] @endphp
													<tr class="social-commerce-row">
														<td>{{ __('db.Social Commerce') }}</td>
														<td class="report-permissions" colspan="5">
															@foreach($social_commerce_module as $key => $label)
																<span>
																	<div aria-checked="false" aria-disabled="false">
																		<div class="custom-control custom-switch permission-switch">
																			@php $inputId = 'permission_' . str_replace(' ', '_', $key); @endphp
																			@if(in_array($key, $all_permission))
																				<input class="custom-control-input" type="checkbox" value="{{ $key }}" id="{{ $inputId }}" name="permissions[]" checked>
																			@else
																				<input class="custom-control-input" type="checkbox" value="{{ $key }}" id="{{ $inputId }}" name="permissions[]">
																			@endif
																			<label for="{{ $inputId }}" class="custom-control-label padding05">{{ __('db.' . $label) }} &nbsp;&nbsp;</label>
																		</div>
																	</div>
																</span>
															@endforeach
														</td>
													</tr>
													@endif

													{{-- PROJECT MODULE PERMISSIONS --}}
													@if($module_present['project_module'])
													@php $project_module = $permissions_data['project_module'] @endphp
													<tr class="project-row">
														<td>{{ __('db.Project Module') }}</td>
														<td class="report-permissions" colspan="5">
															@foreach($project_module as $key => $label)
																<span>
																	<div aria-checked="false" aria-disabled="false">
																		<div class="custom-control custom-switch permission-switch">
																			@if(in_array($key, $all_permission))
																				<input class="custom-control-input" type="checkbox" value="1" id={{$key}}
																					name={{$key}} checked>
																			@else
																				<input class="custom-control-input" type="checkbox" value="1" id={{$key}}
																					name={{$key}}>
																			@endif
																			<label for={{$key}} class="custom-control-label padding05">{{__('db.' . $label)}} &nbsp;&nbsp;</label>
																		</div>
																	</div>
																</span>
															@endforeach
														</td>
													</tr>
													@endif

												</tbody>
												<!-- ENDMOD -->
											</table>
										</div>
									</div>
									<div class="tab-pane fade" id="reports" role="tabpanel" aria-labelledby="reports-tab">
										<div class="table-responsive">
											<table class="table table-bordered permission-table mt-3">
												<thead>
													<tr>
														<th rowspan="2" class="text-center">{{__('db.name')}}</th>
														<th colspan="6" class="text-center">
															<div class="custom-control custom-switch">
																<input type="checkbox"
																	class="custom-control-input section-select-all"
																	id="select_all_reports">
																<label class="custom-control-label"
																	for="select_all_reports">{{__('db.Select All')}}</label>
															</div>
														</th>
													</tr>
													<tr>
													</tr>
												</thead>
												<tbody>
													<tr>
														<td>{{__('db.dashboard')}}</td>
														<td class="report-permissions" colspan="5">
															<span>
																<div>
																	<div class="">
																		@if(in_array("revenue_profit_summary", $all_permission))
																			<input type="checkbox" value="1"
																				id="revenue_profit_summary"
																				name="revenue_profit_summary" checked>
																		@else
																			<input type="checkbox" value="1"
																				id="revenue_profit_summary"
																				name="revenue_profit_summary">
																		@endif
																		<label for="revenue_profit_summary"
																			class="padding05">{{__('db.Revenue and Profit Summary')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("cash_flow", $all_permission))
																			<input type="checkbox" value="1" id="cash_flow"
																				name="cash_flow" checked>
																		@else
																			<input type="checkbox" value="1" id="cash_flow"
																				name="cash_flow">
																		@endif
																		<label for="cash_flow"
																			class="padding05">{{__('db.Cash Flow')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("monthly_summary", $all_permission))
																			<input type="checkbox" value="1"
																				id="monthly_summary" name="monthly_summary"
																				checked>
																		@else
																			<input type="checkbox" value="1"
																				id="monthly_summary" name="monthly_summary">
																		@endif
																		<label for="monthly_summary"
																			class="padding05">{{__('db.Monthly Summary')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("yearly_report", $all_permission))
																			<input type="checkbox" value="1" id="yearly_report"
																				name="yearly_report" checked>
																		@else
																			<input type="checkbox" value="1" id="yearly_report"
																				name="yearly_report">
																		@endif
																		<label for="yearly_report"
																			class="padding05">{{__('db.yearly report')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
														</td>
													</tr>
													<tr>
														<td>{{__('db.POS')}}</td>
														<td class="report-permissions" colspan="5">
															<span>
																<div>
																	<div class="">
																		@if(in_array("cart-product-update", $all_permission))
																			<input type="checkbox" value="1"
																				id="cart-product-update"
																				name="cart-product-update" checked>
																		@else
																			<input type="checkbox" value="1"
																				id="cart-product-update"
																				name="cart-product-update">
																		@endif
																		<label for="cart-product-update"
																			class="padding05">{{__('db.Cart Product Update')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("handle_discount", $all_permission))
																			<input type="checkbox" value="1"
																				id="handle_discount" name="handle_discount"
																				checked>
																		@else
																			<input type="checkbox" value="1"
																				id="handle_discount" name="handle_discount">
																		@endif
																		<label for="handle_discount"
																			class="padding05">{{__('db.Handle Discount')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
														</td>
													</tr>
													<tr>
														<td>{{__('db.Exports')}}</td>
														<td class="report-permissions" colspan="5">
															<span>
																<div>
																	<div class="">
																		@if(in_array("product_export", $all_permission))
																			<input type="checkbox" value="1" id="product_export"
																				name="product_export" checked>
																		@else
																			<input type="checkbox" value="1" id="product_export"
																				name="product_export">
																		@endif
																		<label for="product_export"
																			class="padding05">{{__('db.Product Export')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("purchase_export", $all_permission))
																			<input type="checkbox" value="1"
																				id="purchase_export" name="purchase_export"
																				checked>
																		@else
																			<input type="checkbox" value="1"
																				id="purchase_export" name="purchase_export">
																		@endif
																		<label for="purchase_export"
																			class="padding05">{{__('db.Purchase Export')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("sale_export", $all_permission))
																			<input type="checkbox" value="1" id="sale_export"
																				name="sale_export" checked>
																		@else
																			<input type="checkbox" value="1" id="sale_export"
																				name="sale_export">
																		@endif
																		<label for="sale_export"
																			class="padding05">{{__('db.Sale Export')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("customer_export", $all_permission))
																			<input type="checkbox" value="1"
																				id="customer_export" name="customer_export"
																				checked>
																		@else
																			<input type="checkbox" value="1"
																				id="customer_export" name="customer_export">
																		@endif
																		<label for="customer_export"
																			class="padding05">{{__('db.Customer Export')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
														</td>
													</tr>
													<tr class="accounting-row">
														<td>{{__('db.Accounting')}}</td>
														<td class="report-permissions" colspan="5">
															<span>
																<div>
																	<div class="">
																		@if(in_array("account-index", $all_permission))
																			<input type="checkbox" value="1" id="account-index"
																				name="account-index" checked>
																		@else
																			<input type="checkbox" value="1" id="account-index"
																				name="account-index">
																		@endif
																		<label for="account-index"
																			class="padding05">{{__('db.Account')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("account-add", $all_permission))
																			<input type="checkbox" value="1" id="account-add"
																				name="account-add" checked>
																		@else
																			<input type="checkbox" value="1" id="account-add"
																				name="account-add">
																		@endif
																		<label for="account-add"
																			class="padding05">{{__('db.Add Account')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("chart-of-accounts-manage", $all_permission))
																			<input type="checkbox" value="1" id="chart-of-accounts-manage"
																				name="chart-of-accounts-manage" checked>
																		@else
																			<input type="checkbox" value="1" id="chart-of-accounts-manage"
																				name="chart-of-accounts-manage">
																		@endif
																		<label for="chart-of-accounts-manage"
																			class="padding05">Chart of Accounts
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("semantic-account-mappings-manage", $all_permission))
																			<input type="checkbox" value="1" id="semantic-account-mappings-manage"
																				name="semantic-account-mappings-manage" checked>
																		@else
																			<input type="checkbox" value="1" id="semantic-account-mappings-manage"
																				name="semantic-account-mappings-manage">
																		@endif
																		<label for="semantic-account-mappings-manage"
																			class="padding05">Semantic Account Mappings
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("money-transfer", $all_permission))
																			<input type="checkbox" value="1" id="money-transfer"
																				name="money-transfer" checked>
																		@else
																			<input type="checkbox" value="1" id="money-transfer"
																				name="money-transfer">
																		@endif
																		<label for="money-transfer"
																			class="padding05">{{__('db.Money Transfer')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("balance-sheet", $all_permission))
																			<input type="checkbox" value="1" id="balance-sheet"
																				name="balance-sheet" checked>
																		@else
																			<input type="checkbox" value="1" id="balance-sheet"
																				name="balance-sheet">
																		@endif
																		<label for="balance-sheet"
																			class="padding05">{{__('db.Balance Sheet')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("account-statement", $all_permission))
																			<input type="checkbox" value="1"
																				id="account-statement-permission"
																				name="account-statement" checked>
																		@else
																			<input type="checkbox" value="1"
																				id="account-statement-permission"
																				name="account-statement">
																		@endif
																		<label for="account-statement-permission"
																			class="padding05">{{__('db.Account Statement')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("account-selection", $all_permission))
																			<input type="checkbox" value="1"
																				id="account-selection-permission"
																				name="account-selection" checked>
																		@else
																			<input type="checkbox" value="1"
																				id="account-selection-permission"
																				name="account-selection">
																		@endif
																		<label for="account-selection-permission"
																			class="padding05">{{__('db.Account Selection')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
														</td>
													</tr>
													<tr class="hrm-row">
														<td>HRM</td>
														<td class="report-permissions" colspan="5">


															{{-- hrm report profile --}}
															<span>
																<div>
																	<div class="">
																		@if(in_array("hrm-panel", $all_permission))
																			<input type="checkbox" value="1" id="hrm-panel"
																				name="hrm-panel" checked>
																		@else
																			<input type="checkbox" value="1" id="hrm-panel"
																				name="hrm-panel">
																		@endif
																		<label for="hrm-panel"
																			class="padding05">{{__('db.HRM Panel')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>

															{{-- hrm report profile --}}
															<span>
																<div>
																	<div class="">
																		@if(in_array("sale-agents", $all_permission))
																			<input type="checkbox" value="1" id="sale-agents"
																				name="sale-agents" checked>
																		@else
																			<input type="checkbox" value="1" id="sale-agents"
																				name="sale-agents">
																		@endif
																		<label for="sale-agents"
																			class="padding05">{{__('db.Sale Agents')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>

															{{-- department --}}
															<span>
																<div>
																	<div class="">
																		@if(in_array("department", $all_permission))
																			<input type="checkbox" value="1" id="department"
																				name="department" checked>
																		@else
																			<input type="checkbox" value="1" id="department"
																				name="department">
																		@endif
																		<label for="department"
																			class="padding05">{{__('db.Department')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>

															{{-- designation --}}
															<span>
																<div>
																	<div class="">
																		@if(in_array("designations", $all_permission))
																			<input type="checkbox" value="1" id="designations"
																				name="designations" checked>
																		@else
																			<input type="checkbox" value="1" id="designations"
																				name="designations">
																		@endif
																		<label for="designations"
																			class="padding05">{{__('db.Designation')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>

															{{-- Shift --}}
															<span>
																<div>
																	<div class="">
																		@if(in_array("shift", $all_permission))
																			<input type="checkbox" value="1" id="shift"
																				name="shift" checked>
																		@else
																			<input type="checkbox" value="1" id="shift"
																				name="shift">
																		@endif
																		<label for="shift"
																			class="padding05">{{__('db.Shift')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>

															{{-- Overtime --}}
															<span>
																<div>
																	<div class="">
																		@if(in_array("overtime", $all_permission))
																			<input type="checkbox" value="1" id="overtime"
																				name="overtime" checked>
																		@else
																			<input type="checkbox" value="1" id="overtime"
																				name="overtime">
																		@endif
																		<label for="overtime"
																			class="padding05">{{__('db.Overtime')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>

															{{-- leave type --}}
															<span>
																<div>
																	<div class="">
																		@if(in_array("leave-type", $all_permission))
																			<input type="checkbox" value="1" id="leave-type"
																				name="leave-type" checked>
																		@else
																			<input type="checkbox" value="1" id="leave-type"
																				name="leave-type">
																		@endif
																		<label for="leave-type"
																			class="padding05">{{__('db.Leave Type')}}</label>
																	</div>
																</div>
															</span>

															{{-- leave --}}
															<span>
																<div>
																	<div class="">
																		@if(in_array("leave", $all_permission))
																			<input type="checkbox" value="1" id="leave"
																				name="leave" checked>
																		@else
																			<input type="checkbox" value="1" id="leave"
																				name="leave">
																		@endif
																		<label for="leave"
																			class="padding05">{{__('db.Leaves')}}</label>
																	</div>
																</div>
															</span>


															<span>
																<div>
																	<div class="">
																		@if(in_array("attendance", $all_permission))
																			<input type="checkbox" value="1" id="attendance"
																				name="attendance" checked>
																		@else
																			<input type="checkbox" value="1" id="attendance"
																				name="attendance">
																		@endif
																		<label for="attendance"
																			class="padding05">{{__('db.Attendance')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("payroll", $all_permission))
																			<input type="checkbox" value="1" id="payroll"
																				name="payroll" checked>
																		@else
																			<input type="checkbox" value="1" id="payroll"
																				name="payroll">
																		@endif
																		<label for="payroll"
																			class="padding05">{{__('db.Payroll')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("holiday", $all_permission))
																			<input type="checkbox" value="1" id="holiday"
																				name="holiday" checked>
																		@else
																			<input type="checkbox" value="1" id="holiday"
																				name="holiday">
																		@endif
																		<label for="holiday"
																			class="padding05">{{__('db.Holiday Approve')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
														</td>
													</tr>
													<tr class="report-row">
														<td>{{__('db.Reports')}}</td>
														<td class="report-permissions" colspan="5">
															<span>
																<div>
																	<div class="">
																		@if(in_array("profit-loss", $all_permission))
																			<input type="checkbox" value="1" id="profit-loss"
																				name="profit-loss" checked>
																		@else
																			<input type="checkbox" value="1" id="profit-loss"
																				name="profit-loss">
																		@endif
																		<label for="profit-loss"
																			class="padding05">{{__('db.Summary Report')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("best-seller", $all_permission))
																			<input type="checkbox" value="1" id="best-seller"
																				name="best-seller" checked>
																		@else
																			<input type="checkbox" value="1" id="best-seller"
																				name="best-seller">
																		@endif
																		<label for="best-seller"
																			class="padding05">{{__('db.Best Seller')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("daily-sale", $all_permission))
																			<input type="checkbox" value="1" id="daily-sale"
																				name="daily-sale" checked>
																		@else
																			<input type="checkbox" value="1" id="daily-sale"
																				name="daily-sale">
																		@endif
																		<label for="daily-sale"
																			class="padding05">{{__('db.Daily Sale')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("monthly-sale", $all_permission))
																			<input type="checkbox" value="1" id="monthly-sale"
																				name="monthly-sale" checked>
																		@else
																			<input type="checkbox" value="1" id="monthly-sale"
																				name="monthly-sale">
																		@endif
																		<label for="monthly-sale"
																			class="padding05">{{__('db.Monthly Sale')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("daily-purchase", $all_permission))
																			<input type="checkbox" value="1" id="daily-purchase"
																				name="daily-purchase" checked>
																		@else
																			<input type="checkbox" value="1" id="daily-purchase"
																				name="daily-purchase">
																		@endif
																		<label for="daily-purchase"
																			class="padding05">{{__('db.Daily Purchase')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("monthly-purchase", $all_permission))
																			<input type="checkbox" value="1"
																				id="monthly-purchase" name="monthly-purchase"
																				checked>
																		@else
																			<input type="checkbox" value="1"
																				id="monthly-purchase" name="monthly-purchase">
																		@endif
																		<label for="monthly-purchase"
																			class="padding05">{{__('db.Monthly Purchase')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("product-report", $all_permission))
																			<input type="checkbox" value="1" id="product-report"
																				name="product-report" checked>
																		@else
																			<input type="checkbox" value="1" id="product-report"
																				name="product-report">
																		@endif
																		<label for="product-report"
																			class="padding05">{{__('db.Product Report')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("stock-report", $all_permission))
																			<input type="checkbox" value="1" id="stock-report"
																				name="stock-report" checked>
																		@else
																			<input type="checkbox" value="1" id="stock-report"
																				name="stock-report">
																		@endif
																		<label for="stock-report"
																			class="padding05">{{__('db.Stock Report')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("payment-report", $all_permission))
																			<input type="checkbox" value="1" id="payment-report"
																				name="payment-report" checked>
																		@else
																			<input type="checkbox" value="1" id="payment-report"
																				name="payment-report">
																		@endif
																		<label for="payment-report"
																			class="padding05">{{__('db.Payment Report')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("purchase-report", $all_permission))
																			<input type="checkbox" value="1"
																				id="purchase-report" name="purchase-report"
																				checked>
																		@else
																			<input type="checkbox" value="1"
																				id="purchase-report" name="purchase-report">
																		@endif
																		<label for="purchase-report" class="padding05">
																			{{__('db.Purchase Report')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("sale-report", $all_permission))
																			<input type="checkbox" value="1" id="sale-report"
																				name="sale-report" checked>
																		@else
																			<input type="checkbox" value="1" id="sale-report"
																				name="sale-report">
																		@endif
																		<label for="sale-report"
																			class="padding05">{{__('db.Sale Report')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("sale-report-chart", $all_permission))
																			<input type="checkbox" value="1"
																				id="sale-report-chart" name="sale-report-chart"
																				checked>
																		@else
																			<input type="checkbox" value="1"
																				id="sale-report-chart" name="sale-report-chart">
																		@endif
																		<label for="sale-report-chart"
																			class="padding05">{{__('db.Sale Report Chart')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("warehouse-report", $all_permission))
																			<input type="checkbox" value="1"
																				id="warehouse-report" name="warehouse-report"
																				checked>
																		@else
																			<input type="checkbox" value="1"
																				id="warehouse-report" name="warehouse-report">
																		@endif
																		<label for="warehouse-report"
																			class="padding05">{{__('db.Warehouse Report')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("warehouse-stock-report", $all_permission))
																			<input type="checkbox" value="1"
																				id="warehouse-stock-report"
																				name="warehouse-stock-report" checked>
																		@else
																			<input type="checkbox" value="1"
																				id="warehouse-stock-report"
																				name="warehouse-stock-report">
																		@endif
																		<label for="warehouse-stock-report"
																			class="padding05">{{__('db.Warehouse Stock Chart')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("product-expiry-report", $all_permission))
																			<input type="checkbox" value="1"
																				id="product-expiry-report"
																				name="product-expiry-report" checked>
																		@else
																			<input type="checkbox" value="1"
																				id="product-expiry-report"
																				name="product-expiry-report">
																		@endif
																		<label for="product-expiry-report"
																			class="padding05">{{__('db.Product Expiry Report')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("dso-report", $all_permission))
																			<input type="checkbox" value="1" id="dso-report"
																				name="dso-report" checked>
																		@else
																			<input type="checkbox" value="1" id="dso-report"
																				name="dso-report">
																		@endif
																		<label for="dso-report"
																			class="padding05">{{__('db.Daily Sale Objective Report')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("product-qty-alert", $all_permission))
																			<input type="checkbox" value="1"
																				id="product-qty-alert" name="product-qty-alert"
																				checked>
																		@else
																			<input type="checkbox" value="1"
																				id="product-qty-alert" name="product-qty-alert">
																		@endif
																		<label for="product-qty-alert"
																			class="padding05">{{__('db.Product Quantity Alert')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("user-report", $all_permission))
																			<input type="checkbox" value="1" id="user-report"
																				name="user-report" checked>
																		@else
																			<input type="checkbox" value="1" id="user-report"
																				name="user-report">
																		@endif
																		<label for="user-report"
																			class="padding05">{{__('db.User Report')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("biller-report", $all_permission))
																			<input type="checkbox" value="1" id="biller-report"
																				name="biller-report" checked>
																		@else
																			<input type="checkbox" value="1" id="biller-report"
																				name="biller-report">
																		@endif
																		<label for="biller-report"
																			class="padding05">{{__('db.Biller Report')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("customer-report", $all_permission))
																			<input type="checkbox" value="1"
																				id="customer-report" name="customer-report"
																				checked>
																		@else
																			<input type="checkbox" value="1"
																				id="customer-report" name="customer-report">
																		@endif
																		<label for="customer-report"
																			class="padding05">{{__('db.Customer Report')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("supplier-report", $all_permission))
																			<input type="checkbox" value="1"
																				id="supplier-report" name="supplier-report"
																				checked>
																		@else
																			<input type="checkbox" value="1"
																				id="supplier-report" name="supplier-report">
																		@endif
																		<label for="supplier-report"
																			class="padding05">{{__('db.Supplier Report')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("supplier-due-report", $all_permission))
																			<input type="checkbox" value="1"
																				id="supplier-due-report"
																				name="supplier-due-report" checked>
																		@else
																			<input type="checkbox" value="1"
																				id="supplier-due-report"
																				name="supplier-due-report">
																		@endif
																		<label for="supplier-due-report"
																			class="padding05">{{__('db.Supplier Due Report')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("due-report", $all_permission))
																			<input type="checkbox" value="1" id="due-report"
																				name="due-report" checked>
																		@else
																			<input type="checkbox" value="1" id="due-report"
																				name="due-report">
																		@endif
																		<label for="due-report"
																			class="padding05">{{__('db.Customer Due Report')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
														</td>
													</tr>
												</tbody>
												<!-- ENDREP -->
											</table>
										</div>
									</div>
									<div class="tab-pane fade" id="settings" role="tabpanel" aria-labelledby="settings-tab">
										<div class="table-responsive">
											<table class="table table-bordered permission-table mt-3">
												<thead>
													<tr>
														<th rowspan="2" class="text-center">{{__('db.name')}}</th>
														<th colspan="6" class="text-center">
															<div class="custom-control custom-switch">
																<input type="checkbox"
																	class="custom-control-input section-select-all"
																	id="select_all_settings">
																<label class="custom-control-label"
																	for="select_all_settings">{{__('db.Select All')}}</label>
															</div>
														</th>
													</tr>
													<tr>
													</tr>
												</thead>
												<tbody>
													<tr class="addon-row">
														<td>Addons</td>
														<td class="addons">
															<span>
																<div>
																	<div class="">
																		@if(in_array("addons", $all_permission))
																			<input type="checkbox" value="1" id="addons"
																				name="addons" checked>
																		@else
																			<input type="checkbox" value="1" id="addons"
																				name="addons">
																		@endif
																		<label for="addons" class="padding05">Addons
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
														</td>
													</tr>
													<tr>
														<td>{{__('db.settings')}}</td>
														<td class="report-permissions" colspan="5">
															<span>
																<div>
																	<div class="">
																		@if(in_array("custom_field", $all_permission))
																			<input type="checkbox" value="1" id="custom_field"
																				name="custom_field" checked>
																		@else
																			<input type="checkbox" value="1" id="custom_field"
																				name="custom_field">
																		@endif
																		<label for="custom_field"
																			class="padding05">{{__('db.Custom Field')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("all_notification", $all_permission))
																			<input type="checkbox" value="1"
																				id="all_notification" name="all_notification"
																				checked>
																		@else
																			<input type="checkbox" value="1"
																				id="all_notification" name="all_notification">
																		@endif
																		<label for="all_notification"
																			class="padding05">{{__('db.All Notification')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("send_notification", $all_permission))
																			<input type="checkbox" value="1"
																				id="send_notification" name="send_notification"
																				checked>
																		@else
																			<input type="checkbox" value="1"
																				id="send_notification" name="send_notification">
																		@endif
																		<label for="send_notification"
																			class="padding05">{{__('db.Send Notification')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("discount_plan", $all_permission))
																			<input type="checkbox" value="1" id="discount_plan"
																				name="discount_plan" checked>
																		@else
																			<input type="checkbox" value="1" id="discount_plan"
																				name="discount_plan">
																		@endif
																		<label for="discount_plan"
																			class="padding05">{{__('db.Discount Plan')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("discount", $all_permission))
																			<input type="checkbox" value="1" id="discount"
																				name="discount" checked>
																		@else
																			<input type="checkbox" value="1" id="discount"
																				name="discount">
																		@endif
																		<label for="discount"
																			class="padding05">{{__('db.Discount')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("warehouse", $all_permission))
																			<input type="checkbox" value="1" id="warehouse"
																				name="warehouse" checked>
																		@else
																			<input type="checkbox" value="1" id="warehouse"
																				name="warehouse">
																		@endif
																		<label for="warehouse"
																			class="padding05">{{__('db.Warehouse')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("customer_group", $all_permission))
																			<input type="checkbox" value="1" id="customer_group"
																				name="customer_group" checked>
																		@else
																			<input type="checkbox" value="1" id="customer_group"
																				name="customer_group">
																		@endif
																		<label for="customer_group"
																			class="padding05">{{__('db.Customer Group')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("brand", $all_permission))
																			<input type="checkbox" value="1" id="brand"
																				name="brand" checked>
																		@else
																			<input type="checkbox" value="1" id="brand"
																				name="brand">
																		@endif
																		<label for="brand"
																			class="padding05">{{__('db.Brand')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("unit", $all_permission))
																			<input type="checkbox" value="1" id="unit"
																				name="unit" checked>
																		@else
																			<input type="checkbox" value="1" id="unit"
																				name="unit">
																		@endif
																		<label for="unit"
																			class="padding05">{{__('db.Unit')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("currency", $all_permission))
																			<input type="checkbox" value="1" id="currency"
																				name="currency" checked>
																		@else
																			<input type="checkbox" value="1" id="currency"
																				name="currency">
																		@endif
																		<label for="currency"
																			class="padding05">{{__('db.Currency')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("tax", $all_permission))
																			<input type="checkbox" value="1" id="tax" name="tax"
																				checked>
																		@else
																			<input type="checkbox" value="1" id="tax"
																				name="tax">
																		@endif
																		<label for="tax" class="padding05">{{__('db.Tax')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("backup_database", $all_permission))
																			<input type="checkbox" value="1"
																				id="backup_database" name="backup_database"
																				checked>
																		@else
																			<input type="checkbox" value="1"
																				id="backup_database" name="backup_database">
																		@endif
																		<label for="backup_database"
																			class="padding05">{{__('db.Backup Database')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("general_setting", $all_permission))
																			<input type="checkbox" value="1"
																				id="general_setting" name="general_setting"
																				checked>
																		@else
																			<input type="checkbox" value="1"
																				id="general_setting" name="general_setting">
																		@endif
																		<label for="general_setting"
																			class="padding05">{{__('db.General Setting')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("theme_settings", $all_permission))
																			<input type="checkbox" value="1" id="theme_settings"
																				name="theme_settings" checked>
																		@else
																			<input type="checkbox" value="1" id="theme_settings"
																				name="theme_settings">
																		@endif
																		<label for="theme_settings"
																			class="padding05">{{__('db.theme_settings')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("mail_setting", $all_permission))
																			<input type="checkbox" value="1" id="mail_setting"
																				name="mail_setting" checked>
																		@else
																			<input type="checkbox" value="1" id="mail_setting"
																				name="mail_setting">
																		@endif
																		<label for="mail_setting"
																			class="padding05">{{__('db.Mail Setting')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("payment_gateway_setting", $all_permission))
																			<input type="checkbox" value="1"
																				id="payment_gateway_setting"
																				name="payment_gateway_setting" checked>
																		@else
																			<input type="checkbox" value="1"
																				id="payment_gateway_setting"
																				name="payment_gateway_setting">
																		@endif
																		<label for="payment_gateway_setting"
																			class="padding05">Payment Gateway
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("sms_setting", $all_permission))
																			<input type="checkbox" value="1" id="sms_setting"
																				name="sms_setting" checked>
																		@else
																			<input type="checkbox" value="1" id="sms_setting"
																				name="sms_setting">
																		@endif
																		<label for="sms_setting"
																			class="padding05">{{__('db.SMS Setting')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("create_sms", $all_permission))
																			<input type="checkbox" value="1" id="create_sms"
																				name="create_sms" checked>
																		@else
																			<input type="checkbox" value="1" id="create_sms"
																				name="create_sms">
																		@endif
																		<label for="create_sms"
																			class="padding05">{{__('db.Create SMS')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("pos_setting", $all_permission))
																			<input type="checkbox" value="1" id="pos_setting"
																				name="pos_setting" checked>
																		@else
																			<input type="checkbox" value="1" id="pos_setting"
																				name="pos_setting">
																		@endif
																		<label for="pos_setting"
																			class="padding05">{{__('db.POS Setting')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span class="hrm-setting-section">
																<div>
																	<div class="">
																		@if(in_array("hrm_setting", $all_permission))
																			<input type="checkbox" value="1" id="hrm_setting"
																				name="hrm_setting" checked>
																		@else
																			<input type="checkbox" value="1" id="hrm_setting"
																				name="hrm_setting">
																		@endif
																		<label for="hrm_setting"
																			class="padding05">{{__('db.HRM Setting')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span class="barcode_setting">
																<div>
																	<div class="">
																		@if(in_array("barcode_setting", $all_permission))
																			<input type="checkbox" value="1"
																				id="barcode_setting" name="barcode_setting"
																				checked>
																		@else
																			<input type="checkbox" value="1"
																				id="barcode_setting" name="barcode_setting">
																		@endif
																		<label for="barcode_setting"
																			class="padding05">Barcode &nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span class="language_setting">
																<div>
																	<div class="">
																		@if(in_array("language_setting", $all_permission))
																			<input type="checkbox" value="1"
																				id="language_setting" name="language_setting"
																				checked>
																		@else
																			<input type="checkbox" value="1"
																				id="language_setting" name="language_setting">
																		@endif
																		<label for="language_setting"
																			class="padding05">Language &nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("reward_point_setting", $all_permission))
																			<input type="checkbox" value="1"
																				id="reward_point_setting"
																				name="reward_point_setting" checked>
																		@else
																			<input type="checkbox" value="1"
																				id="reward_point_setting"
																				name="reward_point_setting">
																		@endif
																		<label for="reward_point_setting"
																			class="padding05">{{__('db.Reward Point Setting')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("invoice_setting", $all_permission))
																			<input type="checkbox" value="1"
																				id="invoice_setting" name="invoice_setting"
																				checked>
																		@else
																			<input type="checkbox" value="1"
																				id="invoice_setting" name="invoice_setting">
																		@endif
																		<label for="invoice_setting"
																			class="padding05">{{__('db.Invoice Settings')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("invoice_create_edit_delete", $all_permission))
																			<input type="checkbox" value="1"
																				id="invoice_create_edit_delete"
																				name="invoice_create_edit_delete" checked>
																		@else
																			<input type="checkbox" value="1"
																				id="invoice_create_edit_delete"
																				name="invoice_create_edit_delete">
																		@endif
																		<label for="invoice_create_edit_delete"
																			class="padding05">{{__('db.Invoice Create/Edit/Delete')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("role_permission", $all_permission))
																			<input type="checkbox" value="1"
																				id="role_permission" name="role_permission"
																				checked>
																		@else
																			<input type="checkbox" value="1"
																				id="role_permission" name="role_permission">
																		@endif
																		<label for="role_permission"
																			class="padding05">{{__('db.Role Permission')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
														</td>
													</tr>
													<tr>
														<td>{{__('db.Miscellaneous')}}</td>
														<td class="report-permissions" colspan="5">
															<span>
																<div>
																	<div class="">
																		@if(in_array("category", $all_permission))
																			<input type="checkbox" value="1" id="category"
																				name="category" checked>
																		@else
																			<input type="checkbox" value="1" id="category"
																				name="category">
																		@endif
																		<label for="category"
																			class="padding05">{{__('db.category')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("income-categories", $all_permission))
																			<input type="checkbox" value="1"
																				id="income-categories" name="income-categories"
																				checked>
																		@else
																			<input type="checkbox" value="1"
																				id="income-categories" name="income-categories">
																		@endif
																		<label for="income-categories"
																			class="padding05">{{__('db.Income Category')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("expense-categories", $all_permission))
																			<input type="checkbox" value="1"
																				id="expense-categories"
																				name="expense-categories" checked>
																		@else
																			<input type="checkbox" value="1"
																				id="expense-categories"
																				name="expense-categories">
																		@endif
																		<label for="expense-categories"
																			class="padding05">{{__('db.Expense Category')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span class="packing-slip-challan-section">
																<div>
																	<div class="">
																		@if(in_array("packing_slip_challan", $all_permission))
																			<input type="checkbox" value="1"
																				id="packing_slip_challan"
																				name="packing_slip_challan" checked>
																		@else
																			<input type="checkbox" value="1"
																				id="packing_slip_challan"
																				name="packing_slip_challan">
																		@endif
																		<label for="packing_slip_challan"
																			class="padding05">{{__('db.Packing Slip & Challan')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span class="delivery-section">
																<div>
																	<div class="">
																		@if(in_array("delivery", $all_permission))
																			<input type="checkbox" value="1" id="delivery"
																				name="delivery" checked>
																		@else
																			<input type="checkbox" value="1" id="delivery"
																				name="delivery">
																		@endif
																		<label for="delivery"
																			class="padding05">{{__('db.Delivery')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span class="stock-count-section">
																<div>
																	<div class="">
																		@if(in_array("stock_count", $all_permission))
																			<input type="checkbox" value="1" id="stock_count"
																				name="stock_count" checked>
																		@else
																			<input type="checkbox" value="1" id="stock_count"
																				name="stock_count">
																		@endif
																		<label for="stock_count"
																			class="padding05">{{__('db.Stock Count')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span class="adjustment-section">
																<div>
																	<div class="">
																		@if(in_array("adjustment", $all_permission))
																			<input type="checkbox" value="1" id="adjustment"
																				name="adjustment" checked>
																		@else
																			<input type="checkbox" value="1" id="adjustment"
																				name="adjustment">
																		@endif
																		<label for="adjustment"
																			class="padding05">{{__('db.Adjustment')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span class="damage-stock-section">
																<div>
																	<div class="">
																		@if(in_array("damage-stock", $all_permission))
																			<input type="checkbox" value="1" id="damage-stock"
																				name="damage-stock" checked>
																		@else
																			<input type="checkbox" value="1" id="damage-stock"
																				name="damage-stock">
																		@endif
																		<label for="damage-stock"
																			class="padding05">{{__('db.Damage Stock')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>

															<span class="booking-section">
																<div>
																	<div class="">
																		@if(in_array("booking", $all_permission))
																			<input type="checkbox" value="1" id="booking"
																				name="booking" checked>
																		@else
																			<input type="checkbox" value="1" id="booking"
																				name="booking">
																		@endif
																		<label for="booking"
																			class="padding05">{{__('db.Booking')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>

															<span>
																<div>
																	<div class="">
																		@if(in_array("gift_card", $all_permission))
																			<input type="checkbox" value="1" id="gift_card"
																				name="gift_card" checked>
																		@else
																			<input type="checkbox" value="1" id="gift_card"
																				name="gift_card">
																		@endif
																		<label for="gift_card"
																			class="padding05">{{__('db.Gift Card')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("coupon", $all_permission))
																			<input type="checkbox" value="1" id="coupon"
																				name="coupon" checked>
																		@else
																			<input type="checkbox" value="1" id="coupon"
																				name="coupon">
																		@endif
																		<label for="coupon"
																			class="padding05">{{__('db.Coupon')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("product_history", $all_permission))
																			<input type="checkbox" value="1"
																				id="product_history" name="product_history"
																				checked>
																		@else
																			<input type="checkbox" value="1"
																				id="product_history" name="product_history">
																		@endif
																		<label for="product_history"
																			class="padding05">{{__('db.Product History')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("print_barcode", $all_permission))
																			<input type="checkbox" value="1" id="print_barcode"
																				name="print_barcode" checked>
																		@else
																			<input type="checkbox" value="1" id="print_barcode"
																				name="print_barcode">
																		@endif
																		<label for="print_barcode"
																			class="padding05">{{__('db.print_barcode')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("business_reset", $all_permission))
																			<input type="checkbox" value="1" id="business_reset"
																				name="business_reset" checked>
																		@else
																			<input type="checkbox" value="1" id="business_reset"
																				name="business_reset">
																		@endif
																		<label for="business_reset"
																			class="padding05">{{__('db.fresh_reset_title')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("today_sale", $all_permission))
																			<input type="checkbox" value="1" id="today_sale"
																				name="today_sale" checked>
																		@else
																			<input type="checkbox" value="1" id="today_sale"
																				name="today_sale">
																		@endif
																		<label for="today_sale"
																			class="padding05">{{__('db.Today Sale')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("today_profit", $all_permission))
																			<input type="checkbox" value="1" id="today_profit"
																				name="today_profit" checked>
																		@else
																			<input type="checkbox" value="1" id="today_profit"
																				name="today_profit">
																		@endif
																		<label for="today_profit"
																			class="padding05">{{__('db.Today Profit')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("change_sale_date", $all_permission))
																			<input type="checkbox" value="1"
																				id="change_sale_date" name="change_sale_date"
																				checked>
																		@else
																			<input type="checkbox" value="1"
																				id="change_sale_date" name="change_sale_date">
																		@endif
																		<label for="change_sale_date"
																			class="padding05">{{__('db.Change Sale Date')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("cost_edit_in_products", $all_permission))
																			<input type="checkbox" value="1"
																				id="cost_edit_in_products"
																				name="cost_edit_in_products" checked>
																		@else
																			<input type="checkbox" value="1"
																				id="cost_edit_in_products"
																				name="cost_edit_in_products">
																		@endif
																		<label for="cost_edit_in_products"
																			class="padding05">{{__('db.Cost Edit in Products')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															<span>
																<div>
																	<div class="">
																		@if(in_array("price_edit_in_sale", $all_permission))
																			<input type="checkbox" value="1"
																				id="price_edit_in_sale"
																				name="price_edit_in_sale" checked>
																		@else
																			<input type="checkbox" value="1"
																				id="price_edit_in_sale"
																				name="price_edit_in_sale">
																		@endif
																		<label for="price_edit_in_sale"
																			class="padding05">{{__('db.Price Edit in Sale')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															@if($module_present['ai_assistant_module'])
															<span>
																<div>
																	<div class="">
																		@if(in_array("ai-assistant-index", $all_permission))
																			<input type="checkbox" value="1"
																				id="ai-assistant-index"
																				name="ai-assistant-index" checked>
																		@else
																			<input type="checkbox" value="1"
																				id="ai-assistant-index"
																				name="ai-assistant-index">
																		@endif
																		<label for="ai-assistant-index"
																			class="padding05">{{__('aiassistant::app.ai_assistant')}}
																			&nbsp;&nbsp;</label>
																	</div>
																</div>
															</span>
															@endif
														</td>
													</tr>
												</tbody>
											</table>
										</div>
									</div>
								</div>
								<div class="form-group">
									<input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</section>

@endsection

@push('css')
	<style>
		.permission-table .permission-switch {
			display: inline-flex;
			align-items: center;
			min-height: 1.5rem;
			margin: 0.15rem 0.45rem 0.15rem 0;
			padding-left: 2.4rem;
			text-align: left;
		}

		.permission-table .permission-switch .custom-control-label {
			cursor: pointer;
			line-height: 1.35rem;
			white-space: nowrap;
		}

		.permission-table td.text-center .permission-switch {
			margin-right: 0;
		}

		.permission-table .report-permissions span {
			display: inline-block;
			margin-bottom: 0.35rem;
		}
	</style>
@endpush

@push('scripts')
	<script type="text/javascript">

		$("ul#setting").siblings('a').attr('aria-expanded', 'true');
		$("ul#setting").addClass("show");
		$("ul#setting #role-menu").addClass("active");

		function enhancePermissionSwitches() {
			$('.permission-table input[type="checkbox"]').each(function (index) {
				var $input = $(this);
				var id = $input.attr('id');
				if (!id) {
					id = 'permission_switch_' + index;
					$input.attr('id', id);
				}

				var $label = $('label[for="' + id.replace(/([:\\[\\].,#])/g, '\\$1') + '"]').first();
				var $container = $input.closest('.custom-control');
				if (!$container.length) {
					$container = $input.parent();
				}

				$container.addClass('custom-control custom-switch permission-switch');
				$input.addClass('custom-control-input');
				$label.addClass('custom-control-label');

				if (!$label.length) {
					$input.after('<label class="custom-control-label" for="' + id + '"></label>');
				}
			});
		}

		enhancePermissionSwitches();

		@if(config('database.connections.saleprosaas_landlord'))
			$.ajax({
				type: 'GET',
				async: false,
				url: '{{route("package.fetchData", gen_setting()->package_id)}}',
				success: function (data) {
					package_features = JSON.parse(data['features']);   // allowed features
					all_features = @json($all_features);

					$.each(all_features, function (feature_key, feature_data) {
						// যদি feature package এ না থাকে
						if (!package_features.includes(feature_key)) {
							if (feature_data.permission_names) {
								let perms = feature_data.permission_names.split(',');
								perms.forEach(function (p) {
									let $el = $('#' + p.trim());
									if ($el.length) {
										// ১. আগে চেক করুন এটি কোনো span এর ভেতরে আছে কিনা (Sidebar/Settings আইটেমের জন্য)
										if ($el.closest('span').length > 0) {
											$el.closest('span').hide(); // অথবা .addClass('d-none') ব্যবহার করতে পারেন
										}
										// ২. যদি span না থাকে, কিন্তু td এর ভেতরে থাকে (সাধারণ টেবিল আইটেমের জন্য)
										else if ($el.closest('td').length > 0) {
											$el.closest('td').hide(); // আপনার চাওয়া অনুযায়ী শুধু td হাইড হবে
										}
									}
								});
							}
						}
					});

					@php
						$tenantInfo = new class {
							use \App\Traits\TenantInfo;
						};
						$addon_features = $tenantInfo->addonList();
					@endphp
					let addon_features = @json($addon_features);
					let hasAddon = package_features.some(feature => addon_features.includes(feature));

					if (!hasAddon) {
						$('.addon-row').hide();
					}

					$('tr').each(function () {
						var $row = $(this);
						var $allInputsInRow = $row.find('input');

						// ১. যদি এই রো-তে কোনো ইনপুটই না থাকে (যেমন হেডার), তাহলে কিছু করার দরকার নেই
						if ($allInputsInRow.length === 0) {
							return;
						}

						// ২. আমরা চেক করব: এই রো-এর ইনপুটগুলোর মধ্যে কয়টি আসলে দৃশ্যমান?
						// (দৃশ্যমান মানে: যার TD-ও হাইড করা নেই, এবং যার SPAN-ও হাইড করা নেই)

						var visibleInputCount = 0;

						$allInputsInRow.each(function () {
							var $input = $(this);
							var $parentSpan = $input.closest('span');
							var $parentTd = $input.closest('td');

							// চেক ১: চেকবক্সটি যে TD-তে আছে, সেটি কি হাইড করা? (Income-এর ক্ষেত্রে)
							var isTdHidden = $parentTd.css('display') === 'none';

							// চেক ২: চেকবক্সটি যে SPAN-এ আছে, সেটি কি হাইড করা? (Manufacturing-এর ক্ষেত্রে)
							// (নোট: সব ইনপুট span-এ নাও থাকতে পারে, তাই length চেক করা হচ্ছে)
							var isSpanHidden = $parentSpan.length > 0 && $parentSpan.css('display') === 'none';

							// যদি TD-ও হাইড না থাকে এবং SPAN-ও হাইড না থাকে, তার মানে এটি একটি ভ্যালিড ইনপুট
							if (!isTdHidden && !isSpanHidden) {
								visibleInputCount++;
							}
						});

						// ৩. যদি রো-তে ইনপুট ছিল, কিন্তু দৃশ্যমান ইনপুটের সংখ্যা ০ হয়
						if (visibleInputCount === 0) {
							$row.hide();
						}
					});
				}
			});
		@endif

		enhancePermissionSwitches();

		$(".section-select-all").on("change", function () {
			var $tbody = $(this).closest('table').find('tbody');
			if ($(this).is(':checked')) {
				$tbody.find("input[type='checkbox']").prop('checked', true);
			}
			else {
				$tbody.find("input[type='checkbox']").prop('checked', false);
			}
		});

		$('#permissionTabs a').on('click', function (e) {
			e.preventDefault();
			$(this).tab('show');
		});
	</script>
@endpush
