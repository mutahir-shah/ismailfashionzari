@php
    $asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
@endphp
<!DOCTYPE html>
<html dir="rtl">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="icon" type="image/png" href="{{url('favicon.ico')}}" />
    <title>{{gen_setting()->site_title}}</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="all,follow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Bootstrap CSS-->
    <link rel="stylesheet" href="<?php echo asset($asset_prefix . 'vendor/bootstrap/css/bootstrap.min.css') ?>" type="text/css">

    <link rel="preload" href="<?php echo asset($asset_prefix . 'vendor/bootstrap-toggle/css/bootstrap-toggle.min.css') ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="<?php echo asset($asset_prefix . 'vendor/bootstrap-toggle/css/bootstrap-toggle.min.css') ?>" rel="stylesheet"></noscript>
    <link rel="preload" href="<?php echo asset($asset_prefix . 'vendor/bootstrap/css/bootstrap-datepicker.min.css') ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="<?php echo asset($asset_prefix . 'vendor/bootstrap/css/bootstrap-datepicker.min.css') ?>" rel="stylesheet"></noscript>
    <link rel="preload" href="<?php echo asset($asset_prefix . 'vendor/bootstrap/css/awesome-bootstrap-checkbox.css') ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="<?php echo asset($asset_prefix . 'vendor/bootstrap/css/awesome-bootstrap-checkbox.css') ?>" rel="stylesheet"></noscript>
    <link rel="preload" href="<?php echo asset($asset_prefix . 'vendor/bootstrap/css/bootstrap-select.min.css') ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="<?php echo asset($asset_prefix . 'vendor/bootstrap/css/bootstrap-select.min.css') ?>" rel="stylesheet"></noscript>

    <!-- Google fonts - Roboto -->
    <link rel="preload" href="https://fonts.googleapis.com/css?family=Nunito:400,500,700" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.googleapis.com/css?family=Nunito:400,500,700" rel="stylesheet"></noscript>

    @if(Route::current()->getName() != '/')
    <!-- date range stylesheet-->
    <link rel="preload" href="<?php echo asset($asset_prefix . 'vendor/daterange/css/daterangepicker.min.css') ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="<?php echo asset($asset_prefix . 'vendor/daterange/css/daterangepicker.min.css') ?>" rel="stylesheet"></noscript>
    <!-- table sorter stylesheet-->
    <link rel="preload" href="<?php echo asset($asset_prefix . 'vendor/datatable/dataTables.bootstrap4.min.css') ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="<?php echo asset($asset_prefix . 'vendor/datatable/dataTables.bootstrap4.min.css') ?>" rel="stylesheet"></noscript>
    <link rel="preload" href="https://cdn.datatables.net/fixedheader/3.1.6/css/fixedHeader.bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://cdn.datatables.net/fixedheader/3.1.6/css/fixedHeader.bootstrap.min.css" rel="stylesheet"></noscript>
    <link rel="preload" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css" rel="stylesheet"></noscript>
    @endif

    <link rel="stylesheet" href="<?php echo asset($asset_prefix . 'css/style.default.css') ?>" id="theme-stylesheet" type="text/css">
    


    <script type="text/javascript" src="<?php echo asset($asset_prefix . 'vendor/jquery/jquery.min.js') ?>"></script>
    <script type="text/javascript" src="<?php echo asset($asset_prefix . 'vendor/jquery/jquery-ui.min.js') ?>"></script>
    <script type="text/javascript" src="<?php echo asset($asset_prefix . 'vendor/jquery/bootstrap-datepicker.min.js') ?>"></script>
    <script type="text/javascript" src="<?php echo asset($asset_prefix . 'vendor/popper.js/umd/popper.min.js') ?>">
    </script>
    <script type="text/javascript" src="<?php echo asset($asset_prefix . 'vendor/bootstrap/js/bootstrap.min.js') ?>"></script>
    <script type="text/javascript" src="<?php echo asset($asset_prefix . 'vendor/bootstrap-toggle/js/bootstrap-toggle.min.js') ?>"></script>
    <script type="text/javascript" src="<?php echo asset($asset_prefix . 'vendor/bootstrap/js/bootstrap-select.min.js') ?>"></script>

    <script type="text/javascript" src="<?php echo asset($asset_prefix . 'vendor/jquery.cookie/jquery.cookie.js') ?>">
    </script>
    <script type="text/javascript" src="<?php echo asset($asset_prefix . 'vendor/chart.js/Chart.min.js') ?>"></script>
    <script type="text/javascript" src="<?php echo asset($asset_prefix . 'js/charts-custom.js') ?>"></script>
    <script type="text/javascript" src="<?php echo asset($asset_prefix . 'vendor/jquery-validation/jquery.validate.min.js') ?>"></script>

    <script type="text/javascript" src="<?php echo asset($asset_prefix . 'js/front.js') ?>"></script>

    @if(Route::current()->getName() != '/')
    <script type="text/javascript" src="<?php echo asset($asset_prefix . 'vendor/tinymce/js/tinymce/tinymce.min.js') ?>"></script>
    

    <!-- table sorter js-->
    @if( Config::get('app.locale') == 'ar')
      <script type="text/javascript" src="<?php echo asset($asset_prefix . 'vendor/datatable/pdfmake_arabic.min.js') ?>"></script>
      <script type="text/javascript" src="<?php echo asset($asset_prefix . 'vendor/datatable/vfs_fonts_arabic.js') ?>"></script>
    @else
      <script type="text/javascript" src="<?php echo asset($asset_prefix . 'vendor/datatable/pdfmake.min.js') ?>"></script>
      <script type="text/javascript" src="<?php echo asset($asset_prefix . 'vendor/datatable/vfs_fonts.js') ?>"></script>
    @endif
    <script type="text/javascript" src="<?php echo asset($asset_prefix . 'vendor/datatable/jquery.dataTables.min.js') ?>"></script>
    <script type="text/javascript" src="<?php echo asset($asset_prefix . 'vendor/datatable/dataTables.bootstrap4.min.js') ?>"></script>
    <script type="text/javascript" src="<?php echo asset($asset_prefix . 'vendor/datatable/dataTables.buttons.min.js') ?>"></script>
    <script type="text/javascript" src="<?php echo asset($asset_prefix . 'vendor/datatable/buttons.bootstrap4.min.js') ?>">"></script>
    <script type="text/javascript" src="<?php echo asset($asset_prefix . 'vendor/datatable/buttons.colVis.min.js') ?>"></script>
    <script type="text/javascript" src="<?php echo asset($asset_prefix . 'vendor/datatable/buttons.html5.min.js') ?>"></script>
    <script type="text/javascript" src="<?php echo asset($asset_prefix . 'vendor/datatable/buttons.print.min.js') ?>"></script>

    <script type="text/javascript" src="<?php echo asset($asset_prefix . 'vendor/datatable/sum().js') ?>"></script>
    <script type="text/javascript" src="<?php echo asset($asset_prefix . 'vendor/datatable/dataTables.checkboxes.min.js') ?>"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/fixedheader/3.1.6/js/dataTables.fixedHeader.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
    @endif

    <!-- Custom stylesheet - for your changes-->
    <link rel="stylesheet" href="<?php echo asset('css/custom-'.gen_setting()->theme) ?>" type="text/css" id="custom-style">
    @if( Config::get('app.locale') == 'ar')
    <!-- RTL css -->
    <link rel="stylesheet" href="<?php echo asset($asset_prefix . 'vendor/bootstrap/css/bootstrap-rtl.min.css') ?>" type="text/css">
    <link rel="stylesheet" href="<?php echo asset($asset_prefix . 'css/custom-rtl.css') ?>" type="text/css" id="custom-style">
    @endif
  </head>

  <body onload="myFunction()">
    <div id="loader"></div>
      <!-- Side Navbar -->
      <nav class="side-navbar">
        <div class="side-navbar-wrapper">
          <!-- Sidebar Header    -->
          <!-- Sidebar Navigation Menus-->
          <div class="main-menu">
            <ul id="side-main-menu" class="side-menu list-unstyled">
              <li><a href="{{url('/')}}"> <i class="ti ti-meter"></i><span>{{ __('dbdashboard') }}</span></a></li>
               <?php
                  $role = DB::table('roles')->find(Auth::user()->role_id);
                  $category_permission_active = DB::table('permissions')
                      ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                      ->where([
                        ['permissions.name', 'category'],
                        ['role_id', $role->id] ])->first();
                  $index_permission = DB::table('permissions')->where('name', 'products-index')->first();
                  $index_permission_active = DB::table('role_has_permissions')->where([
                      ['permission_id', $index_permission->id],
                      ['role_id', $role->id]
                  ])->first();

                  $print_barcode = DB::table('permissions')->where('name', 'print_barcode')->first();
                      $print_barcode_active = DB::table('role_has_permissions')->where([
                          ['permission_id', $print_barcode->id],
                          ['role_id', $role->id]
                      ])->first();

                  $stock_count = DB::table('permissions')->where('name', 'stock_count')->first();
                      $stock_count_active = DB::table('role_has_permissions')->where([
                          ['permission_id', $stock_count->id],
                          ['role_id', $role->id]
                      ])->first();

                    $adjustment = DB::table('permissions')->where('name', 'adjustment')->first();
                    $adjustment_active = DB::table('role_has_permissions')->where([
                        ['permission_id', $adjustment->id],
                        ['role_id', $role->id]
                    ])->first();
              ?>
              @if($category_permission_active || $index_permission_active || $print_barcode_active || $stock_count_active || $adjustment_active)
              <li><a href="#product" aria-expanded="false" data-toggle="collapse"> <i class="ti ti-list"></i><span>{{__('dbproduct')}}</span><span></a>
                <ul id="product" class="collapse list-unstyled ">
                  @if($category_permission_active)
                  <li id="category-menu"><a href="{{route('category.index')}}">{{__('dbcategory')}}</a></li>
                  @endif
                  @if($index_permission_active)
                  <li id="product-list-menu"><a href="{{route('products.index')}}">{{__('dbproduct_list')}}</a></li>
                  <?php
                    $add_permission = DB::table('permissions')->where('name', 'products-add')->first();
                    $add_permission_active = DB::table('role_has_permissions')->where([
                        ['permission_id', $add_permission->id],
                        ['role_id', $role->id]
                    ])->first();
                  ?>
                  @if($add_permission_active)
                  <li id="product-create-menu"><a href="{{route('products.create')}}">{{__('dbadd_product')}}</a></li>
                  @endif
                  @endif
                  @if($print_barcode_active)
                  <li id="printBarcode-menu"><a href="{{route('product.printBarcode')}}">{{__('dbprint_barcode')}}</a></li>
                  @endif
                  @if($adjustment_active)
                    <li id="adjustment-list-menu"><a href="{{route('qty_adjustment.index')}}">{{__('db.Adjustment List')}}</a></li>
                    <li id="adjustment-create-menu"><a href="{{route('qty_adjustment.create')}}">{{__('db.Add Adjustment')}}</a></li>
                  @endif
                  @if($stock_count_active)
                    <li id="stock-count-menu"><a href="{{route('stock-count.index')}}">{{__('db.Stock Count')}}</a></li>
                  @endif
                </ul>
              </li>
              @endif
              <?php
                $index_permission = DB::table('permissions')->where('name', 'purchases-index')->first();
                  $index_permission_active = DB::table('role_has_permissions')->where([
                        ['permission_id', $index_permission->id],
                        ['role_id', $role->id]
                    ])->first();
              ?>
              @if($index_permission_active)
              <li><a href="#purchase" aria-expanded="false" data-toggle="collapse"> <i class="ti ti-credit-card"></i><span>{{__('db.Purchase')}}</span></a>
                <ul id="purchase" class="collapse list-unstyled ">
                  <li id="purchase-list-menu"><a href="{{route('purchases.index')}}">{{__('db.Purchase List')}}</a></li>
                  <?php
                    $add_permission = DB::table('permissions')->where('name', 'purchases-add')->first();
                    $add_permission_active = DB::table('role_has_permissions')->where([
                        ['permission_id', $add_permission->id],
                        ['role_id', $role->id]
                    ])->first();
                  ?>
                  @if($add_permission_active)
                  <li id="purchase-create-menu"><a href="{{route('purchases.create')}}">{{__('db.Add Purchase')}}</a></li>
                  <li id="purchase-import-menu"><a href="{{url('purchases/purchase_by_csv')}}">{{__('db.Import Purchase By CSV')}}</a></li>
                  @endif
                </ul>
              </li>
              @endif
              <?php
                $sale_index_permission = DB::table('permissions')->where('name', 'sales-index')->first();
                $sale_index_permission_active = DB::table('role_has_permissions')->where([
                        ['permission_id', $sale_index_permission->id],
                        ['role_id', $role->id]
                    ])->first();

                $gift_card_permission = DB::table('permissions')->where('name', 'gift_card')->first();
                $gift_card_permission_active = DB::table('role_has_permissions')->where([
                        ['permission_id', $gift_card_permission->id],
                        ['role_id', $role->id]
                    ])->first();

                $coupon_permission = DB::table('permissions')->where('name', 'coupon')->first();
                $coupon_permission_active = DB::table('role_has_permissions')->where([
                        ['permission_id', $coupon_permission->id],
                        ['role_id', $role->id]
                    ])->first();

                $delivery_permission_active = DB::table('permissions')
                      ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                      ->where([
                        ['permissions.name', 'delivery'],
                        ['role_id', $role->id] ])->first();

                $sale_add_permission = DB::table('permissions')->where('name', 'sales-add')->first();
                $sale_add_permission_active = DB::table('role_has_permissions')->where([
                    ['permission_id', $sale_add_permission->id],
                    ['role_id', $role->id]
                ])->first();
              ?>
              @if($sale_index_permission_active || $gift_card_permission_active || $coupon_permission_active || $delivery_permission_active)
              <li><a href="#sale" aria-expanded="false" data-toggle="collapse"> <i class="ti ti-shopping-cart"></i><span>{{__('db.Sale')}}</span></a>
                <ul id="sale" class="collapse list-unstyled ">
                  @if($sale_index_permission_active)
                  <li id="sale-list-menu"><a href="{{route('sales.index')}}">{{__('db.Sale List')}}</a></li>
                    @if($sale_add_permission_active)
                    <li><a href="{{route('sale.pos')}}">POS</a></li>
                    <li id="sale-create-menu"><a href="{{route('sales.create')}}">{{__('db.Add Sale')}}</a></li>
                    <li id="sale-import-menu"><a href="{{url('sales/sale_by_csv')}}">{{__('db.Import Sale By CSV')}}</a></li>
                    @endif
                  @endif

                  @if($gift_card_permission_active)
                  <li id="gift-card-menu"><a href="{{route('gift_cards.index')}}">{{__('db.Gift Card List')}}</a> </li>
                  @endif
                  @if($coupon_permission_active)
                  <li id="coupon-menu"><a href="{{route('coupons.index')}}">{{__('db.Coupon List')}}</a> </li>
                  @endif
                  @if($delivery_permission_active)
                  <li id="delivery-menu"><a href="{{route('delivery.index')}}">{{__('db.Delivery List')}}</a></li>
                  @endif
                </ul>
              </li>
              @endif

              <?php
                $index_permission = DB::table('permissions')->where('name', 'expenses-index')->first();
                $index_permission_active = DB::table('role_has_permissions')->where([
                        ['permission_id', $index_permission->id],
                        ['role_id', $role->id]
                    ])->first();
              ?>
              @if($index_permission_active)
              <li><a href="#expense" aria-expanded="false" data-toggle="collapse"> <i class="ti ti-wallet"></i><span>{{__('db.Expense')}}</span></a>
                <ul id="expense" class="collapse list-unstyled ">
                  <li id="exp-cat-menu"><a href="{{route('expense_categories.index')}}">{{__('db.Expense Category')}}</a></li>
                  <li id="exp-list-menu"><a href="{{route('expenses.index')}}">{{__('db.Expense List')}}</a></li>
                  <?php
                    $add_permission = DB::table('permissions')->where('name', 'expenses-add')->first();
                    $add_permission_active = DB::table('role_has_permissions')->where([
                        ['permission_id', $add_permission->id],
                        ['role_id', $role->id]
                    ])->first();
                  ?>
                  @if($add_permission_active)
                  <li><a id="add-expense" href=""> {{__('db.Add Expense')}}</a></li>
                  @endif
                </ul>
              </li>
              @endif
              <?php
                $index_permission = DB::table('permissions')->where('name', 'quotes-index')->first();
                $index_permission_active = DB::table('role_has_permissions')->where([
                        ['permission_id', $index_permission->id],
                        ['role_id', $role->id]
                    ])->first();
              ?>
              @if($index_permission_active)
              <li><a href="#quotation" aria-expanded="false" data-toggle="collapse"> <i class="ti ti-document"></i><span>{{__('db.quotation')}}</span><span></a>
                <ul id="quotation" class="collapse list-unstyled ">
                  <li id="quotation-list-menu"><a href="{{route('quotations.index')}}">{{__('db.Quotation List')}}</a></li>
                  <?php
                    $add_permission = DB::table('permissions')->where('name', 'quotes-add')->first();
                    $add_permission_active = DB::table('role_has_permissions')->where([
                        ['permission_id', $add_permission->id],
                        ['role_id', $role->id]
                    ])->first();
                  ?>
                  @if($add_permission_active)
                  <li id="quotation-create-menu"><a href="{{route('quotations.create')}}">{{__('db.Add Quotation')}}</a></li>
                  @endif
                </ul>
              </li>
              @endif
              <?php
                $index_permission = DB::table('permissions')->where('name', 'transfers-index')->first();
                $index_permission_active = DB::table('role_has_permissions')->where([
                        ['permission_id', $index_permission->id],
                        ['role_id', $role->id]
                    ])->first();
              ?>
              @if($index_permission_active)
              <li><a href="#transfer" aria-expanded="false" data-toggle="collapse"> <i class="ti ti-export"></i><span>{{__('db.Transfer')}}</span></a>
                <ul id="transfer" class="collapse list-unstyled ">
                  <li id="transfer-list-menu"><a href="{{route('transfers.index')}}">{{__('db.Transfer List')}}</a></li>
                  <?php
                    $add_permission = DB::table('permissions')->where('name', 'transfers-add')->first();
                    $add_permission_active = DB::table('role_has_permissions')->where([
                        ['permission_id', $add_permission->id],
                        ['role_id', $role->id]
                    ])->first();
                  ?>
                  @if($add_permission_active)
                  <li id="transfer-create-menu"><a href="{{route('transfers.create')}}">{{__('db.Add Transfer')}}</a></li>
                  <li id="transfer-import-menu"><a href="{{url('transfers/transfer_by_csv')}}">{{__('db.Import Transfer By CSV')}}</a></li>
                  @endif
                </ul>
              </li>
              @endif

              <?php
                $sale_return_index_permission = DB::table('permissions')->where('name', 'returns-index')->first();

                $sale_return_index_permission_active = DB::table('role_has_permissions')->where([
                        ['permission_id', $sale_return_index_permission->id],
                        ['role_id', $role->id]
                    ])->first();

                $purchase_return_index_permission = DB::table('permissions')->where('name', 'purchase-return-index')->first();

                $purchase_return_index_permission_active = DB::table('role_has_permissions')->where([
                            ['permission_id', $purchase_return_index_permission->id],
                            ['role_id', $role->id]
                        ])->first();
              ?>
              @if($sale_return_index_permission_active || $purchase_return_index_permission_active)
              <li><a href="#return" aria-expanded="false" data-toggle="collapse"> <i class="ti ti-arrow-back"></i><span>{{__('db.return')}}</span></a>
                <ul id="return" class="collapse list-unstyled ">
                  @if($sale_return_index_permission_active)
                  <li id="sale-return-menu"><a href="{{route('return-sale.index')}}">{{__('db.Sale')}}</a></li>
                  @endif
                  @if($purchase_return_index_permission_active)
                  <li id="purchase-return-menu"><a href="{{route('return-purchase.index')}}">{{__('db.Purchase')}}</a></li>
                  @endif
                </ul>
              </li>
              @endif
              <?php
                $index_permission = DB::table('permissions')->where('name', 'account-index')->first();
                $index_permission_active = DB::table('role_has_permissions')->where([
                        ['permission_id', $index_permission->id],
                        ['role_id', $role->id]
                    ])->first();

                $money_transfer_permission = DB::table('permissions')->where('name', 'money-transfer')->first();
                $money_transfer_permission_active = DB::table('role_has_permissions')->where([
                        ['permission_id', $money_transfer_permission->id],
                        ['role_id', $role->id]
                    ])->first();

                $balance_sheet_permission = DB::table('permissions')->where('name', 'balance-sheet')->first();
                $balance_sheet_permission_active = DB::table('role_has_permissions')->where([
                        ['permission_id', $balance_sheet_permission->id],
                        ['role_id', $role->id]
                    ])->first();

                $account_statement_permission = DB::table('permissions')->where('name', 'account-statement')->first();
                $account_statement_permission_active = DB::table('role_has_permissions')->where([
                        ['permission_id', $account_statement_permission->id],
                        ['role_id', $role->id]
                    ])->first();

              ?>
              @if($index_permission_active || $balance_sheet_permission_active || $account_statement_permission_active || $money_transfer_permission_active)
              <li class=""><a href="#account" aria-expanded="false" data-toggle="collapse"> <i class="ti ti-briefcase"></i><span>{{__('db.Accounting')}}</span></a>
                <ul id="account" class="collapse list-unstyled ">
                  @if($index_permission_active)
                  <li id="account-list-menu"><a href="{{route('accounts.index')}}">{{__('db.Account List')}}</a></li>
                  <li><a id="add-account" href="">{{__('db.Add Account')}}</a></li>
                  @endif
                  @if($money_transfer_permission_active)
                  <li id="money-transfer-menu"><a href="{{route('money-transfers.index')}}">{{__('db.Money Transfer')}}</a></li>
                  @endif
                  @if($balance_sheet_permission_active)
                  <li id="balance-sheet-menu"><a href="{{route('accounts.balancesheet')}}">{{__('db.Balance Sheet')}}</a></li>
                  @endif
                  @if($account_statement_permission_active)
                  <li id="account-statement-menu"><a id="account-statement" href="">{{__('db.Account Statement')}}</a></li>
                  @endif
                </ul>
              </li>
              @endif
              <?php
                $department = DB::table('permissions')->where('name', 'department')->first();
                $department_active = DB::table('role_has_permissions')->where([
                        ['permission_id', $department->id],
                        ['role_id', $role->id]
                    ])->first();
                $index_employee = DB::table('permissions')->where('name', 'employees-index')->first();
                $index_employee_active = DB::table('role_has_permissions')->where([
                        ['permission_id', $index_employee->id],
                        ['role_id', $role->id]
                    ])->first();
                $attendance = DB::table('permissions')->where('name', 'attendance')->first();
                $attendance_active = DB::table('role_has_permissions')->where([
                        ['permission_id', $attendance->id],
                        ['role_id', $role->id]
                    ])->first();
                $payroll = DB::table('permissions')->where('name', 'payroll')->first();
                $payroll_active = DB::table('role_has_permissions')->where([
                        ['permission_id', $payroll->id],
                        ['role_id', $role->id]
                    ])->first();
              ?>

              @if(Auth::user()->role_id != 5)
              <li class=""><a href="#hrm" aria-expanded="false" data-toggle="collapse"> <i class="ti ti-user-group"></i><span>HRM</span></a>
                <ul id="hrm" class="collapse list-unstyled ">
                  @if($department_active)
                  <li id="dept-menu"><a href="{{route('departments.index')}}">{{__('db.Department')}}</a></li>
                  @endif
                  @if($index_employee_active)
                  <li id="employee-menu"><a href="{{route('employees.index')}}">{{__('db.Employee')}}</a></li>
                  @endif
                  @if($attendance_active)
                  <li id="attendance-menu"><a href="{{route('attendance.index')}}">{{__('db.Attendance')}}</a></li>
                  @endif
                  @if($payroll_active)
                  <li id="payroll-menu"><a href="{{route('payroll.index')}}">{{__('db.Payroll')}}</a></li>
                  @endif
                  <li id="holiday-menu"><a href="{{route('holidays.index')}}">{{__('db.Holiday')}}</a></li>
                </ul>
              </li>
              @endif

              <?php
                  $user_index_permission_active = DB::table('permissions')
                      ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                      ->where([
                        ['permissions.name', 'users-index'],
                        ['role_id', $role->id] ])->first();

                  $customer_index_permission = DB::table('permissions')->where('name', 'customers-index')->first();

                  $customer_index_permission_active = DB::table('role_has_permissions')->where([
                            ['permission_id', $customer_index_permission->id],
                            ['role_id', $role->id]
                        ])->first();

                  $biller_index_permission = DB::table('permissions')->where('name', 'billers-index')->first();

                  $biller_index_permission_active = DB::table('role_has_permissions')->where([
                            ['permission_id', $biller_index_permission->id],
                            ['role_id', $role->id]
                        ])->first();

                  $supplier_index_permission = DB::table('permissions')->where('name', 'suppliers-index')->first();

                  $supplier_index_permission_active = DB::table('role_has_permissions')->where([
                            ['permission_id', $supplier_index_permission->id],
                            ['role_id', $role->id]
                        ])->first();
              ?>
              @if($user_index_permission_active || $customer_index_permission_active || $biller_index_permission_active || $supplier_index_permission_active)
              <li><a href="#people" aria-expanded="false" data-toggle="collapse"> <i class="ti ti-user"></i><span>{{__('db.People')}}</span></a>
                <ul id="people" class="collapse list-unstyled ">

                  @if($user_index_permission_active)
                  <li id="user-list-menu"><a href="{{route('user.index')}}">{{__('db.User List')}}</a></li>
                  <?php $user_add_permission_active = DB::table('permissions')
                        ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                        ->where([
                          ['permissions.name', 'users-add'],
                          ['role_id', $role->id] ])->first();
                  ?>
                  @if($user_add_permission_active)
                  <li id="user-create-menu"><a href="{{route('user.create')}}">{{__('db.Add User')}}</a></li>
                  @endif
                  @endif

                  @if($customer_index_permission_active)
                  <li id="customer-list-menu"><a href="{{route('customer.index')}}">{{__('db.Customer List')}}</a></li>
                  <?php
                    $customer_add_permission = DB::table('permissions')->where('name', 'customers-add')->first();
                    $customer_add_permission_active = DB::table('role_has_permissions')->where([
                        ['permission_id', $customer_add_permission->id],
                        ['role_id', $role->id]
                    ])->first();
                  ?>
                  @if($customer_add_permission_active)
                  <li id="customer-create-menu"><a href="{{route('customer.create')}}">{{__('db.Add Customer')}}</a></li>
                  @endif
                  @endif

                  @if($biller_index_permission_active)
                  <li id="biller-list-menu"><a href="{{route('biller.index')}}">{{__('db.Biller List')}}</a></li>
                  <?php
                    $biller_add_permission = DB::table('permissions')->where('name', 'billers-add')->first();
                    $biller_add_permission_active = DB::table('role_has_permissions')->where([
                        ['permission_id', $biller_add_permission->id],
                        ['role_id', $role->id]
                    ])->first();
                  ?>
                  @if($biller_add_permission_active)
                  <li id="biller-create-menu"><a href="{{route('biller.create')}}">{{__('db.Add Biller')}}</a></li>
                  @endif
                  @endif

                  @if($supplier_index_permission_active)
                  <li id="supplier-list-menu"><a href="{{route('supplier.index')}}">{{__('db.Supplier List')}}</a></li>
                  <?php
                    $supplier_add_permission = DB::table('permissions')->where('name', 'suppliers-add')->first();
                    $supplier_add_permission_active = DB::table('role_has_permissions')->where([
                        ['permission_id', $supplier_add_permission->id],
                        ['role_id', $role->id]
                    ])->first();
                  ?>
                  @if($supplier_add_permission_active)
                  <li id="supplier-create-menu"><a href="{{route('supplier.create')}}">{{__('db.Add Supplier')}}</a></li>
                  @endif
                  @endif
                </ul>
              </li>
              @endif

              <?php
                $profit_loss_active = DB::table('permissions')
                      ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                      ->where([
                        ['permissions.name', 'profit-loss'],
                        ['role_id', $role->id] ])->first();
                $best_seller_active = DB::table('permissions')
                      ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                      ->where([
                        ['permissions.name', 'best-seller'],
                        ['role_id', $role->id] ])->first();
                $warehouse_report_active = DB::table('permissions')
                      ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                      ->where([
                        ['permissions.name', 'warehouse-report'],
                        ['role_id', $role->id] ])->first();
                $warehouse_stock_report_active = DB::table('permissions')
                      ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                      ->where([
                        ['permissions.name', 'warehouse-stock-report'],
                        ['role_id', $role->id] ])->first();
                $product_report_active = DB::table('permissions')
                      ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                      ->where([
                        ['permissions.name', 'product-report'],
                        ['role_id', $role->id] ])->first();
                $daily_sale_active = DB::table('permissions')
                      ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                      ->where([
                        ['permissions.name', 'daily-sale'],
                        ['role_id', $role->id] ])->first();
                $monthly_sale_active = DB::table('permissions')
                      ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                      ->where([
                        ['permissions.name', 'monthly-sale'],
                        ['role_id', $role->id]])->first();
                $daily_purchase_active = DB::table('permissions')
                      ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                      ->where([
                        ['permissions.name', 'daily-purchase'],
                        ['role_id', $role->id] ])->first();
                $monthly_purchase_active = DB::table('permissions')
                      ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                      ->where([
                        ['permissions.name', 'monthly-purchase'],
                        ['role_id', $role->id] ])->first();
                $purchase_report_active = DB::table('permissions')
                      ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                      ->where([
                        ['permissions.name', 'purchase-report'],
                        ['role_id', $role->id] ])->first();
                $sale_report_active = DB::table('permissions')
                      ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                      ->where([
                        ['permissions.name', 'sale-report'],
                        ['role_id', $role->id] ])->first();
                $payment_report_active = DB::table('permissions')
                      ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                      ->where([
                        ['permissions.name', 'payment-report'],
                        ['role_id', $role->id] ])->first();
                $product_qty_alert_active = DB::table('permissions')
                      ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                      ->where([
                        ['permissions.name', 'product-qty-alert'],
                        ['role_id', $role->id] ])->first();
                $user_report_active = DB::table('permissions')
                      ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                      ->where([
                        ['permissions.name', 'user-report'],
                        ['role_id', $role->id] ])->first();

                $customer_report_active = DB::table('permissions')
                      ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                      ->where([
                        ['permissions.name', 'customer-report'],
                        ['role_id', $role->id] ])->first();
                $supplier_report_active = DB::table('permissions')
                      ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                      ->where([
                        ['permissions.name', 'supplier-report'],
                        ['role_id', $role->id] ])->first();
                $due_report_active = DB::table('permissions')
                      ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                      ->where([
                        ['permissions.name', 'due-report'],
                        ['role_id', $role->id] ])->first();
              ?>
              @if($profit_loss_active || $best_seller_active || $warehouse_report_active || $warehouse_stock_report_active || $product_report_active || $daily_sale_active || $monthly_sale_active || $daily_purchase_active || $monthly_purchase_active || $purchase_report_active || $sale_report_active || $payment_report_active || $product_qty_alert_active || $user_report_active || $customer_report_active || $supplier_report_active || $due_report_active)
              <li><a href="#report" aria-expanded="false" data-toggle="collapse"> <i class="ti ti-document-remove"></i><span>{{__('db.Reports')}}</span></a>
                <ul id="report" class="collapse list-unstyled ">
                  @if($profit_loss_active)
                  <li id="profit-loss-report-menu">
                    <form action="{{ route('report.profitLoss') }}" method="post" id="profitLoss-report-form">
                      @csrf
                      <input type="hidden" name="start_date" value="{{date('Y-m').'-'.'01'}}" />
                      <input type="hidden" name="end_date" value="{{date('Y-m-d')}}" />
                      <a id="profitLoss-link" href="">{{__('db.Summary Report')}}</a>
                    </form>
                  </li>
                  @endif
                  @if($best_seller_active)
                  <li id="best-seller-report-menu">
                    <a href="{{url('report/best_seller')}}">{{__('db.Best Seller')}}</a>
                  </li>
                  @endif
                  @if($product_report_active)
                  <li id="product-report-menu">
                    <form action="{{ route('report.product') }}" method="get" id="product-report-form">
                      <input type="hidden" name="start_date" value="{{date('Y-m').'-'.'01'}}" />
                      <input type="hidden" name="end_date" value="{{date('Y-m-d')}}" />
                      <input type="hidden" name="warehouse_id" value="0" />
                      <a id="report-link" href="">{{__('db.Product Report')}}</a>
                    </form>
                  </li>
                  @endif
                  @if($daily_sale_active)
                  <li id="daily-sale-report-menu">
                    <a href="{{url('report/daily_sale/'.date('Y').'/'.date('m'))}}">{{__('db.Daily Sale')}}</a>
                  </li>
                  @endif
                  @if($monthly_sale_active)
                  <li id="monthly-sale-report-menu">
                    <a href="{{url('report/monthly_sale/'.date('Y'))}}">{{__('db.Monthly Sale')}}</a>
                  </li>
                  @endif
                  @if($daily_purchase_active)
                  <li id="daily-purchase-report-menu">
                    <a href="{{url('report/daily_purchase/'.date('Y').'/'.date('m'))}}">{{__('db.Daily Purchase')}}</a>
                  </li>
                  @endif
                  @if($monthly_purchase_active)
                  <li id="monthly-purchase-report-menu">
                    <a href="{{url('report/monthly_purchase/'.date('Y'))}}">{{__('db.Monthly Purchase')}}</a>
                  </li>
                  @endif
                  @if($sale_report_active)
                  <li id="sale-report-menu">
                    <form action="{{ route('report.sale') }}" method="post" id="sale-report-form">
                      @csrf
                      <input type="hidden" name="start_date" value="{{date('Y-m').'-'.'01'}}" />
                      <input type="hidden" name="end_date" value="{{date('Y-m-d')}}" />
                      <input type="hidden" name="warehouse_id" value="0" />
                      <a id="sale-report-link" href="">{{__('db.Sale Report')}}</a>
                    </form>
                  </li>
                  @endif
                  @if($payment_report_active)
                  <li id="payment-report-menu">
                    <form action="{{ route('report.paymentByDate') }}" method="post" id="payment-report-form">
                      @csrf
                      <input type="hidden" name="start_date" value="{{date('Y-m').'-'.'01'}}" />
                      <input type="hidden" name="end_date" value="{{date('Y-m-d')}}" />
                      <a id="payment-report-link" href="">{{__('db.Payment Report')}}</a>
                    </form>
                  </li>
                  @endif
                  @if($purchase_report_active)
                  <li id="purchase-report-menu">
                    <form action="{{ route('report.purchase') }}" method="post" id="purchase-report-form">
                      @csrf
                      <input type="hidden" name="start_date" value="{{date('Y-m').'-'.'01'}}" />
                      <input type="hidden" name="end_date" value="{{date('Y-m-d')}}" />
                      <input type="hidden" name="warehouse_id" value="0" />
                      <a id="purchase-report-link" href="">{{__('db.Purchase Report')}}</a>
                    </form>
                  </li>
                  @endif
                  @if($warehouse_report_active)
                  <li id="warehouse-report-menu">
                    <a id="warehouse-report-link" href="">{{__('db.Warehouse Report')}}</a>
                  </li>
                  @endif
                  @if($warehouse_stock_report_active)
                  <li id="warehouse-stock-report-menu">
                    <a href="{{route('report.warehouseStock')}}">{{__('db.Warehouse Stock Chart')}}</a>
                  </li>
                  @endif
                  @if($product_qty_alert_active)
                  <li id="qtyAlert-report-menu">
                    <a href="{{route('report.qtyAlert')}}">{{__('db.Product Quantity Alert')}}</a>
                  </li>
                  @endif
                  @if($user_report_active)
                  <li id="user-report-menu">
                    <a id="user-report-link" href="">{{__('db.User Report')}}</a>
                  </li>
                  @endif
                  @if($customer_report_active)
                  <li id="customer-report-menu">
                    <a id="customer-report-link" href="">{{__('db.Customer Report')}}</a>
                  </li>
                  @endif
                  @if($supplier_report_active)
                  <li id="supplier-report-menu">
                    <a id="supplier-report-link" href="">{{__('db.Supplier Report')}}</a>
                  </li>
                  @endif
                  @if($due_report_active)
                  <li id="due-report-menu">
                    <form action="{{ route('report.dueByDate') }}" method="post" id="due-report-form">
                      @csrf
                      <input type="hidden" name="start_date" value="{{date('Y-m').'-'.'01'}}" />
                      <input type="hidden" name="end_date" value="{{date('Y-m-d')}}" />
                      <a id="due-report-link" href="">{{__('db.Due Report')}}</a>
                    </form>
                  </li>
                  @endif
                </ul>
              </li>
              @endif

              <li><a href="#setting" aria-expanded="false" data-toggle="collapse"> <i class="ti ti-settings"></i><span>{{__('db.settings')}}</span></a>
                <ul id="setting" class="collapse list-unstyled ">
                  <?php
                      $send_notification_permission = DB::table('permissions')->where('name', 'send_notification')->first();
                      $send_notification_permission_active = DB::table('role_has_permissions')->where([
                                  ['permission_id', $send_notification_permission->id],
                                  ['role_id', $role->id]
                              ])->first();

                      $warehouse_permission = DB::table('permissions')->where('name', 'warehouse')->first();
                      $warehouse_permission_active = DB::table('role_has_permissions')->where([
                                  ['permission_id', $warehouse_permission->id],
                                  ['role_id', $role->id]
                              ])->first();

                      $customer_group_permission = DB::table('permissions')->where('name', 'customer_group')->first();
                      $customer_group_permission_active = DB::table('role_has_permissions')->where([
                                  ['permission_id', $customer_group_permission->id],
                                  ['role_id', $role->id]
                              ])->first();

                      $brand_permission = DB::table('permissions')->where('name', 'brand')->first();
                      $brand_permission_active = DB::table('role_has_permissions')->where([
                                  ['permission_id', $brand_permission->id],
                                  ['role_id', $role->id]
                              ])->first();

                      $unit_permission = DB::table('permissions')->where('name', 'unit')->first();
                      $unit_permission_active = DB::table('role_has_permissions')->where([
                                  ['permission_id', $unit_permission->id],
                                  ['role_id', $role->id]
                              ])->first();

                      $currency_permission = DB::table('permissions')->where('name', 'currency')->first();
                      $currency_permission_active = DB::table('role_has_permissions')->where([
                                  ['permission_id', $currency_permission->id],
                                  ['role_id', $role->id]
                              ])->first();

                      $tax_permission = DB::table('permissions')->where('name', 'tax')->first();
                      $tax_permission_active = DB::table('role_has_permissions')->where([
                                  ['permission_id', $tax_permission->id],
                                  ['role_id', $role->id]
                              ])->first();

                      $general_setting_permission = DB::table('permissions')->where('name', 'general_setting')->first();
                      $general_setting_permission_active = DB::table('role_has_permissions')->where([
                                  ['permission_id', $general_setting_permission->id],
                                  ['role_id', $role->id]
                              ])->first();

                      $backup_database_permission = DB::table('permissions')->where('name', 'backup_database')->first();
                      $backup_database_permission_active = DB::table('role_has_permissions')->where([
                                  ['permission_id', $backup_database_permission->id],
                                  ['role_id', $role->id]
                              ])->first();

                      $mail_setting_permission = DB::table('permissions')->where('name', 'mail_setting')->first();
                      $mail_setting_permission_active = DB::table('role_has_permissions')->where([
                          ['permission_id', $mail_setting_permission->id],
                          ['role_id', $role->id]
                      ])->first();

                      $sms_setting_permission = DB::table('permissions')->where('name', 'sms_setting')->first();
                      $sms_setting_permission_active = DB::table('role_has_permissions')->where([
                          ['permission_id', $sms_setting_permission->id],
                          ['role_id', $role->id]
                      ])->first();

                      $create_sms_permission = DB::table('permissions')->where('name', 'create_sms')->first();
                      $create_sms_permission_active = DB::table('role_has_permissions')->where([
                          ['permission_id', $create_sms_permission->id],
                          ['role_id', $role->id]
                      ])->first();

                      $pos_setting_permission = DB::table('permissions')->where('name', 'pos_setting')->first();
                      $pos_setting_permission_active = DB::table('role_has_permissions')->where([
                          ['permission_id', $pos_setting_permission->id],
                          ['role_id', $role->id]
                      ])->first();

                      $hrm_setting_permission = DB::table('permissions')->where('name', 'hrm_setting')->first();
                      $hrm_setting_permission_active = DB::table('role_has_permissions')->where([
                          ['permission_id', $hrm_setting_permission->id],
                          ['role_id', $role->id]
                      ])->first();

                      $reward_point_setting_permission = DB::table('permissions')->where('name', 'reward_point_setting')->first();
                      $reward_point_setting_permission_active = DB::table('role_has_permissions')->where([
                          ['permission_id', $reward_point_setting_permission->id],
                          ['role_id', $role->id]
                      ])->first();
                  ?>
                  @if($role->id <= 2)
                  <li id="role-menu"><a href="{{route('role.index')}}">{{__('db.Role Permission')}}</a></li>
                  @endif
                  @if($send_notification_permission_active)
                  <li id="notification-menu">
                    <a href="" id="send-notification">{{__('db.Send Notification')}}</a>
                  </li>
                  @endif
                  @if($warehouse_permission_active)
                  <li id="warehouse-menu"><a href="{{route('warehouse.index')}}">{{__('db.Warehouse')}}</a></li>
                  @endif
                  @if($customer_group_permission_active)
                  <li id="customer-group-menu"><a href="{{route('customer_group.index')}}">{{__('db.Customer Group')}}</a></li>
                  @endif
                  @if($brand_permission_active)
                  <li id="brand-menu"><a href="{{route('brand.index')}}">{{__('db.Brand')}}</a></li>
                  @endif
                  @if($unit_permission_active)
                  <li id="unit-menu"><a href="{{route('unit.index')}}">{{__('db.Unit')}}</a></li>
                  @endif
                  @if($currency_permission_active)
                  <li id="currency-menu"><a href="{{route('currency.index')}}">{{__('db.Currency')}}</a></li>
                  @endif
                  @if($tax_permission_active)
                  <li id="tax-menu"><a href="{{route('tax.index')}}">{{__('db.Tax')}}</a></li>
                  @endif
                  <li id="user-menu"><a href="{{route('user.profile', ['id' => Auth::id()])}}">{{__('db.User Profile')}}</a></li>
                  @if($create_sms_permission_active)
                  <li id="create-sms-menu"><a href="{{route('setting.createSms')}}">{{__('db.Create SMS')}}</a></li>
                  @endif
                  @if($backup_database_permission_active)
                  <li><a href="{{route('setting.backup')}}">{{__('db.Backup Database')}}</a></li>
                  @endif
                  @if($general_setting_permission_active)
                  <li id="general-setting-menu"><a href="{{route('setting.general')}}">{{__('db.General Setting')}}</a></li>
                  @endif
                  @if($mail_setting_permission_active)
                  <li id="mail-setting-menu"><a href="{{route('setting.mail')}}">{{__('db.Mail Setting')}}</a></li>
                  @endif
                  @if($reward_point_setting_permission_active)
                  <li id="reward-point-setting-menu"><a href="{{route('setting.rewardPoint')}}">{{__('db.Reward Point Setting')}}</a></li>
                  @endif
                  @if($sms_setting_permission_active)
                  <li id="sms-setting-menu"><a href="{{route('setting.sms')}}">{{__('db.SMS Setting')}}</a></li>
                  @endif
                  @if($pos_setting_permission_active)
                  <li id="pos-setting-menu"><a href="{{route('setting.pos')}}">POS {{__('db.settings')}}</a></li>
                  @endif
                  @if($hrm_setting_permission_active)
                  <li id="hrm-setting-menu"><a href="{{route('setting.hrm')}}"> {{__('db.HRM Setting')}}</a></li>
                  @endif
                </ul>
              </li>
            </ul>
          </div>
        </div>
      </nav>
      <!-- navbar-->
      <header class="header">
        <nav class="navbar">
          <div class="container-fluid">
            <div class="navbar-holder d-flex align-items-center justify-content-between">
              <a id="toggle-btn" href="#" class="menu-btn"><i class="ti ti-menu-deep"> </i></a>
              <span class="brand-big">
                @if(gen_setting()->site_logo)
                <a href="{{url('/')}}"><img src="{{url('logo', gen_setting()->site_logo)}}" width="115"></a>
                @else
                  <a href="{{url('/')}}"><h1 class="d-inline">{{gen_setting()->site_title}}</h1></a>
                @endif
              </span>

              <ul class="nav-menu list-unstyled d-flex flex-md-row align-items-md-center">
                <?php
                  $add_permission = DB::table('permissions')->where('name', 'sales-add')->first();
                  $add_permission_active = DB::table('role_has_permissions')->where([
                      ['permission_id', $add_permission->id],
                      ['role_id', $role->id]
                  ])->first();

                  $business_reset_permission = DB::table('permissions')->where('name', 'business_reset')->first();
                  $business_reset_permission_active = DB::table('role_has_permissions')->where([
                      ['permission_id', $business_reset_permission->id],
                      ['role_id', $role->id]
                  ])->first();
                ?>
                @if($add_permission_active)
                <li class="nav-item"><a class="dropdown-item btn-pos btn-sm" href="{{route('sale.pos')}}"><i class="ti ti-shopping-bag"></i><span> POS</span></a></li>
                @endif
                <li class="nav-item"><a id="btnFullscreen" data-toggle="tooltip" title="{{__('db.Full Screen')}}"><i class="ti ti-arrows-maximize"></i></a></li>
                @if(\Auth::user()->role_id <= 2)
                  <li class="nav-item"><a href="{{route('cashRegister.index')}}" data-toggle="tooltip" title="{{__('db.Cash Register List')}}"><i class="ti ti-archive"></i></a></li>
                @endif
                @php
                  $alertProductCount = (int) ($alert_product ?? 0);
                  $unreadCount = Auth::user() ? count(\Auth::user()->unreadNotifications) : 0;
                  $totalAlerts = $alertProductCount + $unreadCount;
                @endphp
                @if($product_qty_alert_active ?? false)
                  @if($totalAlerts > 0)
                  <li class="nav-item" id="notification-icon">
                        <a rel="nofollow" data-toggle="tooltip" title="{{__('Notifications')}}" class="nav-link dropdown-item"><i class="ti ti-bell"></i><span class="badge badge-danger notification-number">{{$totalAlerts}}</span>
                        </a>
                        <ul class="right-sidebar">
                            @if($alertProductCount > 0)
                            <li class="notifications">
                              <a href="{{route('report.qtyAlert')}}" class="btn btn-link"> {{$alertProductCount}} product exceeds alert quantity</a>
                            </li>
                            @endif
                            @if(Auth::user())
                              @foreach(\Auth::user()->unreadNotifications as $key => $notification)
                                  <li class="notifications">
                                      <a href="#" class="btn btn-link">{{ $notification->data['message'] }}</a>
                                  </li>
                              @endforeach
                            @endif
                        </ul>
                  </li>
                  @endif
                @elseif($unreadCount > 0)
                  <li class="nav-item" id="notification-icon">
                        <a rel="nofollow" data-toggle="tooltip" title="{{__('Notifications')}}" class="nav-link dropdown-item"><i class="ti ti-bell"></i><span class="badge badge-danger notification-number">{{$unreadCount}}</span>
                        </a>
                        <ul class="right-sidebar">
                            @if(Auth::user())
                              @foreach(\Auth::user()->unreadNotifications as $key => $notification)
                                  <li class="notifications">
                                      <a href="#" class="btn btn-link">{{ $notification->data['message'] }}</a>
                                  </li>
                              @endforeach
                            @endif
                        </ul>
                  </li>
                @endif
                <li class="nav-item">
                      <a rel="nofollow" data-toggle="tooltip" class="nav-link dropdown-item"><i class="ti ti-world"></i></a>
                      <ul class="right-sidebar">
                        @foreach ($languages as $language)
                          <li>
                              <a href="{{ url('language_switch/'.$language->id) }}" class="btn btn-link">{{ $language->name }}</a>
                          </li>
                        @endforeach
                      </ul>

                      @if (!config('app.user_verified'))
                        @if ($language_setting_active)
                            <li id="languages" style="background-color: #f0f0f0; padding: 10px; border-radius: 5px;">
                                <a href="{{route('languages')}}"> {{__('db.Languages')}} <span style="font-size: 16px;">→</span></i></a>
                            </li>
                        @endif
                    @endif
                </li>
                <li class="nav-item">
                  <a rel="nofollow" data-toggle="tooltip" class="nav-link dropdown-item"><i class="ti ti-user"></i> <span>{{ucfirst(Auth::user()->name)}}</span> <i class="ti ti-angle-down"></i>
                  </a>
                  <ul class="right-sidebar">
                      <li>
                        <a href="{{route('user.profile', ['id' => Auth::id()])}}"><i class="ti ti-user"></i> {{__('db.profile')}}</a>
                      </li>
                      @if($general_setting_permission_active)
                      <li>
                        <a href="{{route('setting.general')}}"><i class="ti ti-settings"></i> {{__('db.settings')}}</a>
                      </li>
                      @endif
                      <li>
                        <a href="{{url('my-transactions/'.date('Y').'/'.date('m'))}}"><i class="ti ti-calendar-dollar"></i> {{__('db.My Transaction')}}</a>
                      </li>
                      @if(Auth::user()->role_id != 5)
                      <li>
                        <a href="{{url('holidays/my-holiday/'.date('Y').'/'.date('m'))}}"><i class="ti ti-luggage"></i> {{__('db.My Holiday')}}</a>
                      </li>
                      @endif
                      @if ($business_reset_permission_active)
                      <li>
                          <a href="{{ route('setting.freshBusinessReset') }}"><i class="ti ti-alert-triangle"></i> {{ __('db.fresh_reset_title') }}</a>
                      </li>
                      @endif
                      <li>
                        <a href="{{ route('logout') }}"
                           onclick="event.preventDefault();
                                         document.getElementById('logout-form').submit();"><i class="ti ti-power"></i>
                            {{__('db.logout')}}
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                      </li>
                  </ul>
                </li>
              </ul>
            </div>
          </div>
        </nav>
      </header>
    <div class="page">

      <!-- notification modal -->
      <div id="notification-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
        <div role="document" class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="exampleModalLabel" class="modal-title">{{__('db.Send Notification')}}</h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
                </div>
                <div class="modal-body">
                  <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                    <form action="{{ route('notifications.store') }}" method="post">
                      @csrf
                      <div class="row">
                          <?php
                              $lims_user_list = DB::table('users')->where([
                                ['is_active', true],
                                ['id', '!=', \Auth::user()->id]
                              ])->get();
                          ?>
                          <div class="col-md-6 form-group">
                              <label>{{__('db.User')}} *</label>
                              <select name="user_id" class="selectpicker form-control" required data-live-search="true" data-live-search-style="begins" title="Select user...">
                                  @foreach($lims_user_list as $user)
                                  <option value="{{$user->id}}">{{$user->name . ' (' . $user->email. ')'}}</option>
                                  @endforeach
                              </select>
                          </div>
                          <div class="col-md-12 form-group">
                              <label>{{__('db.Message')}} *</label>
                              <textarea rows="5" name="message" class="form-control" required></textarea>
                          </div>
                      </div>
                      <div class="form-group">
                          <button type="submit" class="btn btn-primary">{{__('db.submit')}}</button>
                      </div>
                    </form>
                </div>
            </div>
        </div>
      </div>
      <!-- end notification modal -->

      <!-- expense modal -->
      <div id="expense-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
        <div role="document" class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="exampleModalLabel" class="modal-title">{{__('db.Add Expense')}}</h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
                </div>
                <div class="modal-body">
                  <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                    <form action="{{ route('expenses.store') }}" method="post">
                      @csrf
                    <?php
                      $lims_expense_category_list = DB::table('expense_categories')->where('is_active', true)->get();
                      if(Auth::user()->role_id > 2)
                        $lims_warehouse_list = DB::table('warehouses')->where([
                          ['is_active', true],
                          ['id', Auth::user()->warehouse_id]
                        ])->get();
                      else
                        $lims_warehouse_list = DB::table('warehouses')->where('is_active', true)->get();
                      $lims_account_list = app(\App\Services\PaymentAccountService::class)->validOperationalAccounts();

                    ?>
                      <div class="row">
                        <div class="col-md-6 form-group">
                            <label>{{__('db.Expense Category')}} *</label>
                            <select name="expense_category_id" class="selectpicker form-control" required data-live-search="true" data-live-search-style="begins" title="Select Expense Category...">
                                @foreach($lims_expense_category_list as $expense_category)
                                <option value="{{$expense_category->id}}">{{$expense_category->name . ' (' . $expense_category->code. ')'}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>{{__('db.Warehouse')}} *</label>
                            <select name="warehouse_id" class="selectpicker form-control" required data-live-search="true" data-live-search-style="begins" title="Select Warehouse...">
                                @foreach($lims_warehouse_list as $warehouse)
                                <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>{{__('db.Amount')}} *</label>
                            <input type="number" name="amount" step="any" required class="form-control">
                        </div>
                        <div class="col-md-6 form-group">
                            <label> {{__('db.Account')}}</label>
                            <select class="form-control selectpicker" name="account_id">
                            @foreach($lims_account_list as $account)
                                @if($account->is_default)
                                <option selected value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]</option>
                                @else
                                <option value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]</option>
                                @endif
                            @endforeach
                            </select>
                        </div>
                      </div>
                      <div class="form-group">
                          <label>{{__('db.note')}}</label>
                          <textarea name="note" rows="3" class="form-control"></textarea>
                      </div>
                      <div class="form-group">
                          <button type="submit" class="btn btn-primary">{{__('db.submit')}}</button>
                      </div>
                    </form>
                </div>
            </div>
        </div>
      </div>
      <!-- end expense modal -->

      <!-- account modal -->
      <div id="account-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
        <div role="document" class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="exampleModalLabel" class="modal-title">{{__('db.Add Account')}}</h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
                </div>
                <div class="modal-body">
                  <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                    <form action="{{ route('accounts.store') }}" method="post">
                      @csrf
                      <div class="form-group">
                          <label>{{__('db.Account No')}} *</label>
                          <input type="text" name="account_no" required class="form-control">
                      </div>
                      <div class="form-group">
                          <label>{{__('db.name')}} *</label>
                          <input type="text" name="name" required class="form-control">
                      </div>
                      <div class="form-group">
                          <label>{{__('db.Initial Balance')}}</label>
                          <input type="number" name="initial_balance" step="any" class="form-control">
                      </div>
                      <div class="form-group">
                          <label>{{__('db.note')}}</label>
                          <textarea name="note" rows="3" class="form-control"></textarea>
                      </div>
                      <div class="form-group">
                          <button type="submit" class="btn btn-primary">{{__('db.submit')}}</button>
                      </div>
                    </form>
                </div>
            </div>
        </div>
      </div>
      <!-- end account modal -->

      <!-- account statement modal -->
      <div id="account-statement-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
        <div role="document" class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="exampleModalLabel" class="modal-title">{{__('db.Account Statement')}}</h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
                </div>
                <div class="modal-body">
                  <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                    <form action="{{ route('accounts.statement') }}" method="post">
                      @csrf
                      <div class="row">
                        <div class="col-md-6 form-group">
                            <label> {{__('db.Account')}}</label>
                            <select class="form-control selectpicker" name="account_id">
                            @foreach($lims_account_list as $account)
                                <option value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]</option>
                            @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label> {{__('db.Type')}}</label>
                            <select class="form-control selectpicker" name="type">
                                <option value="0">{{__('db.All')}}</option>
                                <option value="1">{{__('db.Debit')}}</option>
                                <option value="2">{{__('db.Credit')}}</option>
                            </select>
                        </div>
                        <div class="col-md-12 form-group">
                            <label>{{__('db.Choose Your Date')}}</label>
                            <div class="input-group">
                                <input type="text" class="daterangepicker-field form-control" required />
                                <input type="hidden" name="start_date" />
                                <input type="hidden" name="end_date" />
                            </div>
                        </div>
                      </div>
                      <div class="form-group">
                          <button type="submit" class="btn btn-primary">{{__('db.submit')}}</button>
                      </div>
                    </form>
                </div>
            </div>
        </div>
      </div>
      <!-- end account statement modal -->

      <!-- warehouse modal -->
      <div id="warehouse-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
        <div role="document" class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="exampleModalLabel" class="modal-title">{{__('db.Warehouse Report')}}</h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
                </div>
                <div class="modal-body">
                  <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                    <form action="{{ route('report.warehouse') }}" method="post">
                      @csrf
                    <?php
                      $lims_warehouse_list = DB::table('warehouses')->where('is_active', true)->get();
                    ?>
                      <div class="form-group">
                          <label>{{__('db.Warehouse')}} *</label>
                          <select name="warehouse_id" class="selectpicker form-control" required data-live-search="true" id="warehouse-id" data-live-search-style="begins" title="Select warehouse...">
                              @foreach($lims_warehouse_list as $warehouse)
                              <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                              @endforeach
                          </select>
                      </div>

                      <input type="hidden" name="start_date" value="{{date('Y-m').'-'.'01'}}" />
                      <input type="hidden" name="end_date" value="{{date('Y-m-d')}}" />

                      <div class="form-group">
                          <button type="submit" class="btn btn-primary">{{__('db.submit')}}</button>
                      </div>
                    </form>
                </div>
            </div>
        </div>
      </div>
      <!-- end warehouse modal -->

      <!-- user modal -->
      <div id="user-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
        <div role="document" class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="exampleModalLabel" class="modal-title">{{__('db.User Report')}}</h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
                </div>
                <div class="modal-body">
                  <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                    <form action="{{ route('report.user') }}" method="post">
                      @csrf
                    <?php
                      $lims_user_list = DB::table('users')->where('is_active', true)->get();
                    ?>
                      <div class="form-group">
                          <label>{{__('db.User')}} *</label>
                          <select name="user_id" class="selectpicker form-control" required data-live-search="true" id="user-id" data-live-search-style="begins" title="Select user...">
                              @foreach($lims_user_list as $user)
                              <option value="{{$user->id}}">{{$user->name . ' (' . $user->phone. ')'}}</option>
                              @endforeach
                          </select>
                      </div>

                      <input type="hidden" name="start_date" value="{{date('Y-m').'-'.'01'}}" />
                      <input type="hidden" name="end_date" value="{{date('Y-m-d')}}" />

                      <div class="form-group">
                          <button type="submit" class="btn btn-primary">{{__('db.submit')}}</button>
                      </div>
                    </form>
                </div>
            </div>
        </div>
      </div>
      <!-- end user modal -->

      <!-- customer modal -->
      <div id="customer-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
        <div role="document" class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="exampleModalLabel" class="modal-title">{{__('db.Customer Report')}}</h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
                </div>
                <div class="modal-body">
                  <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                    <form action="{{ route('report.customer') }}" method="post">
                      @csrf
                    <?php
                      $lims_customer_list = DB::table('customers')->where('is_active', true)->get();
                    ?>
                      <div class="form-group">
                          <label>{{__('db.customer')}} *</label>
                          <select name="customer_id" class="selectpicker form-control" required data-live-search="true" id="customer-id" data-live-search-style="begins" title="Select customer...">
                              @foreach($lims_customer_list as $customer)
                              <option value="{{$customer->id}}">{{$customer->name . ' (' . $customer->phone_number. ')'}}</option>
                              @endforeach
                          </select>
                      </div>

                      <input type="hidden" name="start_date" value="{{date('Y-m').'-'.'01'}}" />
                      <input type="hidden" name="end_date" value="{{date('Y-m-d')}}" />

                      <div class="form-group">
                          <button type="submit" class="btn btn-primary">{{__('db.submit')}}</button>
                      </div>
                    </form>
                </div>
            </div>
        </div>
      </div>
      <!-- end customer modal -->

      <!-- supplier modal -->
      <div id="supplier-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
        <div role="document" class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="exampleModalLabel" class="modal-title">{{__('db.Supplier Report')}}</h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
                </div>
                <div class="modal-body">
                  <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                    <form action="{{ route('report.supplier') }}" method="post">
                      @csrf
                    <?php
                      $lims_supplier_list = DB::table('suppliers')->where('is_active', true)->get();
                    ?>
                      <div class="form-group">
                          <label>{{__('db.Supplier')}} *</label>
                          <select name="supplier_id" class="selectpicker form-control" required data-live-search="true" id="supplier-id" data-live-search-style="begins" title="Select Supplier...">
                              @foreach($lims_supplier_list as $supplier)
                              <option value="{{$supplier->id}}">{{$supplier->name . ' (' . $supplier->phone_number. ')'}}</option>
                              @endforeach
                          </select>
                      </div>

                      <input type="hidden" name="start_date" value="{{date('Y-m').'-'.'01'}}" />
                      <input type="hidden" name="end_date" value="{{date('Y-m-d')}}" />

                      <div class="form-group">
                          <button type="submit" class="btn btn-primary">{{__('db.submit')}}</button>
                      </div>
                    </form>
                </div>
            </div>
        </div>
      </div>
      <!-- end supplier modal -->

      <div style="display:none" id="content" class="animate-bottom">
          @yield('content')
      </div>

      <footer class="main-footer">
        <div class="container-fluid">
          <div class="row">
            <div class="col-sm-12">
              <p>&copy; {{gen_setting()->site_title}} | {{__('db.Developed')}} {{__('db.By')}} <span class="external">{{gen_setting()->developed_by}}</span></p>
            </div>
          </div>
        </div>
      </footer>
    </div>
    @yield('scripts')
    <script type="text/javascript">

      var alert_product = <?php echo json_encode($alert_product ?? 0) ?>;

      if ($(window).outerWidth() > 1199) {
          $('nav.side-navbar').removeClass('shrink');
      }
      function myFunction() {
          setTimeout(showPage, 150);
      }
      function showPage() {
        document.getElementById("loader").style.display = "none";
        document.getElementById("content").style.display = "block";
      }

      $("div.alert").delay(3000).slideUp(750);

      function confirmDelete() {
          if (confirm("Are you sure want to delete?")) {
              return true;
          }
          return false;
      }

      $("li#notification-icon").on("click", function (argument) {
          $.get('notifications/mark-as-read', function(data) {
              $("span.notification-number").text(alert_product);
          });
      });

      $("a#add-expense").click(function(e){
        e.preventDefault();
        $('#expense-modal').modal();
      });

      $("a#send-notification").click(function(e){
        e.preventDefault();
        $('#notification-modal').modal();
      });

      $("a#add-account").click(function(e){
        e.preventDefault();
        $('#account-modal').modal();
      });

      $("a#account-statement").click(function(e){
        e.preventDefault();
        $('#account-statement-modal').modal();
      });

      $("a#profitLoss-link").click(function(e){
        e.preventDefault();
        $("#profitLoss-report-form").submit();
      });

      $("a#report-link").click(function(e){
        e.preventDefault();
        $("#product-report-form").submit();
      });

      $("a#purchase-report-link").click(function(e){
        e.preventDefault();
        $("#purchase-report-form").submit();
      });

      $("a#sale-report-link").click(function(e){
        e.preventDefault();
        $("#sale-report-form").submit();
      });

      $("a#payment-report-link").click(function(e){
        e.preventDefault();
        $("#payment-report-form").submit();
      });

      $("a#warehouse-report-link").click(function(e){
        e.preventDefault();
        $('#warehouse-modal').modal();
      });

      $("a#user-report-link").click(function(e){
        e.preventDefault();
        $('#user-modal').modal();
      });

      $("a#customer-report-link").click(function(e){
        e.preventDefault();
        $('#customer-modal').modal();
      });

      $("a#supplier-report-link").click(function(e){
        e.preventDefault();
        $('#supplier-modal').modal();
      });

      $("a#due-report-link").click(function(e){
        e.preventDefault();
        $("#due-report-form").submit();
      });

      $(".daterangepicker-field").daterangepicker({
          callback: function(startDate, endDate, period){
            var start_date = startDate.format('YYYY-MM-DD');
            var end_date = endDate.format('YYYY-MM-DD');
            var title = start_date + ' To ' + end_date;
            $(this).val(title);
            $('#account-statement-modal input[name="start_date"]').val(start_date);
            $('#account-statement-modal input[name="end_date"]').val(end_date);
          }
      });

      $('.selectpicker').selectpicker({
          style: 'btn-link',
      });
    </script>
    @include('backend.layout.theme_customizer')
  </body>
</html>
