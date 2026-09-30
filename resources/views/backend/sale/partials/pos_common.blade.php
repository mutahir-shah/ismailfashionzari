@php
    $activeWorkflow = $activeWorkflow ?? ($isRestaurantWorkflow ? 'restaurant' : 'retail');
    $isRestaurantWorkflow = (bool) ($isRestaurantWorkflow ?? ($activeWorkflow === 'restaurant'));
    $isRestaurantWarehouse = $isRestaurantWorkflow;
    $posType = $selectedPosWarehouse->pos_type ?? 'regular';
@endphp

@extends('backend.layout.top-head')

@push('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.9/css/intlTelInput.css" />
    <style type="text/css">
        /* --- INLINED FROM style.default.css --- */
        :root{--theme-color:{{ $theme_color ?? '#7c5cc4' }}}
        nav.navbar{background:#FFF;border-radius:10px;box-shadow:rgba(37, 83, 185, 0.1) 0px 2px 6px 0px;height:50px;margin-bottom:20px;padding: 5px 20px;z-index:999}
        .side-navbar .badge,.spinner-border,i,span{display:inline-block}
        body{background:#fafbfd;color:#1a1a1a;overflow-x:hidden}
        body.dark-mode{background-color:#141b2e;color:#d0d2d6}
        .mb-0{margin-bottom:0!important}
        table{font-size:.85em}
        .spinner-border{width:1rem;height:1rem;vertical-align:-.125em;border:.25em solid currentcolor;border-right-color:transparent;border-radius:50%;-webkit-animation:.75s linear infinite spinner-border;animation:.75s linear infinite spinner-border}
        .badge{background-color:unset;font-weight:600;font-size:11px}
        h2{color:#555}
        nav.navbar .nav-menu{margin-bottom:0}
        .card:not([class*=text]) p{color:#888}
        .display{font-weight:400!important}
        section header{padding-top:2rem;padding-bottom:2rem}
        nav.navbar .container-fluid{width:100%}
        nav.navbar a{font-size:14px;line-height:60px;position:relative;padding:0 5px}
        nav.navbar .notification-number{border:none;border-radius:50%;height:20px;line-height:1.2;position:absolute;top:7px;right:0;width:20px}
        nav.navbar a.logout i{margin-left:5px}
        nav.navbar small{font-size:.75em;color:#999}
        nav.navbar .dropdown-menu{border:none;box-shadow:0 10px 15px rgba(0,0,0,.1);width:auto;right:auto;left:auto;float:left;margin-top:0;margin-bottom:0;padding:5px;font-size:1.1em}
        .side-navbar li a,.side-navbar li ul{border-radius:10px;margin:0 10px;position:relative}
        nav.navbar .nav-menu li:last-child .dropdown-menu{right:2rem;float:right}
        nav.navbar .dropdown-menu li{line-height:18px}
        nav.navbar .dropdown-menu a{font-size:13px;color:#7d7d7d;line-height:18px;padding:10px;display:block}
        nav.navbar .dropdown-menu a:active,nav.navbar .dropdown-menu a:active span{color:#fff}
        nav.navbar .dropdown-menu a span{color:#777}
        nav.navbar .dropdown-menu a i{margin-right:5px}
        nav.navbar a.menu-btn{width:40px;height:40px}
        .side-navbar.hide{left:-50px}
        .side-navbar li{position:relative}
        .side-navbar li a{font-size:15px;font-weight:400;text-transform:capitalize;color:#303030;display:block;margin:2.5px 10px;padding:7px 10px}
        .dark-mode .right-sidebar a,.dark-mode .side-navbar li a{color:#d0d2d6 !important}
        .side-navbar li a i{color:var(--theme-color);font-size:17px;line-height:10px;margin-right:15px;width:20px}
        .dark-mode .right-sidebar a i,.dark-mode .side-navbar li a i{color:var(--theme-color)}
        .side-navbar li a i.icon{font-size:24px}
        .side-navbar li ul{border:1px solid #ddd;left:0;padding:10px}
        .dark-mode .side-navbar li ul{background:#283046;border:1px solid var(--theme-color)}
        .side-navbar li ul li{margin-left:20px}
        .side-navbar li ul li a{color:#333 !important;font-size:14px;line-height:1.8;padding:5px;transition: all 0.3s;}
        .side-navbar li a:hover{background:#f0f5f9}
        .dark-mode .side-navbar li a:hover{background:#141b2e}
        .side-navbar i{font-size:inherit;-webkit-transition:.3s;transition:.3s;margin-right:10px}
        .side-navbar .badge{font-size:.6em}
        .form-group{margin-bottom:10px}
    @media (min-width:992px) {
        nav.navbar .dropdown-menu strong{font-weight:400}
    }
    @media (max-width:991px) {
        p{font-size:.75em}
    }
        .dropdown-item:disabled,.forms p,.forms small{color:#868e96}
        .forms label,.forms p,h6{font-size:.8rem;font-weight:600}
    @media (max-width:575px) {
        nav.navbar .dropdown-menu{width:auto;left:-40px}
    }
        input.form-control::-moz-placeholder{font-size:.75em;font-weight:400;color:#aaa;font-family:Inter,sans-serif}
        input.form-control::-webkit-input-placeholder{font-size:.75em;font-weight:400;color:#aaa;font-family:Inter,sans-serif}
        input.form-control:-ms-input-placeholder{font-size:.75em;font-weight:400;color:#aaa;font-family:Inter,sans-serif}
        select.form-control{border-radius:0;border-color:#ddd;font-weight:300;font-family:Inter,sans-serif}
        select.form-control option{color:#999;font-weight:300}
        .input-group-text{color:#868e96;background:#fff;border-radius:0}
        .btn:not([disabled]):not(.disabled).active,.btn:not([disabled]):not(.disabled):active{background-image:none}
        button{cursor:pointer}
    @media (min-width:768px) {
        .container-fluid{padding:0 1.5rem}
        .forms p{font-size:.9em}
        .forms h2{font-size:1rem}
        .forms input.form-control::-moz-placeholder{font-size:.85em}
        .forms input.form-control::-webkit-input-placeholder{font-size:.85em}
        .forms input.form-control:-ms-input-placeholder{font-size:.85em}
    }
        .navbar{padding:.5rem 1rem}
        .btn{font-weight:400;border:1px solid transparent;padding:.45rem .75rem;font-size:.9rem;line-height:1.5;-webkit-transition:background-color .15s ease-in-out,border-color .15s ease-in-out,-webkit-box-shadow .15s ease-in-out;transition:background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out,-webkit-box-shadow .15s ease-in-out}
        .btn.focus,.btn:focus{outline:0;-webkit-box-shadow:0 0 0 .2rem rgba(51,179,90,.25);box-shadow:0 0 0 .2rem rgba(51,179,90,.25)}
        .btn:disabled{opacity:.65}
        .btn-primary.focus,.btn-primary:focus{-webkit-box-shadow:0 0 0 .2rem rgba(51,179,90,.5);box-shadow:0 0 0 .2rem rgba(51,179,90,.5)}
        .btn-primary:not([disabled]):not(.disabled).active,.btn-primary:not([disabled]):not(.disabled):active,.show>.btn-primary.dropdown-toggle{color:color-yiq(#288b46);background-color:#288b46;border-color:#258141;-webkit-box-shadow:0 0 0 .2rem rgba(51,179,90,.5);box-shadow:0 0 0 .2rem rgba(51,179,90,.5)}
        .btn-secondary{color:color-yiq(#868e96);background-color:#868e96;border-color:#868e96}
        .btn-secondary:hover{color:color-yiq(#727b84);background-color:#727b84;border-color:#6c757d}
        .btn-secondary.focus,.btn-secondary:focus{-webkit-box-shadow:0 0 0 .2rem rgba(134,142,150,.5);box-shadow:0 0 0 .2rem rgba(134,142,150,.5)}
        .btn-secondary:disabled{background-color:#868e96;border-color:#868e96}
        .btn-success,.btn-success:disabled,.btn-success:hover{border-color:#34cea7;background-color:#34cea7}
        .btn-secondary:not([disabled]):not(.disabled).active,.btn-secondary:not([disabled]):not(.disabled):active,.show>.btn-secondary.dropdown-toggle{color:color-yiq(#6c757d);background-color:#6c757d;border-color:#666e76;-webkit-box-shadow:0 0 0 .2rem rgba(134,142,150,.5);box-shadow:0 0 0 .2rem rgba(134,142,150,.5)}
        .btn-success,.btn-success:hover{color:color-yiq(#34cea7)}
        .btn-success.focus,.btn-success:focus{-webkit-box-shadow:0 0 0 .2rem rgba(40,167,69,.5);box-shadow:0 0 0 .2rem rgba(40,167,69,.5)}
        .btn-success:not([disabled]):not(.disabled).active,.btn-success:not([disabled]):not(.disabled):active,.show>.btn-success.dropdown-toggle{color:color-yiq(#34cea7);background-color:#34cea7;border-color:#1c7430;-webkit-box-shadow:0 0 0 .2rem rgba(40,167,69,.5);box-shadow:0 0 0 .2rem rgba(40,167,69,.5)}
        .btn-info{color:color-yiq(#17a2b8);background-color:#17a2b8;border-color:#17a2b8}
        .btn-info:hover{color:color-yiq(#138496);background-color:#138496;border-color:#117a8b}
        .btn-info.focus,.btn-info:focus{-webkit-box-shadow:0 0 0 .2rem rgba(23,162,184,.5);box-shadow:0 0 0 .2rem rgba(23,162,184,.5)}
        .btn-info:disabled{background-color:#17a2b8;border-color:#17a2b8}
        .btn-info:not([disabled]):not(.disabled).active,.btn-info:not([disabled]):not(.disabled):active,.show>.btn-info.dropdown-toggle{color:color-yiq(#117a8b);background-color:#117a8b;border-color:#10707f;-webkit-box-shadow:0 0 0 .2rem rgba(23,162,184,.5);box-shadow:0 0 0 .2rem rgba(23,162,184,.5)}
        .btn-warning{color:color-yiq(#ffc107);background-color:#ffc107;border-color:#ffc107}
        .btn-warning:hover{color:color-yiq(#e0a800);background-color:#e0a800;border-color:#d39e00}
        .btn-warning.focus,.btn-warning:focus{-webkit-box-shadow:0 0 0 .2rem rgba(255,193,7,.5);box-shadow:0 0 0 .2rem rgba(255,193,7,.5)}
        .btn-warning:disabled{background-color:#ffc107;border-color:#ffc107}
        .btn-warning:not([disabled]):not(.disabled).active,.btn-warning:not([disabled]):not(.disabled):active,.show>.btn-warning.dropdown-toggle{color:color-yiq(#d39e00);background-color:#d39e00;border-color:#c69500;-webkit-box-shadow:0 0 0 .2rem rgba(255,193,7,.5);box-shadow:0 0 0 .2rem rgba(255,193,7,.5)}
        .btn-danger{color:color-yiq(#dc3545);background-color:#dc3545;border-color:#dc3545}
        .btn-danger:hover{color:color-yiq(#c82333);background-color:#c82333;border-color:#ff7588}
        .btn-danger.focus,.btn-danger:focus{-webkit-box-shadow:0 0 0 .2rem rgba(220,53,69,.5);box-shadow:0 0 0 .2rem rgba(220,53,69,.5)}
        .btn-danger:disabled{background-color:#dc3545;border-color:#dc3545}
        .btn-danger:not([disabled]):not(.disabled).active,.btn-danger:not([disabled]):not(.disabled):active,.show>.btn-danger.dropdown-toggle{color:color-yiq(#ff7588);background-color:#ff7588;border-color:#ff7588;-webkit-box-shadow:0 0 0 .2rem rgba(220,53,69,.5);box-shadow:0 0 0 .2rem rgba(220,53,69,.5)}
        .btn-light{color:color-yiq(#f8f9fa);background-color:#f8f9fa;border-color:#f8f9fa}
        .btn-light:hover{color:color-yiq(#e2e6ea);background-color:#e2e6ea;border-color:#dae0e5}
        .btn-light.focus,.btn-light:focus{-webkit-box-shadow:0 0 0 .2rem rgba(248,249,250,.5);box-shadow:0 0 0 .2rem rgba(248,249,250,.5)}
        .btn-light:disabled{background-color:#f8f9fa;border-color:#f8f9fa}
        .btn-light:not([disabled]):not(.disabled).active,.btn-light:not([disabled]):not(.disabled):active,.show>.btn-light.dropdown-toggle{color:color-yiq(#dae0e5);background-color:#dae0e5;border-color:#d3d9df;-webkit-box-shadow:0 0 0 .2rem rgba(248,249,250,.5);box-shadow:0 0 0 .2rem rgba(248,249,250,.5)}
        .btn-sm{padding:.25rem .5rem;font-size:.875rem;line-height:1.5}
        a:focus,a:hover{color:#22773c;text-decoration:underline}
        h1,h2,h3,h4,h5,h6{margin-bottom:.5rem;font-family:inherit;font-weight:500;line-height:1.1;color:inherit}
        h1{font-size:1.5rem}
        h2{font-size:1.3rem}
        h3{font-size:1.2rem}
        h4{font-size:1.3rem}
        h5{font-size:1rem}
        hr{border-top:1px solid rgba(0,0,0,.1)}
        .small,small{font-size:80%;font-weight:400}
        .dropdown-menu{z-index:1000;min-width:12rem;padding:1rem .5rem;margin:.125rem 0 0;font-size:13px;line-height:2;color:#212529;background-color:#fff;border:1px solid rgba(0,0,0,.15);border-radius:10px}
        .dark-mode .dropdown-menu{background-color:#283046;border:1px solid #3b4253}
        .badge-light,.dropdown-item:focus,.dropdown-item:hover{background-color:transparent}
        .bg-info{background-color:#17a2b8!important}
        a.bg-info:focus,a.bg-info:hover{background-color:#117a8b!important}
        .text-light{color:#f8f9fa!important}
        a.text-light:focus,a.text-light:hover{color:#dae0e5!important}
        .badge-danger{color:color-yiq(#ff7588);background-color:#ff7588}
        .badge-light{color:color-yiq(#f8f9fa)}
        .nav-link{padding:0 1.25rem}
        .nav-link.pos{border-left:1px solid #e9e8ef;border-right:1px solid #e9e8ef}
    @media all and (max-width:600px) {
        .nav-link span{display:none}
    }
        .nav-tabs .nav-item{margin-bottom:-1px}
        .nav-tabs .nav-link{border:1px solid transparent;border-top-left-radius:0;border-top-right-radius:0}
        .nav-tabs .nav-item.show .nav-link,.nav-tabs .nav-link.active{color:#495057;background-color:#fff}
        .nav-tabs .dropdown-menu{margin-top:-1px}
        .card{border:none;margin-bottom:30px;box-shadow:rgba(37, 83, 185, 0.1) 0px 2px 6px 0px;border-radius:10px}
        .card-body{padding:1.5rem}
        .right-sidebar{background-color:#fff;box-shadow:0 0 35px 0 rgba(154,161,171,.15);height:100%;overflow-y:scroll;padding:20px 5px;position:fixed;right:0;top:0;visibility:hidden;width:0;z-index:999}
        .dark-mode .right-sidebar{background-color:#283046}
        .right-sidebar.open{visibility:visible;min-width:250px;width:auto}
        .right-sidebar li{border-bottom:1px solid #f5f6f7;list-style:none;padding:0 20px;line-height:50px}
        .dark-mode .right-sidebar li{border-bottom:1px solid #3b4253}
        ::-webkit-scrollbar{height:8px;width: 4px;}
        ::-webkit-scrollbar-thumb{background-color: #888;border-radius: 10px;}
        ::-webkit-scrollbar-track{background: #eee;}
        .italic{font-style:italic}
        nav.navbar{line-height:60px}
        .brand-img,.category-img,.product-img,.product-title{text-transform:capitalize}
        .card,.side-navbar{background-color:#fff}
        .dark-mode .card,.dark-mode .modal-content,.dark-mode .side-navbar,.dark-mode nav.navbar{background-color:#283046}
        .side-navbar{position:fixed;top:0;left:0;height:100vh;opacity:1;width:250px;text-align:left;transition:.1s linear;z-index:999;}
        .side-navbar li a:focus,.side-navbar li a:hover,.side-navbar li a.active{background:#f0f5f9;color:var(--theme-color);text-decoration:none}
        .dark-mode .side-navbar li a.active{background:#141b2e;}
        .badge-primary,.btn-primary{color:#FFF}
        .btn-primary,.btn-primary:disabled{background-color:var(--theme-color);border-color:var(--theme-color)}
        .bootstrap-select .btn-link,.table.order-list tr td .btn-link,.table.totals tr td .btn-link,a{color:var(--theme-color)}
        .dark-mode .table thead,.dark-mode .table tfoot{background-color:#343d55}
        a,a:hover{text-decoration:none}
        .dropdown-item.active,.dropdown-item:active{color:#fff;background-color:var(--theme-color)}
        .badge-primary{background-color:var(--theme-color)}
        .nav-tabs{line-height:2.3}
        .nav-tabs .nav-link:focus,.nav-tabs .nav-link:hover{border-color:transparent;color:var(--theme-color)}
        .btn:focus,a:focus,button:focus{outline:0;box-shadow:none}
        .btn-info:focus,.btn-primary:focus,nav.navbar .nav-item li.notifications a i{color:#fff}
        .btn-primary.active{box-shadow:none!important}
        .btn-primary.active{background-color:transparent!important;border:2px solid var(--theme-color)!important;color:var(--theme-color)!important}
        .brand-img,.category-img,.product-img{cursor:pointer}
        .bootstrap-select.form-control{border:1px solid}
        .bootstrap-select.form-control,.form-control,.input-group-text{background-color:#fdfdff;border-color:#e4e6fc}
        .dark-mode .bootstrap-select.form-control,.dark-mode .form-control,.dark-mode .input-group-text{background-color:#343d55;border-color:#404656;color:#676d7d}
        .btn-primary:hover{color:color-yiq(#2b954b);background-color:var(--theme-color);border-color:var(--theme-color)}
        .form-control:focus{border:1px solid var(--theme-color);box-shadow:none}
        .table:not(.permission-table) thead th{border-bottom:none;border-top:none}
        .modal-header,.table td{border-bottom:1px solid #ebe9f1;align-items:center}
        .dark-mode .table td{border-color:#3b4253!important}
        .table:not(.permission-table) tr:last-child td{border-bottom:none}
        .dark-mode table.table{background-color:#283046;color:#d0d2d6}
        .table thead th{border-bottom:1px solid #e4e6fc;font-weight:600}
        section{padding:30px 0}
        .side-navbar li a i,.table.totals tr td{vertical-align:middle}
        .side-navbar li ul.collapse a:hover{padding-left:15px}
        nav.navbar a.menu-btn{line-height:40px;background:0 0;color:var(--theme-color);text-align:center;padding:0;border:1px solid var(--theme-color);border-radius:5px}
        .dark-mode nav.navbar a.menu-btn{border:1px solid var(--theme-color);color:var(--theme-color)}
        nav.navbar a.menu-btn:hover{background:var(--theme-color);border:1px solid var(--theme-color);color:#fff}
        nav.navbar .nav-item a i, nav.navbar .nav-item a svg{color:var(--theme-color);font-size:17px;margin-right:5px}
        .dark-mode nav.navbar .nav-item a i, .dark-mode nav.navbar .nav-item a svg{color:var(--theme-color)}
        nav.navbar .notifications{width:260px}
        .nav-tabs .nav-item .nav-link.active{border-color:transparent;border-top:2px solid var(--theme-color)}
        .dropdown-item{padding:0 .5rem;color:#7d7d7d;cursor:pointer}
        .table-bordered td,.table-bordered th{border-color:#e4e6fc}
        .pos .bootstrap-select.form-control:not([class*=col-]){width:100px}
    @media all and (min-width:768px) {
        .table-fixed tbody,.table-fixed td,.table-fixed th,.table-fixed thead,.table-fixed tr{display:block}
        .table-fixed tr{clear:both}
        .table-fixed tbody td,.table-fixed thead>tr>th{float:left;border-bottom-width:0}
    }
        .btn-custom{font-size:15px;color:#fff}
        #myTable .input-group{max-width:150px}
        .filter-window{width:100%;height:100vh;background-color:#fff;overflow-y:auto;padding:0 10px;position:absolute;top:0;right:0;z-index:999999;display:none}
    @media all and (min-width:576px) {
        .modal-dialog{max-width:800px}
    }
        .modal{overflow:scroll;background-color:rgba(255,255,255,.4)}
        .modal-header .close{color:#666}
        .modal-content{border:none}
        .payment-amount{background-color:#d6deff;text-align:center}
        .payment-amount h2{color:var(--theme-color);margin-bottom:0}
        .totals .totals-title{color:#7d7d7d;display:inline-block;width:100px}
    @media all and (max-width:1023px) {
        .side-navbar .close{position:absolute;top:20px;right:5px;content:'X'}
    }
        .ui-helper-hidden-accessible{display:none}
        .dark-mode .side-navbar li a.active{background:#141b2e}
        .dropdown-toggle::after{vertical-align: .50em;}

        /* -------------------------------------- */

        body {
            color: #303030;
            font-family: 'Inter', sans-serif
        }

        .bootstrap-select-sm .btn {
            font-size: 13px;
            padding: 3px 25px 3px 10px;
            height: 30px !important
        }

        .minus,
        .plus {
            padding: .35rem .75rem
        }

        .numkey.qty {
            font-size: 13px;
            padding: 0 0;
            max-width: 50px;
            text-align: center
        }

        .sub-total {
            font-weight: 500;
        }

        .pos-page .container-fluid {
            padding: 0 15px
        }

        .pos-page .side-navbar {
            top: 0
        }

        section.pos-section {
            padding: 5px 0
        }

        .pos-page .table-fixed {
            margin-bottom: 0
        }

        .pos-text {
            line-height: 1.8
        }

        .pos-page section header {
            padding: 0 0
        }

        .pos .bootstrap-select button {
            padding-right: 21px !important
        }

        .pos .bootstrap-select.form-control:not([class*=col-]) {
            width: 100px
        }

        .pos-page .order-list .btn {
            padding: 2px 5px
        }

        .pos-page [class=row] {
            margin-left: -10px;
            margin-right: -10px
        }

        .pos-page [class*=col-] {
            padding: 0 7px
        }

        .pos-page #myTable [class*=col-] {
            padding: .3rem .5rem
        }

        .pos-page #myTable tr th {
            background: #f8f9fa;
            color: #303030;
            font-size: 12px
        }

        .product-btns {
            margin: 0 -5px
        }

        .edit-product {
            white-space: break-spaces;
            font-size: 13px;
            font-weight: 500;
            text-align: left;
            padding: 0 0 !important
        }

        .edit-product i {
            color: #00cec9
        }

        .product-title span {
            font-size: 12px
        }

        label {
            font-size: 13px
        }

        #tbody-id tr td {
            font-size: 13px;
            padding: 0
        }

        table,
        tr,
        td {
            border-collapse: collapse;
        }

        .top-fields {
            margin-top: 10px;
            position: relative;
        }

        .top-fields label {
            font-size: 11px;
            margin-left: 10px;
            padding: 0 3px;
            position: absolute;
            top: -8px;
            z-index: 9;
        }

        .top-fields input,
        .top-fields .btn {
            font-size: 13px;
            height: 37px
        }

        .product-grid {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            padding: 0;
            margin: 0;
            width: 100%;
        }

        .product-grid>div {
            background: #FFF;
            border-radius: 10px;
            box-shadow: rgba(37, 83, 185, 0.1) 0px 2px 6px 0px;
            margin: 5px;
            overflow: hidden;
            padding: .5rem;
            position: relative;
            max-width: 300px;
            min-width: 100px;
            vertical-align: top;
            width: calc(100%/4 - 10px);
        }

        .product-grid>div p {
            color: #303030;
            font-size: 12px;
            font-weight: 500;
            margin: 7px 0 0;
            min-height: 36px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            overflow: hidden;
            text-overflow: ellipsis;
            -webkit-box-orient: vertical
        }

        .product-grid>div span {
            font-size: 12px
        }
        .skeleton-grid > div {
            min-height: 180px;
            animation: skeleton-pulse 1.5s infinite ease-in-out;
            background-color: #f0f5f9;
            border: none;
            box-shadow: none;
        }
        .dark-mode .skeleton-grid > div {
            background-color: #2b2b2b;
        }
        @keyframes skeleton-pulse {
            0% { opacity: 0.6; }
            50% { opacity: 1; }
            100% { opacity: 0.6; }
        }

        .product-grid .loader {
            background: none;
            box-shadow: none;
            margin: 0;
            padding: 0
        }

        #print-layout {
            padding: 0 0;
            margin: 0 0;
        }

        .category-img p,
        .brand-img p {
            color: #5e5873;
            font-size: 12px;
            font-weight: 500
        }

        .brand-img,
        .category-img {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .brand-img img {
            max-width: 70%
        }

        .load-more {
            margin-top: 15px
        }

        .load-more:disabled {
            opacity: 0.5
        }

        .ui-helper-hidden-accessible {
            display: none !important
        }

        .btn-custom {
            font-size: 13px;
        }

        #register-details-modal table tr td {
            padding: .35rem 0
        }

        .totals strong {
            font-size: 13px;
        }

        .totals .totals-title {
            color: #555;
        }

        .date-choice.dropdown-toggle::after {
            display: none
        }

        .country-phone-group .bootstrap-select {
            display: none !important
        }

        .product-img {
            margin-bottom: 0;
            padding: 15px 7px 0;
            text-align: center
        }

        .category-img img,
        .product-img img {
            height: 70px;
            max-width: 100%;
            width: auto
        }

        .transaction-list {
            height: 48vh;
            overflow-y: auto;
            width: 100%
        }

        .table-container {
            height: calc(100vh - 100px);
            overflow-y: auto
        }

        nav.navbar .nav-item {
            margin-left: 10px
        }

        nav.navbar .nav-item:first-child {
            margin-left: 0
        }

        body.pos-offline .pos-offline-hide {
            display: none !important;
        }

        @media (max-width: 575px) {
            nav.navbar .dropdown-menu {
                left: 0;
            }

            .product-grid>div {
                width: calc(100%/3 - 10px);
            }
        }

        @media (max-width: 375px) {
            .product-grid>div {
                width: calc(100%/2 - 10px);
            }
        }

        @media all and (max-width:767px) {
            section.pos-section {
                padding: 0 5px
            }

            nav.navbar {
                margin: 0 -10px
            }

            .pos-form {
                padding: 0 0 !important
            }

            .product-btns {
                margin: 0 -15px 10px -15px
            }

            .product-btns .btn {
                font-size: 12px;
            }

            .more-options {
                margin-top: 0;
            }

            .transaction-list {
                height: 35vh;
            }

            .filter-window {
                position: fixed;
            }
        }

        @media print {
            .hidden-print {
                display: none !important;
            }
        }

        #print-layout * {
            font-size: 10px;
            line-height: 20px;
            font-family: 'Ubuntu', sans-serif;
            text-transform: capitalize;
        }

        #print-layout .btn {
            padding: 7px 10px;
            text-decoration: none;
            border: none;
            display: block;
            text-align: center;
            margin: 7px;
            cursor: pointer;
        }

        #print-layout .btn-info {
            background-color: #999;
            color: #FFF;
        }

        #print-layout .btn-primary {
            background-color: #6449e7;
            color: #FFF;
            width: 100%;
        }

        #print-layout td,
        #print-layout th,
        #print-layout tr,
        #print-layout table {
            border-collapse: collapse;
        }

        #print-layout tr {
            border-bottom: 1px dotted #ddd;
            display: block
        }

        #print-layout td,
        #print-layout th {
            padding: 7px 0;
        }

        #print-layout table {
            width: 100%;
        }

        #print-layout .centered {
            display: block;
            text-align: center;
            align-content: center;
        }

        #print-layout small {
            font-size: 10px;
        }

        @media print {
            #print-layout * {
                font-size: 10px !important;
                line-height: 20px;
            }

            #print-layout table {
                width: 100%;
                margin: 0 0;
            }

            #print-layout td,
            #print-layout th {
                padding: 5px 0;
            }

            #print-layout .hidden-print {
                display: none !important;
            }
        }

        .loader {
            display: block;
            max-width: 100% !important;
            min-width: 100% !important;
            text-align: center;
            vertical-align: middle;
            width: 100% !important;
            margin-top: 50px
        }

        .product-grid .loader {
            margin-top: 25%;
        }

        .loader svg path,
        .loader svg rect {
            fill: #303030;
        }

        nav.navbar {
            margin-bottom: 10px;
        }

        nav.navbar a.menu-btn {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        nav.navbar a {
            align-items: center;
            display: flex;
        }

        .right-sidebar li a svg {
            margin-right: 10px
        }

        .nav-menu svg {
            width: 20px;
            height: 20px;
            stroke: #303030;
            vertical-align: middle
        }

        .dark-mode .nav-menu svg {
            stroke: #ccc;
        }

        .btn svg {
            vertical-align: middle;
            width: 16px
        }

        button.close svg {
            vertical-align: middle;
            width: 26px
        }

        .bootstrap-select.btn-group>.dropdown-toggle {
            height: 37px
        }

        .dropdown-toggle-no-arrow::after {
            display: none !important
        }

        .calculator {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, .2);
            width: 240px
        }

        .calculator .display {
            width: 100%;
            height: 50px;
            background-color: #f5f5f5;
            border: 2px solid #303030;
            font-size: 1.5em;
            text-align: right;
            padding: 0 10px;
            margin-bottom: 10px;
            border-radius: 5px
        }

        .calculator .buttons {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px
        }

        .calculator .btn {
            height: 40px;
            font-size: 1em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color .2s
        }

        .calculator .btn.number {
            background-color: #fff;
            color: #000;
            border: 1px solid #ddd
        }

        .calculator .btn.operator {
            background-color: #f0f0f0;
            color: #000
        }

        .calculator .btn.action.ac {
            background-color: #d63031;
            color: #fff
        }

        .calculator .btn.action.ce {
            background-color: #e28d02;
            color: #fff
        }

        .calculator .btn.equals {
            background-color: #303030;
            color: #fff;
            grid-column: span 2
        }

        #product-results-container {
            background: #f5f6f7;
            position: absolute;
            overflow: hidden;
            max-height: 300px;
            overflow-y: auto;
            top: 50px;
            width: 100%;
            z-index: 999999
        }

        #product-results-container .product-img {
            border-radius: 3px;
            color: #303030;
            font-size: 13px;
            padding-top: 7px;
            padding-bottom: 7px;
            text-align: left
        }

        #product-results-container .product-img:hover {
            background-color: #303030;
            color: #FFF
        }

        #shortcut-list .dropdown-item {
            font-size: 13px;
            padding: 6px 10px;
        }

        #shortcut-list .badge {
            font-size: 11px;
        }

        #customer-display.inactive svg {
            stroke: #9ca3af;
        }

        #customer-display.active svg {
            stroke: #22c55e;
            filter: drop-shadow(0 0 4px #22c55e);
        }


        @keyframes posSlideDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        #offline-cloud-wrap {
            position: relative;
            cursor: pointer;
            display: flex;
        }

        #offline-cloud-wrap.online svg {
            stroke: #22c55e;
            filter: drop-shadow(0 0 4px #22c55e);
        }

        #offline-cloud-wrap.offline svg {
            stroke: #dc3545;
            filter: drop-shadow(0 0 4px #dc3545);
        }

        #offline-sale-badge {
            display: none;
            position: absolute;
            top: -2px;
            right: -5px;
            background: #dc3545;
            color: #fff;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            font-weight: 700;
            align-items: center;
            justify-content: center;
        }

        #offline-sale-badge.show {
            display: flex;
        }

        .product-grid {
            display: grid !important;
            grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)) !important;
            gap: 8px !important;
            padding: 8px !important;
            width: 100% !important;
            align-items: start !important;
        }
        .product-grid>div {
            width: 100% !important;
            max-width: none !important;
            min-width: 0 !important;
            margin: 0 !important;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease !important;
        }
        .product-grid>div:hover {
            transform: translateY(-3px) !important;
            box-shadow: rgba(100, 73, 231, 0.25) 0px 6px 16px 0px !important;
        }
        .product-grid>div:active {
            transform: scale(0.97) !important;
        }
        .payment-amount {
            padding: 10px 15px !important;
        }
        .payment-amount h2 {
            background: linear-gradient(135deg, #6449e7 0%, #a855f7 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            font-size: 2rem !important;
            font-weight: 800 !important;
            letter-spacing: -0.5px !important;
            margin: 4px 0 !important;
            display: inline-block !important;
        }
        .dark-mode .payment-amount h2 {
            background: linear-gradient(135deg, #a855f7 0%, #f43f5e 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
        }
        .dark-mode.pos-page .search-box, .dark-mode.pos-page .transaction-list {
            background-color: #283046 !important;
            border-color: #3b4253 !important;
            box-shadow: none !important;
        }
        .dark-mode.pos-page #product-search-input {
            background-color: #283046;
            color: #d0d2d6;
        }
        .dark-mode.pos-page #myTable tr th {
            background-color: #141b2e !important;
            color: #d0d2d6 !important;
            border-bottom-color: #3b4253 !important;
        }
        .dark-mode.pos-page #myTable tr td {
            color: #d0d2d6 !important;
            border-bottom-color: #3b4253 !important;
        }
        .dark-mode.pos-page #myTable {
            background-color: #283046 !important;
        }
        .dark-mode.pos-page .totals {
            background-color: #141b2e !important;
            border-top: 2px solid #3b4253 !important;
            color: #d0d2d6;
        }
        .dark-mode.pos-page .totals .totals-title {
            color: #a0a0a0;
        }
        .dark-mode.pos-page .payment-amount {
            background-color: #141b2e !important;
        }
        .dark-mode.pos-page .product-grid>div {
            background-color: #283046 !important;
            box-shadow: none !important;
            border: 1px solid #3b4253 !important;
        }
        .dark-mode.pos-page .product-grid>div p {
            color: #d0d2d6 !important;
        }
        .dark-mode.pos-page .product-grid .btn-custom {
            color: #fff;
        }
        .dark-mode.pos-page .category-img p,
        .dark-mode.pos-page .brand-img p {
            color: #d0d2d6 !important;
        }
        .dark-mode.pos-page .alert-primary {
            background-color: #141b2e !important;
            color: var(--theme-color) !important;
            border-color: #3b4253 !important;
        }
        .dark-mode.pos-page #no-results-message {
            background-color: #283046 !important;
            color: #d0d2d6 !important;
        }
        .dark-mode.pos-page .btn-link {
            color: var(--theme-color) !important;
        }
        .dark-mode.pos-page .top-fields input {
            background-color: #283046 !important;
            color: #d0d2d6 !important;
            border-color: #3b4253 !important;
        }
        .dark-mode.pos-page .top-fields .bootstrap-select > .btn {
            background-color: #283046 !important;
            color: #d0d2d6 !important;
            border-color: #3b4253 !important;
        }
    </style>
@endpush
@section('content')

    @php
        $handle_discount_active = $role_has_permissions_list->where('name', 'handle_discount')->first();
        $price_type = session('price_type');
        if (!in_array($price_type, ['retail', 'wholesale'], true)) {
            $price_type = 'retail';
        }
    @endphp

    <x-success-message key="message" />
    <x-error-message key="phone_number" />
    <x-error-message key="not_permitted" />
    <x-error-message key="error" />

    @if(!empty($restaurantModuleMissingForRestaurant) && $restaurantModuleMissingForRestaurant)
        <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
            <i class="dripicons-warning mr-2"></i>
            <strong>{{ __('db.Restaurant POS') }}:</strong> This warehouse is configured for Restaurant POS, but the Restaurant module is not installed or enabled. Standard POS features are active.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <section id="pos-layout" class="forms pos-section hidden-print">
        <div class="container-fluid">
            <div class="row">
                <!-- product list -->
                <div class="col-md-5 order-first order-md-2">
                    <!-- navbar-->
                    <header>
                        <nav class="navbar" style="padding: 5px 10px">
                            <div class="dropdown pos-offline-hide">
                                <a class="btn menu-btn dropdown-toggle-no-arrow" type="button" data-toggle="dropdown"
                                    aria-expanded="false" role="button"><svg style="width: 18px; height: 18px;"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="size-6">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                    </svg></a>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" target="_blank"
                                        href="{{url('/dashboard')}}">{{__('db.dashboard')}}</a>
                                    <?php
    $product_permission_active = $role_has_permissions_list->where('name', 'products-index')->first();
                                        ?>
                                    @if($product_permission_active)
                                        <a class="dropdown-item" target="_blank"
                                            href="{{route('products.index')}}">{{__('db.product_list')}}</a>
                                    @endif

                                    <?php
    $sale_permission_active = $role_has_permissions_list->where('name', 'sales-index')->first();
                                        ?>
                                    @if($sale_permission_active)
                                        <a class="dropdown-item" target="_blank"
                                            href="{{route('sales.index')}}">{{__('db.Sale List')}}</a>
                                    @endif

                                    <?php
    $purchase_permission_active = $role_has_permissions_list->where('name', 'purchases-index')->first();
                                        ?>
                                    @if($purchase_permission_active)
                                        <a class="dropdown-item" target="_blank"
                                            href="{{route('purchases.index')}}">{{__('db.Purchase List')}}</a>
                                    @endif

                                    <?php
    $transfer_permission_active = $role_has_permissions_list->where('name', 'transfers-index')->first();
                                        ?>
                                    @if($transfer_permission_active)
                                        <a class="dropdown-item" target="_blank"
                                            href="{{route('transfers.index')}}">{{__('db.Transfer List')}}</a>
                                    @endif
                                </div>
                            </div>
                            <ul class="nav-menu list-unstyled d-flex flex-md-row align-items-md-center">
                                <!-- //keyboard shortcuts -->
                                <li class="nav-item d-none d-lg-block dropdown">
                                    <a class="dropdown-toggle-no-arrow" type="button" data-toggle="dropdown"
                                        aria-expanded="false" role="button">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="icon icon-tabler icons-tabler-outline icon-tabler-keyboard">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path
                                                d="M2 8a2 2 0 0 1 2 -2h16a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-16a2 2 0 0 1 -2 -2l0 -8" />
                                            <path d="M6 10l0 .01" />
                                            <path d="M10 10l0 .01" />
                                            <path d="M14 10l0 .01" />
                                            <path d="M18 10l0 .01" />
                                            <path d="M6 14l0 .01" />
                                            <path d="M18 14l0 .01" />
                                            <path d="M10 14l4 .01" />
                                        </svg>
                                    </a>

                                    <div class="dropdown-menu dropdown-menu-right p-2" aria-labelledby="shortcutDropdown"
                                        style="min-width: 260px; max-height: 350px; overflow-y: auto;">

                                        <div id="shortcut-list"></div>

                                    </div>
                                </li>
                                <!-- //mobile collapse -->
                                <li class="nav-item d-md-none">
                                    <a data-toggle="collapse" href="#collapseProducts" role="button" aria-expanded="false"
                                        aria-controls="collapseProducts"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m7.875 14.25 1.214 1.942a2.25 2.25 0 0 0 1.908 1.058h2.006c.776 0 1.497-.4 1.908-1.058l1.214-1.942M2.41 9h4.636a2.25 2.25 0 0 1 1.872 1.002l.164.246a2.25 2.25 0 0 0 1.872 1.002h2.092a2.25 2.25 0 0 0 1.872-1.002l.164-.246A2.25 2.25 0 0 1 16.954 9h4.636M2.41 9a2.25 2.25 0 0 0-.16.832V12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 12V9.832c0-.287-.055-.57-.16-.832M2.41 9a2.25 2.25 0 0 1 .382-.632l3.285-3.832a2.25 2.25 0 0 1 1.708-.786h8.43c.657 0 1.281.287 1.709.786l3.284 3.832c.163.19.291.404.382.632M4.5 20.25h15A2.25 2.25 0 0 0 21.75 18v-2.625c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125V18a2.25 2.25 0 0 0 2.25 2.25Z" />
                                        </svg></a>
                                </li>
                                <!-- //calculator -->
                                <li class="nav-item d-none d-lg-block dropdown">
                                    <a class="dropdown-toggle-no-arrow" type="button" data-toggle="dropdown"
                                        aria-expanded="false" role="button"><svg xmlns="http://www.w3.org/2000/svg"
                                            fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                            class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008Zm0 2.25h.008v.008H8.25V13.5Zm0 2.25h.008v.008H8.25v-.008Zm0 2.25h.008v.008H8.25V18Zm2.498-6.75h.007v.008h-.007v-.008Zm0 2.25h.007v.008h-.007V13.5Zm0 2.25h.007v.008h-.007v-.008Zm0 2.25h.007v.008h-.007V18Zm2.504-6.75h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V13.5Zm0 2.25h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V18Zm2.498-6.75h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V13.5ZM8.25 6h7.5v2.25h-7.5V6ZM12 2.25c-1.892 0-3.758.11-5.593.322C5.307 2.7 4.5 3.65 4.5 4.757V19.5a2.25 2.25 0 0 0 2.25 2.25h10.5a2.25 2.25 0 0 0 2.25-2.25V4.757c0-1.108-.806-2.057-1.907-2.185A48.507 48.507 0 0 0 12 2.25Z" />
                                        </svg></a>
                                    <div class="dropdown-menu calculator p-3" onclick="event.stopPropagation();">
                                        <input type="text" class="display" readonly>
                                        <div class="buttons">
                                            <button class="btn action ac">AC</button>
                                            <button class="btn action ce">CE</button>
                                            <button class="btn operator">%</button>
                                            <button class="btn operator">÷</button>

                                            <button class="btn number">7</button>
                                            <button class="btn number">8</button>
                                            <button class="btn number">9</button>
                                            <button class="btn operator">x</button>

                                            <button class="btn number">4</button>
                                            <button class="btn number">5</button>
                                            <button class="btn number">6</button>
                                            <button class="btn operator">-</button>

                                            <button class="btn number">1</button>
                                            <button class="btn number">2</button>
                                            <button class="btn number">3</button>
                                            <button class="btn operator">+</button>

                                            <button class="btn number">0</button>
                                            <button class="btn number">.</button>
                                            <button class="btn equals">=</button>
                                        </div>
                                    </div>
                                </li>
                                <!-- //Sale return -->
                                <li class="nav-item pos-offline-hide" data-toggle="tooltip" title="Sale Return">
                                    <a type="button" data-toggle="dropdown" aria-expanded="false" role="button"><svg
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                                        </svg></a>
                                    <div class="dropdown-menu pl-3 pr-3" style="max-width: 250px;">
                                        <form method="GET" action="{{route('return-sale.create')}}" target="_blank"
                                            accept-charset="UTF-8">
                                            <div class="form-group">
                                                <label>Sale Reference *</label>
                                                <div class="input-group">
                                                    <input type="text" name="reference_no" class="form-control" required>
                                                    <button type="submit" class="btn btn-primary btn-sm"
                                                        data-toggle="tooltip"><svg xmlns="http://www.w3.org/2000/svg"
                                                            fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                                            style="stroke:#FFF" class="size-6">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                                        </svg></button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </li>
                                <!-- //fullscreen -->
                                <li class="nav-item d-none d-lg-block">
                                    <a id="btnFullscreen" data-toggle="tooltip" title="Full Screen"><svg
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
                                        </svg></a>
                                </li>
                                <!-- //Customer Display Screen -->
                                <li class="nav-item d-none d-lg-block pos-offline-hide">
                                    <a id="customer-display" class="inactive" href="{{route('sales.customerDisplay')}}"
                                        data-toggle="tooltip" title="{{__('db.enable_customer_display')}}">

                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="size-6">

                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3.75 4.5h16.5a.75.75 0 01.75.75v10.5a.75.75 0 01-.75.75H3.75a.75.75 0 01-.75-.75V5.25a.75.75 0 01.75-.75zM6 18h12m-6 0v2.25" />
                                        </svg>

                                    </a>
                                </li>
                                <!-- //print last reciept -->
                                <li class="nav-item pos-offline-hide">
                                    <a id="print-last-receipt" href="{{route('sales.printLastReciept')}}"
                                        data-toggle="tooltip" title="{{__('db.Print Last Reciept')}}"><svg
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
                                        </svg></a>
                                </li>
                                @if($lims_pos_setting_data && $lims_pos_setting_data->cash_register)
                                    <!-- //cash register -->
                                    <li class="nav-item d-none d-lg-block pos-offline-hide">
                                        <a href="" id="register-details-btn" class="d-none" data-toggle="tooltip"
                                            title="{{__('db.Cash Register Details')}}"><svg xmlns="http://www.w3.org/2000/svg"
                                                fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                class="size-6">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z" />
                                            </svg></a>
                                    </li>
                                @endif
                                @if(((($alert_product ?? 0) + count(\Auth::user()->unreadNotifications))) > 0)
                                    <li class="nav-item d-none d-lg-block" id="notification-icon">
                                        <a rel="nofollow" data-toggle="tooltip" title="{{__('Notifications')}}"
                                            class="nav-link dropdown-item"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                                            </svg><span
                                                class="badge badge-danger notification-number">{{($alert_product ?? 0) + count(\Auth::user()->unreadNotifications)}}</span>
                                            <span class="caret"></span>
                                            <span class="sr-only">Toggle Dropdown</span>
                                        </a>
                                        <ul class="right-sidebar" user="menu">
                                            <li class="notifications">
                                                <a href="{{route('report.qtyAlert')}}" class="btn btn-link">{{$alert_product ?? 0}}
                                                    product exceeds alert quantity</a>
                                            </li>
                                            @foreach(\Auth::user()->unreadNotifications as $key => $notification)
                                                <li class="notifications">
                                                    <a href="#" class="btn btn-link">{{ $notification->data['message'] }}</a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </li>
                                @endif
                                {{-- Offline Cloud Button in Navbar --}}
                                <li class="nav-item" id="offline-cloud-nav">
                                    <div id="offline-cloud-wrap" style="position:relative;cursor:pointer;"
                                        title="Offline Sales">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor"
                                            style="width:22px;height:22px;vertical-align:middle;">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.25 15a4.5 4.5 0 0 0 4.5 4.5H18a3.75 3.75 0 0 0 1.332-7.257 3 3 0 0 0-3.758-3.848 5.25 5.25 0 0 0-10.233 2.33A4.502 4.502 0 0 0 2.25 15Z" />
                                        </svg>
                                        <span id="offline-sale-badge">0</span>
                                    </div>
                                </li>
                                <li class="nav-item pos-offline-hide">
                                    <a rel="nofollow" data-toggle="tooltip" class="nav-link dropdown-item"><svg
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                    </a>
                                    <ul class="right-sidebar">
                                        <li>
                                            <a target="_blank" href="{{route('user.profile', ['id' => Auth::id()])}}"><svg
                                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                    stroke-width="1.5" stroke="currentColor" class="size-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                </svg> {{__('db.profile')}}</a>
                                        </li>
                                        <?php
    $add_expense_permission = $role_has_permissions_list->where('name', 'expenses-add')->first();
                                            ?>
                                        @if($add_expense_permission)
                                            <li>
                                                <a href="" data-toggle="modal" data-target="#expense-modal"><svg
                                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                        stroke-width="1.5" stroke="currentColor" class="size-6">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3" />
                                                    </svg> {{__('db.Add Expense')}}</a>
                                            </li>
                                        @endif
                                        <?php
    $add_payment_permission = $role_has_permissions_list->where('name', 'purchase-payment-add')->first();
                                            ?>
                                        @if($add_payment_permission)
                                            <li>
                                                <a href="" class="add-supplier-payment" data-toggle="modal"
                                                    data-target="#add-supplier-payment"><svg xmlns="http://www.w3.org/2000/svg"
                                                        fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                        class="size-6">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                    </svg> {{__('db.Add Payment')}}</a>
                                            </li>
                                        @endif
                                        <?php
    $today_sale_permission_active = $role_has_permissions_list->where('name', 'today_sale')->first();

    $today_profit_permission_active = $role_has_permissions_list->where('name', 'today_profit')->first();
                                            ?>

                                        @if($today_sale_permission_active)
                                            <li>
                                                <a href="" id="today-sale-btn" title="{{__('db.Today Sale')}}"><svg
                                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                        stroke-width="1.5" stroke="currentColor" class="size-6">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                                    </svg>{{__('db.Today Sale')}}</a>
                                            </li>
                                        @endif
                                        @if($today_profit_permission_active)
                                            <li>
                                                <a href="" id="today-profit-btn" title="{{__('db.Today Profit')}}"><svg
                                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                        stroke-width="1.5" stroke="currentColor" class="size-6">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                    </svg>{{__('db.Today Profit')}}</a>
                                            </li>
                                        @endif
                                        <?php

                                            $general_setting_permission_active = $role_has_permissions_list->where('name', 'general_setting')->first();

                                            $pos_setting_permission_active = $role_has_permissions_list->where('name', 'pos_setting')->first();

                                            $authUser = Auth::user()->role_id;
                                        ?>
                                        @if($pos_setting_permission_active)
                                            <li><a href="{{route('setting.pos')}}" title="{{__('db.POS Setting')}}"><svg
                                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                        stroke-width="1.5" stroke="currentColor" class="size-6">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                    </svg> {{__('db.POS Setting')}}</a> </li>
                                        @endif
                                        @if($general_setting_permission_active)
                                            <li>
                                                <a href="{{route('setting.general')}}"><svg xmlns="http://www.w3.org/2000/svg"
                                                        fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                        class="size-6">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                    </svg> {{__('db.settings')}}</a>
                                            </li>
                                        @endif
                                        <li>
                                            <a href="{{url('my-transactions/' . date('Y') . '/' . date('m'))}}"><svg
                                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                    stroke-width="1.5" stroke="currentColor" class="size-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                                                </svg> {{__('db.My Transaction')}}</a>
                                        </li>
                                        @if(Auth::user()->role_id != 5)
                                            <li>
                                                <a href="{{url('holidays/my-holiday/' . date('Y') . '/' . date('m'))}}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                        stroke-width="1.5" stroke="currentColor" class="size-6">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z" />
                                                    </svg> {{__('db.My Holiday')}}</a>
                                            </li>
                                        @endif
                                        <li>
                                            <a href="{{ route('logout') }}" onclick="event.preventDefault();
                                                                document.getElementById('logout-form').submit();"><svg
                                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                    stroke-width="1.5" stroke="currentColor" class="size-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9" />
                                                </svg>

                                                {{__('db.logout')}}
                                            </a>
                                            <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                                style="display: none;">
                                                @csrf
                                            </form>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </nav>
                    </header>

                    <div class="filter-window">
                        <div class="category mt-3">
                            <div class="row ml-2 mr-2 px-2">
                                <div class="col-7">Choose category</div>
                                <div class="col-5 text-right">
                                    <span class="btn btn-light btn-sm btn-close">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                        </svg>
                                    </span>
                                </div>
                            </div>
                            <div class="row ml-2 mt-3">
                                <div class="col-12 mb-3">
                                    <input type="text" id="categorySearch" class="form-control form-control live-filter"
                                        data-target=".category-img" placeholder="Search category...">
                                </div>
                                @foreach (get_active_categories() as $category)
                                    <div class="col-md-3 col-6 category-img text-center" data-category="{{$category->id}}"
                                        data-name="{{ strtolower($category->name) }}">
                                        @if($category->image)
                                            <img src="{{url('images/category', $category->image)}}" />
                                        @else
                                            <img src="{{url('/images/product/zummXD2dvAtI.png')}}" />
                                        @endif
                                        <p class="text-center">{{$category->name}}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="brand mt-3">
                            <div class="row ml-2 mr-2 px-2">
                                <div class="col-7">Choose brand</div>
                                <div class="col-5 text-right">
                                    <span class="btn btn-light btn-sm btn-close">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                        </svg>
                                    </span>
                                </div>
                            </div>
                            <div class="row ml-2 mt-3">
                                <div class="col-12 mb-3">
                                    <input type="text" id="brandSearch" class="form-control form-control live-filter"
                                        data-target=".brand-img" placeholder="Search brand...">
                                </div>
                                @foreach($lims_brand_list as $brand)
                                    <div class="col-md-3 col-6 brand-img text-center" data-brand="{{$brand->id}}"
                                        data-name="{{ strtolower($brand->title) }}">
                                        @if($brand->image)
                                            <img src="{{url('images/brand', $brand->image)}}" />
                                        @else
                                            <img src="{{url('/images/product/zummXD2dvAtI.png')}}" />
                                        @endif
                                        <p class="text-center">{{$brand->title}}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="products-m mt-3">
                            <div class="row ml-2 mr-2 px-2">
                                <div class="col-7"></div>
                                <div class="col-5 text-right">
                                    <span class="btn btn-light btn-sm btn-close">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                        </svg>
                                    </span>
                                </div>
                            </div>
                            <div class="product_list_mobile table-container row mt-3" data-cat="" data-brand="">

                            </div>
                        </div>
                    </div>
                    <div id="collapseProducts" class="">
                        <div class="d-flex justify-content-between product-btns">

                            <button class="btn btn-block btn-primary mt-0 ml-1 mr-1"
                                id="category-filter">{{__('db.category')}}</button>

                            <button class="btn btn-block btn-info mt-0 ml-1 mr-1"
                                id="brand-filter">{{__('db.Brand')}}</button>

                            <button class="btn btn-block btn-danger mt-0 ml-1 mr-1"
                                id="featured-filter">{{__('db.Featured')}}</button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 table-container main mt-2" data-cat="" data-brand="">

                            <div class="product-grid skeleton-grid"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-7 pos-form">
                    <form action="{{ route('sales.store') }}" method="POST" enctype="multipart/form-data"
                        class="payment-form">
                        @csrf

                        @php
                            if ($lims_pos_setting_data)
                                $keybord_active = $lims_pos_setting_data->keybord_active;
                            else
                                $keybord_active = 0;

                            $customer_active = $role_has_permissions_list->where('name', 'customers-add')->first();
                        @endphp
                        <div class="card mb-2 px-2">
                            <div class="d-flex align-items-center flex-wrap">

                                <button type="button" class="btn btn-light btn-md mr-2 date-choice dropdown-toggle"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round"
                                        class="icon icon-tabler icons-tabler-outline icon-tabler-calendar-week">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path
                                            d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12" />
                                        <path d="M16 3v4" />
                                        <path d="M8 3v4" />
                                        <path d="M4 11h16" />
                                        <path d="M7 14h.013" />
                                        <path d="M10.01 14h.005" />
                                        <path d="M13.01 14h.005" />
                                        <path d="M16.015 14h.005" />
                                        <path d="M13.015 17h.005" />
                                        <path d="M7.01 17h.005" />
                                        <path d="M10.01 17h.005" />
                                    </svg>
                                </button>
                                <div class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                                    <div class="input-group">
                                        @can('change_sale_date')
                                            <input type="text" name="created_at" class="form-control date"
                                                placeholder="{{ __('db.Choose date') }}"
                                                value="{{date(gen_setting()->date_format, strtotime('now'))}}" />
                                        @else
                                            <input type="text" name="created_at" class="form-control date"
                                                placeholder="{{ __('db.Choose date') }}"
                                                value="{{date(gen_setting()->date_format, strtotime('now'))}}" readonly />
                                        @endcan
                                    </div>
                                </div>

                                @if(isset(auth()->user()->warehouse_id))
                                    <input type="hidden" id="warehouse_id" name="warehouse_id"
                                        value="{{ auth()->user()->warehouse_id }}" />

                                @else
                                    <div class="pos-offline-hide"
                                        data-toggle="tooltip"
                                        title=""
                                        data-original-title="{{ __('db.Warehouse') }}">

                                        <button type="button"
                                            class="btn btn-light btn-md mr-2"
                                            data-toggle="collapse"
                                            data-target="#warehousePanel"
                                            aria-expanded="false">

                                            <svg style="width: 18px; height: 18px;"
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke-width="1.5"
                                                stroke="currentColor"
                                                class="size-6">

                                                <path stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />

                                                <path stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                            </svg>
                                        </button>
                                    </div>
                                @endif

                                <input type="hidden" name="pos_workflow" id="pos_workflow" value="{{ $activeWorkflow }}" />

                                @if(isset(auth()->user()->biller_id))
                                    <input type="hidden" id="biller_id" name="biller_id"
                                        value="{{auth()->user()->biller_id}}" />
                                @else
                                    <div class="pos-offline-hide" data-toggle="tooltip" title=""
                                        data-original-title="{{__('db.Biller')}}">
                                        <button type="button" class="btn btn-light btn-md mr-2" data-toggle="collapse"
                                            data-target="#billerPanel" aria-expanded="false">
                                            <svg style="width: 18px; height: 18px;" xmlns="http://www.w3.org/2000/svg"
                                                fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                class="size-6">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                        </button>
                                    </div>
                                @endif

                                <div class="pos-offline-hide" data-toggle="tooltip" title=""
                                    data-original-title="{{__('db.Currency')}}">
                                    <button type="button" class="btn btn-light btn-md mr-2" data-toggle="collapse"
                                        data-target="#currencyPanel" aria-expanded="false">
                                        <svg style="width: 18px; height: 18px;" xmlns="http://www.w3.org/2000/svg"
                                            fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                            class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                    </button>
                                </div>

                                <div class="form-group top-fields mr-2" style="margin: 7px 0">
                                    <label>{{__('db.customer')}}</label>
                                    <div class="input-group pos">
                                        @php
                                            $deposit = [];
                                            $points = [];
                                            if (isset($lims_sale_data) && !empty($lims_sale_data) && $lims_sale_data->customer_id) {
                                                $customer_id = $lims_sale_data->customer_id;
                                            } elseif ($lims_pos_setting_data && $lims_pos_setting_data->customer_id) {
                                                $customer_id = $lims_pos_setting_data->customer_id;
                                            } else {
                                                $customer_id = $lims_customer_list[0]->id ?? null;
                                            }
                                        @endphp
                                        <select required name="customer_id" id="customer_id"
                                            class="selectpicker form-control" data-live-search="true" title="Select..."
                                            style="width: 100px">
                                            @foreach($lims_customer_list as $customer)
                                                <option data-points="{{ $customer->points }}"
                                                    data-deposit="{{ $customer->deposit }}"
                                                    data-credit-limit="{{ $customer->credit_limit }}"
                                                    data-pay_term_no="{{ $customer->pay_term_no }}"
                                                    data-pay_term_period="{{ $customer->pay_term_period }}"
                                                    data-type="{{ $customer->type }}" value="{{ $customer->id }}"
                                                    @if($customer->id == $customer_id) selected @endif>
                                                    {{ $customer->name }}
                                                    <span>({{ $customer->wa_number ?? $customer->phone_number }})</span>
                                                </option>
                                            @endforeach
                                        </select>
                                        @if($customer_active)
                                            <button type="button" class="btn btn-light btn-sm" data-toggle="modal"
                                                data-target="#addCustomer"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M12 4.5v15m7.5-7.5h-15" />
                                                </svg></button>
                                        @endif
                                        <x-validation-error fieldName="customer_id" />
                                    </div>
                                </div>


                                @if(!$isRestaurantWarehouse)
                                    <!-- Price type -->
                                    <div class="form-group top-fields mr-2" style="margin: 7px 0">
                                        <label>{{__('db.Price Option')}}</label>
                                        <select id="price_type" class="form-control selectpicker" style="width: 100px">
                                            <option value="retail" {{ $price_type == 'retail' ? 'selected' : '' }}>Retail</option>
                                            <option value="wholesale" {{ $price_type == 'wholesale' ? 'selected' : '' }}>Wholesale
                                            </option>
                                        </select>
                                    </div>
                                @endif

                                @if($isRestaurantWarehouse)
                                    <div class="form-group top-fields mr-2">
                                        <label>{{__('db.Service')}}</label>
                                        @php
                                            if (isset($lims_sale_data) && !empty($lims_sale_data) && $lims_sale_data->service_id) {
                                                $service_id = $lims_sale_data->service_id;
                                            }
                                        @endphp
                                        @if(!empty($service_id))
                                            <div class="input-group pos">
                                                <select required id="service_id" name="service_id" class="selectpicker form-control"
                                                    title="Select service...">
                                                    <option value="1" @if($service_id == 1) selected @endif>{{__('db.Dine In')}}
                                                    </option>
                                                    <option value="2" @if($service_id == 2) selected @endif>{{__('db.Take Away')}}
                                                    </option>
                                                    <option value="3" @if($service_id == 3) selected @endif>{{__('db.Delivery')}}
                                                    </option>
                                                </select>
                                            </div>
                                        @else
                                            <div class="input-group pos">
                                                <select required id="service_id" name="service_id" class="selectpicker form-control"
                                                    title="Select service...">
                                                    <option value="1" selected>{{__('db.Dine In')}}</option>
                                                    <option value="2">{{__('db.Take Away')}}</option>
                                                    <option value="3">{{__('db.Delivery')}}</option>
                                                </select>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="form-group top-fields mr-2">
                                        <label>{{__('db.Table')}}</label>
                                        <div class="input-group pos">
                                            <button type="button" id="visual-table-btn" class="btn btn-outline-primary btn-block text-left" style="display: none;" onclick="openVisualTableModal()">
                                                <span id="visual-table-btn-text">Select Table</span>
                                            </button>
                                            <div id="table-select-wrapper" style="width: 100%;">
                                                @php
                                                    if (isset($lims_sale_data) && !empty($lims_sale_data) && !empty($lims_sale_data->table_id)) {
                                                        $table_id = $lims_sale_data->table_id;
                                                    }
                                                @endphp
                                                <select required id="table_id" name="table_id" class="selectpicker form-control"
                                                    data-live-search="true" data-live-search-style="begins" title="Select table...">
                                                    @foreach($lims_table_list as $table)
                                                        <option value="{{$table->id}}" data-name="{{$table->name}}" @if(!empty($table_id) && $table->id == $table_id)
                                                        selected @endif>
                                                            {{$table->name}} ( ðŸ‘¤ {{$table->number_of_person}})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group top-fields mr-2">
                                        <label>{{__('db.Waiter')}}</label>
                                        <div class="input-group pos">
                                            @php
                                                if (isset($lims_sale_data) && !empty($lims_sale_data) && !empty($lims_sale_data->waiter_id)) {
                                                    $waiter_id = $lims_sale_data->waiter_id;
                                                }
                                            @endphp
                                            <select required id="waiter_id" name="waiter_id" class="selectpicker form-control"
                                                title="Select waiter...">
                                                @if(auth()->user()->service_staff == 1)
                                                    <option value="{{auth()->user()->id}}" selected>{{auth()->user()->name}}
                                                    </option>
                                                @else
                                                    @foreach($waiter_list as $waiter)
                                                        <option value="{{$waiter->id}}" @if(!empty($waiter_id) && $waiter->id == $waiter_id) selected @endif>
                                                            {{$waiter->name}}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                    </div>
                                @endif

                                <a class="btn btn-primary btn-md more-options" data-toggle="collapse" href="#moreOptions"
                                    role="button" aria-expanded="false" aria-controls="moreOptions"><svg
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="size-6">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM18.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                                    </svg></a>
                            </div>
                            <div id="pos-collapse">
                                <div id="warehousePanel" class="collapse pos-panel" data-parent="#pos-collapse">
                                    <div class="card card-body shadow-sm">
                                        <label class="font-weight-bold text-muted small mb-1">{{ __('db.Warehouse') }}</label>
                                        <div class="input-group">
                                            @php
                                                if (!empty($selectedPosWarehouseId)) {
                                                    $warehouse_id = $selectedPosWarehouseId;
                                                } elseif (isset($lims_sale_data) && !empty($lims_sale_data) && $lims_sale_data->warehouse_id) {
                                                    $warehouse_id = $lims_sale_data->warehouse_id;
                                                } elseif ($lims_pos_setting_data && $lims_pos_setting_data->warehouse_id) {
                                                    $warehouse_id = $lims_pos_setting_data->warehouse_id;
                                                } else {
                                                    $warehouse_id = optional($lims_warehouse_list->first())->id;
                                                }

                                                if (auth()->user()->role_id > 2 && auth()->user()->warehouse_id) {
                                                    $warehouse_id = auth()->user()->warehouse_id;
                                                }
                                            @endphp
                                            <select required id="warehouse_id" name="warehouse_id"
                                                class="selectpicker form-control" data-live-search="true"
                                                data-live-search-style="begins" title="Select warehouse...">
                                                @foreach($lims_warehouse_list as $warehouse)
                                                    <option value="{{$warehouse->id}}"
                                                        data-pos-type="{{ $warehouse->pos_type ?? 'regular' }}"
                                                        @if($warehouse->id == $warehouse_id) selected @endif>
                                                        {{$warehouse->name}}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        @if(($posType === 'both') && is_dir(base_path('Modules/Restaurant')))
                                            <div class="pos-workflow-panel-section mt-3 pt-2 border-top" id="posWorkflowPanelSwitch">
                                                <label class="font-weight-bold text-muted small d-block mb-2">{{ __('db.POS Workflow') }}</label>
                                                <div class="btn-group btn-group-sm w-100" role="group" aria-label="POS Workflow">
                                                    <button type="button" class="btn {{ $activeWorkflow === 'retail' ? 'btn-primary font-weight-bold' : 'btn-outline-secondary' }}" onclick="switchPosWorkflow('retail')">
                                                        <i class="ti ti-shopping-cart mr-1"></i> {{ __('db.Retail') }}
                                                    </button>
                                                    <button type="button" class="btn {{ $activeWorkflow === 'restaurant' ? 'btn-primary font-weight-bold' : 'btn-outline-secondary' }}" onclick="switchPosWorkflow('restaurant')">
                                                        <i class="ti ti-cup mr-1"></i> {{ __('db.Restaurant') }}
                                                    </button>
                                                </div>
                                            </div>
                                        @endif

                                    </div>
                                </div>
                                <div id="billerPanel" class="collapse pos-panel" data-parent="#pos-collapse">
                                    <div class="card card-body shadow-sm">

                                        <div class="input-group">
                                            @php
                                                if (isset($lims_sale_data) && !empty($lims_sale_data) && $lims_sale_data->biller_id) {
                                                    $biller_id = $lims_sale_data->biller_id;
                                                } elseif ($lims_pos_setting_data && $lims_pos_setting_data->biller_id) {
                                                    $biller_id = $lims_pos_setting_data->biller_id;
                                                } else {
                                                    $biller_id = $lims_biller_list[0]->id ?? null;
                                                }
                                                if (auth()->user()->role_id > 2) {
                                                    $warehouse_id = auth()->user()->warehouse_id;
                                                }
                                            @endphp
                                            <select required id="biller_id" name="biller_id"
                                                class="selectpicker form-control" data-live-search="true"
                                                data-live-search-style="begins" title="Select Biller...">
                                                @foreach($lims_biller_list as $biller)
                                                    <option value="{{$biller->id}}" @if($biller->id == $biller_id) selected
                                                    @endif>{{$biller->name . ' (' . $biller->company_name . ')'}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div id="currencyPanel" class="collapse pos-panel" data-parent="#pos-collapse">
                                    <div class="card card-body shadow-sm">
                                        <div class="form-group d-flex">
                                            <div class="input-group-prepend">
                                                <select name="currency_id" id="currency" class="form-control selectpicker"
                                                    data-toggle="tooltip" title="" data-original-title="Sale currency">
                                                    @foreach($currency_list as $currency_data)
                                                        <option value="{{$currency_data->id}}"
                                                            data-rate="{{$currency_data->exchange_rate}}">
                                                            {{$currency_data->code}}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <input class="form-control" type="text" id="exchange_rate" name="exchange_rate"
                                                value="{{$currency->exchange_rate ?? 1}}">
                                            <div class="input-group-append">
                                                <span class="input-group-text"><x-info title="currency exchange rate"
                                                        type="info" /></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="collapse" id="moreOptions" data-parent="#pos-collapse">
                                    <div class="card card-body">
                                        <div class="row">
                                            <?php
    $accountSelection = $role_has_permissions_list->where('name', 'account-selection')->first();
                                            ?>
                                            @if ($accountSelection)
                                                <!-- New Account Selection Field -->
                                                <div class="col-md-3 col-6">
                                                    <div class="form-group">
                                                        <label>{{__('db.Account')}}</label>
                                                        <select required name="account_id" id="account_id"
                                                            class="selectpicker form-control" data-live-search="true">
                                                            <option value="0" style="color: #A7B49E;">Select an Account</option>
                                                            @foreach($lims_account_list as $account)
                                                                <option value="{{ $account->id }}"
                                                                    @if(auth()->user()->account_id == $account->id) selected
                                                                    @elseif($account->is_default == 1) selected @endif>
                                                                    {{ $account->name }}
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            @endif
                                            <div class="col-md-3">
                                                <label>{{__('db.Sale Reference No')}} <x-info
                                                        title="Sale reference is auto-generated if not inserted manually"
                                                        type="info" /></label>
                                                <div class="form-group">
                                                    <input type="text" id="reference-no" name="reference_no"
                                                        class="form-control" placeholder="Type reference number" />
                                                </div>
                                                <x-validation-error fieldName="reference_no" />
                                            </div>
                                            @foreach($custom_fields as $field)
                                                @if(!$field->is_admin || \Auth::user()->role_id == 1)
                                                    <div class="{{'col-md-' . $field->grid_value}}">
                                                        <div class="form-group">
                                                            <label>{{$field->name}}</label>
                                                            @if($field->type == 'text')
                                                                <input type="text"
                                                                    name="{{str_replace(' ', '_', strtolower($field->name))}}"
                                                                    value="{{$field->default_value}}" class="form-control"
                                                                    @if($field->is_required){{'required'}}@endif>
                                                            @elseif($field->type == 'number')
                                                                <input type="number"
                                                                    name="{{str_replace(' ', '_', strtolower($field->name))}}"
                                                                    value="{{$field->default_value}}" class="form-control"
                                                                    @if($field->is_required){{'required'}}@endif>
                                                            @elseif($field->type == 'textarea')
                                                                <textarea rows="5"
                                                                    name="{{str_replace(' ', '_', strtolower($field->name))}}"
                                                                    value="{{$field->default_value}}" class="form-control"
                                                                    @if($field->is_required){{'required'}}@endif></textarea>
                                                            @elseif($field->type == 'checkbox')
                                                                <br>
                                                                <?php            $option_values = explode(",", $field->option_value); ?>
                                                                @foreach($option_values as $value)
                                                                    <label>
                                                                        <input type="checkbox"
                                                                            name="{{str_replace(' ', '_', strtolower($field->name))}}[]"
                                                                            value="{{$value}}"
                                                                            @if($value == $field->default_value){{'checked'}}@endif
                                                                            @if($field->is_required){{'required'}}@endif> {{$value}}
                                                                    </label>
                                                                    &nbsp;
                                                                @endforeach
                                                            @elseif($field->type == 'radio_button')
                                                                <br>
                                                                <?php            $option_values = explode(",", $field->option_value); ?>
                                                                @foreach($option_values as $value)
                                                                    <label class="radio-inline">
                                                                        <input type="radio"
                                                                            name="{{str_replace(' ', '_', strtolower($field->name))}}"
                                                                            value="{{$value}}"
                                                                            @if($value == $field->default_value){{'checked'}}@endif
                                                                            @if($field->is_required){{'required'}}@endif> {{$value}}
                                                                    </label>
                                                                    &nbsp;
                                                                @endforeach
                                                            @elseif($field->type == 'select')
                                                                <?php            $option_values = explode(",", $field->option_value); ?>
                                                                <select class="form-control"
                                                                    name="{{str_replace(' ', '_', strtolower($field->name))}}"
                                                                    @if($field->is_required){{'required'}}@endif>
                                                                    @foreach($option_values as $value)
                                                                        <option value="{{$value}}"
                                                                            @if($value == $field->default_value){{'selected'}}@endif>
                                                                            {{$value}}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            @elseif($field->type == 'multi_select')
                                                                <?php            $option_values = explode(",", $field->option_value); ?>
                                                                <select class="form-control"
                                                                    name="{{str_replace(' ', '_', strtolower($field->name))}}[]"
                                                                    @if($field->is_required){{'required'}}@endif multiple>
                                                                    @foreach($option_values as $value)
                                                                        <option value="{{$value}}"
                                                                            @if($value == $field->default_value){{'selected'}}@endif>
                                                                            {{$value}}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            @elseif($field->type == 'date_picker')
                                                                <input type="text"
                                                                    name="{{str_replace(' ', '_', strtolower($field->name))}}"
                                                                    value="{{$field->default_value}}" class="form-control date"
                                                                    @if($field->is_required){{'required'}}@endif>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if($lims_pos_setting_data->is_table && !in_array('restaurant', explode(',', gen_setting()->modules)))
                                <div class="col-12 pl-0 pr-0">
                                    <div class="form-group">
                                        <select required id="table_id" name="table_id" class="selectpicker form-control"
                                            data-live-search="true" data-live-search-style="begins" title="Select table...">
                                            @foreach($lims_table_list as $table)
                                                <option value="{{$table->id}}">{{$table->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="search-box form-group mb-2"
                            style="border: 1px solid #ddd;border-radius: 10px;box-shadow: rgba(37, 83, 185, 0.1) 0px 2px 6px 0px;position:relative">
                            <div class="input-group pos align-items-center px-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="tabler-icon tabler-icon-search icon-search-icon">
                                    <path d="M3 10a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"></path>
                                    <path d="M21 21l-6 -6"></path>
                                </svg>
                                <input style="border:none;height: 42px;" type="text" name="product_code_name"
                                    id="product-search-input" placeholder="Scan/Search product by name/code/IMEI"
                                    class="form-control" autofocus />
                                <button type="button" class="btn btn-primary" onclick="barcode()"><svg
                                        xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                        class="bi bi-upc" viewBox="0 0 16 16">
                                        <path
                                            d="M3 4.5a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0zm2 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0zm2 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0zm2 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0z" />
                                    </svg></button>
                            </div>
                            <div id="product-results-container">

                            </div>
                            <div id="no-results-message"
                                style="background-color: #f5f6f7;color: #666; margin-top: 5px;padding: 3px 5px; display: none;">
                                No results found</div>
                        </div>
                        <div class="table-responsive transaction-list"
                            style="background:#FFF; border-radius: 10px; box-shadow: rgba(37, 83, 185, 0.1) 0px 2px 6px 0px;">
                            <table id="myTable" class="table table-hover table-striped order-list table-fixed">
                                <thead class="d-none d-md-block">
                                    <tr>
                                        <th class="col-sm-4 col-5">{{__('db.product')}}</th>
                                        <th class="col-sm-2">{{__('db.Price')}}</th>
                                        <th class="col-sm-3 text-center">{{__('db.Quantity')}}</th>
                                        <th class="col-sm-2">{{__('db.Subtotal')}}</th>
                                        <th class="col-sm-1 text-right"><i class="dripicons-trash"></i></th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-id">
                                    <tr id="empty-cart-row">
                                        <td class="text-center py-5" style="color:#9ca3af;width:100%">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 48px; height: 48px; margin: 0 auto; display: block; opacity: 0.5;">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                                            </svg>
                                            <p class="mt-2 mb-0">{{__('db.No_items_added_yet')}} {{__('db.Scan_or_click_a_product')}}</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="row" style="display: none;">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <input type="hidden" name="total_qty" value="0" />
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <input type="hidden" name="total_discount"
                                        value="{{number_format(0, gen_setting()->decimal, '.', '')}}" />
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <input type="hidden" name="total_tax"
                                        value="{{number_format(0, gen_setting()->decimal, '.', '')}}" />
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <input type="hidden" name="total_price"
                                        value="{{number_format(0, gen_setting()->decimal, '.', '')}}" />
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <input type="hidden" name="item" value="0" />
                                    <input type="hidden" name="order_tax"
                                        value="{{number_format(0, gen_setting()->decimal, '.', '')}}" />
                                </div>
                                <x-validation-error fieldName="item" />
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <input type="hidden" name="grand_total"
                                        value="{{number_format(0, gen_setting()->decimal, '.', '')}}" />
                                    <input type="hidden" name="used_points" />

                                    @if($isRestaurantWarehouse)
                                        <input type="hidden" name="sale_status" value="5" />
                                    @else
                                        <input type="hidden" name="sale_status" value="1" />
                                    @endif
                                    <x-validation-error fieldName="sale_status" />

                                    <input type="hidden" name="coupon_active">
                                    <input type="hidden" name="coupon_id" value="">
                                    <input type="hidden" name="coupon_discount" value="0" />

                                    <input type="hidden" name="pos" value="1" />

                                    @if(isset($lims_sale_data) && !empty($lims_sale_data))
                                        <input type="hidden" name="sale_id" value="{{$lims_sale_data->id}}" />
                                        <input type="hidden" name="draft" value="1" />
                                    @else
                                        <input type="hidden" name="draft" value="0" />
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-12 totals"
                            style="background-color:#f5f6f7;border-top: 2px solid #ebe9f1;padding-bottom: 7px;padding-top: 7px;">
                            <div class="row">
                                <div class="col-sm-6 col-6"></div>
                                <div class="col-sm-3 col-6">
                                    <strong class="totals-title">{{__('db.Items')}}</strong><strong id="item">0 (0)</strong>
                                </div>
                                <div class="col-sm-3 col-6">
                                    <strong class="totals-title">{{__('db.Total')}}</strong><strong
                                        id="subtotal">{{number_format(0, gen_setting()->decimal, '.', '')}}</strong>
                                </div>
                                @if ($handle_discount_active)
                                    <div class="col-sm-3 col-6">
                                        <strong class="totals-title">{{__('db.Discount')}} <span id="discount" class="d-none">{{number_format(0, gen_setting()->decimal, '.', '')}}</span></strong> 
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                @php
                                                    $selected_discount_type = old('order_discount_type_select') ?? ($lims_sale_data->order_discount_type ?? 'Flat');
                                                @endphp
                                                <select name="order_discount_type_select" class="form-control quick-calc" style="flex: 0 0 40%; max-width: 40%; padding: 0 5px; font-size: 14px; border-right: 0;">
                                                    <option value="Flat" {{ $selected_discount_type == 'Flat' ? 'selected' : '' }}>Flat</option>
                                                    <option value="Percentage" {{ $selected_discount_type == 'Percentage' ? 'selected' : '' }}>%</option>
                                                </select>
                                            </div>
                                            <input type="text" name="order_discount_value" id="order-discount-val" class="form-control numkey quick-calc" placeholder="0.00" value="{{$lims_sale_data->order_discount_value ?? ''}}" style="text-align: right;">
                                            <input type="hidden" name="order_discount" class="form-control" id="order-discount">
                                            <input type="hidden" name="order_discount_type">
                                        </div>
                                    </div>
                                @endif
                                <div class="col-sm-3 col-6">
                                    <strong class="totals-title">{{__('db.Coupon')}} <span id="coupon-text" class="d-none">{{number_format(0, gen_setting()->decimal, '.', '')}}</span></strong>
                                    <div class="input-group">
                                        @php
                                            $coupon_code = '';
                                            if (isset($lims_sale_data)) {
                                                $lims_coupon_data = $lims_coupon_list->where('id', $lims_sale_data->coupon_id)->first();
                                                $coupon_code = $lims_coupon_data ? $lims_coupon_data->code : '';
                                            }
                                        @endphp
                                        <input type="text" id="coupon-code" class="form-control" placeholder="{{__('db.Code')}}" value="{{ $coupon_code }}">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-primary coupon-check">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M5 12l5 5l10 -10" /></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-3 col-6">
                                    <strong class="totals-title">{{__('db.Tax')}} <span id="tax" class="d-none">{{number_format(0, gen_setting()->decimal, '.', '')}}</span></strong>
                                    <div class="input-group">
                                        <input type="hidden" name="order_tax_rate">
                                        <select class="form-control quick-calc" name="order_tax_rate_select" id="order-tax-rate-select">
                                            <option value="0">No Tax</option>
                                            @foreach($lims_tax_list as $tax)
                                                <option value="{{$tax->rate}}">{{$tax->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-3 col-6">
                                    <strong class="totals-title">{{__('db.Shipping')}} <span id="shipping-cost" class="d-none">{{number_format(0, gen_setting()->decimal, '.', '')}}</span></strong>
                                    <div class="input-group">
                                        <input type="text" name="shipping_cost" class="form-control numkey quick-calc" id="shipping-cost-val" step="any" placeholder="0.00" value="{{$lims_sale_data->shipping_cost ?? ''}}" style="text-align: right;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Checkout CTA -->
                        <button class="btn btn-success btn-lg btn-block payment-btn" style="font-size: 24px; font-weight: bold; margin-bottom: 5px; height: 60px; display: flex; justify-content: space-between; align-items: center; padding: 0 20px; border-radius: 5px; width: 100%;" type="button" data-toggle="modal" data-target="#add-payment" id="main-pay-btn" disabled="true">
                            <span style="display: flex; align-items: center;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 28px; height: 28px; margin-right: 10px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                                </svg> 
                                {{__('db.Pay')}}
                            </span>
                            <span id="grand-total">{{number_format(0, gen_setting()->decimal, '.', '')}}</span>
                        </button>

                        <button style="background: #010429;" type="button" class="btn btn-custom payment-btn pos-offline-hide" data-toggle="modal" data-target="#add-payment" id="multiple-payment-btn" disabled="true">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg> {{__('db.Multiple Payment')}}
                        </button>
                            
                        @if(in_array("credit", $options))
                        <button style="background: #f05969;" type="button" class="btn btn-custom payment-btn pos-offline-hide" data-toggle="modal" data-target="#add-payment" id="credit-sale-btn" disabled="true">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg> {{__('db.Credit Sale')}}
                        </button>
                        @endif
                            
                        @if(in_array("installment", $options))
                        <button type="button" class="btn btn-warning pos-offline-hide" disabled="true" id="installmentPlanBtn">
                            <i class="bi bi-credit-card"></i> {{__('db.Instalment')}}
                        </button>
                        @endif

                        <button style="background-color: #e28d02;" type="button" class="btn btn-custom pos-offline-hide" id="draft-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5" /></svg> {{__('db.Draft')}}
                        </button>
                            
                        <button style="background-color: #d63031;" type="button" class="btn btn-custom" id="cancel-btn" onclick="return confirmCancel()">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg> {{__('db.Cancel')}}
                        </button>
                            
                        <button style="background-color: #ffc107;" type="button" class="btn btn-custom pos-offline-hide" data-toggle="modal" data-target="#recentTransaction">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg> {{__('db.Recent Transaction')}}
                        </button>

                        <!-- payment modal -->

                        @if(in_array("payhere",$options) || in_array("stripe",$options) || in_array("moyasar",$options) || in_array("card",$options))
                        <!-- ===== PayHere Payment Modal ===== -->
                        <div id="payhere-modal" tabindex="-1" role="dialog" aria-hidden="true" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;z-index:99999;background:rgba(10,14,30,0.82);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);align-items:center;justify-content:center;">
                            <div style="background:#fff;border-radius:20px;width:420px;max-width:94vw;box-shadow:0 25px 60px rgba(0,0,0,0.35);overflow:hidden;font-family:'Inter',sans-serif;animation:phSlideIn .35s cubic-bezier(.34,1.56,.64,1) both">
                                <!-- Header -->
                                <div style="background:linear-gradient(135deg,#1a1f71 0%,#2563eb 100%);padding:24px 28px 20px;position:relative;">
                                    <div style="display:flex;align-items:center;gap:12px">
                                        <div style="background:rgba(255,255,255,0.15);border-radius:12px;padding:8px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#fff" style="width:24px;height:24px"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" /></svg>
                                        </div>
                                        <div>
                                            <div style="color:rgba(255,255,255,0.7);font-size:11px;font-weight:600;letter-spacing:1.5px;text-transform:uppercase">Secure Payment</div>
                                            <div id="qr-modal-header-title" style="color:#fff;font-size:18px;font-weight:700;margin-top:1px">PayHere</div>
                                        </div>
                                    </div>
                                    <button id="payhere-modal-close" type="button" style="position:absolute;top:16px;right:18px;background:rgba(255,255,255,0.15);border:none;border-radius:50%;width:30px;height:30px;color:#fff;cursor:pointer;font-size:16px;line-height:1;display:flex;align-items:center;justify-content:center;transition:background .2s" onmouseover="this.style.background='rgba(255,255,255,0.25)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">&times;</button>
                                </div>

                                <!-- Amount Bar -->
                                <div style="background:#f0f4ff;border-bottom:1px solid #e2e8f0;padding:16px 28px;display:flex;align-items:center;justify-content:space-between">
                                    <span style="color:#64748b;font-size:13px;font-weight:500">Payment Amount</span>
                                    <span id="payhere-modal-amount" style="color:#1a1f71;font-size:22px;font-weight:800;letter-spacing:-0.5px"></span>
                                </div>

                                <!-- Body -->
                                <div style="padding:28px">
                                    <!-- State: Loading -->
                                    <div id="ph-state-loading" style="text-align:center">
                                        <div style="margin:0 auto 16px;width:56px;height:56px;border-radius:50%;background:#f0f4ff;display:flex;align-items:center;justify-content:center">
                                            <svg style="animation:phSpin 1.2s linear infinite" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="28" height="28"><circle cx="12" cy="12" r="10" stroke="#c7d2fe" stroke-width="3"/><path d="M12 2a10 10 0 0 1 10 10" stroke="#2563eb" stroke-width="3" stroke-linecap="round"/></svg>
                                        </div>
                                        <p id="qr-modal-loading-msg" style="color:#1e293b;font-size:15px;font-weight:600;margin:0 0 6px">Connecting to PayHere...</p>
                                        <p style="color:#94a3b8;font-size:13px;margin:0">Please wait while we prepare your checkout</p>
                                    </div>

                                    <!-- State: Waiting (popup open) -->
                                    <div id="ph-state-waiting" style="text-align:center;display:none">
                                        <div style="margin:0 auto 16px;width:56px;height:56px;border-radius:50%;background:#fffbeb;display:flex;align-items:center;justify-content:center">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#d97706" width="28" height="28"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                        </div>
                                        <p style="color:#1e293b;font-size:15px;font-weight:600;margin:0 0 6px">Awaiting Payment</p>
                                        <p style="color:#94a3b8;font-size:13px;margin:0 0 16px">Complete the payment in the PayHere popup window</p>
                                        <div style="background:#f8fafc;border-radius:10px;padding:12px 16px;display:flex;align-items:center;gap:10px;border:1px solid #e2e8f0">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2563eb" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
                                            <span style="color:#475569;font-size:12px">Do not close this window. Status will update automatically.</span>
                                        </div>
                                    </div>

                                    <!-- State: Success -->
                                    <div id="ph-state-success" style="text-align:center;display:none">
                                        <div style="margin:0 auto 16px;width:56px;height:56px;border-radius:50%;background:#f0fdf4;display:flex;align-items:center;justify-content:center">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="#16a34a" width="28" height="28"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                        </div>
                                        <p style="color:#15803d;font-size:15px;font-weight:700;margin:0 0 6px">Payment Successful!</p>
                                        <p style="color:#94a3b8;font-size:13px;margin:0">Completing your sale, please wait...</p>
                                    </div>

                                    <!-- State: Error -->
                                    <div id="ph-state-error" style="text-align:center;display:none">
                                        <div style="margin:0 auto 16px;width:56px;height:56px;border-radius:50%;background:#fef2f2;display:flex;align-items:center;justify-content:center">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="#dc2626" width="28" height="28"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
                                        </div>
                                        <p style="color:#dc2626;font-size:15px;font-weight:700;margin:0 0 6px">Payment Failed</p>
                                        <p id="ph-error-msg" style="color:#94a3b8;font-size:13px;margin:0 0 16px"></p>
                                        <button id="ph-retry-btn" type="button" style="background:linear-gradient(135deg,#1a1f71,#2563eb);color:#fff;border:none;border-radius:10px;padding:10px 24px;font-size:13px;font-weight:600;cursor:pointer">Try Again</button>
                                    </div>
                                </div>

                                <!-- Footer -->
                                <div style="padding:0 28px 22px;display:flex;align-items:center;justify-content:center;gap:6px">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#94a3b8" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                                    <span id="qr-modal-footer" style="color:#94a3b8;font-size:11px">256-bit SSL encrypted</span>
                                </div>
                            </div>
                        </div>
                        <style>
                            @keyframes phSlideIn { from{opacity:0;transform:translateY(-30px) scale(.97)} to{opacity:1;transform:none} }
                            @keyframes phSpin   { to{transform:rotate(360deg)} }
                            #payhere-modal.ph-visible { display:flex !important; }
                        </style>
                        @push('scripts')
                        <script>
                            function phShowState(state) {
                                $('#ph-state-loading, #ph-state-waiting, #ph-state-success, #ph-state-error').hide();
                                $('#ph-state-' + state).show();
                            }
                            function phCloseModal() {
                                $('#payhere-modal').removeClass('ph-visible');
                            }
                            function qrOpenModal(amount, providerName) {
                                $('#qr-modal-header-title').text(providerName);
                                $('#qr-modal-loading-msg').text('Connecting to ' + providerName + '...');
                                $('#qr-modal-footer').html('256-bit SSL encrypted &mdash; Powered by ' + providerName);
                                $('#payhere-modal-amount').text(amount);
                                $('#payhere-modal').addClass('ph-visible');
                                phShowState('loading');
                            }
                        </script>
                        @endpush
                        @endif

                        <!-- ===== Stripe Payment Modal ===== -->
                        <div id="stripe-modal" tabindex="-1" role="dialog" aria-hidden="true" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;z-index:99999;background:rgba(10,14,30,0.82);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);align-items:center;justify-content:center;">
                            <div style="background:#fff;border-radius:20px;width:420px;max-width:94vw;box-shadow:0 25px 60px rgba(0,0,0,0.35);overflow:hidden;font-family:'Inter',sans-serif;animation:phSlideIn .35s cubic-bezier(.34,1.56,.64,1) both">
                                <!-- Header -->
                                <div style="background:linear-gradient(135deg,#6772e5 0%,#5469d4 100%);padding:24px 28px 20px;position:relative;">
                                    <div style="display:flex;align-items:center;gap:12px">
                                        <div style="background:rgba(255,255,255,0.15);border-radius:12px;padding:8px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#fff" style="width:24px;height:24px"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" /></svg>
                                        </div>
                                        <div>
                                            <div style="color:rgba(255,255,255,0.7);font-size:11px;font-weight:600;letter-spacing:1.5px;text-transform:uppercase">Secure Payment</div>
                                            <div style="color:#fff;font-size:18px;font-weight:700;margin-top:1px">Stripe</div>
                                        </div>
                                    </div>
                                    <button id="stripe-modal-close" type="button" style="position:absolute;top:16px;right:18px;background:rgba(255,255,255,0.15);border:none;border-radius:50%;width:30px;height:30px;color:#fff;cursor:pointer;font-size:16px;line-height:1;display:flex;align-items:center;justify-content:center;transition:background .2s" onmouseover="this.style.background='rgba(255,255,255,0.25)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">&times;</button>
                                </div>

                                <!-- Amount Bar -->
                                <div style="background:#f0f4ff;border-bottom:1px solid #e2e8f0;padding:16px 28px;display:flex;align-items:center;justify-content:space-between">
                                    <span style="color:#64748b;font-size:13px;font-weight:500">Payment Amount</span>
                                    <span id="stripe-modal-amount" style="color:#5469d4;font-size:22px;font-weight:800;letter-spacing:-0.5px"></span>
                                </div>

                                <!-- Body -->
                                <div style="padding:28px">
                                    <!-- State: Loading -->
                                    <div id="stripe-state-loading" style="text-align:center">
                                        <div style="margin:0 auto 16px;width:56px;height:56px;border-radius:50%;background:#f0f4ff;display:flex;align-items:center;justify-content:center">
                                            <svg style="animation:phSpin 1.2s linear infinite" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="28" height="28"><circle cx="12" cy="12" r="10" stroke="#c7d2fe" stroke-width="3"/><path d="M12 2a10 10 0 0 1 10 10" stroke="#5469d4" stroke-width="3" stroke-linecap="round"/></svg>
                                        </div>
                                        <p style="color:#1e293b;font-size:15px;font-weight:600;margin:0 0 6px">Connecting to Stripe...</p>
                                        <p style="color:#94a3b8;font-size:13px;margin:0">Generating secure checkout link</p>
                                    </div>

                                    <!-- State: Waiting (popup open) -->
                                    <div id="stripe-state-waiting" style="text-align:center;display:none">
                                        <div style="margin:0 auto 16px;width:56px;height:56px;border-radius:50%;background:#f3f4f6;display:flex;align-items:center;justify-content:center">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#6b7280" width="28" height="28"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                                        </div>
                                        <p style="color:#1e293b;font-size:15px;font-weight:600;margin:0 0 6px">Ready for Payment</p>
                                        <p style="color:#94a3b8;font-size:13px;margin:0 0 16px">Click below to securely pay via Stripe</p>
                                        
                                        <button id="stripe-proceed-btn" type="button" style="background:#5469d4;color:#fff;border:none;border-radius:10px;padding:12px 24px;font-size:14px;font-weight:600;cursor:pointer;width:100%;margin-bottom:12px;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 4px 6px -1px rgba(84,105,212,0.4)">
                                            <span>Pay with Stripe</span>
                                        </button>
                                        
                                        <div style="background:#f8fafc;border-radius:10px;padding:12px 16px;display:flex;align-items:center;gap:10px;border:1px solid #e2e8f0;text-align:left">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#5469d4" width="18" height="18" style="flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
                                            <span style="color:#475569;font-size:12px">A popup will open. Once paid, this window will automatically complete the sale.</span>
                                        </div>
                                    </div>

                                    <!-- State: Polling -->
                                    <div id="stripe-state-polling" style="text-align:center;display:none">
                                        <div style="margin:0 auto 16px;width:56px;height:56px;border-radius:50%;background:#fffbeb;display:flex;align-items:center;justify-content:center">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#d97706" width="28" height="28"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                        </div>
                                        <p style="color:#1e293b;font-size:15px;font-weight:600;margin:0 0 6px">Awaiting Payment Confirmation</p>
                                        <p style="color:#94a3b8;font-size:13px;margin:0 0 16px">Please complete the payment in the Stripe window</p>
                                    </div>

                                    <!-- State: Success -->
                                    <div id="stripe-state-success" style="text-align:center;display:none">
                                        <div style="margin:0 auto 16px;width:56px;height:56px;border-radius:50%;background:#f0fdf4;display:flex;align-items:center;justify-content:center">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="#16a34a" width="28" height="28"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                        </div>
                                        <p style="color:#15803d;font-size:15px;font-weight:700;margin:0 0 6px">Payment Successful!</p>
                                        <p style="color:#94a3b8;font-size:13px;margin:0">Completing your sale, please wait...</p>
                                    </div>

                                    <!-- State: Error -->
                                    <div id="stripe-state-error" style="text-align:center;display:none">
                                        <div style="margin:0 auto 16px;width:56px;height:56px;border-radius:50%;background:#fef2f2;display:flex;align-items:center;justify-content:center">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="#dc2626" width="28" height="28"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
                                        </div>
                                        <p style="color:#dc2626;font-size:15px;font-weight:700;margin:0 0 6px">Payment Failed</p>
                                        <p id="stripe-error-msg" style="color:#94a3b8;font-size:13px;margin:0 0 16px"></p>
                                        <button id="stripe-retry-btn" type="button" style="background:#5469d4;color:#fff;border:none;border-radius:10px;padding:10px 24px;font-size:13px;font-weight:600;cursor:pointer">Try Again</button>
                                    </div>
                                </div>

                                <!-- Footer -->
                                <div style="padding:0 28px 22px;display:flex;align-items:center;justify-content:center;gap:6px">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#94a3b8" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                                    <span style="color:#94a3b8;font-size:11px">256-bit SSL encrypted &mdash; Powered by Stripe</span>
                                </div>
                            </div>
                        </div>
                        <style>
                            #stripe-modal.ph-visible { display:flex !important; }
                        </style>
                        @push('scripts')
                        <script>
                            function stripeShowState(state) {
                                $('#stripe-state-loading, #stripe-state-waiting, #stripe-state-polling, #stripe-state-success, #stripe-state-error').hide();
                                $('#stripe-state-' + state).show();
                            }
                            function stripeCloseModal() {
                                $('#stripe-modal').removeClass('ph-visible');
                            }
                            function stripeOpenModal(amount) {
                                $('#stripe-modal-amount').text(amount);
                                $('#stripe-modal').addClass('ph-visible');
                                stripeShowState('loading');
                            }
                            $(document).on('click', '#stripe-modal-close', function() {
                                stripeCloseModal();
                            });
                        </script>
                        @endpush
                        <!-- ===== End Stripe Modal ===== -->

                        <div id="add-payment" tabindex="-1" role="dialog" aria-hidden="true" class="modal fade text-left">
                            <div role="document" class="modal-dialog modal-lg modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">{{__('db.Finalize Sale')}}</h5>
                                        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span
                                                aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="size-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M6 18 18 6M6 6l12 12" />
                                                </svg></span></button>
                                    </div>
                                    <div class="modal-body">
                                        <!-- Payment Method Selector Tiles -->
                                        <style>
                                        .pos-payment-grid {
                                            display: grid;
                                            gap: 8px;
                                            padding-bottom: 10px;
                                            grid-template-columns: repeat(5, minmax(0, 1fr));
                                        }
                                        @media (max-width: 1200px) { .pos-payment-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
                                        @media (max-width: 992px) { .pos-payment-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
                                        @media (max-width: 576px) { .pos-payment-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
                                        
                                        .pos-payment-grid .payment-btn {
                                            width: 100% !important;
                                            min-height: 42px !important;
                                            height: auto !important;
                                            padding: 8px 5px !important;
                                            margin: 0 !important;
                                            border-radius: 5px;
                                            grid-column: span 1 !important;
                                            color: #333 !important;
                                            background: #f8f9fa !important;
                                            border: 1px solid #ced4da !important;
                                            box-shadow: none !important;
                                            transition: all 0.2s ease-in-out;
                                            font-size: 14px;
                                            display: flex;
                                            flex-direction: column;
                                            align-items: center;
                                            justify-content: center;
                                        }
                                        
                                        .pos-payment-grid .payment-btn.active-method {
                                            background: #7c5cc4 !important;
                                            color: #fff !important;
                                            border-color: #7c5cc4 !important;
                                            font-weight: bold;
                                        }
                                        
                                        .pos-payment-grid .payment-btn i {
                                            font-size: 16px;
                                            margin-bottom: 2px;
                                        }
                                        .pos-payment-receiver-group,
                                        .pos-sale-note-group,
                                        .pos-staff-note-group {
                                            display: none !important;
                                            margin: 0 !important;
                                            padding: 0 !important;
                                            height: 0 !important;
                                            overflow: hidden !important;
                                        }
                                        </style>
                                        
                                        <div class="row">
                                            <div class="col-lg-9 col-md-8" id="payment-select-row">
                                                <div class="pos-payment-grid mb-3">
                                            @if(in_array("cash", $options))
                                                <button type="button" class="btn btn-sm btn-custom payment-btn" id="cash-btn"><i class="bi bi-cash"></i> {{__('db.Cash')}}</button>
                                            @endif
                                            @if(in_array("card", $options))
                                                <button type="button" class="btn btn-sm btn-custom payment-btn pos-offline-hide" id="credit-card-btn"><i class="bi bi-credit-card"></i> {{__('db.Card')}}</button>
                                            @endif
                                            @if(in_array("cheque", $options))
                                                <button type="button" class="btn btn-sm btn-custom payment-btn pos-offline-hide" id="cheque-btn"><i class="bi bi-journal-text"></i> {{__('db.Cheque')}}</button>
                                            @endif
                                            @if(in_array("gift_card", $options))
                                                <button type="button" class="btn btn-sm btn-custom payment-btn pos-offline-hide" id="gift-card-btn"><i class="bi bi-gift"></i> {{__('db.Gift Card')}}</button>
                                            @endif
                                            @if(in_array("deposit", $options))
                                                <button type="button" class="btn btn-sm btn-custom payment-btn pos-offline-hide" id="deposit-btn"><i class="bi bi-bank"></i> {{__('db.Deposit')}}</button>
                                            @endif
                                            @if(in_array("points", $options))
                                                @if($lims_reward_point_setting_data && $lims_reward_point_setting_data->is_active)
                                                    <button type="button" class="btn btn-sm btn-custom payment-btn pos-offline-hide" id="point-btn"><i class="bi bi-star"></i> {{__('db.Points')}}</button>
                                                @endif
                                            @endif
                                            
                                            <?php
                                                $fixed_methods_for_tiles = ['cash', 'card', 'cheque', 'gift_card', 'deposit', 'pesapal', 'credit', 'points','installment'];
                                                $payment_methods_for_tiles = explode(',', $lims_pos_setting_data->payment_options);
                                                $payment_methods_for_tiles = array_diff($payment_methods_for_tiles, $fixed_methods_for_tiles);
                                                $payment_methods_for_tiles = array_values($payment_methods_for_tiles);
                                            ?>
                                            @foreach ($payment_methods_for_tiles as $method)
                                                @php
                                                    $display_label = ucfirst($method);
                                                    if (strtolower($method) === 'mpesa') $display_label = 'M-Pesa';
                                                    elseif (strtolower($method) === 'mtnmomo') $display_label = 'MTN MoMo';
                                                    elseif (strtolower($method) === 'payhere') $display_label = 'PayHere';
                                                    elseif (strtolower($method) === 'bkash') $display_label = 'bKash';
                                                @endphp
                                                <button class="btn btn-sm btn-custom payment-btn pay-options pos-offline-hide" type="button" value="{{ $method }}" data-payment-method="{{ $method }}">{{ $display_label }}</button>
                                            @endforeach
                                        </div>
                                                <div class="row">
                                                    <div class="col-md-6 col-lg-4 mt-1 paying-amount-container">
                                                        <label>{{__('db.Paying Amount')}} *</label>
                                                        <input type="text" name="paid_amount[]" value="0"
                                                            class="form-control paid_amount numkey" step="any">
                                                    </div>
                                                    <div class="col-md-6 col-lg-4 mt-1">
                                                        <input type="hidden" name="paid_by_id[]">
                                                        <label>{{__('db.Paid By')}}</label>
                                                        <select name="paid_by_id_select[]"
                                                            class="form-control selectpicker">
                                                            @if(in_array("cash", $options))
                                                                <option value="1">Cash</option>
                                                            @endif
                                                            @if(in_array("gift_card", $options))
                                                                <option value="2">Gift Card</option>
                                                            @endif
                                                            @if(in_array("card", $options))
                                                                <option value="3">Credit Card</option>
                                                            @endif
                                                            @if(in_array("cheque", $options))
                                                                <option value="4">Cheque</option>
                                                            @endif
                                                            @if(in_array("deposit", $options))
                                                                <option value="6">Deposit</option>
                                                            @endif
                                                            @if($lims_reward_point_setting_data && $lims_reward_point_setting_data->is_active)
                                                                <option value="7">Points</option>
                                                            @endif
                                                            @if(in_array("credit", $options)) {{-- ✅ এটা যোগ করুন --}}
                                                                <option value="credit_sale">Credit Sale</option>
                                                            @endif
                                                            @if(in_array("razorpay", $options))
                                                                <option value="razorpay">Razorpay</option>
                                                            @endif
                                                            @foreach($options as $option)
                                                                @if(!in_array($option, ['cash', 'card', 'cheque', 'gift_card', 'deposit', 'paypal', 'pesapal', 'points', 'credit']))
                                                                    <option value="{{$option}}">{{ucfirst($option)}}</option>
                                                                @endif
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 col-lg-4 mt-1 cash-received-container">
                                                        <label id="received-paying">{{__('db.Cash Received')}} <x-info
                                                                title="Cash handed over to you. example: sale amount is 300. customer gives you 500. cash received: 500 "
                                                                type="info" /> *</label>
                                                        <input type="text" name="paying_amount[]"
                                                            class="form-control paying_amount numkey" required step="any">
                                                    </div>
                                                </div>
                                                <div class="row add-more-row mt-2">
                                                    <div class="col-md-12 text-center"><button
                                                            class="btn btn-info add-more">+
                                                            {{__('db.Add More Payment')}}</button></div>
                                                </div>
                                                <div id="payment_receiver_id" class="row">
                                                    <div class="col-md-12 mt-1 pos-payment-receiver-group" style="display: none !important;">
                                                        <label>{{__('db.Payment Receiver')}}</label>
                                                        <input type="text" name="payment_receiver" class="form-control">
                                                    </div>
                                                    <div class="form-group col-md-12">
                                                        <label>{{__('db.Payment Note')}}</label>
                                                        <textarea id="payment_note" rows="2" class="form-control"
                                                            name="payment_note" style="max-height: 80px;"></textarea>
                                                    </div>
                                                    <div class="col-md-6 form-group pos-sale-note-group" style="display: none !important;">
                                                        <label>{{__('db.Sale Note')}}</label>
                                                        <textarea rows="3" class="form-control" name="sale_note"></textarea>
                                                    </div>
                                                    <div class="col-md-6 form-group pos-staff-note-group" style="display: none !important;">
                                                        <label>{{__('db.Staff Note')}}</label>
                                                        <textarea rows="3" class="form-control"
                                                            name="staff_note"></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-4 p-2 bg-info text-light pt-4 pb-4 payment-info">
                                                <div class="mt-4">
                                                    <h2>{{__('db.Total Payable')}}</h2>
                                                    <p class="total_payable text-light"></p>
                                                </div>
                                                <div class="mt-4">
                                                    <h2>{{__('db.Total Paying')}}</h2>
                                                    <p class="total_paying text-light">0.00</p>
                                                </div>
                                                <div class="mt-4">
                                                    <h2>{{__('db.Change')}}</h2>
                                                    <p class="change text-light">0.00</p>
                                                </div>
                                                <div class="mt-4">
                                                    <h2>{{__('db.Due')}}</h2>
                                                    <p class="due text-light">0.00</p>
                                                </div>
                                            </div>
                                            {{-- points info here --}}
                                            <div class="points-info col-lg-3 col-md-4 bg-info text-light p-2  pt-4 pb-4 ">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="mt-3">
                                                    @include('backend.sale.credit_limit_checker')
                                                    <button id="submit-btn" type="button"
                                                        class="btn btn-primary sale-submit-btn">{{__('db.Complete Sale')}}</button>
                                                    @if ($lims_pos_setting_data && $lims_pos_setting_data->show_print_invoice)
                                                        <div class="form-check d-inline-block ml-3">
                                                            <input class="form-check-input" type="checkbox" name="print_invoice"
                                                                id="print_invoice" checked>
                                                            <label style="color:rgb(136, 136, 136);" class="form-check-label"
                                                                for="print_invoice">
                                                                {{ __('db.print_invoice') }}
                                                            </label>
                                                        </div>
                                                    @endif
                                                    <div class="form-check pos-offline-hide d-inline-block ml-3">
                                                        <input class="form-check-input" type="checkbox" name="send_whatsapp"
                                                            id="send_whatsapp" checked>
                                                        <label style="color:rgb(136, 136, 136);" class="form-check-label"
                                                            for="send_whatsapp">
                                                            {{ __('db.send_whatsapp_message') }}
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>



                    </form>

                    {{-- invoice modal start --}}
                    <div id="invoice-modal" tabindex="-1" role="dialog" aria-hidden="true" class="modal fade text-left">
                        <div role="document" class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span
                                            aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6 18 18 6M6 6l12 12" />
                                            </svg></span></button>
                                </div>
                                <div id="invoice-modal-content" class="modal-body">
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- invoice modal end --}}

                    <!-- product edit modal -->
                    <div id="editModal" tabindex="-1" role="dialog" aria-hidden="true" class="modal fade text-left">
                        <div role="document" class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 id="modal_header" class="modal-title"></h5>
                                    <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span
                                            aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6 18 18 6M6 6l12 12" />
                                            </svg></span></button>
                                </div>
                                <div class="modal-body">
                                    <form>
                                        <div class="row modal-element">
                                            <div class="col-md-4 form-group">
                                                <label>{{__('db.Quantity')}}</label>
                                                <input type="text" name="edit_qty" class="form-control numkey">
                                            </div>
                                            <div class="col-md-4 form-group">
                                                <label>{{__('db.Unit Discount')}}</label>
                                                <input type="text" name="edit_discount" class="form-control numkey">
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>{{__('db.Price Option')}}</strong> </label>
                                                    <div class="input-group">
                                                        <select class="form-control selectpicker" name="price_option"
                                                            class="price-option">
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 form-group">
                                                <label>{{__('db.Unit Price')}}</label>
                                                @can('price_edit_in_sale')
                                                    <input type="text" name="edit_unit_price" class="form-control numkey"
                                                        step="any">
                                                @else
                                                    <input type="text" name="edit_unit_price" class="form-control numkey"
                                                        step="any" readonly>
                                                @endcan
                                            </div>
                                            <?php
    $tax_name_all[] = 'No Tax';
    $tax_rate_all[] = 0;
    foreach ($lims_tax_list as $tax) {
        $tax_name_all[] = $tax->name;
        $tax_rate_all[] = $tax->rate;
    }
                                                ?>
                                            <div class="col-md-4 form-group">
                                                <label>{{__('db.Tax Rate')}}</label>
                                                <select name="edit_tax_rate" class="form-control selectpicker">
                                                    @foreach($tax_name_all as $key => $name)
                                                        <option value="{{$key}}">{{$name}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div id="edit_unit" class="col-md-4 form-group">
                                                <label>{{__('db.Product Unit')}}</label>
                                                <select name="edit_unit" class="form-control selectpicker">
                                                </select>
                                            </div>
                                            <!-- <div class="col-md-4 form-group">
                                                    <label>{{__('db.Cost')}}</label>
                                                    <p id="product-cost"></p>
                                                </div> -->
                                        </div>
                                        <button type="button" name="update_btn"
                                            class="btn btn-primary">{{__('db.update')}}</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- add customer modal -->
                    <div id="addCustomer" tabindex="-1" role="dialog" aria-hidden="true" class="modal fade text-left">
                        <div role="document" class="modal-dialog">
                            <div class="alert-container mb-3"></div>
                            <div class="modal-content">
                                <form action="{{ route('customer.store') }}" method="POST" enctype="multipart/form-data"
                                    id="customer-form">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title">{{__('db.Add Customer')}}</h5>
                                        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span
                                                aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="size-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M6 18 18 6M6 6l12 12" />
                                                </svg></span></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="italic">
                                            <small>{{__('db.The field labels marked with are required input fields')}}.</small>
                                        </p>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>{{__('db.Customer Group')}} *</strong> </label>
                                                    <select required class="form-control selectpicker"
                                                        name="customer_group_id">
                                                        @foreach($lims_customer_group_all as $customer_group)
                                                            <option value="{{$customer_group->id}}">{{$customer_group->name}}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>{{__('db.name')}} *</strong> </label>
                                                    <input type="text" name="customer_name" required class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>{{ __('db.Type') }}</label>
                                                    <select name="type" class="form-control">
                                                        @foreach(\App\Enums\CustomerTypeEnum::cases() as $case)
                                                            <option value="{{ $case->value }}">
                                                                {{ $case->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>{{__('db.Email')}}</label>
                                                    <input type="text" name="email" placeholder="example@example.com"
                                                        class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>{{__('db.Phone Number')}} *</label>
                                                    <input type="text" name="phone_number" required class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="country-phone-group form-group">
                                                    <label>{{__('db.WhatsApp Number')}}</label>
                                                    <div class="d-flex">
                                                        <select id="country_code" name="country_code"
                                                            class="form-control w-auto me-2">
                                                        </select>
                                                        <input type="tel" id="wa_number" class="form-control">
                                                        <input type="hidden" id="full_phone" name="wa_number">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>{{__('db.Address')}}</label>
                                                    <input type="text" name="address" required class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>{{__('db.City')}}</label>
                                                    <input type="text" name="city" required class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-4 customer-credit-field">
                                                <div class="form-group">
                                                    <label>{{__('db.Credit Limit')}} <x-info
                                                            title="Leave it blank for unlimited credit"
                                                            type="info" /></label>
                                                    <input type="number" name="credit_limit" class="form-control" value="0"
                                                        step="any" min="0">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>{{__('db.Tax Number')}}</label>
                                                    <input type="text" name="tax_no" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <input type="hidden" name="pos" value="1">
                                            <button type="button"
                                                class="btn btn-primary customer-submit-btn">{{__('db.submit')}}</button>

                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- recent transaction modal -->
                    <div id="recentTransaction" tabindex="-1" role="dialog" aria-hidden="true" class="modal fade text-left">
                        <div role="document" class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">{{__('db.Recent Transaction')}}
                                        <div class="badge badge-primary">{{__('db.latest')}} 10</div>
                                    </h5>
                                    <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span
                                            aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6 18 18 6M6 6l12 12" />
                                            </svg></span></button>
                                </div>
                                <div class="modal-body">
                                    <ul class="nav nav-tabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active" href="#sale-latest" role="tab"
                                                data-toggle="tab">{{__('db.Sale')}}</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="#draft-latest" role="tab"
                                                data-toggle="tab">{{__('db.Draft')}}</a>
                                        </li>
                                    </ul>
                                    <div class="tab-content">
                                        <div role="tabpanel" class="tab-pane show active" id="sale-latest">
                                            <div class="table-responsive">
                                                <table class="table">
                                                    <thead>
                                                        <tr>
                                                            <th>{{__('db.date')}}</th>
                                                            <th>{{__('db.reference')}}</th>
                                                            <th>{{__('db.customer')}}</th>
                                                            <th>{{__('db.grand total')}}</th>
                                                            <th>{{__('db.action')}}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>

                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div role="tabpanel" class="tab-pane fade" id="draft-latest">
                                            <div class="table-responsive">
                                                <table class="table">
                                                    <thead>
                                                        <tr>
                                                            <th>{{__('db.date')}}</th>
                                                            <th>{{__('db.reference')}}</th>
                                                            <th>{{__('db.customer')}}</th>
                                                            <th>{{__('db.grand total')}}</th>
                                                            <th>{{__('db.action')}}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @include('backend.sale.sale_details_modal')

                    <!-- today sale modal -->
                    <div id="today-sale-modal" tabindex="-1" role="dialog" aria-hidden="true" class="modal fade text-left">
                        <div role="document" class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">{{__('db.Today Sale')}}</h5>
                                    <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span
                                            aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6 18 18 6M6 6l12 12" />
                                            </svg></span></button>
                                </div>
                                <div class="modal-body">
                                    <small>{{__('db.Please review the transaction and payments')}}</small>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <table class="table table-hover">
                                                <tbody>
                                                    <tr>
                                                        <td>{{__('db.Total Sale Amount')}}:</td>
                                                        <td class="total_sale_amount text-right"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>{{__('db.Cash Payment')}}:</td>
                                                        <td class="cash_payment text-right"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>{{__('db.Credit Card Payment')}}:</td>
                                                        <td class="credit_card_payment text-right"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>{{__('db.Cheque Payment')}}:</td>
                                                        <td class="cheque_payment text-right"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>{{__('db.Gift Card Payment')}}:</td>
                                                        <td class="gift_card_payment text-right"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>{{__('db.Deposit Payment')}}:</td>
                                                        <td class="deposit_payment text-right"></td>
                                                    </tr>
                                                    @if(in_array("paypal", $options) && (strlen(env('PAYPAL_LIVE_API_USERNAME')) > 0) && (strlen(env('PAYPAL_LIVE_API_PASSWORD')) > 0) && (strlen(env('PAYPAL_LIVE_API_SECRET')) > 0))
                                                        <tr>
                                                            <td>{{__('db.Paypal Payment')}}:</td>
                                                            <td class="paypal_payment text-right"></td>
                                                        </tr>
                                                    @endif
                                                    <tr>
                                                        <td>{{__('db.Total Payment')}}:</td>
                                                        <td class="total_payment text-right"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>{{__('db.Total Sale Return')}}:</td>
                                                        <td class="total_sale_return text-right"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>{{__('db.Total Expense')}}:</td>
                                                        <td class="total_expense text-right"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>{{__('db.Total Cash')}}:</strong></td>
                                                        <td class="total_cash text-right"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- today profit modal -->
                    <div id="today-profit-modal" tabindex="-1" role="dialog" aria-hidden="true"
                        class="modal fade text-left">
                        <div role="document" class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">{{__('db.Today Profit')}}</h5>
                                    <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span
                                            aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6 18 18 6M6 6l12 12" />
                                            </svg></span></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <select required name="warehouseId" class="form-control">
                                                <option value="0">{{__('db.All Warehouse')}}</option>
                                                @foreach($lims_warehouse_list as $warehouse)
                                                    <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-12 mt-2">
                                            <table class="table table-hover">
                                                <tbody>
                                                    <tr>
                                                        <td>{{__('db.Product Revenue')}}:</td>
                                                        <td class="product_revenue text-right"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>{{__('db.Product Cost')}}:</td>
                                                        <td class="product_cost text-right"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>{{__('db.Expense')}}:</td>
                                                        <td class="expense_amount text-right"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>{{__('db.profit')}} <x-info
                                                                    title="Revenue - Product Cost - Expense"
                                                                    type="info" />:</strong></td>
                                                        <td class="profit text-right"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- expense modal -->
        <div id="expense-modal" tabindex="-1" role="dialog" aria-hidden="true" class="modal fade text-left">
            <div role="document" class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Add Expense') }}</h5>
                        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span
                                aria-hidden="true"><i class="X"></i></span></button>
                    </div>
                    <div class="modal-body">
                        <p class="italic"><small>{{ __('The field labels marked with are required input fields') }}.</small>
                        </p>
                        <form action="{{ route('expenses.store') }}" method="POST">
                            @csrf
                            <?php
    if (Auth::user()->role_id > 2)
        $lims_warehouse_list = $lims_warehouse_list->where('id', Auth::user()->warehouse_id);

                ?>
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>{{ __('Date') }}</label>
                                    <input type="text" name="created_at" class="form-control date"
                                        placeholder="{{__('db.Choose date')}}"
                                        value="{{date(gen_setting()->date_format, strtotime('now'))}}" />
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>{{ __('Expense Category') }} *</label>
                                    <select name="expense_category_id" class="selectpicker form-control" required
                                        data-live-search="true" data-live-search-style="begins"
                                        title="Select Expense Category...">
                                        @foreach($lims_expense_category_list as $expense_category)
                                            <option value="{{$expense_category->id}}">
                                                {{$expense_category->name . ' (' . $expense_category->code . ')'}}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>{{ __('Warehouse') }} *</label>
                                    <select name="warehouse_id" class="selectpicker form-control" required
                                        data-live-search="true" data-live-search-style="begins" title="Select Warehouse...">
                                        @foreach($lims_warehouse_list as $warehouse)
                                            <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>{{ __('Amount') }} *</label>
                                    <input type="number" id="expense-amount" name="amount" step="any" required
                                        class="form-control">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label> {{ __('Account') }}</label>
                                    <select class="form-control selectpicker" name="account_id">
                                        @foreach($lims_account_list as $account)
                                            @if($account->is_default)
                                                <option selected value="{{$account->id}}">{{$account->name}}
                                                    [{{$account->account_no}}]</option>
                                            @else
                                                <option value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>{{ __('Note') }}</label>
                                <textarea name="note" rows="3" class="form-control"></textarea>
                            </div>
                            <div class="form-group">
                                <input type="hidden" name="cash_register" value="" />
                                <button type="submit" class="btn btn-primary">{{ __('submit') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- end expense modal -->

        <!-- supplier payment modal -->
        <div id="add-supplier-payment" tabindex="-1" role="dialog" aria-hidden="true" class="modal fade text-left">
            <div role="document" class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{__('db.Add Payment')}}</h5>
                        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span
                                aria-hidden="true"><i class="X"></i></span></button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('supplier.clearDue') }}" method="POST" class="supplier-payment-form">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 mt-1">
                                    <label>{{__('db.Supplier')}} *</label>
                                    <select name="supplier_id" id="supplier_list" class="form-control"
                                        data-live-search="true" data-live-search-style="begins" title="Select Supplier..."
                                        required>
                                        <option value=""></option>
                                    </select>
                                </div>
                                <div class="col-md-6 mt-1">
                                    <label>{{__('db.Due')}}</label>
                                    <input type="number" class="form-control" readonly name="balance">
                                </div>
                                <div class="col-md-12 mt-1">
                                    <label>{{__('db.Amount')}} *</label>
                                    <input type="number" id="supplier-amount" name="amount" step="any" class="form-control"
                                        required>
                                </div>
                                <div class="col-md-12 mt-1">
                                    <label>{{__('db.Note')}}</label>
                                    <textarea name="note" rows="4" class="form-control"></textarea>
                                </div>
                            </div>
                            <input type="hidden" name="cash_register" value="" />
                            <button type="submit" class="btn btn-primary">{{__('db.submit')}}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- end supplier payment modal -->

        @if($lims_pos_setting_data && $lims_pos_setting_data->cash_register)
            <!-- add cash register modal -->
            <div id="cash-register-modal" data-backdrop="static" tabindex="-1" role="dialog" aria-hidden="true"
                class="modal fade text-left">
                <div role="document" class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route('cashRegister.store') }}" method="POST">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">{{__('db.Add Cash Register')}}</h5>
                            </div>
                            <div class="modal-body">
                                <p class="italic">
                                    <small>{{__('db.The field labels marked with are required input fields')}}.</small>
                                </p>
                                <div class="row">
                                    <div class="col-md-6 form-group warehouse-section">
                                        <label>{{__('db.Warehouse')}} *</strong> </label>
                                        <select required name="warehouse_id" class="selectpicker form-control"
                                            data-live-search="true" data-live-search-style="begins" title="Select warehouse...">
                                            @foreach($lims_warehouse_list as $warehouse)
                                                <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                            @endforeach
                                        </select>
                                        <x-validation-error fieldName="warehouse_id" />
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>{{__('db.Cash in Hand')}} *</strong> </label>
                                        <input type="number" step="any" name="cash_in_hand" required class="form-control">
                                    </div>
                                    <div class="col-md-12 form-group">
                                        <button type="submit" class="btn btn-primary">{{__('db.submit')}}</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- cash register details modal -->
            <div id="register-details-modal" tabindex="-1" role="dialog" aria-hidden="true" class="modal fade text-left">
                <div role="document" class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title">{{__('db.Cash Register Details')}}</h5>
                                <small>{{__('db.Please review the transaction and payments')}}</small>
                            </div>
                            <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span
                                    aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="size-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg></span></button>
                        </div>
                        <div class="modal-body pt-0">
                            <form action="{{route('cashRegister.close')}}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-md-12">
                                        <table class="table table-hover">
                                            <tbody>
                                                <tr>
                                                    <td>{{__('db.Cash in Hand')}}:</td>
                                                    <td id="cash_in_hand" class="text-right">0</td>
                                                </tr>
                                                <tr>
                                                    <td>{{__('db.Total Sale Amount')}}:</td>
                                                    <td id="total_sale_amount" class="text-right"></td>
                                                </tr>
                                                <tr>
                                                    <td>{{__('db.Total Payment')}}:</td>
                                                    <td id="total_payment" class="text-right"></td>
                                                </tr>
                                                @if(in_array("cash", $options))
                                                    <tr>
                                                        <td>{{__('db.Cash Payment')}}:</td>
                                                        <td id="cash_payment" class="text-right"></td>
                                                    </tr>
                                                @endif
                                                @if(in_array("card", $options))
                                                    <tr>
                                                        <td>{{__('db.Credit Card Payment')}}:</td>
                                                        <td id="credit_card_payment" class="text-right"></td>
                                                    </tr>
                                                @endif
                                                @if(in_array("cheque", $options))
                                                    <tr>
                                                        <td>{{__('db.Cheque Payment')}}:</td>
                                                        <td id="cheque_payment" class="text-right"></td>
                                                    </tr>
                                                @endif
                                                @if(in_array("gift_card", $options))
                                                    <tr>
                                                        <td>{{__('db.Gift Card Payment')}}:</td>
                                                        <td id="gift_card_payment" class="text-right"></td>
                                                    </tr>
                                                @endif
                                                @if(in_array("deposit", $options))
                                                    <tr>
                                                        <td>{{__('db.Deposit Payment')}}:</td>
                                                        <td id="deposit_payment" class="text-right"></td>
                                                    </tr>
                                                @endif
                                                @if(in_array("paypal", $options) && (strlen(env('PAYPAL_LIVE_API_USERNAME')) > 0) && (strlen(env('PAYPAL_LIVE_API_PASSWORD')) > 0) && (strlen(env('PAYPAL_LIVE_API_SECRET')) > 0))
                                                    <tr>
                                                        <td>{{__('db.Paypal Payment')}}:</td>
                                                        <td id="paypal_payment" class="text-right"></td>
                                                    </tr>
                                                @endif
                                            <tbody id="custom-methods-container"></tbody>
                                            <tr>
                                                <td>{{__('db.Total Sale Return')}}:</td>
                                                <td id="total_sale_return" class="text-right"></td>
                                            </tr>
                                            <tr>
                                                <td>{{__('db.Total Expense')}}:</td>
                                                <td id="total_expense" class="text-right"></td>
                                            </tr>
                                            <tr>
                                                <td>{{__('db.Total Supplier Payment')}}:</td>
                                                <td id="total_supplier_payment" class="text-right"></td>
                                            </tr>
                                            <tr>
                                                <td><strong>{{__('db.Total Cash')}}:</strong></td>
                                                <td id="total_cash" class="text-right"></td>
                                            </tr>
                                            <tr id="closing_row" style="display:none">
                                                <td><strong>{{__('db.Actual Cash')}}:</strong></td>
                                                <td class="text-right">
                                                    <input class="form-control" type="text" name="actual_cash"
                                                        style="max-width:200px; text-align:right;float:right" value=""
                                                        min="0" />
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="col-md-12 text-center" id="closing-section">
                                        <button id="close_register" type="button"
                                            class="btn btn-primary">{{__('db.Close Register')}}</button>
                                        <input type="hidden" name="closing_balance">
                                        <input type="hidden" name="cash_register_id">
                                        <button type="submit" class="btn btn-primary"
                                            id="submit_register">{{__('db.Close Register')}}</button>

                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        <!-- Choose POS Workflow Modal -->
        <div class="modal fade" id="posWorkflowChoiceModal" tabindex="-1" role="dialog" aria-labelledby="posWorkflowChoiceModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title font-weight-bold" id="posWorkflowChoiceModalLabel">
                            <i class="ti ti-layout-grid mr-2"></i>{{ __('db.Choose POS Workflow') }}
                        </h5>
                    </div>
                    <div class="modal-body text-center p-4">
                        <input type="hidden" id="workflowModalWarehouseId" value="{{ $selectedPosWarehouseId }}">
                        <p class="lead mb-4">
                            <strong id="workflowModalWarehouseName">{{ $selectedPosWarehouse->name ?? ($lims_warehouse_data->name ?? '') }}</strong>
                            <br>
                            <span class="text-muted small">{{ __('db.supports_both_retail_and_restaurant') }}</span>
                        </p>
                        <div class="row justify-content-center">
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-primary btn-block py-3 d-flex flex-column align-items-center justify-content-center shadow-sm" onclick="selectInitialWorkflow('retail')">
                                    <i class="ti ti-shopping-cart mb-2" style="font-size: 28px;"></i>
                                    <span class="font-weight-bold">{{ __('db.Retail POS') }}</span>
                                </button>
                            </div>
                            @if(is_dir(base_path('Modules/Restaurant')))
                            <div class="col-6">
                                <button type="button" class="btn btn-primary btn-block py-3 d-flex flex-column align-items-center justify-content-center shadow-sm" onclick="selectInitialWorkflow('restaurant')">
                                    <i class="ti ti-cup mb-2" style="font-size: 28px;"></i>
                                    <span class="font-weight-bold">{{ __('db.Restaurant POS') }}</span>
                                </button>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ✅ Instalment Plan Modal -->
        <div class="modal fade" id="installmentPlanModal" tabindex="-1" aria-labelledby="installmentPlanModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Instalment Plan</h5>
                        <button type="button" id="close-installment-modal-x" data-dismiss="modal" aria-label="Close"
                            class="close"><span aria-hidden="true">&times;</span></button>
                    </div>

                    <div class="modal-body">
                        <input type="checkbox" class="form-check-input" id="enable_installment" name="enable_installment"
                            style="display:none;">
                        <div id="installmentFields">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">{{__('db.Plan Name')}}</label>
                                        <input type="text" class="form-control" name="installment_plan[name]"
                                            value="12 Months" placeholder="e.g., 6 Month Plan">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">{{__('db.Price')}}</label>
                                        <input type="number" step="0.01" class="form-control" name="installment_plan[price]"
                                            id="installment_price" readonly>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">{{__('db.Additional Amount')}}</label>
                                        <input id="additional_amount" type="number" step="0.01" class="form-control"
                                            name="installment_plan[additional_amount]" value="0">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">{{__('db.Total Amount')}}</label>
                                        <input type="number" step="0.01" class="form-control"
                                            name="installment_plan[total_amount]" id="installment_total" readonly>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">{{__('db.Down Payment')}}</label>
                                        <input type="number" step="0.01" class="form-control" id="down_payment_id"
                                            name="installment_plan[down_payment]" value="0">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">{{__('db.Months')}}</label>
                                        <input type="number" step="1" class="form-control" name="installment_plan[months]"
                                            id="installment_months" min="1" value="12">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div id="paymentFields" style="display: none;">
                                        <div class="mb-3">
                                            <label class="form-label">{{__('db.Payment Method')}}</label>
                                            <select name="installment_plan[paid_by_id]" class="form-control selectpicker">
                                                @if(in_array("cash", $options))
                                                    <option value="1">{{ __('db.Cash') }}</option>
                                                @endif
                                                @if(in_array("gift_card", $options))
                                                    <option value="2">{{ __('db.Gift Card') }}</option>
                                                @endif
                                                @if(in_array("card", $options))
                                                    <option value="3">{{ __('db.Credit Card') }}</option>
                                                @endif
                                                @if(in_array("cheque", $options))
                                                    <option value="4">{{ __('db.Cheque') }}</option>
                                                @endif
                                                @if(in_array("deposit", $options))
                                                    <option value="6">{{ __('db.Deposit') }}</option>
                                                @endif
                                                @if($lims_reward_point_setting_data && $lims_reward_point_setting_data->is_active)
                                                    <option value="7">{{ __('db.Points') }}</option>
                                                @endif
                                                @foreach($options as $option)
                                                    @if($option !== 'cash' && $option !== 'card' && $option !== 'cheque' && $option !== 'gift_card' && $option !== 'deposit' && $option !== 'paypal' && $option !== 'pesapal')
                                                        <option value="{{$option}}">{{ucfirst($option)}}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">{{__('db.Account')}}</label>
                                            <select name="installment_plan[account_id]" class="form-control selectpicker">
                                                @foreach($lims_account_list as $account)
                                                    <option value="{{$account->id}}">{{$account->name}}
                                                        [{{$account->account_no}}]</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">{{__('db.Payment Note')}}</label>
                                            <textarea name="installment_plan[payment_note]" class="form-control"></textarea>
                                        </div>
                                    </div>
                                    <div id="schedulePreview" class="mt-2">
                                        <h6>Schedule Preview</h6>
                                        <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                                            <table class="table table-sm table-bordered" id="installmentScheduleTable">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>{{__('db.date')}}</th>
                                                        <th>{{__('db.Amount')}}</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="installment_plan[reference_type]" value="sale">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="close-installment-modal"
                            data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="done-installment-modal">Create Instalment</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section id="print-layout" class="">
    </section>
    <div style="width:100%;max-width:350px;position:fixed;top:5%;left:50%;transform:translateX(-50%);z-index:999">
        <button type="button" class="btn btn-danger" id="closeScannerBtn" style="display:none"><svg
                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg></button>
        <div id="reader" style="width:100%;"></div>
    </div>

@endsection

<!-- Variant Selection Modal -->
<div id="visual-table-modal" tabindex="-1" role="dialog" aria-labelledby="visualTableModalLabel" aria-hidden="true" class="modal fade text-left">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="visualTableModalLabel">Select Table</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <!-- Floor Tabs -->
                        <ul class="nav nav-tabs" id="floor-tabs" role="tablist">
                            <!-- Populated dynamically -->
                        </ul>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-end mb-2">
                            <span class="badge badge-success mr-2 p-2">Available</span>
                            <span class="badge badge-danger p-2">Occupied</span>
                        </div>
                        <div class="tab-content" id="floor-tabs-content">
                            <!-- Populated dynamically -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="variantSelectionModal" tabindex="-1" role="dialog" aria-labelledby="variantSelectionModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="variantSelectionModalLabel" class="modal-title">{{trans('file.Choose a Variant') == 'file.Choose a Variant' ? 'Choose a Variant' : trans('file.Choose a Variant')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <h5 id="variant-modal-product-name" class="font-weight-bold mb-1"></h5>
                <p class="text-muted small mb-3">{{trans('file.Select one option below to add it to the cart.') == 'file.Select one option below to add it to the cart.' ? 'Select one option below to add it to the cart.' : trans('file.Select one option below to add it to the cart.')}}</p>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>{{trans('file.Variant') == 'file.Variant' ? 'Variant' : trans('file.Variant')}}</th>
                                <th>{{__('db.Code')}}</th>
                                <th>{{trans('file.Stock') == 'file.Stock' ? 'Stock' : trans('file.Stock')}}</th>
                                <th>{{__('db.Price')}}</th>
                                <th class="text-right">{{trans('file.Action') == 'file.Action' ? 'Action' : trans('file.Action')}}</th>
                            </tr>
                        </thead>
                        <tbody id="variant-modal-table-body">
                            <!-- Variant rows injected here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>
    <script>
        const input = document.querySelector("#wa_number");
        if (input) {
            document.addEventListener('DOMContentLoaded', function() {
                window.intlTelInput(input, {
                    initialCountry: "auto",
                    geoIpLookup: function (callback) {
                        fetch("https://ipapi.co/json")
                            .then((res) => res.json())
                            .then((data) => callback(data.country_code))
                            .catch(() => callback("us"));
                    },
                    utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js"
                });
            });
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/mousetrap@1.6.5/mousetrap.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>

    <audio id="audio" src="{{url('beep/beep-07.mp3')}}"></audio>
    <script>
        function playSound() {
            @if($lims_pos_setting_data->play_sound)
                var sound = document.getElementById("audio");
                sound.play();
            @else
                return;
            @endif
        }

        @if($lims_pos_setting_data)
            var public_key = <?php    echo json_encode($lims_pos_setting_data->stripe_public_key) ?>;
        @endif
            var without_stock = <?php echo json_encode(gen_setting()->without_stock) ?>;
        var alert_product = <?php echo json_encode($alert_product ?? 0) ?>;
        /* Active invoice layout size from system setting: 'A4', '58mm', '80mm' */
        var POS_INVOICE_SIZE = <?php
    $activeInvoice = \App\Models\InvoiceSetting::active_setting();
    echo json_encode($activeInvoice && $activeInvoice->size ? strtolower(trim($activeInvoice->size)) : 'a4');
            ?>;
        var currency = <?php echo json_encode($currency) ?>;
        var valid;
        var authUser = <?php echo json_encode($authUser) ?>;
        // array data depend on warehouse
        var lims_product_array = [];
        var product_code = [];
        var product_name = [];
        var product_qty = [];
        var product_type = [];
        var product_id = [];
        var product_list = [];
        var qty_list = [];

        // array data with selection
        var product_price = [];
        var wholesale_price = [];
        var cost = [];
        var product_discount = [];
        var tax_rate = [];
        var tax_name = [];
        var tax_method = [];
        var unit_name = [];
        var unit_operator = [];
        var unit_operation_value = [];
        var is_imei = [];
        var is_variant = [];
        var gift_card_amount = [];
        var gift_card_expense = [];

        // temporary array
        var temp_unit_name = [];
        var temp_unit_operator = [];
        var temp_unit_operation_value = [];

        var reward_point_setting = <?php echo json_encode($lims_reward_point_setting_data ?? []) ?>;
        if (reward_point_setting === null) reward_point_setting = {};

        @if($lims_pos_setting_data)
            var product_row_number = <?php    echo json_encode($lims_pos_setting_data->product_number) ?>;
        @endif
            var rowindex;
        var customer_group_rate;
        var row_product_price;
        var pos;
        var keyboard_active = <?php echo json_encode($keybord_active); ?>;
        
        var DEFAULT_CUSTOMER_ID = null;
        var DEFAULT_WAREHOUSE_ID = null;
        var DEFAULT_BILLER_ID = null;
        var DEFAULT_SERVICE_ID = null;
        var DEFAULT_WAITER_ID = null;
        var DEFAULT_TABLE_ID = null;
        var ismultiplepayment = 0;

        $(document).ready(function() {
            DEFAULT_CUSTOMER_ID = $('#customer_id').val();
            DEFAULT_WAREHOUSE_ID = $('#warehouse_id').val();
            DEFAULT_BILLER_ID = $('#biller_id').val();
            @if($isRestaurantWarehouse)
                DEFAULT_SERVICE_ID = $('#service_id').val();
                DEFAULT_WAITER_ID = $('#waiter_id').val();
                DEFAULT_TABLE_ID = $('#table_id').val();
            @endif

            @if(!empty($showWorkflowModal) && $showWorkflowModal)
                $('#posWorkflowChoiceModal').modal('show');
            @endif
        });
        var role_id = <?php echo json_encode(\Auth::user()->role_id) ?>;
        var warehouse_id = $('#warehouse_id').val();

        function posUrlForWarehouse(warehouseId, targetWorkflow) {
            const baseUrl = @json(url('/pos'));
            var currentUrl = new URL(window.location.href);
            var searchParams = new URLSearchParams(currentUrl.search);

            if (warehouseId) {
                searchParams.set('warehouse_id', warehouseId);
            }
            if (targetWorkflow) {
                searchParams.set('workflow', targetWorkflow);
            }

            var queryString = searchParams.toString();
            return baseUrl + (queryString ? '?' + queryString : '');
        }

        function openWorkflowChoiceModal(warehouseId, warehouseName) {
            $('#workflowModalWarehouseId').val(warehouseId);
            if (warehouseName) {
                $('#workflowModalWarehouseName').text(warehouseName);
            }
            $('#posWorkflowChoiceModal').modal('show');
        }

        function selectInitialWorkflow(targetWorkflow) {
            var warehouseId = $('#workflowModalWarehouseId').val() || $('#warehouse_id').val() || @json($selectedPosWarehouseId);
            window.location.href = posUrlForWarehouse(warehouseId, targetWorkflow);
        }

        function switchPosWorkflow(targetWorkflow) {
            var currentWorkflow = @json($activeWorkflow);
            if (currentWorkflow === targetWorkflow) {
                return;
            }
            var hasItems = ($('table.order-list tbody tr').length > 0);
            if (hasItems) {
                var promptName = targetWorkflow === 'restaurant' ? 'Restaurant' : 'Retail';
                var confirmMsg = "Switch to " + promptName + " POS?\n\nYour current cart and temporary POS data will be cleared because Retail and Restaurant orders use different workflows.";
                if (!confirm(confirmMsg)) {
                    return;
                }
            }
            var warehouseId = $('#warehouse_id').val() || @json($selectedPosWarehouseId);
            window.location.href = posUrlForWarehouse(warehouseId, targetWorkflow);
        }
        var coupon_list = <?php echo json_encode($lims_coupon_list) ?>;
        var currencyChange = false;
        var all_permission = <?php echo json_encode($all_permission) ?>;
        var next_page_url;
        window.isAppOnline = navigator.onLine;
        var lims_customer_list = <?php echo json_encode($lims_customer_list) ?>;

        const doneTypingInterval = 200;
        const $input = $('#product-search-input');
        const $results = $('#product-results-container');
        const $noResults = $('#no-results-message');

        function clearResults() {
            $results.empty().css('padding', '0');
            $noResults.hide();
        }

        ///start code for mobile////
        var isMobile = false;
        if (($(window).width() < 767)) {
            isMobile = true;
        }

        function renderProductCard(data, index) {
            let rawImg = (data['image'] && data['image'][index]) ? data['image'][index].toString().trim() : '';
            let image = (rawImg !== '' && rawImg !== 'null' && rawImg !== 'undefined') ? rawImg : 'zummXD2dvAtI.png';
            let type = data['type'] ? data['type'][index] : '';
            
            if (type === 'variant_parent') {
                let variantsStr = data['variants'] && data['variants'][index] ? data['variants'][index].replace(/'/g, "&#39;") : '[]';
                return '<div class="product-img variant-parent-btn" title="' + data['name'][index] + '" data-variants=\'' + variantsStr + '\'><img src="{{url("/images/product")}}/' + image + '" width="100%" /><p>' + data['name'][index] + '</p> <span class="d-block text-muted">' + data['qty'][index] + '</span></div>';
            } else {
                return '<div class="product-img sound-btn" title="' + data['name'][index] + '" data-code="' + data['code'][index] + '" data-qty="' + data['qty'][index] + '" data-imei="' + data['is_imei'][index] + '" data-embedded="' + data['is_embeded'][index] + '" data-batch="' + data['batch'][index] + '" data-price="' + data['price'][index] + '" data-type="' + type + '"><img src="{{url("/images/product")}}/' + image + '" width="100%" /><p>' + data['name'][index] + '</p><span>[' + data['code'][index] + ']</span> <span class="d-block">Qty: ' + data['qty'][index] + '</span></div>';
            }
        }

        function populateProduct(response) {
            if (!response || !response.data || !response.data['name']) {
                $(".table-container").html('<p class="text-center text-muted mt-3">No products found or session expired.</p>');
                return;
            }
            var tableData = '<div class="product-grid">';
            $.each(response.data['name'], function (index) {
                tableData += renderProductCard(response.data, index);
            });

            tableData += '</div>';

            next_page_url = response.next_page_url;

            $(".table-container").html(tableData);

            if (isMobile) {
                $('.brand').hide();
                $('.category').hide();
                $('.products-m').show();
                $(".product_list_mobile.table-container").show();
            } else {
                $(".table-container").show();
            }
        }

        $(document).ready(function () {

            $('#product-search-input').focus();

            //Get featured product data on right section
            $.get('{{ url("sales/getproducts") }}/' + warehouse_id + '/featured/1', function (response) {
                populateProduct(response);
            });

            let typingTimer;

            var click = 0;

            function searchProducts(search) {
                $results.css('padding', '0 10px 15px');
                $results.html('<div class="loader " title="4" style="border:none;min-height:300px"><svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="24px" height="30px" viewBox="0 0 24 30" style="enable-background:new 0 0 50 50;" xml:space="preserve"><rect x="0" y="0" width="4" height="10" fill="#333"><animateTransform attributeType="xml" attributeName="transform" type="translate" values="0 0; 0 20; 0 0" begin="0" dur="0.6s" repeatCount="indefinite"></animateTransform></rect><rect x="10" y="0" width="4" height="10" fill="#333"><animateTransform attributeType="xml" attributeName="transform" type="translate" values="0 0; 0 20; 0 0" begin="0.2s" dur="0.6s" repeatCount="indefinite"></animateTransform></rect><rect x="20" y="0" width="4" height="10" fill="#333"><animateTransform attributeType="xml" attributeName="transform" type="translate" values="0 0; 0 20; 0 0" begin="0.4s" dur="0.6s" repeatCount="indefinite"></animateTransform></rect></svg></div>');
                $noResults.hide();

                $.ajax({
                    url: '{{ url("/sales/search") }}',
                    type: 'GET',
                    data: {
                        warehouse_id: warehouse_id,
                        search: search
                    },
                    success: function (data) {
                        $results.empty();
                        if (data.length > 0) {
                            $noResults.hide();
                            data.forEach(function (product) {
                                let productHtml = '';
                                let displayStock = '';

                                if (authUser > 2) {
                                    displayStock = '';
                                } else {
                                    displayStock = ` | ${(product.warehouse_qty ?? product.qty)} {{ __('db.In Stock') }} `;
                                }

                                var batch_id = product.product_batch_id ? product.product_batch_id : '';

                                if (product.is_imei == '1' || product.is_imei === 1 || product.is_imei === true) {

                                    // Check if IMEI already exists in the selected products
                                    let imeiNumbersArray = [];
                                    let exists = false;
                                    $('.imei-number').each(function () {
                                        let val = $(this).val();
                                        imeiNumbersArray = val.split(",");
                                        if (imeiNumbersArray.includes(product.imei_number)) {
                                            exists = true;
                                            return;
                                        }
                                    });

                                    if ((exists == false) && (product.imei_number !== null && $.trim(product.imei_number) !== '')) {
                                        productHtml = `
                                                <div class="product-img" data-code="${product.code}"
                                                                        data-qty="${product.qty}"
                                                                        data-warehouse-qty="${product.warehouse_qty ?? product.qty}"
                                                                        data-imei="${product.imei_number}"
                                                                        data-embedded="${product.is_embeded}"
                                                                        data-batch="${batch_id}"
                                                                        data-price="${product.price}"
                                                                        data-type="${product.type}">
                                                    ${product.name} (${product.code}) | ${product.price} | IMEI: ${product.imei_number}
                                                </div>
                                            `;
                                    } else {
                                        $noResults.show();
                                    }
                                } else if (product.product_batch_id != null) {
                                    let expired = '';
                                    if (parseInt(product.qty) > 0 || without_stock == 'yes') {
                                        if (product.expired_date == 0) {
                                            product.expired_date = "{{__('db.expired')}}";
                                            expired = "expired";
                                        }
                                        productHtml = `
                                                <div class="product-img ${expired}" data-code="${product.code}"
                                                                                    data-qty="${product.qty}"
                                                                                    data-warehouse-qty="${product.warehouse_qty ?? product.qty}"
                                                                                    data-imei="${product.is_imei}"
                                                                                    data-embedded="${product.is_embeded}"
                                                                                    data-batch="${batch_id}"
                                                                                    data-price="${product.price}"
                                                                                    data-type="${product.type}">
                                                    ${product.name} (${product.code}) - ${product.expired_date} | ${product.price} ${displayStock}
                                                </div>
                                            `;
                                    } else {
                                        $noResults.show();
                                    }
                                } else if (product.type == 'service' || product.type == 'digital') {
                                    productHtml = `
                                            <div class="product-img" data-code="${product.code}"
                                                                                data-qty="N/A"
                                                                                data-warehouse-qty="${product.warehouse_qty ?? product.qty}"
                                                                                data-imei="${product.is_imei}"
                                                                                data-embedded="${product.is_embeded}"
                                                                                data-batch="${batch_id}"
                                                                                data-price="${product.price}"
                                                                                data-type="${product.type}">
                                                ${product.name} (${product.code}) | ${product.price} ${displayStock}
                                            </div>
                                        `;
                                } else if (product.type == 'combo') {
                                    productHtml = `
                                            <div class="product-img" data-code="${product.code}"
                                                                    data-qty="${product.qty}"
                                                                    data-warehouse-qty="${product.warehouse_qty ?? product.qty}"
                                                                    data-imei="${product.is_imei}"
                                                                    data-embedded="${product.is_embeded}"
                                                                    data-batch="${batch_id}"
                                                                    data-price="${product.price}"
                                                                    data-type="combo">
                                                ${product.name} (${product.code}) | ${product.price} ${displayStock}
                                            </div>
                                        `;
                                } else {
                                    if (parseInt(product.qty) > 0 || without_stock == 'yes') {
                                        productHtml = `
                                                <div class="product-img" data-code="${product.code}"
                                                                        data-qty="${product.qty}"
                                                                        data-warehouse-qty="${product.warehouse_qty ?? product.qty}"
                                                                        data-imei="${product.is_imei}"
                                                                        data-embedded="${product.is_embeded}"
                                                                        data-batch="${batch_id}"
                                                                        data-price="${product.price}"
                                                                        data-type="${product.type}">
                                                    ${product.name} (${product.code}) | ${product.price} ${displayStock}
                                                </div>
                                            `;
                                    }
                                }

                                $results.append(productHtml);
                            });

                            $results.off('click', '.product-img').on('click', '.product-img', function () {
                                clearResults();
                            });

                            // Auto-click if only one result
                            if (data.length === 1) {

                                //let product = data.name; // ✅ define it properly

                                if (click === 0) {
                                    $('#product-results-container .product-img').first().trigger('click');
                                }

                                clearResults();
                                click = 1;
                            }

                        } else {
                            clearResults();
                            $noResults.show();
                        }
                    },
                    error: function () {
                        $noResults.text("Error searching products.").show();
                    }
                });
            }

            // Trigger on input
            $input.on('input', function () {
                const value = $(this).val().trim();
                if (value.length >= 3) {
                    click = 0;
                    clearTimeout(typingTimer);
                    typingTimer = setTimeout(() => searchProducts(value), doneTypingInterval);
                } else {
                    clearResults();
                }
            });

            // Trigger on paste
            $input.on('paste', function (e) {
                const pastedData = (e.originalEvent || e).clipboardData.getData('text');
                if (pastedData.length >= 3) {
                    click = 0;
                    searchProducts(pastedData.trim());
                }
            });

            $(document).on('click', function (e) {
                if (!$(e.target).closest('#product-results-container, #product-search-input').length) {
                    clearResults();
                }
            });

            // ✅ Show modal and calculate base totals
            $('#installmentPlanBtn').on('click', function () {
                let baseTotal = parseFloat($('input[name="grand_total"]').val()) || 0;
                let additionalAmount = parseFloat($('#additional_amount').val()) || 0;
                let installment_total_price = baseTotal + additionalAmount;

                $('#installment_price').val(baseTotal.toFixed(2));
                $('#installment_total').val(installment_total_price.toFixed(2));

                updateSchedulePreview();
                $('#installmentPlanModal').modal('show');
            });

            function updateSchedulePreview() {
                let total = parseFloat($('#installment_total').val()) || 0;
                let downPayment = parseFloat($('#down_payment_id').val()) || 0;
                
                if (downPayment > total) {
                    downPayment = total;
                    $('#down_payment_id').val(total.toFixed(2));
                }

                let months = parseInt($('#installment_months').val()) || 1;
                let remaining = total - downPayment;
                let installmentAmount = (months > 0) ? (remaining / months).toFixed(2) : 0;

                let tbody = $('#installmentScheduleTable tbody');
                tbody.empty();

                let date = new Date();
                for (let i = 1; i <= months; i++) {
                    let nextDate = new Date(date);
                    let expectedMonth = (date.getMonth() + i) % 12;
                    nextDate.setMonth(date.getMonth() + i);
                    if (nextDate.getMonth() !== expectedMonth) {
                        nextDate.setDate(0);
                    }
                    let dateString = nextDate.toISOString().split('T')[0];
                    tbody.append(`<tr><td>${i}</td><td>${dateString}</td><td>${installmentAmount}</td></tr>`);
                }

                if (downPayment > 0) {
                    $('#paymentFields').slideDown();
                    $('#done-installment-modal').text('Create Instalment & Pay');
                } else {
                    $('#paymentFields').slideUp();
                    $('#done-installment-modal').text('Create Instalment');
                }
            }

            $('#additional_amount, #down_payment_id, #installment_months').on('input', function () {
                let baseTotal = parseFloat($('#installment_price').val()) || 0;
                let additional_amount = parseFloat($('#additional_amount').val()) || 0;
                let installment_total_price = baseTotal + additional_amount;

                $('#installment_total').val(installment_total_price.toFixed(2));
                updateSchedulePreview();
            });

            // ✅ When Close button clicked
            $('#close-installment-modal, #close-installment-modal-x').on('click', function () {
                $('#enable_installment').prop('checked', false);
                $('#installmentPlanModal').modal('hide');
            });

            // ✅ Reset modal fields every time it's closed
            $('#installmentPlanModal').on('hidden.bs.modal', function () {
                if (!$('#enable_installment').prop('checked')) {
                    $(this).find('input[name="installment_plan[name]"]').val('12 Months');
                    $(this).find('input[name="installment_plan[additional_amount]"]').val('0');
                    $(this).find('input[name="installment_plan[down_payment]"]').val('0');
                    $(this).find('input[name="installment_plan[months]"]').val('12');
                    $(this).find('textarea[name="installment_plan[payment_note]"]').val('');
                    $(this).find('select[name="installment_plan[paid_by_id]"]').val('1').trigger('change');
                    $('#paymentFields').hide();
                    $('#done-installment-modal').text('Create Instalment');
                    $('#installmentScheduleTable tbody').empty();
                }
            });

            // ✅ When Done button clicked
            $('#done-installment-modal').on('click', function () {
                let months = parseInt($('#installment_months').val()) || 0;
                if (months < 1) {
                    alert('Please enter valid number of months.');
                    return;
                }

                let total = parseFloat($('#installment_total').val()) || 0;
                let downPayment = parseFloat($('#down_payment_id').val()) || 0;
                if (downPayment > total) {
                    alert('Down payment cannot exceed total amount.');
                    return;
                }

                let dp = {{ gen_setting()->decimal ?? 2 }};
                $('input[name="grand_total"]').val(total.toFixed(dp));
                $('input[name="total_price"]').val(total.toFixed(dp));
                $('#grand-total').text(total.toFixed(dp));
                $('#grand-total-m').text(total.toFixed(dp));

                $('#enable_installment').prop('checked', true);

                // Use one-time hidden.bs.modal listener to safely sequence modal transitions
                $('#installmentPlanModal').one('hidden.bs.modal', function () {
                    if (!$('#enable_installment').prop('checked')) {
                        return;
                    }

                    if (downPayment <= 0) {
                        // Case A: Down payment = 0 -> skip payment modal and trigger canonical submit
                        $('.paid_amount').val((0).toFixed(dp));
                        $('.paying_amount').val((0).toFixed(dp));
                        $('.total_paying').text((0).toFixed(dp));
                        $('.total_payable').text(total.toFixed(dp));
                        $('.due').text(total.toFixed(dp));
                        $('.change').text((0).toFixed(dp));

                        $("#submit-btn").trigger('click');
                    } else {
                        // Case B: Down payment > 0 -> open canonical payment modal initialized to down payment
                        appendRemoveElement('cash');
                        $('#add-payment').modal('show');
                    }
                });

                $('#installmentPlanModal').modal('hide');
            });

        });
    </script>
    <script>
        const closeScannerBtn = document.getElementById("closeScannerBtn");
        const scanner = document.getElementById("reader");
        const html5Qrcode = new Html5Qrcode('reader');

        function barcode() {
            const qrCodeSuccessCallback = (decodedText, decodedResult) => {
                if (decodedText) {
                    document.getElementById('lims_productcodeSearch').value = decodedText;
                    html5Qrcode.stop();
                    closeScannerBtn.style.display = "none";
                }
            };

            const config = {
                fps: 30,
                qrbox: { width: 300, height: 100 },
                // ðŸ‘‡ Add this line to support Code128
                // formatsToSupport: [ Html5QrcodeSupportedFormats.CODE_128 ]
            };

            html5Qrcode.start({ facingMode: "environment" }, config, qrCodeSuccessCallback);
            closeScannerBtn.style.display = "inline-block";
        }

        closeScannerBtn.addEventListener("click", function () {
            closeScannerBtn.style.display = "none";
            html5Qrcode.stop();
        });
    </script>

    <script>
        var isEditMode = {{ isset($lims_sale_data) ? 1 : 0 }};

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        ////Start the code is for SaleproSaas///
        @if(config('database.connections.saleprosaas_landlord'))
            numberOfInvoice = <?php echo json_encode($numberOfInvoice) ?>;
            $.ajax({
                type: 'GET',
                async: false,
                url: '{{route("package.fetchData", gen_setting()->package_id)}}',
                success: function (data) {
                    if (data['number_of_invoice'] > 0 && data['number_of_invoice'] <= numberOfInvoice) {
                        location.href = "{{route('sales.index')}}";
                    }
                }
            });
        @endif
        ////End the code is for SaleproSaas///

        ///NOT NEEDED - Check///
        $("ul#sale").siblings('a').attr('aria-expanded', 'true');
        $("ul#sale").addClass("show");
        $("ul#sale #sale-pos-menu").addClass("active");
        ///NOT NEEDED - Check///

        if (isMobile == true) {
            $('.loading-message').hide();
            $('.table-container').hide();
            $('#collapseProducts').addClass('collapse');
            $('#grand-total-m').html($('input[name="grand_total"]').val());
        }

        $(window).on('load', async function () {
            //await getProduct(warehouse_id);

            var customer_id = $('#customer_id').val();
            var cus_gr_rt = await $.get('{{ url("sales/getcustomergroup") }}/' + customer_id);
            customer_group_rate = (cus_gr_rt / 100);

            @if($lims_pos_setting_data && $lims_pos_setting_data->cash_register)
                isCashRegisterAvailable(warehouse_id);
            @endif


            //Get recents sale when clicking recent transaction button
            $.get('{{url("sales/recent-sale")}}', function (data) {
                populateRecentSale(data);
            });
            //Get recents draft when clicking recent transaction button
            $.get('{{url("/sales/recent-draft")}}', function (data) {
                populateRecentDraft(data);
            });

            if (isEditMode) {
                await processDraftData();
            }

            saveDataToLocalStorageForCustomerDisplay('clear_no');

        })

        ///category button
        $('#category-filter').on('click', function (e) {
            e.stopPropagation();
            $('.filter-window').show('slide', { direction: 'right' }, 'fast');
            $('.category').show();
            $('.brand').hide();
            $('.products-m').hide();
            $(".table-container").removeClass('brand').removeClass('featured').addClass('category');
        });

        //click on category image on the filter window shown after clicking the category button
        $(document).on('click', '.category-img', function () {
            let category_id = $(this).data('category');
            $('.filter-window').hide('slide', { direction: 'right' }, 'fast');
            $(".table-container").html('<div class="product-grid skeleton-grid"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>');
            if (!navigator.onLine) {
                window.filterOfflineProducts('category_id', category_id);
            } else {
                $.get('{{ url("sales/getproducts") }}/' + warehouse_id + '/category/' + category_id, function (response) {
                    populateProduct(response);
                });
            }

            if (isMobile == true) {
                $('.filter-window').show('slide', { direction: 'right' }, 'fast');
            }
        });

        ///brand button
        $('#brand-filter').on('click', function (e) {
            e.stopPropagation();
            $('.filter-window').show('slide', { direction: 'right' }, 'fast');
            $('.brand').show();
            $('.category').hide();
            $('.products-m').hide();
            $(".table-container").removeClass('category').removeClass('featured').addClass('brand');
        });

        //click on brand image on the filter window shown after clicking the brand button
        $(document).on('click', '.brand-img', function () {
            var brand_id = $(this).data('brand');
            $('.filter-window').hide('slide', { direction: 'right' }, 'fast');
            $(".table-container").html('<div class="product-grid skeleton-grid"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>');
            if (!navigator.onLine) {
                window.filterOfflineProducts('brand_id', brand_id);
            } else {
                $.get('{{ url("sales/getproducts") }}/' + warehouse_id + '/brand/' + brand_id, function (response) {
                    populateProduct(response);
                });
            }

            if (isMobile == true) {
                $('.filter-window').show('slide', { direction: 'right' }, 'fast');
            }
        });

        ///featured button
        $('#featured-filter').on('click', function (e) {
            $(".table-container").removeClass('category').removeClass('brand').addClass('featured');
            $(".table-container").html('<div class="product-grid skeleton-grid"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>');

            if (!navigator.onLine) {
                window.filterOfflineProducts('featured', 1);
            } else {
                $.get('{{ url("sales/getproducts") }}/' + warehouse_id + '/featured/1', function (response) {
                    populateProduct(response);
                });
            }

            if (isMobile == true) {
                e.stopPropagation();
                $(".product_list_mobile.table-container").show();
                $('.product_list_mobile').html('');
                let featured_products = $(".table-container .product-grid").clone();
                $('.product_list_mobile').html(featured_products);
                $('.filter-window').show('slide', { direction: 'right' }, 'fast');
                $('.brand').hide();
                $('.category').hide();
            }
        });

        //close button on filter-window
        $(document).on('click', '.btn-close', function (e) {
            $('.filter-window').hide('slide', { direction: 'right' }, 'fast');
            $(".table-container").removeClass('category').removeClass('brand').removeClass('featured');
            if (isMobile == true) {
                $(".table-container").hide();
            }
        });

        /// Start Load more button function///
        $(document).on('click', '.load-more', function () {
            if (!navigator.onLine) {
                if (typeof window.loadMoreOfflineProducts === 'function') {
                    window.loadMoreOfflineProducts();
                }
                return;
            }
            $.ajax({
                url: next_page_url,
                type: "get",
            }).done(function (response) {
                appendProduct(response);
            });
        });

        $('#warehouse_id').on('change', function () {
            const selectedWarehouseId = $(this).val();

            if (!selectedWarehouseId) {
                return;
            }

            const posType = $(this).find(':selected').data('pos-type') || 'regular';
            const selectedWarehouseName = $(this).find(':selected').text().trim();
            const hasItems = ($('table.order-list tbody tr').length > 0);

            if (posType === 'regular') {
                if (hasItems) {
                    if (!confirm("Changing warehouse will clear your current cart. Do you want to proceed?")) {
                        $(this).val(@json($selectedPosWarehouseId)).selectpicker('refresh');
                        return;
                    }
                }
                window.location.href = posUrlForWarehouse(selectedWarehouseId, 'retail');
            } else if (posType === 'restaurant') {
                if (hasItems) {
                    if (!confirm("Changing warehouse will clear your current cart. Do you want to proceed?")) {
                        $(this).val(@json($selectedPosWarehouseId)).selectpicker('refresh');
                        return;
                    }
                }
                window.location.href = posUrlForWarehouse(selectedWarehouseId, 'restaurant');
            } else if (posType === 'both') {
                if (hasItems) {
                    if (!confirm("Changing warehouse will clear your current cart. Do you want to proceed?")) {
                        $(this).val(@json($selectedPosWarehouseId)).selectpicker('refresh');
                        return;
                    }
                }
                @if(is_dir(base_path('Modules/Restaurant')))
                    openWorkflowChoiceModal(selectedWarehouseId, selectedWarehouseName);
                @else
                    window.location.href = posUrlForWarehouse(selectedWarehouseId, 'retail');
                @endif
            }
        });

        $('#customer_id').on('change', function () {
            var customer_id = $(this).val();
            $.get('{{url("sales/getcustomergroup")}}/' + customer_id, function (data) {
                customer_group_rate = (data / 100);
            });

            var customer_type = $(this).find(':selected').data('type');
            if (customer_type == 'walkin') {
                $('#credit-sale-btn').attr('disabled', true);
                $('#installmentPlanBtn').attr('disabled', true);
            } else {
                if ($('table.order-list tbody tr').length > 0) {
                    $('#credit-sale-btn').removeAttr('disabled');
                    $('#installmentPlanBtn').removeAttr('disabled');
                }
            }
        });

        @if($lims_pos_setting_data && $lims_pos_setting_data->cash_register)
            function isCashRegisterAvailable(warehouse_id) {
                $("#register-details-btn").addClass('d-none').removeData('id');
                $.ajax({
                    url: '{{url("cash-register/check-availability")}}/' + warehouse_id,
                    type: "GET",
                    success: function (data) {
                        if (!data) {
                            //$("#pos-layout").addClass('d-none');
                            $("#register-details-btn").addClass('d-none');
                            $('#cash-register-modal select[name=warehouse_id]').val(warehouse_id);

                            if (role_id <= 2)
                                $("#cash-register-modal .warehouse-section").removeClass('d-none');
                            else
                                $("#cash-register-modal .warehouse-section").addClass('d-none');

                            $('#cash-register-modal').modal({
                                backdrop: 'static',
                                keyboard: false // Optional: Prevents closing with the Escape key as well
                            });

                            $('.selectpicker').selectpicker('refresh');
                            $("#cash-register-modal").modal('show');
                        }
                        else {
                            $("#register-details-btn").removeClass('d-none');
                            $("#register-details-btn").data('id', data);
                            $('input[name="cash_register"]').val(data);
                        }
                    },
                    error: function () {
                        $("#register-details-btn").addClass('d-none').removeData('id');
                    }
                });
            }
        @endif

        let isLoadingProducts = false;

        function loadMoreProducts(context) {
            // stop if already loading or no next page
            if (isLoadingProducts || !next_page_url) {
                return;
            }

            let scrollTop, clientHeight, scrollHeight;

            if (context === window) {
                scrollTop = $(window).scrollTop();
                clientHeight = $(window).height();
                scrollHeight = $(document).height();
            } else {
                scrollTop = context.scrollTop || $(context).scrollTop();
                clientHeight = context.clientHeight || $(context).innerHeight();
                scrollHeight = context.scrollHeight || $(context)[0].scrollHeight;
            }

            // trigger before reaching bottom
            if (scrollTop + clientHeight >= scrollHeight - 200) {

                isLoadingProducts = true;

                // show loader
                if (!$('.scroll-loader').length) {
                    $('.table-container').append(`
                            <div class="scroll-loader text-center py-2">
                                <svg version="1.1" width="24px" height="30px" viewBox="0 0 24 30">
                                    <rect x="0" y="0" width="4" height="10" fill="#333">
                                        <animateTransform attributeType="xml"
                                            attributeName="transform"
                                            type="translate"
                                            values="0 0; 0 20; 0 0"
                                            begin="0"
                                            dur="0.6s"
                                            repeatCount="indefinite">
                                        </animateTransform>
                                    </rect>
                                    <rect x="10" y="0" width="4" height="10" fill="#333">
                                        <animateTransform attributeType="xml"
                                            attributeName="transform"
                                            type="translate"
                                            values="0 0; 0 20; 0 0"
                                            begin="0.2s"
                                            dur="0.6s"
                                            repeatCount="indefinite">
                                        </animateTransform>
                                    </rect>
                                    <rect x="20" y="0" width="4" height="10" fill="#333">
                                        <animateTransform attributeType="xml"
                                            attributeName="transform"
                                            type="translate"
                                            values="0 0; 0 20; 0 0"
                                            begin="0.4s"
                                            dur="0.6s"
                                            repeatCount="indefinite">
                                        </animateTransform>
                                    </rect>
                                </svg>
                            </div>
                        `);
                }

                if (window.isAppOnline === false) {
                    if (typeof window.loadMoreOfflineProducts === 'function') {
                        setTimeout(function() {
                            window.loadMoreOfflineProducts();
                            isLoadingProducts = false;
                            $('.scroll-loader').remove();
                        }, 200);
                    } else {
                        isLoadingProducts = false;
                        $('.scroll-loader').remove();
                    }
                } else {
                    $.ajax({
                        url: next_page_url,
                        type: "GET",
                        success: function (response) {

                            appendProduct(response);

                            isLoadingProducts = false;

                            $('.scroll-loader').remove();
                        },
                        error: function () {

                            isLoadingProducts = false;

                            $('.scroll-loader').remove();
                        }
                    });
                }
            }
        }

        $('.table-container').on('scroll', function () {
            loadMoreProducts(this);
        });

        $(window).on('scroll', function () {
            loadMoreProducts(window);
        });

        function appendProduct(response) {
            if (!response || !response.data || !response.data['name']) {
                return;
            }
            var tableData = '';
            $.each(response.data['name'], function (index) {
                tableData += renderProductCard(response.data, index);
            });
            $(".table-container .product-grid").append(tableData);

            next_page_url = response.next_page_url;
        }

        $(document).on('click', '.expired', function () {
            playSound();
            alert('Product is expired!');
            return false;
        });

        $(document).on('click', '#main-pay-btn', function() {
            setTimeout(function() {
                $('#cash-btn').click();
            }, 200); // Give modal time to open
        });

        $(document).on('click', '.product-img', function () {
            if ($(this).hasClass('variant-parent-btn')) {
                return;
            }
            playSound();

            clearResults();

            var customer_id = $('#customer_id').val();
            var warehouse_id = $('#warehouse_id').val();
            var biller_id = $('#biller_id').val();

            @if($isRestaurantWarehouse)
                var table_id = $('#table_id').val();
                var waiter_id = $('#waiter_id').val();
                var service_id = $('#service_id').val();
            @endif

                if (isMobile) {
                $('.filter-window').hide('slide', { direction: 'right' }, 'fast');
            }
            if (!customer_id)
                alert('Please select Customer!');
            else if (!warehouse_id)
                alert('Please select Warehouse!');
            else if (!biller_id)
                alert('Please select Biller!');

            @if($isRestaurantWarehouse)
                        else if (!table_id && service_id == 1) {
                    alert('Please select Table!');
                }
                else if (!waiter_id && service_id == 1) {
                    alert('Please select Waiter!');
                }
            @endif
                else {
                var data = $(this).data();
                if (data.type.trim() === 'combo' && parseFloat(data.qty) <= 0) {
                    alert('This combo product is out of stock!');
                    return false;
                }
                productSearch(data);
            }
        });

        async function processDraftData() {
            @if(isset($lims_sale_data))
                let draft_product_data = @json($draft_product_data);
                for (let product of draft_product_data) {
                    await productSearch(product); // product is already an object
                }
            @endif
            }

        async function productSearch(data) {

            var item_code = data.code;
            var pre_qty = 0;
            var flag = true;
            $(".product-code").each(function (i) {
                if ($(this).val().trim() == item_code) {
                    rowindex = i;
                    if (data.imei && String(data.imei) !== 'null') {
                        imeiNumbers = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .imei-number').val();
                        imeiNumbersArray = imeiNumbers.split(",");

                        if (imeiNumbersArray.includes(data.imei)) {
                            alert('Same imei or serial number is not allowed!');
                            flag = false;
                            $('#product-search-input').val('');
                            return;
                        }
                    }
                    pre_qty = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val();
                }
            });

            if (flag && pre_qty > 0) {
                var max_qty = parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').attr('max'));
                var current_qty = parseFloat(pre_qty);
                if (without_stock == 'no' && !isNaN(max_qty) && (current_qty + 1) > max_qty) {
                    alert('Quantity exceeds stock quantity!');
                    return;
                }
            }

            if (flag) {
                let product = {
                    code: data.code,
                    qty: data.qty,
                    warehouse_qty: data.warehouseQty || data.warehouse_qty || data.warehouseqty || data.qty,
                    pre_qty: (parseFloat(pre_qty) + 1),
                    imei: data.imei,
                    embedded: data.embedded,
                    batch: data.batch,
                    price: data.price,
                    customer_id: $('#customer_id').val(),
                    warehouse_id: $('#warehouse_id').val()
                };
                // ── Online path ────────────────────────────────────────────────────────
                if (navigator.onLine) {
                    await $.ajax({
                        type: 'GET',
                        url: '{{url("sales/lims_product_search")}}',
                        data: {
                            data: product
                        },
                        success: function (data) {
                            // Capture rowindex immediately — it's a shared global that can
                            // change if the user clicks another product before this callback fires.
                            const capturedRowindex = rowindex;

                            if (data.modifiers && data.modifiers.length) {
                                data.qty = 1;
                                pre_qty = 0;
                            }
                            if (pre_qty > 0 && data.batch_id) {
                                const $existingRow = $('table.order-list tbody tr:nth-child(' + (capturedRowindex + 1) + ')');
                                const old_batch = $existingRow.find('.batch-no').val();
                                if (old_batch && old_batch != data.batch_no) {
                                    pre_qty = 0;
                                    data.qty = 1;
                                }
                            }

                            var flag = 1;
                            if (pre_qty > 0) {
                                const qty = data.qty;
                                rowindex = capturedRowindex;
                                const $existingRow = $('table.order-list tbody tr:nth-child(' + (capturedRowindex + 1) + ')');
                                $existingRow.find('.qty').val(qty);

                                if (window.POS_PRICE_TYPE === 'wholesale') {
                                    product_price[capturedRowindex] = parseFloat(data.wholesale_price * currency['exchange_rate']) + parseFloat(data.wholesale_price * currency['exchange_rate'] * customer_group_rate);
                                } else if (window.POS_PRICE_TYPE === 'retail') {
                                    product_price[capturedRowindex] = parseFloat(data.price * currency['exchange_rate']) + parseFloat(data.price * currency['exchange_rate'] * customer_group_rate);
                                }

                                // Also update discount from fresh server response (qty may have changed discount tier)
                                if (data.discount !== undefined) {
                                    product_discount[capturedRowindex] = parseFloat(data.discount);
                                }

                                rowindex = capturedRowindex;
                                checkQuantity(String(qty), true);
                                flag = 0;
                            }
                            $("input[name='product_code_name']").val('');

                            if (flag) {
                                rowindex = capturedRowindex;
                                addNewProduct(data);
                            }
                            else if (data.imei_number && String(data.imei_number) !== 'null') {
                                const $existingRow = $('table.order-list tbody tr:nth-child(' + (capturedRowindex + 1) + ')');
                                let imeiNumbers = $existingRow.find('.imei-number').val();
                                imeiNumbers = imeiNumbers ? imeiNumbers + ',' + data.imei_number : data.imei_number;
                                $existingRow.find('.imei-number').val(imeiNumbers);
                            }
                        }
                    });

                    // ── Offline path (IndexedDB fallback via window.POS_DB) ────────────────
                } else {
                    if (window.POS_DB) {
                        const capturedRowindex = rowindex;
                        var tx = window.POS_DB.transaction('products', 'readonly');
                        var offlineStore = tx.objectStore('products');
                        var getReq = offlineStore.get(String(product.code));

                        getReq.onsuccess = function (e) {
                            var p = e.target.result;
                            if (!p) {
                                alert('Product not found in offline cache!');
                                return;
                            }

                            // Resolve price: use scanned price if set, else stored price
                            var resolvedPrice = (product.price && parseFloat(product.price) > 0)
                                ? parseFloat(product.price)
                                : parseFloat(p.price);

                            // Apply promotion discount if active today
                            var today = new Date().toISOString().split('T')[0];
                            if (p.promotion && today <= p.last_date) {
                                resolvedPrice = parseFloat(p.promotion_price);
                            }

                            // Embedded (weight-scale) products carry their own qty
                            var resolvedQty = (product.embedded == 1)
                                ? parseFloat(product.qty)
                                : parseFloat(product.pre_qty);

                            // Flat discount per unit (base price minus effective price)
                            var resolvedDiscount = parseFloat(p.price) - resolvedPrice;
                            if (resolvedDiscount < 0) resolvedDiscount = 0;

                            // Build a named-object that matches the online AJAX response shape
                            // so addNewProduct() and the inline qty-update path work identically.
                            var offlineData = {
                                name: p.actual_name,
                                code: p.search_code,
                                price: resolvedPrice,
                                tax_rate: p.taxRate,
                                tax_name: p.taxName,
                                tax_method: p.tax_method,
                                unit_name: p.unitNames,
                                unit_operator: p.unitOperators,
                                unit_operation_value: p.unitValues,
                                id: p.id,
                                is_variant: p.is_variant ? p.id : null,
                                promotion: p.promotion,
                                is_batch: p.is_batch,
                                batch_id: null,
                                batch_no: product.batch || '',
                                is_imei: p.is_imei,
                                qty: resolvedQty,
                                wholesale_price: p.wholesale_price,
                                cost: p.cost,
                                imei_number: (product.imei && product.imei !== 'null') ? product.imei : null,
                                warehouse_qty: p.warehouse_qty,
                                type: p.type,
                                discount: resolvedDiscount,
                                extras: null
                            };

                            // Same logic as the online success callback ──────────────────
                            var flag = 1;
                            if (pre_qty > 0) {
                                rowindex = capturedRowindex;
                                const $existingRow = $('table.order-list tbody tr:nth-child(' + (capturedRowindex + 1) + ')');
                                $existingRow.find('.qty').val(offlineData.qty);

                                if (window.POS_PRICE_TYPE === 'wholesale') {
                                    product_price[capturedRowindex] = parseFloat(offlineData.wholesale_price * currency['exchange_rate']) + parseFloat(offlineData.wholesale_price * currency['exchange_rate'] * customer_group_rate);
                                } else if (window.POS_PRICE_TYPE === 'retail') {
                                    product_price[capturedRowindex] = parseFloat(offlineData.price * currency['exchange_rate']) + parseFloat(offlineData.price * currency['exchange_rate'] * customer_group_rate);
                                }

                                product_discount[capturedRowindex] = resolvedDiscount;

                                rowindex = capturedRowindex;
                                checkQuantity(String(offlineData.qty), true);
                                flag = 0;
                            }
                            $("input[name='product_code_name']").val('');

                            if (flag) {
                                rowindex = capturedRowindex;
                                addNewProduct(offlineData);
                            } else if (offlineData.imei_number && offlineData.imei_number !== 'null' && offlineData.imei_number !== '') {
                                const $existingRow = $('table.order-list tbody tr:nth-child(' + (capturedRowindex + 1) + ')');
                                let imeiNumbers = $existingRow.find('.imei-number').val();
                                imeiNumbers = imeiNumbers ? imeiNumbers + ',' + offlineData.imei_number : offlineData.imei_number;
                                $existingRow.find('.imei-number').val(imeiNumbers);
                            }
                        };

                        getReq.onerror = function () {
                            alert('Error reading product from offline database.');
                        };

                    } else {
                        alert('Offline database not initialized. Please reload while online to sync products.');
                    }
                }
            }

        }

        @if(!empty($lims_product_sale_data))
            const productSale = @json($lims_product_sale_data);
        @else
            const productSale = null;
        @endif

        window.POS_PRICE_TYPE = @json($price_type);

        function addNewProduct(data) {
            $('#empty-cart-row').remove();
            $('.payment-btn').removeAttr('disabled');
            var customer_type = $('#customer_id option:selected').data('type');
            if (customer_type == 'walkin') {
                $('#credit-sale-btn').attr('disabled', true);
                $('#installmentPlanBtn').attr('disabled', true);
            } else {
                $('#credit-sale-btn').removeAttr('disabled');
                $('#installmentPlanBtn').removeAttr('disabled');
            }
            var newRow = $('<tr id=' + data.code + '>');
            var cols = '';
            temp_unit_name = (data.unit_name).split(',');
            //pos = product_code.indexOf(data.code);

            let stockDisplay = '';
            var variantDisplay = '';
            if (data.variant_name && data.variant_name.length > 0) {
                variantDisplay = '<br><span class="product-variant font-weight-bold" style="font-size: 0.85em; color: #555;">' + "{{trans('file.Variant') == 'file.Variant' ? 'Variant' : trans('file.Variant')}}" + ': ' + data.variant_name + '</span>';
            }

            if (all_permission.includes("cart-product-update")) {
                if (data.type.trim() == 'standard' || data.type.trim() == 'combo') {
                    if (!data.imei_number || data.imei_number == 'null') {
                        stockDisplay = ` | {{ __('db.In Stock') }} : <span class="in-stock">` + data.warehouse_qty + `</span>`;
                    }
                }
                cols += '<td class="col-sm-4 col-5 product-title"><strong class="edit-product btn btn-link" data-toggle="modal" data-target="#editModal">' + data.name + ' <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg></strong>' + variantDisplay + '<br><span>' + data.code + '</span>' + stockDisplay + ' <strong class="product-price d-none"></strong>';
            } else {
                cols += '<td class="col-sm-4 col-5 product-title"><strong>' + data.name + '</strong>' + variantDisplay + '<br><span>' + data.code + '</span>' + stockDisplay + ' <strong class="product-price d-none"></strong>';
            }

            if (data.is_batch) {
                cols += '<br><input style="font-size:13px;padding:3px 25px 3px 10px;height:30px !important" type="text" class="form-control batch-no" value="' + data.batch_no + '" required/> <input type="hidden" class="product-batch-id" name="product_batch_id[]" value="' + data.batch_id + '"/>';
            }
            else {
                cols += '<input type="text" class="form-control batch-no d-none" disabled/> <input type="hidden" class="product-batch-id" name="product_batch_id[]"/>';
            }

            cols += '</td>';
            @can('price_edit_in_sale')
                cols += '<td class="col-sm-2 d-none d-md-block" style="padding-top: 5px;"><input type="text" class="form-control product-price d-none d-md-block numkey" style="height: 32px;" step="any"></td>';
            @else
                cols += '<td class="col-sm-2 d-none d-md-block" style="padding-top: 5px;"><input type="text" class="form-control product-price d-none d-md-block" readonly style="height: 32px;" step="any"></td>';
            @endcan
            cols += '<td class="col-sm-3" style="min-width:140px"><div class="input-group"><span class="input-group-btn">';

            // If no IMEI, show minus button
            if (!data.imei_number || data.imei_number == 'null') {
                cols += '<button type="button" class="btn btn-light minus mr-1" style="padding:5px"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" /></svg></button>';
            }

            // Input field
            cols += '</span><input type="text" name="qty[]" class="form-control qty numkey input-number" step="any" value="' + data.qty + '" max="' + data.warehouse_qty + '" required><span class="input-group-btn">';

            // If no IMEI, show plus button
            if (!data.imei_number || data.imei_number == 'null') {
                cols += '<button type="button" class="btn btn-light plus ml-1" style="padding:5px"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg></button>';
            }

            cols += '</span></div></td>';

            cols += '<td class="col-sm-2 sub-total"></td>';
            cols += '<td class="col-sm-1 text-right"><button type="button" class="ibtnDel btn btn-danger btn-sm" style="padding:5px"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button></td>';
            cols += '<input type="hidden" class="product-code" name="product_code[]" value="' + data.code + '"/>';
            cols += '<input type="hidden" class="product-id" name="product_id[]" value="' + data.id + '"/>';
            cols += '<input type="hidden" class="product_type" name="product_type[]" value="' + data.type + '"/>';
            cols += '<input type="hidden" class="product_price" />';
            cols += '<input type="hidden" class="sale-unit" name="sale_unit[]" value="' + temp_unit_name[0] + '"/>';
            cols += '<input type="hidden" class="net_unit_price" name="net_unit_price[]" />';
            cols += '<input type="hidden" class="discount-value" name="discount[]" />';
            cols += '<input type="hidden" class="tax-rate" name="tax_rate[]" value="' + data.tax_rate + '"/>';
            cols += '<input type="hidden" class="tax-value" name="tax[]" />';
            cols += '<input type="hidden" class="tax-name" value="' + data.tax_name + '" />';
            cols += '<input type="hidden" class="tax-method" value="' + data.tax_method + '" />';
            cols += '<input type="hidden" class="sale-unit-operator" value="' + data.unit_operator + '" />';
            cols += '<input type="hidden" class="sale-unit-operation-value" value="' + data.unit_operation_value + '" />';
            cols += '<input type="hidden" class="subtotal-value" name="subtotal[]" />';
            if (data.imei_number && String(data.imei_number) !== 'null')
                cols += '<input type="hidden" class="imei-number" name="imei_number[]" value="' + data.imei_number + '" />';
            else
                cols += '<input type="hidden" class="imei-number" name="imei_number[]" value="" />';
            if (data.modifiers && data.modifiers.length) {
                cols += '<input type="hidden" class="topping_product" name="topping_product[]" value="" />';
                cols += '<input type="hidden" class="topping-price" name="topping-price" value="" />';
            }

            newRow.append(cols);

            if (keyboard_active == 1) {
                $("table.order-list tbody").prepend(newRow).find('.qty').keyboard({
                    usePreview: false, layout: 'custom', display: { 'accept': '&#10004;', 'cancel': '&#10006;' }, customLayout: {
                        'normal': ['1 2 3', '4 5 6', '7 8 9', '0 {dec} {bksp}', '{clear} {cancel} {accept}']
                    }, restrictInput: true, preventPaste: true, autoAccept: true, css: { container: 'center-block dropdown-menu', buttonDefault: 'btn btn-light', buttonHover: 'btn-primary', buttonAction: 'active', buttonDisabled: 'disabled' },
                });
            }
            else
                $("table.order-list tbody").prepend(newRow);

            rowindex = newRow.index();

            // ── Price Arrays ──────────────────────────────────────────────────────────
            // Determine effective unit price based on price type setting.
            const _exRate = currency['exchange_rate'];
            const _cgRate = customer_group_rate;
            const _retail = parseFloat(data.price * _exRate) + parseFloat(data.price * _exRate * _cgRate);
            const _wholesale = data.wholesale_price
                ? parseFloat(data.wholesale_price * _exRate) + parseFloat(data.wholesale_price * _exRate * _cgRate)
                : 0;

            if (window.POS_PRICE_TYPE === 'wholesale') {
                product_price.splice(rowindex, 0, _wholesale);
            } else {
                // 'retail' and default both use the discounted retail price
                product_price.splice(rowindex, 0, _retail);
            }

            wholesale_price.splice(rowindex, 0, _wholesale || '{{number_format(0, gen_setting()->decimal, '.', '')}}');
            cost.splice(rowindex, 0, parseFloat(data.cost * _exRate));

            // ── Discount from server response (no second AJAX needed) ──────────────
            // data.discount is the flat discount amount per unit already computed
            // by limsProductSearch (price - discountedPrice). Apply currency exchange.
            const _discountPerUnit = parseFloat(data.discount || 0) * _exRate;
            product_discount.splice(rowindex, 0, _discountPerUnit);

            tax_rate.splice(rowindex, 0, parseFloat(data.tax_rate));
            tax_name.splice(rowindex, 0, data.tax_name);
            tax_method.splice(rowindex, 0, data.tax_method);
            unit_name.splice(rowindex, 0, data.unit_name);
            unit_operator.splice(rowindex, 0, data.unit_operator);
            unit_operation_value.splice(rowindex, 0, data.unit_operation_value);
            is_imei.splice(rowindex, 0, data.is_imei);
            is_variant.splice(rowindex, 0, data.is_variant);

            // Cache row reference once — reused below (avoids repeated nth-child scans)
            const $newRow = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')');
            $newRow.find('.product_price').val(product_price[rowindex]);

            // ── Single synchronous calculation — no AJAX, no redundant DOM scans ──
            // checkQuantity validates stock and then calls calculateRowProductData.
            checkQuantity(data.qty, true);

            // Global POS Price Option already determines retail/wholesale pricing.
            // Never auto-open the per-line edit modal merely because wholesale pricing exists.

            // if(data.imei_number) {
            //     $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number').val(imeiNumbers);
            // }

            // reset productSale array if product has modifiers and is added from recent sale or draft
            if (data.modifiers && Array.isArray(data.modifiers) && data.modifiers.length > 0) {
                if (productSale && productSale.length > 0) {

                    if (product_discount[rowindex] < 1) {
                        cur_product_id = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .product-id').val();
                        @if (isset($draft_product_discount))
                            if (product_discount[rowindex] < 1) {
                                draft_discounts = @json($draft_product_discount['discount']);
                                product_discount[rowindex] = draft_discounts[cur_product_id];
                            }
                        @endif
                        }

                    // Find a match for current data.id (product_id)
                    let matchedIndex = productSale.findIndex(p => parseInt(p.product_id) === parseInt(data.id));

                    if (matchedIndex !== -1) {
                        let matchedProduct = productSale[matchedIndex];

                        // Parse toppings
                        let toppings = JSON.parse(matchedProduct.topping_id || '[]');

                        let toppingNames = toppings.map(t => `${t.group_name || ''}: ${t.name}`).join(" | ");
                        let totalToppingPrice = toppings.reduce((sum, t) => sum + parseFloat(t.price_adjustment || t.price || 0), 0);

                        var $includes = $('<small>').text('Includes: ' + toppingNames);
                        newRow.find('.product-title').append($('<br>')).append($includes);
                        newRow.find('.topping_product').val(matchedProduct.topping_id || JSON.stringify(toppings));
                        newRow.find('.topping-price').val(totalToppingPrice.toFixed({{gen_setting()->decimal}}));

                        // Base price + Modifiers
                        const currentPrice = parseFloat(newRow.find('.net_unit_price').val()) || 0;
                        let newPrice = currentPrice + totalToppingPrice;
                        
                        // Update net_unit_price
                        newRow.find('.net_unit_price').val(newPrice.toFixed({{gen_setting()->decimal}}));
                        product_price[rowindex] = newPrice;
                        
                        // Re-calculate row
                        var qty = newRow.find('.qty').val();
                        checkDiscount(qty, true, newPrice);

                        // Remove used item from array
                        productSale.splice(matchedIndex, 1);

                        // calculateTotal called by checkDiscount
                    }

                } else {

                    const capturedRow = newRow; // C8 fix: capture newRow locally
                    const capturedRowIndex = rowindex; // capture rowindex locally

                    openToppingsModal(data, [], capturedRowIndex);

                    // Handle selection confirmation
                    $("#confirmSelection").off('click').on('click', function () {
                        let selectedToppings = [];
                        let totalAdditionalPrice = 0;
                        let validationError = null;

                        if (product_discount[capturedRowIndex] < 1) {
                            cur_product_id = $('table.order-list tbody tr:nth-child(' + (capturedRowIndex + 1) + ') .product-id').val();
                            @if (isset($draft_product_discount))
                                if (product_discount[capturedRowIndex] < 1) {
                                    draft_discounts = @json($draft_product_discount['discount']);
                                    product_discount[capturedRowIndex] = draft_discounts[cur_product_id];
                                }
                            @endif
                        }

                        // Validate groups
                        $('.modifier-group').each(function() {
                            let $group = $(this);
                            let min = parseInt($group.data('min')) || 0;
                            let max = parseInt($group.data('max')) || 0;
                            let req = parseInt($group.data('required'));
                            let name = $group.data('name');
                            let checkedCount = $group.find('.mod-input:checked').length;

                            if (req === 1 && checkedCount < min) {
                                validationError = `Please select at least ${min} option(s) for ${name}.`;
                                return false;
                            }
                            if (max > 0 && checkedCount > max) {
                                validationError = `You can only select up to ${max} option(s) for ${name}.`;
                                return false;
                            }
                        });

                        if (validationError) {
                            $('#modifier-error').text(validationError).removeClass('d-none');
                            return;
                        }

                        $('#modifier-error').addClass('d-none');

                        $(".mod-input:checked").each(function () {
                            const topping = {
                                group_id: $(this).data('group-id'),
                                id: $(this).val(),
                                group_name: $(this).data('group-name'),
                                name: $(this).data('name'),
                                price: parseFloat($(this).data('price')) || 0,
                                qty: 1
                            };

                            selectedToppings.push(topping);
                            totalAdditionalPrice += topping.price;
                        });

                        if (selectedToppings.length > 0) {
                            const selectedToppingsJson = JSON.stringify(selectedToppings);
                            const selectedProductNames = selectedToppings.map(t => `${t.group_name}: ${t.name}`).join(' | ');

                            var $selectedLabel = $('<small class="text-muted">').text('Incl: ' + selectedProductNames);
                            capturedRow.find('.product-title').append($('<br>')).append($selectedLabel);
                            capturedRow.find('.topping_product').val(selectedToppingsJson); 
                            capturedRow.find('.topping-price').val(totalAdditionalPrice.toFixed({{gen_setting()->decimal}}));

                            let baseUnitPrice = parseFloat(capturedRow.find('.net_unit_price').val()) || 0;
                            baseUnitPrice += totalAdditionalPrice;
                            capturedRow.find('.net_unit_price').val(baseUnitPrice.toFixed({{gen_setting()->decimal}}));

                            product_price[capturedRowIndex] = baseUnitPrice;
                            
                            var qty = capturedRow.find('.qty').val();
                            checkDiscount(qty, true, baseUnitPrice);
                        }

                        $("#productSelectionModal").modal('hide');
                        $(".modal-backdrop").remove();
                        $("#productSelectionModal").remove();
                    });

                    // C7 fix: also cleanup if modal is dismissed without confirming
                    $(document).one('hidden.bs.modal', '#productSelectionModal', function () {
                        $("#confirmSelection").off('click');
                    });

                    // Stop further processing until the modal is resolved
                    return;
                }
            }
        }

        function openToppingsModal(data, selectedToppings = [], rowIndex = null) {
            const escapeModifierText = function(value) {
                return String(value == null ? '' : value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            };
            let modalContent = '<form id="product-selection-form">';
            data.modifiers.forEach(group => {
                const groupId = parseInt(group.id, 10);
                const groupName = escapeModifierText(group.name);
                const minSelection = Math.max(0, parseInt(group.min_selection, 10) || 0);
                const maxSelection = Math.max(0, parseInt(group.max_selection, 10) || 0);
                let requiredLabel = group.is_required ? '<span class="text-danger">*</span>' : '';
                let reqText = [];
                if (group.is_required && minSelection > 0) reqText.push(`Min: ${minSelection}`);
                if (maxSelection > 0) reqText.push(`Max: ${maxSelection}`);
                let smallText = reqText.length ? `<small class="text-muted ml-2">(${reqText.join(', ')})</small>` : '';

                modalContent += `
                    <div class="modifier-group mb-3" data-id="${groupId}" data-name="${groupName}" data-min="${minSelection}" data-max="${maxSelection}" data-required="${group.is_required ? 1 : 0}">
                        <h6 class="border-bottom pb-1 mb-2">${groupName} ${requiredLabel} ${smallText}</h6>
                        <div class="modifier-options">`;
                
                group.modifiers.forEach(mod => {
                    const selected = selectedToppings.find(t => t.id == mod.id && t.group_id == group.id);
                    const isChecked = selected ? 'checked' : '';
                    const inputType = group.selection_type === 'single' ? 'radio' : 'checkbox';
                    const modifierId = parseInt(mod.id, 10);
                    const modifierName = escapeModifierText(mod.name);
                    const modifierPrice = Number.isFinite(parseFloat(mod.price_adjustment)) ? parseFloat(mod.price_adjustment) : 0;
                    const inputName = group.selection_type === 'single' ? `group_${groupId}` : `modifier_${modifierId}`;
                    const priceLabel = modifierPrice > 0 ? ` (+${modifierPrice.toFixed(2)})` : '';
                    
                    modalContent += `
                        <div class="form-check d-flex align-items-center mb-1">
                            <div>
                                <input class="form-check-input mod-input" type="${inputType}" name="${inputName}" id="mod_${modifierId}" value="${modifierId}" data-group-id="${groupId}" data-group-name="${groupName}" data-name="${modifierName}" data-price="${modifierPrice}" ${isChecked}>
                                <label class="form-check-label" for="mod_${modifierId}">
                                    ${modifierName}${priceLabel}
                                </label>
                            </div>
                        </div>`;
                });
                modalContent += `</div></div>`;
            });
            modalContent += '</form>';

            const modalHTML = `
                    <div class="modal fade" id="productSelectionModal" tabindex="-1" role="dialog" aria-labelledby="productSelectionModalLabel" aria-hidden="true" data-rowindex="${rowIndex}" data-backdrop="static">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="productSelectionModalLabel">{{__('db.Select Modifiers')}}</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body" style="max-height: 60vh; overflow-y: auto;">
                                    <div id="modifier-error" class="alert alert-danger d-none p-2 mb-2"></div>
                                    ${modalContent}
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary" id="confirmSelection">Confirm</button>
                                </div>
                            </div>
                        </div>
                    </div>`;

            // Remove existing modal if any, then append and show new
            $("#productSelectionModal").remove();
            $("body").append(modalHTML);
            $("#productSelectionModal").modal('show');
        }

        $('#currency').val(currency['id']);

        $('#currency').change(function () {
            var rate = $(this).find(':selected').data('rate');
            var prev_rate = currency['exchange_rate'];
            var currency_id = $(this).val();
            $('#exchange_rate').val(rate);
            //$('input[name="currency_id"]').val(currency_id);
            currency['exchange_rate'] = rate;
            $("table.order-list tbody .product-id").each(function (index) {
                rowindex = index;
                currencyChange = true;
                cur_product_id = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .product-id').val();
                var qty = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val();
                var price = (parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_price').val()) + parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-value').val()));

                price = (price / prev_rate);
                checkDiscount(qty, true, price);
                couponDiscount();
            });
        });

        $(document).on("click", "#print-btn", function () {
            var divContents = document.getElementById("get-sale-details").innerHTML;
            var a = window.open('');
            a.document.write('<html>');
            a.document.write('<body>');
            a.document.write('<style>body{line-height: 1.15;-webkit-text-size-adjust: 100%;}.d-print-none{display:none}.text-left{text-align:left}.text-center{text-align:center}.text-right{text-align:right}.row{width:100%;margin-right: -15px;margin-left: -15px;}.col-md-12{width:100%;display:block;padding: 5px 15px;}.col-md-6{width: 50%;float:left;padding: 5px 15px;}table{width:100%;margin-top:30px;}th{text-aligh:left}td{padding:10px}table,th,td{border: 1px solid black; border-collapse: collapse;}</style><style>@media print {.modal-dialog { max-width: 1000px;} }</style>');
            a.document.write(divContents);
            a.document.write('</body></html>');
            a.document.close();
            a.print();
            setTimeout(function () { a.close(); }, 10);
        });

        function convertDate(isoDate) {
            var date = new Date(isoDate);
            var day = String(date.getDate()).padStart(2, '0');
            var month = String(date.getMonth() + 1).padStart(2, '0'); // Months are zero-based
            var year = date.getFullYear();

            if ('{{gen_setting()->date_format}}' == 'd-m-Y') {
                return day + '-' + month + '-' + year;
            } else if ('{{gen_setting()->date_format}}' == 'd/m/Y') {
                return day + '/' + month + '/' + year;
            } else if ('{{gen_setting()->date_format}}' == 'd.m.Y') {
                return day + '.' + month + '.' + year;
            } else if ('{{gen_setting()->date_format}}' == 'm-d-Y') {
                return month + '-' + day + '-' + year;
            } else if ('{{gen_setting()->date_format}}' == 'm/d/Y') {
                return month + '/' + day + '/' + year;
            } else if ('{{gen_setting()->date_format}}' == 'm.d.Y') {
                return month + '.' + day + '.' + year;
            } else if ('{{gen_setting()->date_format}}' == 'Y-m-d') {
                return year + '-' + month + '-' + day;
            } else if ('{{gen_setting()->date_format}}' == 'Y/m/d') {
                return year + '/' + month + '/' + day;
            } else if ('{{gen_setting()->date_format}}' == 'Y.m.d') {
                return year + '.' + month + '.' + day;
            }

        }

        if (keyboard_active == 1) {

            $("input.numkey:text").keyboard({
                usePreview: false,
                layout: 'custom',
                display: {
                    'accept': '&#10004;',
                    'cancel': '&#10006;'
                },
                customLayout: {
                    'normal': ['1 2 3', '4 5 6', '7 8 9', '0 {dec} {bksp}', '{clear} {cancel} {accept}']
                },
                restrictInput: true, // Prevent keys not in the displayed keyboard from being typed in
                preventPaste: true,  // prevent ctrl-v and right click
                autoAccept: true,
                css: {
                    // input & preview
                    // keyboard container
                    container: 'center-block dropdown-menu', // jumbotron
                    // default state
                    buttonDefault: 'btn btn-light',
                    // hovered button
                    buttonHover: 'btn-primary',
                    // Action keys (e.g. Accept, Cancel, Tab, etc);
                    // this replaces "actionClass" option
                    buttonAction: 'active'
                },
            });

            $('input[type="text"]').keyboard({
                usePreview: false,
                autoAccept: true,
                autoAcceptOnEsc: true,
                css: {
                    // input & preview
                    // keyboard container
                    container: 'center-block dropdown-menu', // jumbotron
                    // default state
                    buttonDefault: 'btn btn-light',
                    // hovered button
                    buttonHover: 'btn-primary',
                    // Action keys (e.g. Accept, Cancel, Tab, etc);
                    // this replaces "actionClass" option
                    buttonAction: 'active',
                    // used when disabling the decimal button {dec}
                    // when a decimal exists in the input area
                    buttonDisabled: 'disabled'
                },
                change: function (e, keyboard) {
                    keyboard.$el.val(keyboard.$preview.val())
                    keyboard.$el.trigger('propertychange')
                }
            });

            $('textarea').keyboard({
                usePreview: false,
                autoAccept: true,
                autoAcceptOnEsc: true,
                css: {
                    // input & preview
                    // keyboard container
                    container: 'center-block dropdown-menu', // jumbotron
                    // default state
                    buttonDefault: 'btn btn-light',
                    // hovered button
                    buttonHover: 'btn-primary',
                    // Action keys (e.g. Accept, Cancel, Tab, etc);
                    // this replaces "actionClass" option
                    buttonAction: 'active',
                    // used when disabling the decimal button {dec}
                    // when a decimal exists in the input area
                    buttonDisabled: 'disabled'
                },
                change: function (e, keyboard) {
                    keyboard.$el.val(keyboard.$preview.val())
                    keyboard.$el.trigger('propertychange')
                }
            });

            $('#lims_productcodeSearch').keyboard().autocomplete().addAutocomplete({
                // add autocomplete window positioning
                // options here (using position utility)
                position: {
                    of: '#lims_productcodeSearch',
                    my: 'top+18px',
                    at: 'center',
                    collision: 'flip'
                }
            });
        }
        // Add More Button of Multiple Payment Modal
        $('.add-more').on("click", function (e) {
            e.preventDefault();
            var toPay = 0;
            var htmlText = `<div class="row new-row">
                                    <div class="col-md-3 col-6 mt-2 paying-amount-container">
                                        <label>{{__('db.Paying Amount')}} *</label>
                                        <input type="text" name="paid_amount[]" value="` + toPay + `" class="form-control paid_amount numkey" step="any">
                                    </div>
                                    <div class="col-md-3 col-6 mt-2">
                                        <input type="hidden" name="paid_by_id[]">
                                        <label>{{__('db.Paid By')}}</label>
                                        <select name="paid_by_id_select[]" class="form-control selectpicker">
                                            @if(in_array("cash", $options))
                                                <option value="1">Cash</option>
                                            @endif
                                            @if(in_array("gift_card", $options))
                                                <option value="2">Gift Card</option>
                                            @endif
                                            @if(in_array("card", $options))
                                                <option value="3">Credit Card</option>
                                            @endif
                                            @if(in_array("cheque", $options))
                                                <option value="4">Cheque</option>
                                            @endif
                                            @if(in_array("paypal", $options) && (strlen(env('PAYPAL_LIVE_API_USERNAME')) > 0) && (strlen(env('PAYPAL_LIVE_API_PASSWORD')) > 0) && (strlen(env('PAYPAL_LIVE_API_SECRET')) > 0))
                                                <option value="5">Paypal</option>
                                            @endif
                                            @if(in_array("deposit", $options))
                                                <option value="6">Deposit</option>
                                            @endif
                                            @if($lims_reward_point_setting_data && $lims_reward_point_setting_data->is_active)
                                                <option value="7">Points</option>
                                            @endif

                                            @if(in_array("credit", $options))
                                                <option value="credit_sale">Credit Sale</option>
                                            @endif
                                            @foreach($options as $option)
                                                @if($option !== 'cash' && $option !== 'card' && $option !== 'card' && $option !== 'cheque' && $option !== 'gift_card' && $option !== 'deposit' && $option !== 'paypal' && $option !== 'pesapal' && $option !== 'points')
                                                    <option value="{{$option}}">{{ucfirst($option)}}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 col-5 mt-2 cash-received-container">
                                        <label>{{__('db.Cash Received')}} <x-info title="Cash handed over to you. example: sale amount is 300. customer gives you 500. cash received: 500 " type="info" /> </label>
                                        <input type="text" name="paying_amount[]" class="form-control paying_amount numkey" required step="any">
                                    </div>
                                    <div class="col-1 mt-2">
                                        <button class="btn btn-danger remove-row mt-4">X</button>
                                    </div></div>`;
            $('.add-more-row').before(htmlText);
            var total_paid_amount = 0;
            $('.paid_amount').each(function () {
                var value = parseFloat($(this).val()) || 0;
                total_paid_amount += value;

            });
            var more_to_pay = ($("#grand-total").text() - total_paid_amount).toFixed({{gen_setting()->decimal}});
            $('.paid_amount:last').val(more_to_pay);
            $('.paying_amount:last').val(more_to_pay);
            $('.selectpicker').selectpicker('refresh');
            if ($('.qc').length) {
                $('.qc').data('initial', 1); // Update the data attribute
            }
            calculatePayingAmount();
        });

        $(document).on("click", ".remove-row", function () {
            $(this).parent().parent().remove();
            calculatePayingAmount();
            updateChange();
        });

        function toggleQuickCustomerCreditFields() {
            var customerType = $('#addCustomer select[name="type"]').val();
            if (customerType === 'walkin') {
                $('#addCustomer .customer-credit-field').hide();
                $('#addCustomer input[name="credit_limit"]').val('0');
            } else {
                $('#addCustomer .customer-credit-field').show();
            }
        }

        $(document).on('change', '#addCustomer select[name="type"]', function () {
            toggleQuickCustomerCreditFields();
        });

        $('#addCustomer').on('show.bs.modal', function () {
            toggleQuickCustomerCreditFields();
        });

        $('.customer-submit-btn').on("click", function () {
            var iti = window.intlTelInputGlobals.getInstance(input);
            var full_number = iti.getNumber();
            $('#full_phone').val(full_number);

            $.ajax({
                type: 'POST',
                url: "{{route('customer.store')}}",
                data: $("#customer-form").serialize(),
                success: function (response) {
                    key = response['id'];
                    value = response['name'] + ' [' + response['phone_number'] + ']';
                    var creditLimit = response['credit_limit'] !== undefined ? response['credit_limit'] : ($('#customer-form input[name="credit_limit"]').val() || 0);
                    var deposit = response['deposit'] !== undefined ? response['deposit'] : 0;
                    var points = response['points'] !== undefined ? response['points'] : 0;
                    $('select[name="customer_id"]').append('<option value="' + key + '" data-type="' + response['type'] + '" data-credit-limit="' + creditLimit + '" data-deposit="' + deposit + '" data-points="' + points + '">' + value + '</option>');
                    $('select[name="customer_id"]').val(key).trigger('change');
                    $('.selectpicker').selectpicker('refresh');
                    $("#addCustomer").modal('hide');
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;

                        // Clear old alerts
                        $('.alert-container').html('');

                        // Loop through all errors and create a separate alert for each message
                        $.each(errors, function (field, messages) {
                            $.each(messages, function (index, message) {
                                $('.alert-container').append(`
                                        <div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
                                            ${message}
                                        </div>
                                    `);
                            });
                        });
                    }
                }
            });
        });

        $("li#notification-icon").on("click", function (argument) {
            $.get('{{ url("notifications/mark-as-read") }}', function (data) {
                $("span.notification-number").text(alert_product);
            });
        });
            @if($lims_pos_setting_data && $lims_pos_setting_data->cash_register)
                $("#register-details-btn").on("click", function (e) {
                    e.preventDefault();
                    var cash_register_id = $(this).data('id');
                    if (!cash_register_id) {
                        return;
                    }
                    $('#closing_row').hide();
                    $('#submit_register').hide();
                    $('#close_register').show();
                    $.ajax({
                        url: '{{url("cash-register/getDetails")}}/' + cash_register_id,
                        type: "GET",
                        dataType: "json",
                        success: function (data) {
                            var requiredFields = [
                                'cash_in_hand', 'total_sale_amount', 'total_payment', 'cash_payment',
                                'credit_card_payment', 'cheque_payment', 'gift_card_payment',
                                'deposit_payment', 'paypal_payment', 'total_sale_return',
                                'total_expense', 'total_cash', 'total_supplier_payment'
                            ];
                            var invalidResponse = !data || requiredFields.some(function (field) {
                                return !Number.isFinite(Number(data[field]));
                            });
                            if (invalidResponse) {
                                console.error('Invalid cash register details response.', data);
                                alert(@json(__('db.Something went wrong')));
                                return;
                            }
                            var money = function (value) {
                                return Number(value).toFixed(2);
                            };
                            $('#register-details-modal #cash_in_hand').text(money(data['cash_in_hand']));
                            $('#register-details-modal #total_sale_amount').text(money(data['total_sale_amount']));
                            $('#register-details-modal #total_payment').text(money(data['total_payment']));
                            $('#register-details-modal #cash_payment').text(money(data['cash_payment']));
                            $('#register-details-modal #credit_card_payment').text(money(data['credit_card_payment']));
                            $('#register-details-modal #cheque_payment').text(money(data['cheque_payment']));
                            $('#register-details-modal #gift_card_payment').text(money(data['gift_card_payment']));
                            $('#register-details-modal #deposit_payment').text(money(data['deposit_payment']));
                            $('#register-details-modal #paypal_payment').text(money(data['paypal_payment']));
                            if (data.custom_methods) {
                                $('#custom-methods-container').empty();

                                $.each(data.custom_methods, function (key, value) {
                                    let method_name = key.replace('_payment', '').replace(/_/g, ' ');
                                    $('#custom-methods-container').append(
                                        `<tr>
                                                    <td>${method_name.charAt(0).toUpperCase() + method_name.slice(1)}:</td>
                                                    <td id="${key}" class="text-right">${money(value)}</td>
                                                </tr>`
                                    );
                                });
                            }
                            $('#register-details-modal #total_sale_return').text(money(data['total_sale_return']));
                            $('#register-details-modal #total_expense').text(money(data['total_expense']));
                            $('#register-details-modal #total_cash').text(money(data['total_cash']));
                            $('#register-details-modal input[name=actual_cash]').val(money(data['total_cash']));
                            $('#register-details-modal input[name=closing_balance]').val(money(data['total_cash']));
                            $('#register-details-modal #total_supplier_payment').text(money(data['total_supplier_payment']));
                            $('#register-details-modal input[name=cash_register_id]').val(cash_register_id);

                            $('#register-details-modal').modal('show');
                        },
                        error: function (xhr) {
                            console.error('Cash register details request failed.', xhr.status);
                            alert(@json(__('db.Something went wrong')));
                        }
                    });
                });

                $("#close_register").on("click", function (e) {
                    $('#closing_row').show();
                    $('#submit_register').show();
                    $(this).hide();
                });
            @endif

        $("#today-sale-btn").on("click", function (e) {
            e.preventDefault();
            $.ajax({
                url: '{{url("sales/today-sale")}}',
                type: "GET",
                success: function (data) {
                    $('#today-sale-modal .total_sale_amount').text(data['total_sale_amount']);
                    $('#today-sale-modal .total_payment').text(data['total_payment']);
                    $('#today-sale-modal .cash_payment').text(data['cash_payment']);
                    $('#today-sale-modal .credit_card_payment').text(data['credit_card_payment']);
                    $('#today-sale-modal .cheque_payment').text(data['cheque_payment']);
                    $('#today-sale-modal .gift_card_payment').text(data['gift_card_payment']);
                    $('#today-sale-modal .deposit_payment').text(data['deposit_payment']);
                    $('#today-sale-modal .paypal_payment').text(data['paypal_payment']);
                    $('#today-sale-modal .total_sale_return').text(data['total_sale_return']);
                    $('#today-sale-modal .total_expense').text(data['total_expense']);
                    $('#today-sale-modal .total_cash').text(data['total_cash']);
                    $('#today-sale-modal').modal('show');
                }
            });
        });

        $("#today-profit-btn").on("click", function (e) {
            e.preventDefault();
            calculateTodayProfit(0);
        });

        $("#today-profit-modal select[name=warehouseId]").on("change", function () {
            calculateTodayProfit($(this).val());
        });

        function calculateTodayProfit(warehouse_id) {
            $.ajax({
                url: '{{url("sales/today-profit")}}/' + warehouse_id,
                type: "GET",
                success: function (data) {
                    $('#today-profit-modal .product_revenue').text(data['product_revenue']);
                    $('#today-profit-modal .product_cost').text(data['product_cost']);
                    $('#today-profit-modal .expense_amount').text(data['expense_amount']);
                    $('#today-profit-modal .profit').text(data['profit']);
                }
            });
            $('#today-profit-modal').modal('show');
        }

        if (keyboard_active == 1) {
            $('#lims_productcodeSearch').bind('keyboardChange', function (e, keyboard, el) {
                var customer_id = $('#customer_id').val();
                var warehouse_id = $('#warehouse_id').val();
                var biller_id = $('#biller_id').val();


                    @if($isRestaurantWarehouse)
                                var table_id = $('#table_id').val();
                        var waiter_id = $('#waiter_id').val();
                        var service_id = $('#service_id').val();
                    @endif

                temp_data = $('#lims_productcodeSearch').val();
                if (!customer_id) {
                    $('#lims_productcodeSearch').val(temp_data.substring(0, temp_data.length - 1));
                    alert('Please select Customer!');
                }
                else if (!warehouse_id) {
                    $('#lims_productcodeSearch').val(temp_data.substring(0, temp_data.length - 1));
                    alert('Please select Warehouse!');
                }
                else if (!biller_id) {
                    $('#lims_productcodeSearch').val(temp_data.substring(0, temp_data.length - 1));
                    alert('Please select Biller!');
                }
                    @if($isRestaurantWarehouse)
                                else if (!table_id && service_id == 1) {
                            $('#lims_productcodeSearch').val(temp_data.substring(0, temp_data.length - 1));
                            alert('Please select Table!');
                        }
                        else if (!waiter_id && service_id == 1) {
                            $('#lims_productcodeSearch').val(temp_data.substring(0, temp_data.length - 1));
                            alert('Please select Waiter!');
                        }
                    @endif
                });
        }
        else {
            $('#lims_productcodeSearch').on('input', function () {
                var customer_id = $('#customer_id').val();
                var warehouse_id = $('#warehouse_id').val();
                var biller_id = $('#biller_id').val();


                    @if($isRestaurantWarehouse)
                                var table_id = $('#table_id').val();
                        var waiter_id = $('#waiter_id').val();
                        var service_id = $('#service_id').val();
                    @endif

                temp_data = $('#lims_productcodeSearch').val();
                if (!customer_id) {
                    $('#lims_productcodeSearch').val(temp_data.substring(0, temp_data.length - 1));
                    alert('Please select Customer!');
                }
                else if (!warehouse_id) {
                    $('#lims_productcodeSearch').val(temp_data.substring(0, temp_data.length - 1));
                    alert('Please select Warehouse!');
                }
                else if (!biller_id) {
                    $('#lims_productcodeSearch').val(temp_data.substring(0, temp_data.length - 1));
                    alert('Please select Biller!');
                }
                    @if($isRestaurantWarehouse)
                                else if (!table_id && service_id == 1) {
                            $('#lims_productcodeSearch').val(temp_data.substring(0, temp_data.length - 1));
                            alert('Please select Table!');
                        }
                        else if (!waiter_id && service_id == 1) {
                            $('#lims_productcodeSearch').val(temp_data.substring(0, temp_data.length - 1));
                            alert('Please select Waiter!');
                        }
                    @endif
                });
        }

        $(document).on('click', '.view-sale', function (e) {
            e.preventDefault();
            sale_id = $(this).val();

            $.ajax({
                url: '{{url("sales/get-sale")}}/' + sale_id,
                type: 'GET',
                success: function (sale) {
                    saleDetails(sale);
                },
                error: function (xhr, status, error) {
                    alert('Error: ' + error);
                }
            });
            $('#recentTransaction').modal('hide')
        });

        $(document).on('click', '#close-btn', function () {
            $('#recentTransaction').modal('show')
        });

        @include('backend.sale.sale_details_function');


        function populateRecentSale(data) {
            var tableData = '';
            $.each(data, function (index, sale) {
                tableData += '<tr>';
                tableData += '<td>' + convertDate(sale.created_at) + '</td>';
                tableData += '<td>' + sale.reference_no + '</td>';
                tableData += '<td>' + sale.name + '</td>';
                tableData += '<td>' + sale.grand_total + '</td>';

                tableData += '<td>'

                // if (all_permission.includes("sales-edit")) {
                tableData += '<button  type="button" class="btn btn-success btn-sm view-sale" title="View" data-toggle="modal" data-target="#get-sale-details" value="' + sale.id + '"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg></button>&nbsp';
                // }
                if (all_permission.includes("sales-edit")) {
                    tableData += '<a href="sales/' + sale.id + '/edit" class="btn btn-warning btn-sm" title="Edit"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg></a>&nbsp';
                }
                if (all_permission.includes("sales-delete")) {
                    tableData += '<form class="d-inline" action="{{ url("/sales")}}/' + sale.id + '" method ="POST"><input name="_method" type="hidden" value="DELETE">@csrf';
                    tableData += '<button type="submit" class="btn btn-danger btn-sm" onclick="return confirmDelete()" title="Delete"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg></button>';
                    tableData += '</form>';
                }
                tableData += '</td>'

                tableData += '</tr>';
            });

            $("#sale-latest tbody").html(tableData);
        }

        function populateRecentDraft(data) {
            var tableData = '';

            $.each(data, function (index, draft) {
                tableData += '<tr>';
                tableData += '<td>' + convertDate(draft.created_at) + '</td>';
                tableData += '<td>' + draft.reference_no + '</td>';
                tableData += '<td>' + draft.name + '</td>';
                tableData += '<td>' + draft.grand_total + '</td>';

                tableData += '<td>'

                if (all_permission.includes("sales-edit")) {
                    tableData += '<a href="{{url('/pos')}}/' + draft.id + '" class="btn btn-warning btn-sm" title="Edit"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg></a>&nbsp';
                }

                if (all_permission.includes("sales-delete")) {
                    tableData += '<form class="d-inline" action="{{ url("/sales")}}/' + draft.id + '" method ="POST"><input name="_method" type="hidden" value="DELETE">@csrf';
                    tableData += '<button type="submit" class="btn btn-danger btn-sm" onclick="return confirmDelete()" title="Delete"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg></button>';
                    tableData += '</form>';
                }
                tableData += '</td>'

                tableData += '</tr>';
            });

            $("#draft-latest tbody").html(tableData);
        }

        $("#myTable").on('click', '.plus', function () {
            // var row = $(this).closest('tr');
            // var qtyInput = row.find('.qty');

            rowindex = $(this).closest('tr').index();
            var qtyInput = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty');

            var qty = parseFloat(qtyInput.val()) || 0;
            var max_qty = parseFloat(qtyInput.attr('max'));
            if (!qty)
                qty = 1;
            else if (!isNaN(max_qty) && qty >= max_qty && without_stock == 'no') {
                alert("Quantity cannot exceed available stock (" + max_qty + ").");
                return;
            }
            else
                qty = parseFloat(qty) + 1;
            if (is_variant[rowindex]) {
                checkQuantity(String(qty), true);
            } else {
                checkDiscount(qty, true);
            }
        });

        $("#myTable").on('click', '.minus', function () {
            rowindex = $(this).closest('tr').index();
            var $qtyInput = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty');
            var currentQty = parseFloat($qtyInput.val()) || 0;
            var qty = currentQty - 1;
            if (qty > 0) {
                $qtyInput.val(qty);

                if (is_variant[rowindex])
                    checkQuantity(String(qty), true);
                else
                    checkDiscount(qty, '3');
            }
        });

        $(document).on("change", "select[name=price_option]", function () {
            $("#editModal input[name=edit_unit_price]").val($(this).val());
        });

        $("#myTable").on("change", ".batch-no", function () {
            rowindex = $(this).closest('tr').index();
            var product_id = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-id').val();
            var warehouse_id = $('#warehouse_id').val();
            $.get('{{ url("check-batch-availability") }}/' + product_id + '/' + $(this).val() + '/' + warehouse_id, function (data) {
                if (data['message'] != 'ok') {
                    alert(data['message']);
                    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.batch-no').val('');
                    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-batch-id').val('');
                }
                else {
                    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-batch-id').val(data['product_batch_id']);
                    code = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-code').val();
                    //pos = product_code.indexOf(code);
                    product_qty[pos] = data['qty'];
                }
            });
        });

        let previousqty = '';

        $("#myTable").on('focus', '.qty', function () {
            previousqty = $(this).val();
        });

        //Change quantity
        $("#myTable").on('focusout', '.qty', function () {

            let $input = $(this);
            let value = $.trim($input.val());
            let max = parseFloat($input.attr('max'));
            rowindex = $input.closest('tr').index();

            // --- 1) Empty or non-numeric check
            if (value === "" || isNaN(value)) {
                value = 1;
                $input.val(1);
                alert("Quantity must be a number.");
            } else {
                value = parseFloat(value);

                // --- 2) Must be greater than 0
                if (value <= 0) {
                    value = 1;
                    $input.val(1);
                    alert("Quantity must be greater than 0.");
                }
                // --- 3) Max attribute validation
                else if (!isNaN(max) && value > max && without_stock == 'no') {
                    value = max;
                    $input.val(max);
                    alert("Quantity cannot exceed available stock (" + max + ").");
                } else {
                    $input.val(value);
                }
            }

            // --- 4) Always reach canonical recalculation path with the final corrected value
            if (is_variant[rowindex]) {
                checkQuantity(String(value), true);
            } else {
                checkDiscount(value, 'input');
            }
        });


        $("#myTable").on('click', '.qty', function () {
            rowindex = $(this).closest('tr').index();
        });

        //Delete product
        $("table.order-list tbody").on("click", ".ibtnDel", function (event) {
            playSound();
            rowindex = $(this).closest('tr').index();
            // Remove the row from parallel arrays immediately
            product_price.splice(rowindex, 1);
            wholesale_price.splice(rowindex, 1);
            product_discount.splice(rowindex, 1);
            tax_rate.splice(rowindex, 1);
            tax_name.splice(rowindex, 1);
            tax_method.splice(rowindex, 1);
            unit_name.splice(rowindex, 1);
            unit_operator.splice(rowindex, 1);
            unit_operation_value.splice(rowindex, 1);
            is_variant.splice(rowindex, 1);
            is_imei.splice(rowindex, 1);
            cost.splice(rowindex, 1);
            $(this).closest("tr").remove();
            // calculateTotal re-scans the DOM — it picks up the correct totals after removal.
            // No need for a checkDiscount AJAX call here.
            calculateTotal();
            if ($('#tbody-id tr').length < 1) {
                $('.payment-btn').attr('disabled', true);
                $('#installmentPlanBtn').attr('disabled', true);
                $('#tbody-id').html(`
                    <tr id="empty-cart-row">
                        <td class="text-center py-5" style="color:#9ca3af; width: 100%;" colspan="6">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 48px; height: 48px; margin: 0 auto; display: block; opacity: 0.5;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                            </svg>
                            <p class="mt-2 mb-0">{{__('db.No_items_added_yet')}} {{__('db.Scan_or_click_a_product')}}</p>
                        </td>
                    </tr>
                `);
            }
        });


        //Edit product
        $("table.order-list").on("click", ".edit-product", function () {
            rowindex = $(this).closest('tr').index();
            edit();
        });

        //Update product
        $('button[name="update_btn"]').on("click", function () {
            if (is_imei[rowindex]) {
                var imeiNumbers = '';
                $("#editModal .imei-numbers").each(function (i) {
                    if (i)
                        imeiNumbers += ',' + $(this).val();
                    else
                        imeiNumbers = $(this).val();
                });
                $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number').val(imeiNumbers);
            }

            var edit_discount = $('input[name="edit_discount"]').val();
            var edit_qty = parseFloat($('input[name="edit_qty"]').val());
            var edit_unit_price = $('input[name="edit_unit_price"]').val();

            if (parseFloat(edit_discount) > parseFloat(edit_unit_price)) {
                alert('Invalid Discount Input!');
                return;
            }

            if (isNaN(edit_qty) || edit_qty <= 0) {
                $('input[name="edit_qty"]').val(1);
                edit_qty = 1;
                alert("Quantity can't be less than 0");
            }

            var tax_rate_all = <?php echo json_encode($tax_rate_all) ?>;

            tax_rate[rowindex] = parseFloat(tax_rate_all[$('select[name="edit_tax_rate"]').val()]);
            tax_name[rowindex] = $('select[name="edit_tax_rate"] option:selected').text();

            var product_type = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product_type').val();

            product_discount[rowindex] = $('input[name="edit_discount"]').val();
            if (product_type == 'standard') {

                row_unit_operator = $('#edit_unit select').find(':selected').data('operator');
                row_unit_operation_value = $('#edit_unit select').find(':selected').data('operation-value');

                if (row_unit_operator == '*') {
                    product_price[rowindex] = $('input[name="edit_unit_price"]').val() * row_unit_operation_value;
                } else {
                    product_price[rowindex] = $('input[name="edit_unit_price"]').val() / row_unit_operation_value;
                }
                var position = $('select[name="edit_unit"]').val();
                var temp_operator = temp_unit_operator[position];
                var temp_operation_value = temp_unit_operation_value[position];
                $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sale-unit').val(temp_unit_name[position]);
                temp_unit_name.splice(position, 1);
                temp_unit_operator.splice(position, 1);
                temp_unit_operation_value.splice(position, 1);

                temp_unit_name.unshift($('select[name="edit_unit"] option:selected').text());
                temp_unit_operator.unshift(temp_operator);
                temp_unit_operation_value.unshift(temp_operation_value);

                unit_name[rowindex] = temp_unit_name.toString() + ',';
                unit_operator[rowindex] = temp_unit_operator.toString() + ',';
                unit_operation_value[rowindex] = temp_unit_operation_value.toString() + ',';

            }
            else {
                product_price[rowindex] = $('input[name="edit_unit_price"]').val();
            }
            checkQuantity(String(edit_qty), false);

            $('#editModal').modal('hide');
        });

        $(document).on('change', '.product-price', function() {
            var row = $(this).closest('tr');
            var rowindex = row.index();
            var new_price = parseFloat($(this).val());
            
            if (isNaN(new_price) || new_price < 0) {
                $(this).val(product_price[rowindex].toFixed({{gen_setting()->decimal}}));
                return;
            }

            product_price[rowindex] = new_price;
            var qty = row.find('.qty').val();
            checkQuantity(qty, false);
        });

        $('.quick-calc').on("change keyup", function () {
            calculateGrandTotal();
        });

        $(".coupon-check").on("click", function () {
            couponDiscount();
        });

        function updatePayingAmountWithDownPayment() {
            var downPayment = parseFloat($('input[name="installment_plan[down_payment]"]').val()) || 0;
            var grandTotal = parseFloat($('#grand-total').text()) || 0;
            if (downPayment > grandTotal) {
                alert('Down payment cannot exceed grand total.');
                $('input[name="installment_plan[down_payment]"]').val(grandTotal.toFixed({{gen_setting()->decimal}}));
                downPayment = grandTotal;
            }
            return downPayment;
        }

        $(".payment-btn").on("click", function () {
            playSound();
            $('.pos-payment-grid .payment-btn').removeClass('active-method');
            $(this).addClass('active-method');

            const decimalPlaces = {{ gen_setting()->decimal ?? 2 }};

            if ($('#enable_installment').is(':checked')) {
                let downPayment = parseFloat(updatePayingAmountWithDownPayment()) || 0;

                $('.paid_amount')
                    .val(downPayment.toFixed(decimalPlaces));
                $('.paying_amount')
                    .val(downPayment.toFixed(decimalPlaces))
                    .prop('readonly', false);
            } else {
                let grandTotal = parseFloat($('#grand-total').text()) || 0;

                $('.paid_amount')
                    .val(grandTotal.toFixed(decimalPlaces));
                $('.paying_amount')
                    .val(grandTotal.toFixed(decimalPlaces))
                    .prop('readonly', false);
            }

            $('.qc').data('initial', 1);
        });

        $("#draft-btn").on("click", function () {
            playSound();
            $('input[name="sale_status"]').val(3);
            $('input[name="paying_amount"]').prop('required', false);
            $('input[name="paid_amount"]').prop('required', false);
            var rownumber = $('table.order-list tbody tr:last').index();
            if (rownumber < 0) {
                alert("Please insert product to order table!");
            }
            else
                $('.payment-form').submit();
        });

        let posFinalValidationPassed = false;
        let posFinalValidationRunning = false;

        function showPosSubmitError(message) {
            if (window.SaleRequestFeedback && typeof window.SaleRequestFeedback.show === 'function') {
                window.SaleRequestFeedback.show(message, true);
            } else {
                alert(message);
            }
        }

        function clearPosSubmitError() {
            if (window.SaleRequestFeedback && typeof window.SaleRequestFeedback.clear === 'function') {
                window.SaleRequestFeedback.clear();
            }
        }

        function validateRestaurantWarehouseBeforeSubmit() {
            @if($isRestaurantWarehouse)
                const serviceId = String($('#service_id').val() || '');

                if (!serviceId) {
                    showPosSubmitError('Please select Service!');
                    $('#service_id').selectpicker('toggle');
                    return false;
                }

                if (serviceId === '1') {
                    if (!$('#table_id').val()) {
                        showPosSubmitError('Please select Table!');
                        if ($('#visual-table-btn').is(':visible')) {
                            $('#visual-table-btn').focus();
                        } else {
                            $('#table_id').selectpicker('toggle');
                        }
                        return false;
                    }

                    if (!$('#waiter_id').val()) {
                        showPosSubmitError('Please select Waiter!');
                        $('#waiter_id').selectpicker('toggle');
                        return false;
                    }
                }
            @endif

            return true;
        }

        $("#submit-btn").on("click", function (e) {
            e.preventDefault();

            const $submitButton = $(this);

            if (posFinalValidationRunning) {
                return;
            }

            clearPosSubmitError();

            if (!validateRestaurantWarehouseBeforeSubmit()) {
                return;
            }

            /*
             * Run the same shared credit-limit contract used by Add/Edit Sale
             * before starting any POS gateway or normal sale submission.
             */
            if (
                !posFinalValidationPassed &&
                window.SaleCreditLimit &&
                typeof window.SaleCreditLimit.validate === 'function'
            ) {
                posFinalValidationRunning = true;
                $submitButton.prop('disabled', true);

                window.SaleCreditLimit.validate({ focusError: true })
                    .done(function (allowed) {
                        posFinalValidationRunning = false;
                        $submitButton.prop('disabled', false);

                        if (!allowed) {
                            return;
                        }

                        posFinalValidationPassed = true;
                        $submitButton.trigger('click');
                    });

                return;
            }

            // The validation pass applies to this checkout attempt only.
            posFinalValidationPassed = false;

            const paymentType = $('select[name="paid_by_id_select[]"]').val();
            const form = $('.payment-form');
            const csrf = $('meta[name="csrf-token"]').attr('content');

            // ✅ Gather installment data (if enabled)
            if ($("#enable_installment").is(":checked")) {
                let activePaidById = $('select[name="paid_by_id_select[]"]').val() || $('select[name="installment_plan[paid_by_id]"]').val() || '1';
                let activeAccountId = $('select[name="account_id"]').val() || $('select[name="installment_plan[account_id]"]').val() || '';
                let activePaymentNote = $('textarea[name="payment_note"]').val() || $('textarea[name="installment_plan[payment_note]"]').val() || '';

                const installmentData = {
                    enabled: true,
                    name: $('input[name="installment_plan[name]"]').val(),
                    price: $('input[name="installment_plan[price]"]').val(),
                    additional_amount: $('input[name="installment_plan[additional_amount]"]').val(),
                    total_amount: $('input[name="installment_plan[total_amount]"]').val(),
                    down_payment: $('input[name="installment_plan[down_payment]"]').val(),
                    months: $('input[name="installment_plan[months]"]').val(),
                    reference_type: $('input[name="installment_plan[reference_type]"]').val() || 'sale',
                    paid_by_id: activePaidById,
                    account_id: activeAccountId,
                    payment_note: activePaymentNote
                };

                // 🟢 Append installment plan fields to the form before submitting
                form.find('input[name^="installment_plan["], input[name="enable_installment"]').remove();
                $.each(installmentData, function (key, value) {
                    if (value !== undefined && value !== null && value !== "") {
                        $('<input>').attr({
                            type: "hidden",
                            name: "installment_plan[" + key + "]",
                            value: value
                        }).appendTo(form);
                    }
                });

                // Also include enable_installment flag
                $('<input>').attr({
                    type: "hidden",
                    name: "enable_installment",
                    value: "1"
                }).appendTo(form);
            } else {
                $('<input>').attr({
                    type: "hidden",
                    name: "enable_installment",
                    value: "0"
                }).appendTo(form);

            }

            if (paymentType === 'razorpay') {
                // ✅ 1. Validate required Razorpay fields
                let isValid = true;
                $('.razorpay.remove-element [required]').each(function () {
                    if (!$(this).val().trim()) {
                        $(this).addClass('is-invalid');
                        isValid = false;
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });

                if (!isValid) {
                    alert('Please fill all required Razorpay fields.');
                    return;
                }

                // ✅ 2. Prepare payment data
                let data = {
                    name: $('input[name="customer_name"]').val(),
                    email: $('input[name="customer_email"]').val(),
                    phone: $('input[name="customer_phone"]').val(),
                    amount: $('.paying_amount').val(),
                    _token: csrf,
                };

                const executeRazorpay = function () {
                    // ✅ 3. Create Razorpay order (via backend)
                    $.post("/razorpay/pay", data, function (res) {
                        const options = {
                            key: res.key, // from backend
                            amount: res.amount, // in paise
                            currency: "INR",
                            name: "{{ config('site_title') }}",
                            description: "Order Payment",
                            image: "{{ asset('logo/' . config('site_logo')) }}",
                            order_id: res.order_id,
                            prefill: {
                                name: data.name,
                                email: data.email,
                                contact: data.phone
                            },
                            theme: {
                                color: "#0C9DDA"
                            },
                            handler: function (response) {
                                // ✅ Verify payment on success
                                $.post("/razorpay/verify", {
                                    _token: csrf,
                                    razorpay_order_id: response.razorpay_order_id,
                                    razorpay_payment_id: response.razorpay_payment_id,
                                    razorpay_signature: response.razorpay_signature
                                }, function (verifyRes) {
                                    if (verifyRes.status === 'success') {
                                        $('<input>').attr({ type: 'hidden', name: 'razorpay_payment_id', value: response.razorpay_payment_id }).appendTo(form);
                                        $('<input>').attr({ type: 'hidden', name: 'razorpay_order_id', value: response.razorpay_order_id }).appendTo(form);
                                        $('<input>').attr({ type: 'hidden', name: 'razorpay_signature', value: response.razorpay_signature }).appendTo(form);
                                        form.off('submit').submit();
                                    } else {
                                        alert('Payment verification failed!');
                                    }
                                });
                            },
                            modal: {
                                ondismiss: function () {
                                    alert("UPI payment cancelled.");
                                }
                            },
                            // 🟢 Only show UPI option
                            method: {
                                upi: true,
                                card: false,
                                netbanking: false,
                                wallet: false,
                                emi: false,
                                paylater: false
                            },
                            upi: { flow: "intent" }
                        };

                        const rzp = new Razorpay(options);
                        rzp.open();

                        rzp.on('payment.failed', function (response) {
                            alert("Payment failed: " + response.error.description);
                        });
                    });
                };

                if (typeof Razorpay === 'undefined') {
                    $.getScript("https://checkout.razorpay.com/v1/checkout.js", function () {
                        executeRazorpay();
                    });
                } else {
                    executeRazorpay();
                }
            } @if(in_array("mpesa",$options)) else if (paymentType === 'mpesa') {
            // ===== M-Pesa STK Push + Dynamic QR Code =====
            let phone = $('#mpesa_phone').val();
            if (!phone || phone.trim() === '') {
                alert('Please enter the M-Pesa phone number.');
                return;
            }
            let amount = $('.paying_amount').val();
            let mpesaOrderId = 'MPOS-' + Date.now();

            $('#mpesa-status-box').show();
            $('#mpesa-status-msg').removeClass('alert-danger alert-success').addClass('alert-info').text('Sending STK push to your phone... Please wait.');

            // ===== Sequential: STK Push FIRST, then Dynamic QR on success =====

            // Send STK Push
            $.post('{{ route("payment.push", ["gateway" => "mpesa"]) }}', {
                _token: csrf,
                phone: phone,
                amount: amount
            }, function(res) {
                console.log("=== STK Push Initiated ===", res);
                if (res.success) {
                    $('#mpesa-status-msg').text('STK push sent! Enter your M-Pesa PIN or scan the QR code with M-Pesa App. Waiting for confirmation...');
                    
                    // (A) Generate Dynamic QR Code AFTER successful push
                    $.post('{{ route("mpesa.generateQr") }}', {
                        _token: csrf,
                        amount: amount,
                        order_id: mpesaOrderId
                    }, function(qrRes) {
                        if (qrRes.success && qrRes.qr_code) {
                            $('#mpesa-qr-img').attr('src', 'data:image/png;base64,' + qrRes.qr_code);
                            $('#mpesa-qr-box').show();
                        }
                    });

                    let referenceId = res.CheckoutRequestID;
                    let pollCount = 0, maxPolls = 12;
                    let pollInterval = setInterval(function() {
                        pollCount++;
                        if (pollCount > maxPolls) {
                            clearInterval(pollInterval);
                            $('#mpesa-status-msg').removeClass('alert-info').addClass('alert-danger').text('Payment timeout. Please try again.');
                            return;
                        }
                        $.post('{{ route("payment.queryStatus", ["gateway" => "mpesa"]) }}', {
                            _token: csrf,
                            reference_id: referenceId
                        }, function(qRes) {
                            console.log("=== M-Pesa Polling ===", qRes);
                            if (qRes.status === 'success') {
                                clearInterval(pollInterval);
                                $('#mpesa-qr-box').hide();
                                $('#mpesa-status-msg').removeClass('alert-info alert-danger').addClass('alert-success').text('✅ M-Pesa payment confirmed! Completing sale...');
                                $('<input>').attr({type: 'hidden', name: 'mpesa_checkout_id', value: referenceId}).appendTo(form);
                                setTimeout(function() { form.off('submit').submit(); }, 800);
                            } else if (qRes.status === 'cancelled') {
                                clearInterval(pollInterval);
                                $('#mpesa-status-msg').removeClass('alert-info').addClass('alert-danger').text('❌ Payment cancelled by user.');
                            } else if (qRes.status === 'failed') {
                                clearInterval(pollInterval);
                                $('#mpesa-status-msg').removeClass('alert-info').addClass('alert-danger').text('❌ ' + (qRes.message || 'Payment failed.'));
                            }
                        }).fail(function(xhr) {
                            console.error("M-Pesa query failed:", xhr.responseText);
                        });
                    }, 15000);
                } else {
                    $('#mpesa-status-msg').removeClass('alert-info').addClass('alert-danger').text('❌ ' + (res.message || 'STK Push failed.'));
                }
            }).fail(function(xhr) {
                let errMsg = 'STK Push request failed.';
                try { errMsg = JSON.parse(xhr.responseText).message || errMsg; } catch(e) {}
                $('#mpesa-status-msg').removeClass('alert-info').addClass('alert-danger').text('❌ ' + errMsg);
            });

        } @endif @if(in_array("mtnmomo",$options)) else if (paymentType === 'mtnmomo') {
            // ===== MTN MoMo Request to Pay + USSD QR Code =====
            let momoPhone = $('#mtnmomo_phone').val();
            if (!momoPhone || momoPhone.trim() === '') {
                alert('Please enter the MTN MoMo phone number.');
                return;
            }
            let momoAmount = $('.paying_amount').val();
            $('#mtnmomo-status-box').show();
            $('#mtnmomo-status-msg').removeClass('alert-danger alert-success').addClass('alert-info').text('Sending MTN MoMo request to your phone... Please wait.');

            // ===== Sequential: Request to Pay FIRST, then USSD QR on success =====

            // Send Request to Pay
            $.post('{{ route("payment.push", ["gateway" => "mtnmomo"]) }}', {
                _token: csrf,
                phone: momoPhone,
                amount: momoAmount
            }, function(res) {
                console.log("=== MTN MoMo Initiated ===", res);
                if (res.success) {
                    $('#mtnmomo-status-msg').text('MTN MoMo request sent! Please enter your PIN or scan the QR code. Waiting for confirmation...');
                    
                    // (A) Generate USSD QR Code AFTER successful push
                    $.post('{{ route("mtnmomo.generateQr") }}', {
                        _token: csrf,
                        amount: momoAmount
                    }, function(qrRes) {
                        if (qrRes.success && qrRes.qr_code) {
                            $('#mtnmomo-qr-img').attr('src', 'data:image/png;base64,' + qrRes.qr_code);
                            if (qrRes.ussd_code) {
                                $('#mtnmomo-ussd-code').text(qrRes.ussd_code);
                            }
                            $('#mtnmomo-qr-box').show();
                        }
                    });

                    let momoRefId = res.reference_id;
                    let momoPollCount = 0, momoMaxPolls = 12;
                    let momoPollInterval = setInterval(function() {
                        momoPollCount++;
                        if (momoPollCount > momoMaxPolls) {
                            clearInterval(momoPollInterval);
                            $('#mtnmomo-status-msg').removeClass('alert-info').addClass('alert-danger').text('Payment timeout. Please try again.');
                            return;
                        }
                        $.post('{{ route("payment.queryStatus", ["gateway" => "mtnmomo"]) }}', {
                            _token: csrf,
                            reference_id: momoRefId
                        }, function(qRes) {
                            console.log("=== MTN MoMo Polling ===", qRes);
                            if (qRes.status === 'success') {
                                clearInterval(momoPollInterval);
                                $('#mtnmomo-qr-box').hide();
                                $('#mtnmomo-status-msg').removeClass('alert-info alert-danger').addClass('alert-success').text('✅ MTN MoMo payment confirmed! Completing sale...');
                                $('<input>').attr({type: 'hidden', name: 'mtnmomo_reference_id', value: momoRefId}).appendTo(form);
                                setTimeout(function() { form.off('submit').submit(); }, 800);
                            } else if (qRes.status === 'failed') {
                                clearInterval(momoPollInterval);
                                $('#mtnmomo-status-msg').removeClass('alert-info').addClass('alert-danger').text('❌ ' + (qRes.message || 'Payment failed.'));
                            }
                        }).fail(function(xhr) {
                            console.error("MTN MoMo query failed:", xhr.responseText);
                        });
                    }, 15000);
                } else {
                    $('#mtnmomo-status-msg').removeClass('alert-info').addClass('alert-danger').text('❌ ' + (res.message || 'MTN MoMo request failed.'));
                }
            }).fail(function(xhr) {
                let errMsg = 'MTN MoMo request failed.';
                try { errMsg = JSON.parse(xhr.responseText).message || errMsg; } catch(e) {}
                $('#mtnmomo-status-msg').removeClass('alert-info').addClass('alert-danger').text('❌ ' + errMsg);
            });

        } @endif @if(in_array("payhere",$options)) else if (paymentType === 'payhere') {
            // ===== PayHere — JS SDK Modal =====
            let payHereAmount = parseFloat($('.paying_amount').val()).toFixed(2);
            let payHereOrderId = 'SPO-' + Date.now();

            qrOpenModal(payHereAmount, 'PayHere');

            $.post('{{ route("payment.push", ["gateway" => "payhere"]) }}', {
                _token: csrf,
                amount: payHereAmount,
                order_id: payHereOrderId
            }, function(res) {
                console.log('=== PayHere Initiated ===', res);
                let orderId = res.order_id;
                let qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' + encodeURIComponent(res.qr_checkout_url);

                phShowState('waiting');
                
                // Replace waiting state content with QR code
                $('#ph-state-waiting').html(`
                    <p style="color:#1e293b;font-size:15px;font-weight:600;margin:0 0 6px">Scan to Pay</p>
                    <p style="color:#94a3b8;font-size:13px;margin:0 0 16px">Use your mobile camera to scan this QR code and complete the payment.</p>
                    <div style="background:#fff;padding:10px;display:inline-block;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);margin-bottom:15px;">
                        <img src="${qrUrl}" alt="PayHere QR Code" style="width:200px;height:200px;border-radius:5px;">
                    </div>
                    <div style="margin-bottom:15px;">
                        <a href="${res.qr_checkout_url}" target="_blank" style="display:inline-block;background:#2563eb;color:#fff;padding:8px 16px;border-radius:6px;text-decoration:none;font-weight:600;font-size:14px;">Click here to pay directly</a>
                    </div>
                    <div style="background:#f8fafc;border-radius:10px;padding:12px 16px;display:flex;align-items:center;gap:10px;border:1px solid #e2e8f0;text-align:left;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2563eb" width="24" height="24" style="flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
                        <span style="color:#475569;font-size:12px">Please do not close this window. Status will update automatically once payment is completed.</span>
                    </div>
                `);

                // Start polling to check if the payment is complete
                let phPollTimer = null;
                let pollCount = 0, maxPolls = 60; // 60 polls * 5s = 5 mins timeout
                
                phPollTimer = setInterval(function() {
                    pollCount++;
                    if (pollCount > maxPolls) {
                        clearInterval(phPollTimer);
                        phShowState('error');
                        $('#ph-error-msg').text('Confirmation timeout. Payment took too long.');
                        return;
                    }
                    $.post('{{ route("payment.queryStatus", ["gateway" => "payhere"]) }}', {
                        _token: csrf,
                        reference_id: orderId
                    }, function(qRes) {
                        console.log('=== PayHere QR Poll ===', qRes);
                        if (qRes.status === 'success') {
                            clearInterval(phPollTimer);
                            phShowState('success');
                            $('<input>').attr({type:'hidden', name:'payhere_order_id', value: orderId}).appendTo(form);
                            setTimeout(function() { phCloseModal(); form[0].submit(); }, 1500);
                        } else if (qRes.status === 'failed' || qRes.status === 'cancelled') {
                            clearInterval(phPollTimer);
                            phShowState('error');
                            $('#ph-error-msg').text(qRes.message || 'Payment was not completed or cancelled.');
                        }
                    }).fail(function(xhr) {
                        console.error('PayHere poll failed:', xhr.responseText);
                    });
                }, 5000); // 5 seconds interval

            }).fail(function(xhr) {
                let errMsg = 'PayHere request failed.';
                try { errMsg = JSON.parse(xhr.responseText).message || errMsg; } catch(e) {}
                phShowState('error');
                $('#ph-error-msg').text(errMsg);
            });

        } @endif @if(in_array("moyasar",$options)) else if (paymentType === 'moyasar') {
            // ===== Moyasar — Hosted Invoice via shared QR modal =====
            let moyasarAmount = parseFloat($('.paying_amount').val()).toFixed(2);
            let moyasarOrderId = 'SPO-' + Date.now();

            qrOpenModal(moyasarAmount, 'Moyasar');

            $.post('{{ route("payment.push", ["gateway" => "moyasar"]) }}', {
                _token: csrf,
                amount: moyasarAmount,
                order_id: moyasarOrderId
            }, function(res) {
                console.log('=== Moyasar Initiated ===', res);
                if (res.success === false) {
                    phShowState('error');
                    $('#ph-error-msg').text(res.message);
                    return;
                }

                let orderId = res.order_id; // Moyasar invoice id
                let qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' + encodeURIComponent(res.qr_checkout_url);

                phShowState('waiting');

                $('#ph-state-waiting').html(`
                    <p style="color:#1e293b;font-size:15px;font-weight:600;margin:0 0 6px">Scan to Pay</p>
                    <p style="color:#94a3b8;font-size:13px;margin:0 0 16px">Use your mobile camera to scan this QR code and complete the payment.</p>
                    <div style="background:#fff;padding:10px;display:inline-block;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);margin-bottom:15px;">
                        <img src="${qrUrl}" alt="Moyasar QR Code" style="width:200px;height:200px;border-radius:5px;">
                    </div>
                    <div style="margin-bottom:15px;">
                        <a href="${res.qr_checkout_url}" target="_blank" style="display:inline-block;background:#17b96b;color:#fff;padding:8px 16px;border-radius:6px;text-decoration:none;font-weight:600;font-size:14px;">Click here to pay directly</a>
                    </div>
                    <div style="background:#f8fafc;border-radius:10px;padding:12px 16px;display:flex;align-items:center;gap:10px;border:1px solid #e2e8f0;text-align:left;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#17b96b" width="24" height="24" style="flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
                        <span style="color:#475569;font-size:12px">Please do not close this window. Status will update automatically once payment is completed.</span>
                    </div>
                `);

                let moyasarPollTimer = null;
                let pollCount = 0, maxPolls = 60; // 60 polls * 5s = 5 mins timeout

                moyasarPollTimer = setInterval(function() {
                    pollCount++;
                    if (pollCount > maxPolls) {
                        clearInterval(moyasarPollTimer);
                        phShowState('error');
                        $('#ph-error-msg').text('Confirmation timeout. Payment took too long.');
                        return;
                    }
                    $.post('{{ route("payment.queryStatus", ["gateway" => "moyasar"]) }}', {
                        _token: csrf,
                        reference_id: orderId
                    }, function(qRes) {
                        console.log('=== Moyasar Poll ===', qRes);
                        if (qRes.status === 'success') {
                            clearInterval(moyasarPollTimer);
                            phShowState('success');
                            $('<input>').attr({type:'hidden', name:'moyasar_invoice_id', value: orderId}).appendTo(form);
                            setTimeout(function() { phCloseModal(); form[0].submit(); }, 1500);
                        } else if (qRes.status === 'failed' || qRes.status === 'cancelled') {
                            clearInterval(moyasarPollTimer);
                            phShowState('error');
                            $('#ph-error-msg').text(qRes.message || 'Payment was not completed or cancelled.');
                        }
                    }).fail(function(xhr) {
                        console.error('Moyasar poll failed:', xhr.responseText);
                    });
                }, 5000); // 5 seconds interval

            }).fail(function(xhr) {
                let errMsg = 'Moyasar request failed.';
                try { errMsg = JSON.parse(xhr.responseText).message || errMsg; } catch(e) {}
                phShowState('error');
                $('#ph-error-msg').text(errMsg);
            });

        } @endif @if(in_array("stripe",$options)) else if (paymentType === 'stripe') {
            // ===== Stripe — Isolated Modal Flow =====
            let stripeAmount = parseFloat($('.paying_amount').val()).toFixed(2);
            let stripeOrderId = 'SPO-' + Date.now();

            stripeOpenModal(stripeAmount);

            $.post('{{ route("payment.push", ["gateway" => "stripe"]) }}', {
                _token: csrf,
                amount: stripeAmount,
                order_id: stripeOrderId
            }, function(res) {
                console.log('=== Stripe Initiated ===', res);
                if(res.success === false) {
                    stripeShowState('error');
                    $('#stripe-error-msg').text(res.message);
                    return;
                }
                
                let orderId = res.order_id; // Stripe Session ID
                let checkoutUrl = res.qr_checkout_url;
                let qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' + encodeURIComponent(checkoutUrl);

                stripeShowState('waiting');
                
                // Replace waiting state content with QR code and button
                $('#stripe-state-waiting').html(`
                    <p style="color:#1e293b;font-size:15px;font-weight:600;margin:0 0 6px">Scan to Pay or Click</p>
                    <p style="color:#94a3b8;font-size:13px;margin:0 0 16px">Use your mobile camera to scan this QR code or click the button below.</p>
                    <div style="background:#fff;padding:10px;display:inline-block;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);margin-bottom:15px;">
                        <img src="${qrUrl}" alt="Stripe QR Code" style="width:200px;height:200px;border-radius:5px;">
                    </div>
                    <button id="stripe-proceed-btn" type="button" style="background:#5469d4;color:#fff;border:none;border-radius:10px;padding:12px 24px;font-size:14px;font-weight:600;cursor:pointer;width:100%;margin-bottom:12px;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 4px 6px -1px rgba(84,105,212,0.4)">
                        <span>Pay with Stripe on this device</span>
                    </button>
                    <div style="background:#f8fafc;border-radius:10px;padding:12px 16px;display:flex;align-items:center;gap:10px;border:1px solid #e2e8f0;text-align:left">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#5469d4" width="18" height="18" style="flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
                        <span style="color:#475569;font-size:12px">Once paid via phone or popup, this window will automatically complete the sale.</span>
                    </div>
                `);
                
                // Start polling immediately so if they scan via phone, it detects it
                let stripePollTimer = null;
                let pollCount = 0, maxPolls = 60; // 5 mins timeout
                
                stripePollTimer = setInterval(function() {
                    pollCount++;
                    if (pollCount > maxPolls) {
                        clearInterval(stripePollTimer);
                        stripeShowState('error');
                        $('#stripe-error-msg').text('Confirmation timeout. Payment took too long.');
                        return;
                    }
                    $.post('{{ route("payment.queryStatus", ["gateway" => "stripe"]) }}', {
                        _token: csrf,
                        reference_id: orderId
                    }, function(qRes) {
                        console.log('=== Stripe Poll ===', qRes);
                        if (qRes.status === 'success') {
                            clearInterval(stripePollTimer);
                            stripeShowState('success');
                            $('<input>').attr({type:'hidden', name:'stripe_session_id', value: orderId}).appendTo(form);
                            setTimeout(function() { stripeCloseModal(); form[0].submit(); }, 1500);
                        } else if (qRes.status === 'failed') {
                            clearInterval(stripePollTimer);
                            stripeShowState('error');
                            $('#stripe-error-msg').text(qRes.message || 'Payment session expired or canceled.');
                        }
                    }).fail(function(xhr) {
                        console.error('Stripe poll failed:', xhr.responseText);
                    });
                }, 5000); // 5 seconds interval
                
                // Add click handler to open Stripe checkout in popup if they prefer that over phone
                $('#stripe-proceed-btn').off('click').on('click', function() {
                    window.open(checkoutUrl, 'StripeCheckout', 'width=500,height=600,scrollbars=yes,resizable=yes');
                    stripeShowState('polling');
                });


            }).fail(function(xhr) {
                let errMsg = 'Stripe request failed.';
                try { errMsg = JSON.parse(xhr.responseText).message || errMsg; } catch(e) {}
                stripeShowState('error');
                $('#stripe-error-msg').text(errMsg);
            });

        } @endif else {
                // ✅ Non-Razorpay, Non-MPesa, Non-PayHere → just submit normally
                form.off('submit').submit();
            }
        });

        function validateBatchAndExpiry() {
            let isValid = true;
            $('table.order-list tbody tr').each(function () {
                let batchInput = $(this).find('.batch-no');
                let batchId = $(this).find('.product-batch-id').val();
                let expiryText = $(this).find('.expired-date').text().trim();

                // Only for batch-enabled products
                if (batchInput.length && !batchInput.hasClass('d-none')) {

                    // ❌ Batch missing
                    if (!batchInput.val()) {
                        alert('Batch number is required!');
                        batchInput.focus();
                        isValid = false;
                        return false;
                    }

                    // ❌ Invalid batch
                    if (!batchId) {
                        alert('Invalid batch selected!');
                        batchInput.focus();
                        isValid = false;
                        return false;
                    }

                    // // ❌ Expiry missing
                    // if (!expiryText) {
                    //     alert('Expiry date not found!');
                    //     isValid = false;
                    //     return false;
                    // }

                    // // ❌ Expired (string)
                    // if (expiryText.toLowerCase() === 'expired') {
                    //     alert('Expired batch cannot be sold!');
                    //     isValid = false;
                    //     return false;
                    // }

                    // // ❌ Expired (date comparison)
                    // let expiryDate = new Date(expiryText);
                    // if (!isNaN(expiryDate) && expiryDate < new Date()) {
                    //     alert('Expired batch cannot be sold!');
                    //     isValid = false;
                    //     return false;
                    // }
                }
            });

            return isValid;
        }

        $("#gift-card-btn").on("click", function (e) {
            if (!validateBatchAndExpiry()) {
                e.preventDefault();
                return false;
            }
            appendRemoveElement('gift-card');
        });

        $("#credit-card-btn").on("click", function (e) {
            if (!validateBatchAndExpiry()) {
                e.preventDefault();
                return false;
            }
            appendRemoveElement('credit-card');
        });

        $("#cheque-btn").on("click", function (e) {
            if (!validateBatchAndExpiry()) {
                e.preventDefault();
                return false;
            }
            appendRemoveElement('cheque');
        });

        $("#cash-btn").on("click", function (e) {
            if (!validateBatchAndExpiry()) {
                e.preventDefault();
                return false;
            }
            appendRemoveElement('cash');
        });

            @if(in_array("razorpay", $options))
                $("#razorpay-btn").on("click", function (e) {
                    if (!validateBatchAndExpiry()) {
                        e.preventDefault();
                        return false;
                    }
                    appendRemoveElement('razorpay');
                });
            @endif

            @if(in_array("mpesa",$options))
            $("#mpesa-btn").on("click",function(e) {
                if (!validateBatchAndExpiry()) { e.preventDefault(); return false; }
                appendRemoveElement('mpesa');
            });
            @endif

            @if(in_array("mtnmomo",$options))
            $("#mtnmomo-btn").on("click",function(e) {
                if (!validateBatchAndExpiry()) { e.preventDefault(); return false; }
                appendRemoveElement('mtnmomo');
            });
            @endif

            @if(in_array("payhere",$options))
            $("#payhere-btn").on("click",function(e) {
                if (!validateBatchAndExpiry()) { e.preventDefault(); return false; }
                appendRemoveElement('payhere');
            });
            @endif

        $("#credit-sale-btn").on("click", function (e) {
            var customer_type = $('#customer_id option:selected').data('type');
            if (customer_type == 'walkin') {
                alert("Walk-in Customer is not permitted to use Credit Sale.");
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            }
            if (!validateBatchAndExpiry()) {
                e.preventDefault();
                return false;
            }
            appendRemoveElement('credit-sale');
        });

        $("#moneipoint-btn").on("click", function (e) {
            if (!validateBatchAndExpiry()) {
                e.preventDefault();
                return false;
            }
            appendRemoveElement('moneipoint');
        });

        $("#multiple-payment-btn").on("click", function (e) {
            if (!validateBatchAndExpiry()) {
                e.preventDefault();
                return false;
            }
            appendRemoveElement('multiplepay');
        });

        $("#deposit-btn").on("click", function (e) {
            if (!validateBatchAndExpiry()) {
                e.preventDefault();
                return false;
            }
            appendRemoveElement('deposit');
        });

        $("#point-btn").on("click", function (e) {
            if (!validateBatchAndExpiry()) {
                e.preventDefault();
                return false;
            }
            appendRemoveElement('points');
        });

        $(".pay-options").on("click", function (e) {
            if (!validateBatchAndExpiry()) {
                e.preventDefault();
                return false;
            }
            appendRemoveElement($(this).val(), true);
        });

        function changeLabelText(labelText) {
            $("#received-paying").text(labelText);
        }

        function appendRemoveElement(className, payOption = false) {
            $('.payment-info').show();
            $('.points-info').hide();
            $('#print_invoice').prop('checked', true);
            
            if (className == 'multiplepay') {
                showMultiplePaymentUi();
            } else if (className == 'credit-sale') {
                showCreditSaleUi();
            } else {
                showNormalPayUi();
            }

            $('.selectpicker').selectpicker('refresh');
            
            var dp = {{ gen_setting()->decimal ?? 2 }};
            if ($('#enable_installment').is(':checked')) {
                var downPayment = updatePayingAmountWithDownPayment();
                $('.total_paying').text(downPayment.toFixed(dp));
                $('.total_payable').text(downPayment.toFixed(dp));
                $('.paid_amount').val(downPayment.toFixed(dp));
                $('.paying_amount').val(downPayment.toFixed(dp));
            } else {
                var grandTotal = parseFloat($('#grand-total').text()) || 0;
                $('.total_paying').text(grandTotal.toFixed(dp));
                $('.total_payable').text(grandTotal.toFixed(dp));
                $('.paid_amount').val(grandTotal.toFixed(dp));
                $('.paying_amount').val(grandTotal.toFixed(dp));
            }
            $('.due').text((0).toFixed(dp));
            $('.new-row').remove();
            $('#submit-btn').prop('disabled', false);
            updateChange();

            if (payOption) {
                let $select = $('select[name="paid_by_id_select[]"]');
                if ($select.find(`option[value="${className}"]`).length === 0) {
                    $select.append(`<option value="${className}">${className}</option>`);
                }
                $select.val(className);
            }

            var appendElement = '';
            if (className == 'cash') {
                $('select[name="paid_by_id_select[]"]').val(1);
            }
                @if(in_array("razorpay", $options))
                            else if (className == 'razorpay') {
                        $('select[name="paid_by_id_select[]"]').val('razorpay');

                        let customer_id = $('select[name="customer_id"]').val();
                        let customer = lims_customer_list.find(c => c.id == customer_id);

                        // fallback if customer not found
                        let name = customer ? customer.name : '';
                        let email = customer ? customer.email : '';
                        let phone = customer ? customer.phone_number : '';

                        if (customer && customer.type === 'walkin') {
                            name = email = phone = '';
                        }

                        appendElement = `
                                    <div class="form-group col-md-4 razorpay remove-element">
                                        <label>{{__('db.customer')}} *</label>
                                        <input type="text" name="customer_name" class="form-control" value="${name}" required>
                                    </div>
                                    <div class="form-group col-md-4 razorpay remove-element">
                                        <label>{{__('Customer Email')}}</label>
                                        <input type="email" name="customer_email" class="form-control" value="${email}" required>
                                    </div>
                                    <div class="form-group col-md-4 razorpay remove-element">
                                        <label>{{__('Customer Phone')}} *</label>
                                        <input type="text" name="customer_phone" class="form-control" value="${phone}" required>
                                    </div>
                                `;


                        changeLabelText('Amount');
                        $('#payment_receiver_id').attr('hidden', true);
                        $('#print_invoice').prop('checked', false);
                        $('.paying_amount').prop('readonly', true);
                    }
                @endif
                @if(in_array("mpesa",$options))
                else if (className == 'mpesa') {
                    $('select[name="paid_by_id_select[]"]').val('mpesa');

                    let customer_id = $('select[name="customer_id"]').val();
                    let customer = lims_customer_list.find(c => c.id == customer_id);
                    let phone = customer ? customer.phone_number : '';

                    appendElement = `
                        <div class="form-group col-md-6 mpesa remove-element">
                            <label>{{__('Customer Phone (M-Pesa)')}} *</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" style="background:#00a651;color:#fff;border-color:#00a651">254</span>
                                </div>
                                <input type="text" name="mpesa_phone" id="mpesa_phone" class="form-control" value="${phone}" placeholder="e.g. 0712345678" required>
                            </div>
                            <small class="text-muted">Customer will receive an STK push on this number.</small>
                        </div>
                        <div class="form-group col-md-6 mpesa remove-element" id="mpesa-status-box" style="display:none;">
                            <label>M-Pesa Status</label>
                            <div id="mpesa-status-msg" class="alert alert-info p-2">Waiting for payment confirmation...</div>
                        </div>
                        <div class="col-md-12 mpesa remove-element" id="mpesa-qr-box" style="display:none;margin-top:10px;">
                            <div style="background:#f8fffe;border:2px solid #00a651;border-radius:12px;padding:16px;text-align:center;">
                                <p style="font-weight:600;color:#00a651;margin-bottom:8px;">📱 Or scan with M-Pesa App</p>
                                <img id="mpesa-qr-img" src="" alt="M-Pesa QR Code"
                                     style="width:180px;height:180px;border-radius:8px;border:1px solid #ddd;"/>
                                <p style="font-size:12px;color:#888;margin-top:8px;">Customer can open M-Pesa App → Scan QR → Pay</p>
                            </div>
                        </div>
                    `;

                    changeLabelText('Amount');
                    $('#payment_receiver_id').attr('hidden', true);
                    $('#print_invoice').prop('checked', true);
                    $('.paying_amount').prop('readonly', true);
                }
                @endif
                @if(in_array("mtnmomo",$options))
                else if (className == 'mtnmomo') {
                    $('select[name="paid_by_id_select[]"]').val('mtnmomo');
                    let customer_id = $('select[name="customer_id"]').val();
                    let customer = lims_customer_list.find(c => c.id == customer_id);
                    let phone = customer ? customer.phone_number : '';
                    appendElement = `
                        <div class="form-group col-md-6 mtnmomo remove-element">
                            <label>Customer Phone (MTN MoMo) *</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" style="background:#FFCC00;color:#000;border-color:#FFCC00">+</span>
                                </div>
                                <input type="text" name="mtnmomo_phone" id="mtnmomo_phone" class="form-control" value="${phone}" placeholder="e.g. 256712345678" required>
                            </div>
                            <small class="text-muted">Customer will receive an MTN MoMo prompt on this number.</small>
                        </div>
                        <div class="form-group col-md-6 mtnmomo remove-element" id="mtnmomo-status-box" style="display:none;">
                            <label>MTN MoMo Status</label>
                            <div id="mtnmomo-status-msg" class="alert alert-info p-2">Waiting for payment confirmation...</div>
                        </div>
                        <div class="col-md-12 mtnmomo remove-element" id="mtnmomo-qr-box" style="display:none;margin-top:10px;">
                            <div style="background:#fffef0;border:2px solid #FFCC00;border-radius:12px;padding:16px;text-align:center;">
                                <p style="font-weight:600;color:#f59e0b;margin-bottom:8px;">📱 Or scan with MTN MoMo App</p>
                                <img id="mtnmomo-qr-img" src="" alt="MTN MoMo QR Code"
                                     style="width:180px;height:180px;border-radius:8px;border:1px solid #ddd;"/>
                                <p id="mtnmomo-ussd-code" style="font-size:13px;font-weight:600;color:#555;margin-top:8px;letter-spacing:1px;"></p>
                                <p style="font-size:12px;color:#888;margin-top:4px;">Customer can open MTN MoMo App → Scan QR → Pay</p>
                            </div>
                        </div>
                    `;
                    changeLabelText('Amount');
                    $('#payment_receiver_id').attr('hidden', true);
                    $('#print_invoice').prop('checked', true);
                    $('.paying_amount').prop('readonly', true);
                }
                @endif
                @if(in_array("payhere",$options))
                else if (className == 'payhere') {
                    $('select[name="paid_by_id_select[]"]').val('payhere');
                    appendElement = `
                        <div class="form-group col-md-12 payhere remove-element" id="payhere-status-box" style="display:none;">
                            <label>PayHere Status</label>
                            <div id="payhere-status-msg" class="alert alert-info p-2">Waiting for PayHere payment confirmation...</div>
                        </div>
                    `;
                    changeLabelText('Amount');
                    $('#payment_receiver_id').attr('hidden', true);
                    $('#print_invoice').prop('checked', true);
                    $('.paying_amount').prop('readonly', true);
                }
                @endif
                @if(in_array("moyasar",$options))
                else if (className == 'moyasar') {
                    $('select[name="paid_by_id_select[]"]').val('moyasar');
                    appendElement = `
                        <div class="form-group col-md-12 moyasar remove-element" id="moyasar-status-box" style="display:none;">
                            <label>Moyasar Status</label>
                            <div id="moyasar-status-msg" class="alert alert-info p-2">Waiting for Moyasar payment confirmation...</div>
                        </div>
                    `;
                    changeLabelText('Amount');
                    $('#payment_receiver_id').attr('hidden', true);
                    $('#print_invoice').prop('checked', true);
                    $('.paying_amount').prop('readonly', true);
                }
                @endif
                else if (className == 'credit-sale') {
                $('select[name="paid_by_id_select[]"]').val('');
                $('.paying_amount').val(0);
                $('input[name="paid_amount[]"]').val(0);
                $('.paid_amount').val(0);
                $('.due').text($('#grand-total').text());
                $('.total_paying').text(0);
                // ✅ Pay Term auto-fill from customer
                var selected = $('#customer_id option:selected');
                var pay_term_no = selected.data('pay_term_no') || '';
                var pay_term_period = selected.data('pay_term_period') || 'days';

                appendElement = `<div class="form-group col-md-12 credit-sale remove-element">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label>Pay Term</label>
                                        <div class="input-group">
                                            <input type="number"
                                                name="pay_term_no"
                                                id="pay_term_no"
                                                class="form-control"
                                                min="1"
                                                placeholder="e.g. 30"
                                                value="${pay_term_no}">
                                            <select name="pay_term_period" id="pay_term_period" class="form-control">
                                                <option value="days"   ${pay_term_period == 'days' ? 'selected' : ''}>Days</option>
                                                <option value="months" ${pay_term_period == 'months' ? 'selected' : ''}>Months</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>`;
            }
            else if (className == 'gift-card') {
                $('select[name="paid_by_id_select[]"]').val(2);
                appendElement = `<div class="form-group col-md-12 gift-card remove-element">
                                        <label> {{__('db.Gift Card')}} *</label>
                                        <input type="hidden" name="gift_card_id">
                                        <select id="gift_card_id_select" name="gift_card_id_select" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Gift Card..."></select>
                                    </div>`;
                $.ajax({
                    url: '{{url("sales/get_gift_card")}}',
                    type: "GET",
                    dataType: "json",
                    success: function (data) {
                        $('#add-payment select[name="gift_card_id_select"]').empty();
                        $.each(data, function (index) {
                            gift_card_amount[data[index]['id']] = data[index]['amount'];
                            gift_card_expense[data[index]['id']] = data[index]['expense'];
                            $('#add-payment select[name="gift_card_id_select"]').append('<option value="' + data[index]['id'] + '">' + data[index]['card_no'] + '</option>');
                        });
                        $('.selectpicker').selectpicker('refresh');
                        $('.selectpicker').selectpicker();
                        $('#gift_card_id_select').selectpicker('toggle');
                    }
                });
            }
            else if (className == 'credit-card') {
                $('select[name="paid_by_id_select[]"]').val(3);
                appendElement = `<div class="form-group col-md-12 credit-card remove-element">
                                        <div class="row">
                                            <div class="col-md-5">
                                                <label>Card Number</label>
                                                <input class="form-control card_name" name="card_number">
                                            </div>
                                            <div class="col-md-5">
                                                <label>Card Holder Name</label>
                                                <input class="form-control" name="card_holder_name">
                                            </div>
                                            <div class="col-md-2">
                                                <label>Card Type</label>
                                                <select class="form-control" name="card_type">
                                                    <option>Visa</option>
                                                    <option>Master Card</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>`;
            }
            else if (className == 'cheque') {
                $('select[name="paid_by_id_select[]"]').val(4);
                appendElement = `<div class="form-group col-md-12 cheque remove-element">
                                    <label>{{__('db.Cheque Number')}} *</label>
                                    <input type="text" name="cheque_no" class="form-control" value="" required>
                                </div>`;

            }
            else if (className == 'deposit') {
                $('select[name="paid_by_id_select[]"]').val(6);
                let customerId = $('#customer_id').val();
                let paidAmount = parseFloat($('input[name="paid_amount[]"]').val() || 0);
                let customerDeposit = parseFloat($('#customer_id option:selected').data('deposit') || 0);

                // If the deposit is 0 or less, the modal will not be shown
                if (customerDeposit <= 0 || isNaN(customerDeposit)) {
                    alert('This customer has no deposit balance!');
                    $('#add-payment').modal('hide');
                    return;
                }

                // If the paid amount is greater than the deposit → show the multiple payment option
                if (paidAmount > customerDeposit) {
                    alert('Amount exceeds customer deposit! Opening multiple payment option.');
                    appendRemoveElement('multiplepay'); // multiple payment modal ওপেন করার ফাংশন

                    var paidAmountInput = $('#payment-select-row').find('.paid_amount').first(); 

                    paidAmountInput.val(customerDeposit.toFixed({{ gen_setting()->decimal }})).trigger('input'); // set to deposit amount and trigger input event for recalculations

                    $('#payment-select-row').find('select[name="paid_by_id_select[]"]').first().val(6).trigger('change'); // set payment method to deposit

                    paidAmountInput.attr('readonly', true);

                    $('#payment-select-row').find('.cash-received-container').first().hide(); // hide paying amount for deposit row
                }
                // If there is enough deposit balance, the deposit modal will be shown.
                else {
                    $('#add-payment').modal('show');
                }
            }

            else if (className == 'points') {
                $('select[name="paid_by_id_select[]"]').val(7);
                redeemPoints();
            }
            else if (className == 'multiplepay') {
                ismultiplepayment = 1;
                $('select[name="paid_by_id_select[]"]').val(1);
            }
            $("#payment-select-row .row:eq(0)").append(appendElement);
            if (className == 'credit-sale') {
                checkCreditLimit();
            }

        }
        // Trigger pointCalculation on body click anywhere
        if (reward_point_setting && reward_point_setting.is_active) {
            $(document).on('click', 'body', function (e) {
                // Optional: prevent firing when clicking inside modal to avoid recursion
                if (!$(e.target).closest('#add-payment, input[name="paid_amount[]"], #customer_id, select[name="paid_by_id_select[]"]').length) {
                    updatePointBtnStatus();
                }
            });
        }

        // 1️⃣ Only check points and update button tooltip on body click
        function updatePointBtnStatus() {
            let $pointBtn = $('#point-btn');
            let paid_amount = parseFloat($('#subtotal').text() || 0);
            let customerPoints = parseFloat($('#customer_id option:selected').data('points')) || 0;
            let minOrderTotal = reward_point_setting['min_order_total_for_redeem'] || 0;
            let perPoint = reward_point_setting['redeem_amount_per_unit_rp'] || 1;
            let maxPoints = reward_point_setting['max_redeem_point'] || 0;

            let required_points = Math.ceil(paid_amount / perPoint);

            // Default: disable button
            $pointBtn.prop('disabled', true);

            // Build tooltip message
            let tooltipMessage = `Point Info:
                Customer Points: ${customerPoints}
                Required Points: ${required_points}
                Order Total: ${paid_amount}
                Minimum Order Total for Redeem: ${minOrderTotal}
                Maximum Order Total for Redeem: ${maxPoints}`;

            if (paid_amount <= 0) {
                tooltipMessage += `\n⚠️ Please enter a paid amount.`;
            } else if (minOrderTotal > 0 && paid_amount < minOrderTotal) {
                tooltipMessage += `\n⚠️ Order total must be at least ${minOrderTotal} to redeem points.`;
            } else if (required_points > customerPoints) {
                tooltipMessage += `\n⚠️ Not enough points to redeem.`;
            } else if (maxPoints > 0 && required_points > maxPoints) {
                tooltipMessage += `\n⚠️ You can redeem a maximum of ${maxPoints} points.`;
            } else {
                // Enable button if all conditions pass
                $pointBtn.prop('disabled', false);
                tooltipMessage = "Click to redeem points";
            }

            $pointBtn.attr('title', tooltipMessage);
        }

        // 2️⃣ Full calculation when point button clicked
        function redeemPoints() {
            $('.payment-info').hide();
            let $pointBtn = $('#point-btn');
            let paid_amount = parseFloat($('#subtotal').text() || 0);
            let customerPoints = parseFloat($('#customer_id option:selected').data('points')) || 0;
            let minPoints = reward_point_setting['min_redeem_point'] || 0;
            let maxPoints = reward_point_setting['max_redeem_point'] || 0;
            let minOrderTotal = reward_point_setting['min_order_total_for_redeem'] || 0;
            let perPoint = reward_point_setting['redeem_amount_per_unit_rp'] || 1;

            let required_points = Math.ceil(paid_amount / perPoint);

            // Apply min/max limits
            // if (minPoints > 0 && required_points < minPoints) required_points = minPoints;
            // if (maxPoints > 0 && required_points > maxPoints) required_points = maxPoints;

            if (required_points > customerPoints) required_points = customerPoints;

            let remaining_points = customerPoints - required_points;
            let total_bill = parseFloat($('#grand-total').text()) || 0;

            // Update modal info
            $('.points-info').html(`
                    <div class="mt-4">
                        <h2>Points Info</h2>
                        <hr/>
                        <p class="text-light total_bill"><strong>Total Bill:</strong> ${total_bill}</p>
                    </div>
                    <div class="mt-4">
                        <h2>Customer Points</h2>
                        <p class="text-light customer_points">${customerPoints}</p>
                    </div>
                    <div class="mt-4">
                        <h2>Used Points</h2>
                        <p class="text-light used_points">${required_points}</p>
                        <input type="hidden" name="redeem_point" value="${required_points}" />
                    </div>
                    <div class="mt-4">
                        <h2>Remaining Points</h2>
                        <p class="text-light remaining_points">${remaining_points}</p>
                    </div>
                `);
            $('.points-info').show();

            $("input[name='used_points']").val(required_points);
        }

        $(document).on("change", 'select[name="paid_by_id_select[]"]', function () {
            updateChange();
            var id = $(this).val();
            var $thisSelect = $(this);
            var appendElement = '';
            $(".payment-form").off("submit");
            
            // Remove previous additional elements
            $(this).closest('.row').find('.remove-element, .credit-sale-extra').remove();
            $(this).closest('.row').find('.cash-received-container').addClass('d-none');

            // ✅ Credit Sale select হলে আগে walkin, duplicate ও limit check করো
            if (id === 'credit_sale') {
                var customer_type = $('#customer_id option:selected').data('type');
                if (customer_type == 'walkin') {
                    alert("Walk-in Customer is not permitted to use Credit Sale.");
                    $thisSelect.val('1').selectpicker('refresh');
                    $thisSelect.closest('.row, .new-row').find('.cash-received-container').removeClass('d-none');
                    return;
                }

                // ✅ Duplicate check — অন্য row তে credit_sale আছে কিনা
                var creditSaleAlreadyExists = false;
                $('select[name="paid_by_id_select[]"]').each(function () {
                    if (!$(this).is($thisSelect) && $(this).val() === 'credit_sale') {
                        creditSaleAlreadyExists = true;
                        return false;
                    }
                });

                if (creditSaleAlreadyExists) {
                    alert('Credit Sale already added');
                    $thisSelect.val('1').selectpicker('refresh');
                    $thisSelect.closest('.row, .new-row').find('.cash-received-container').removeClass('d-none');
                    return;
                }

                // ✅ Credit Amount হিসাব করো
                var grandTotal = parseFloat($('#grand-total').text()) || 0;
                var otherPaid = 0;

                $('.paid_amount').each(function () {
                    var $parentRow = $(this).closest('.new-row');
                    if ($parentRow.length === 0) {
                        $parentRow = $(this).closest('#payment-select-row > .row');
                    }
                    if (!$parentRow.find($thisSelect).length) {
                        var $otherSelect = $parentRow.find('select[name="paid_by_id_select[]"]');
                        if ($otherSelect.val() !== 'credit_sale') {
                            otherPaid += parseFloat($(this).val()) || 0;
                        }
                    }
                });

                var creditAmount = grandTotal - otherPaid;
                if (creditAmount < 0) creditAmount = 0;

                // Credit limit verification is now handled centrally by credit_limit_checker.blade.php
                proceedCreditSaleInMultiple($thisSelect, creditAmount);

                return; // async এর জন্য early return
            }
            //cash
            if (id == 1) {
                $(this).closest('.row').find('.cash-received-container').removeClass('d-none');
            }
            //gift
            else if (id == 2) {
                appendElement = `<div class="form-group col-md-10 gift-card remove-element">
                                        <label> {{__('db.Gift Card')}} *</label>
                                        <input type="hidden" name="gift_card_id">
                                        <select id="gift_card_id_select" name="gift_card_id_select" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Gift Card..."></select>
                                    </div>`;
                $(this).closest('.col-md-3').after(appendElement);

                $.ajax({
                    url: '{{url("sales/get_gift_card")}}',
                    type: "GET",
                    dataType: "json",
                    success: function (data) {
                        $('#add-payment select[name="gift_card_id_select"]').empty();
                        $.each(data, function (index) {
                            gift_card_amount[data[index]['id']] = data[index]['amount'];
                            gift_card_expense[data[index]['id']] = data[index]['expense'];
                            $('#add-payment select[name="gift_card_id_select"]').append('<option value="' + data[index]['id'] + '">' + data[index]['card_no'] + '</option>');
                        });
                        $('.selectpicker').selectpicker('refresh');
                    }
                });
            }
            //credit
            else if (id == 3) {
                appendElement = `<div class="form-group col-md-10 credit-card remove-element">
                                        <div class="row">
                                            <div class="col-md-5">
                                                <label>Card Number</label>
                                                <input class="form-control card_name" name="card_number">
                                            </div>
                                            <div class="col-md-5">
                                                <label>Card Holder Name</label>
                                                <input class="form-control" name="card_holder_name">
                                            </div>
                                            <div class="col-md-2">
                                                <label>Card Type</label>
                                                <select class="form-control" name="card_type">
                                                    <option>Visa</option>
                                                    <option>Master Card</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>`;
                $(this).closest('.col-md-3').after(appendElement);
            }
            //cheque
            else if (id == 4) {
                appendElement = `<div class="form-group col-md-10 cheque remove-element">
                                    <label>{{__('db.Cheque Number')}} *</label>
                                    <input type="text" name="cheque_no" class="form-control" value="" required>
                                </div>`;
                $(this).closest('.col-md-3').after(appendElement);
            }

            //deposit
            else if (id == 6) {

            }
            //point
            if (id == '7') {
                var pointAlreadyExists = false;
                $('select[name="paid_by_id_select[]"]').each(function () {
                    if (!$(this).is($thisSelect) && $(this).val() == '7') {
                        pointAlreadyExists = true;
                        return false;
                    }
                });

                if (pointAlreadyExists) {
                    alert('Points Payment has already been added. Multiple points payment is not allowed.');
                    $thisSelect.val('1').selectpicker('refresh');
                    $thisSelect.closest('.row, .new-row').find('.cash-received-container').removeClass('d-none');
                    return;
                }

                let isValid = pointCalculation();
                if (!isValid) {
                    $thisSelect.val('1').selectpicker('refresh');
                    $thisSelect.closest('.row, .new-row').find('.cash-received-container').removeClass('d-none');
                    return;
                }
            }

            // The credit sale logic is handled synchronously and asynchronously above (see if (id == 'credit_sale_...'))
        });

        // function proceedCreditSaleInMultiple resolves C2 and C3 (ReferenceError & Dead code)
        function proceedCreditSaleInMultiple($thisSelect, creditAmount) {
            var selected = $('#customer_id option:selected');
            var pay_term_no = selected.data('pay_term_no') || '';
            var pay_term_period = selected.data('pay_term_period') || 'days';

            var appendElement = `
                    <div class="form-group col-md-10 credit-sale-extra">
                        <label>Pay Term</label>
                        <div class="input-group">
                            <input type="number"
                                name="pay_term_no"
                                class="form-control"
                                min="1"
                                placeholder="e.g. 30"
                                value="${pay_term_no}">
                            <select name="pay_term_period" class="form-control">
                                <option value="days"   ${pay_term_period == 'days' ? 'selected' : ''}>Days</option>
                                <option value="months" ${pay_term_period == 'months' ? 'selected' : ''}>Months</option>
                            </select>
                        </div>
                    </div>`;


            $thisSelect.closest('.col-md-3').after(appendElement);

            // Set the calculated credit amount
            var $thisPaidAmount = $thisSelect.closest('.row, .new-row').find('.paid_amount');
            $thisPaidAmount.val(creditAmount.toFixed({{ gen_setting()->decimal }}));

            // Hide cash received container
            $thisSelect.closest('.row').find('.cash-received-container').addClass('d-none');

            calculatePayingAmount();
            updateChange();
        }

        function pointCalculation() {
            // ⬇️ last paid_amount[] input value
            let paid_amount = parseFloat(
                $('input[name="paid_amount[]"]').last().val()
            ) || 0;
            let customerPoints = parseFloat($('#customer_id option:selected').data('points')) || 0;

            let perPoint = reward_point_setting['redeem_amount_per_unit_rp'] || 1;
            let minPoints = reward_point_setting['min_redeem_point'] || 0;
            let maxPoints = reward_point_setting['max_redeem_point'] || 0;
            let minOrderTotal = reward_point_setting['min_order_total_for_redeem'] || 0;
            let total_bill = parseFloat($('#grand-total').text()) || 0;

            let required_point = Math.ceil(paid_amount / perPoint);

            if (minPoints > 0 && required_point < minPoints) {
                required_point = minPoints;
            }

            if (maxPoints > 0 && required_point > maxPoints) {
                required_point = maxPoints;
            }

            if (paid_amount <= 0) {
                alert('⚠️ Paid amount is required.');
                return false;
            }

            if (minOrderTotal > 0 && total_bill < minOrderTotal) {
                alert('⚠️ Order total must be at least ' + minOrderTotal + ' to redeem points.');
                return false;
            }

            if (required_point > customerPoints) {
                alert(
                    '⚠️ Not enough points\n' +
                    'Required: ' + required_point +
                    '\nAvailable: ' + customerPoints
                );
                return false;
            }

            $("input[name='used_points']").val(required_point);
            return true;
        }


        $(document).on("change", '#add-payment select[name="gift_card_id_select"]', function () {
            var balance = gift_card_amount[$(this).val()] - gift_card_expense[$(this).val()]; 
            $('#add-payment input[name="gift_card_id"]').val($(this).val());
            let isMultiple = (typeof ismultiplepayment !== 'undefined' && ismultiplepayment === 1);
            if (!isMultiple) {
                if ($('input[name="paid_amount[]"]').val() > balance) {
                    $('#submit-btn').prop('disabled', true);
                    alert('Amount exceeds card balance! Gift Card balance: ' + balance);
                } else {
                    $('#submit-btn').prop('disabled', false);
                }
            } else {
                // $(this).parent().parent().siblings('.paying-amount-container').children('.paid_amount').val(balance);
                updateChange();
            }

        });

        function change(paying_amount, paid_amount) {
            $("#change").text(parseFloat(paying_amount - paid_amount).toFixed({{gen_setting()->decimal}}));
        }

        // Event listener for changes to paid_amount
        $(document).on("input keyup change", '.paid_amount', function () {
            let paid_amount = parseFloat($(this).val()) || 0;
            if (paid_amount < 0) {
                $(this).val(0);
                paid_amount = 0;
            }

            let $row = $(this).closest('.row');
            let payment_method = $row.find('select[name="paid_by_id_select[]"]').val();

            // For non-cash rows, paying_amount tracks paid_amount
            if (payment_method != '1' && payment_method != '' && payment_method !== undefined) {
                $row.find('.paying_amount').val(paid_amount);
            }

            if (payment_method == '7') {
                let customerPoints = parseFloat($('#customer_id option:selected').data('points')) || 0;
                let perPoint = reward_point_setting['redeem_amount_per_unit_rp'] || 1;
                let maxPoints = reward_point_setting['max_redeem_point'] || 0;

                let required_point = Math.ceil(paid_amount / perPoint);

                if (required_point > customerPoints) {
                    alert('⚠️ Not enough points. You only have ' + customerPoints + ' points available.');
                    $(this).val(0);
                    paid_amount = 0;
                } else if (maxPoints > 0 && required_point > maxPoints) {
                    alert('⚠️ You can redeem a maximum of ' + maxPoints + ' points.');
                    $(this).val(0);
                    paid_amount = 0;
                }
            }

            calculatePayingAmount();
            updateChange();
        });

        // Event listener for changes to paying_amount (Cash Received)
        $(document).on("input keyup change", '.paying_amount', function () {
            let $input = $(this);
            let paying_amount = parseFloat($input.val()) || 0;
            if (paying_amount < 0) {
                $input.val(0);
                paying_amount = 0;
            }

            let $row = $input.closest('.row');
            let payMethod = $row.find('select[name="paid_by_id_select[]"]').val();

            // Method-aware: only synchronize cash tendered when payment method is Cash/default
            if (payMethod == '1' || payMethod == '' || payMethod === undefined) {
                let dp = {{ gen_setting()->decimal ?? 2 }};
                let payableTotal = $('#enable_installment').is(':checked')
                    ? (parseFloat($('input[name="installment_plan[down_payment]"]').val()) || 0)
                    : (parseFloat($("#grand-total").text()) || 0);

                let isMultiple = (typeof ismultiplepayment !== 'undefined' && ismultiplepayment === 1);
                if (!isMultiple) {
                    if (paying_amount < payableTotal) {
                        $row.find('.paid_amount').val(paying_amount.toFixed(dp));
                    } else {
                        $row.find('.paid_amount').val(payableTotal.toFixed(dp));
                    }
                } else {
                    let targetPaid = parseFloat($row.find('.paid_amount').val()) || 0;
                    if (paying_amount < targetPaid) {
                        $row.find('.paid_amount').val(paying_amount.toFixed(dp));
                    }
                }
            }

            calculatePayingAmount();
            updateChange();
        });

        // Update the change text for cash rows (guaranteed non-negative)
        function updateChange() {
            let change = 0;
            let dp = {{ gen_setting()->decimal ?? 2 }};
            $('select[name="paid_by_id_select[]"]').each(function () {
                if ($(this).val() == '1' || $(this).val() == '') {
                    let $row = $(this).closest('.row');
                    let paying_amount = parseFloat($row.find('.paying_amount').val()) || 0;
                    let paid_amount = parseFloat($row.find('.paid_amount').val()) || 0;
                    if (paying_amount > paid_amount) {
                        change += (paying_amount - paid_amount);
                    }
                }
            });
            $('.change').text(change.toFixed(dp));

            saveDataToLocalStorageForCustomerDisplay('clear_no');
        }

        // Function to calculate the total paying and due
        function calculatePayingAmount() {
            let cashTotal = 0;
            let dp = {{ gen_setting()->decimal ?? 2 }};
            let payableTotal = $('#enable_installment').is(':checked')
                ? (parseFloat($('input[name="installment_plan[down_payment]"]').val()) || 0)
                : (parseFloat($("#grand-total").text()) || 0);

            $('.paid_amount').each(function () {
                var $parentRow = $(this).closest('.new-row');
                if ($parentRow.length === 0) {
                    $parentRow = $(this).closest('#payment-select-row > .row');
                }
                if ($parentRow.length === 0) {
                    $parentRow = $(this).closest('.row');
                }

                var $select = $parentRow.find('select[name="paid_by_id_select[]"]');
                var payType = $select.val();

                // credit_sale is not cash/external paid amount
                if (payType === 'credit_sale') {
                    return; // skip
                }

                if ($.isNumeric($(this).val())) {
                    cashTotal += parseFloat($(this).val());
                }
            });

            var due = payableTotal - cashTotal;
            if (due < 0) due = 0;

            $('.total_paying').text(cashTotal.toFixed(dp));
            $('.due').text(due.toFixed(dp));

            if (typeof checkCreditLimit === "function") {
                checkCreditLimit();
            }
        }

        function confirmDelete() {
            if (confirm("Are you sure want to delete?")) {
                return true;
            }
            return false;
        }

        $('.transaction-btn-plus').on("click", function () {
            $(this).addClass('d-none');
            $('.transaction-btn-close').removeClass('d-none');
        });

        $('.transaction-btn-close').on("click", function () {
            $(this).addClass('d-none');
            $('.transaction-btn-plus').removeClass('d-none');
        });

        $('.coupon-btn-plus').on("click", function () {
            $(this).addClass('d-none');
            $('.coupon-btn-close').removeClass('d-none');
        });

        $('.coupon-btn-close').on("click", function () {
            $(this).addClass('d-none');
            $('.coupon-btn-plus').removeClass('d-none');
        });

        $(document).on('click', '.qc-btn', function (e) {
            if ($(this).data('amount')) {
                if ($('.qc').data('initial')) {
                    $('input[name="paying_amount"]').val($(this).data('amount').toFixed({{ gen_setting()->decimal }}));
        $('.qc').data('initial', 0);
                    }else {
            $('input[name="paying_amount"]').val((parseFloat($('input[name="paying_amount"]').val()) + $(this).data('amount')).toFixed({{gen_setting()->decimal}}));
                    }

                }
                else
        $('input[name="paying_amount"]').val('{{number_format(0, gen_setting()->decimal, '.', '')}}');
        change($('input[name="paying_amount"]').val(), $('input[name="paid_amount"]').val());
            });

        function populatePriceOption() {
            var product_price = parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product_price').val()).toFixed({{gen_setting()->decimal}});
        var ws_price = parseFloat(wholesale_price[rowindex]).toFixed({{gen_setting()->decimal}});

        $('#editModal select[name=price_option]').empty();
        if (ws_price > 0)
            $('#editModal select[name=price_option]').append('<option value="' + ws_price + '">' + ws_price + '</option>');
        $('#editModal select[name=price_option]').append('<option selected value="' + product_price + '">' + product_price + '</option>');

        $('.selectpicker').selectpicker('refresh');
            }

        function edit() {
            $(".imei-section").remove();
            var $row = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')');
            if (is_imei[rowindex]) {

                var imeiNumbers = $row.find('.imei-number').val();

                if (imeiNumbers.length) {
                    imeiArrays = [...new Set(imeiNumbers.split(","))];
                    htmlText = `<div class="col-md-8 form-group imei-section">
                                    <label>IMEI or Serial Numbers</label>
                                    <div class="table-responsive">
                                        <table id="imei-table" class="table table-hover">
                                            <tbody>`;
                    for (var i = 0; i < imeiArrays.length; i++) {
                        htmlText += `<tr>
                                            <td>
                                                <input type="text" class="form-control imei-numbers" name="imei_numbers[]" value="`+ imeiArrays[i] + `" />
                                            </td>
                                            <td>
                                                <button type="button" class="imei-del btn btn-sm btn-danger">X</button>
                                            </td>
                                        </tr>`;
                    }
                    htmlText += `</tbody>
                                        </table>
                                    </div>
                                </div>`;
                    $("#editModal .modal-element").append(htmlText);
                }
            }
            populatePriceOption();
            // $("#product-cost").text(cost[rowindex]);
            var row_product_name_code = $row.find('td:nth-child(1) > strong:nth-child(1)').text();
            $('#modal_header').text(row_product_name_code);

            var qty = $row.find('.qty').val();
            $('input[name="edit_qty"]').val(qty);

            cur_product_id = $row.find('.product-id').val();

            // @if (isset($draft_product_discount))
                //     if (product_discount[rowindex] < 1) {
                //         draft_discounts = @json($draft_product_discount['discount']);
                //         product_discount[rowindex] = draft_discounts[cur_product_id];
                //     }
            // @endif

            $('input[name="edit_discount"]').val(parseFloat(product_discount[rowindex]).toFixed({{gen_setting()->decimal}}));

            var tax_name_all = <?php echo json_encode($tax_name_all) ?>;
            pos = tax_name_all.indexOf(tax_name[rowindex]);
            $('select[name="edit_tax_rate"]').val(pos);

            var row_product_code = $row.find('.product-code').val();
            var product_type = $row.find('.product_type').val();
            if (product_type == 'standard') {
                unitConversion();
                temp_unit_name = (unit_name[rowindex]).split(',');
                temp_unit_name.pop();
                temp_unit_operator = (unit_operator[rowindex]).split(',');
                temp_unit_operator.pop();
                temp_unit_operation_value = (unit_operation_value[rowindex]).split(',');
                temp_unit_operation_value.pop();

                $('select[name="edit_unit"]').empty();
                $.each(temp_unit_name, function (key, value) {
                    $('select[name="edit_unit"]').append('<option data-operator="' + temp_unit_operator[key] + '" data-operation-value="' + temp_unit_operation_value[key] + '" value="' + key + '">' + value + '</option>');
                });
                $("#edit_unit").show();
            }
            else {
                row_product_price = product_price[rowindex];
                $("#edit_unit").hide();
            }
            $('input[name="edit_unit_price"]').val(row_product_price.toFixed({{gen_setting()->decimal}}));
            $('.selectpicker').selectpicker('refresh');
        }

        //Delete imei
        $(document).on("click", "table#imei-table tbody .imei-del", function () {
            // Decrease qty
            var edit_qty = parseFloat($('input[name="edit_qty"]').val());
            edit_qty = (edit_qty - 1);
            $('input[name="edit_qty"]').val(edit_qty);

            // Check number of remaining IMEI for the same product
            let imeis = $('#tbody-id tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number').val();

            let target = $(this).closest("tr").find('.imei-numbers').val();

            // Remove the row
            $(this).closest("tr").remove();

            // 1. Convert to array (remove spaces just in case)
            let arr = imeis.split(',').map(s => s.trim());

            // 2. Filter out the target IMEI
            arr = arr.filter(i => i !== target);

            // 3. Convert back to string
            let updated = arr.join(',');

            // Set the updated value back
            $('#tbody-id tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number').val(updated);

            if (edit_qty == 0) {
                $('#editModal').modal('hide');
                $('#tbody-id tr:eq(' + rowindex + ')').remove();
                return;
            }

            $('#tbody-id tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val(edit_qty);
            checkDiscount(edit_qty, false);
            calculateTotal();
        });

        function couponDiscount() {
            var rownumber = $('table.order-list tbody tr:last').index();
            if (rownumber < 0) {
                alert("Please insert product to order table!")
            }
            else if ($("#coupon-code").val() != '') {
                valid = 0;
                $.each(coupon_list, function (key, value) {
                    if ($("#coupon-code").val() == value['code']) {
                        valid = 1;
                        todayDate = <?php echo json_encode(date('Y-m-d'))?>;
                        if (parseFloat(value['quantity']) <= parseFloat(value['used']))
                            alert('This Coupon is no longer available');
                        else if (new Date(todayDate) > new Date(value['expired_date']))
                            alert('This Coupon has expired!');
                        else if (value['type'] == 'fixed') {
                            if (parseFloat($('input[name="grand_total"]').val()) >= value['minimum_amount']) {
                                $('input[name="grand_total"]').val($('input[name="grand_total"]').val() - (value['amount'] * currency['exchange_rate']));
                                $('#grand-total').text(parseFloat($('input[name="grand_total"]').val()).toFixed({{gen_setting()->decimal}}));
                                $('#grand-total-m').text(parseFloat($('input[name="grand_total"]').val()).toFixed({{gen_setting()->decimal}}));
                                if (!isEditMode && !$('input[name="coupon_active"]').val()) {
                                    alert('Congratulation! You got ' + (value['amount'] * currency['exchange_rate']) + ' ' + currency['code'] + ' discount');
                                }
                                $(".coupon-check").prop("disabled", true);
                                $("#coupon-code").prop("disabled", true);
                                $('input[name="coupon_active"]').val(1);
                                $("#coupon-modal").modal('hide');
                                $('input[name="coupon_id"]').val(value['id']);
                                $('input[name="coupon_discount"]').val(value['amount'] * currency['exchange_rate']);
                                $('#coupon-text').text(parseFloat(value['amount'] * currency['exchange_rate']).toFixed({{gen_setting()->decimal}}));
                            }
                            else
                                alert('Grand Total is not sufficient for discount! Required ' + value['minimum_amount'] + ' ' + currency['code']);
                        }
                        else {
                            var grand_total = $('input[name="grand_total"]').val();
                            var coupon_discount = grand_total * (value['amount'] / 100);
                            grand_total = grand_total - coupon_discount;
                            $('input[name="grand_total"]').val(grand_total);
                            $('#grand-total').text(parseFloat(grand_total).toFixed({{gen_setting()->decimal}}));
                            $('#grand-total-m').text(parseFloat(grand_total).toFixed({{gen_setting()->decimal}}));
                            if (!isEditMode && !$('input[name="coupon_active"]').val()) {
                                alert('Congratulation! You got ' + value['amount'] + '% discount');
                            }
                            $(".coupon-check").prop("disabled", true);
                            $("#coupon-code").prop("disabled", true);
                            $('input[name="coupon_active"]').val(1);
                            $("#coupon-modal").modal('hide');
                            $('input[name="coupon_id"]').val(value['id']);
                            $('input[name="coupon_discount"]').val(coupon_discount);
                            $('#coupon-text').text(parseFloat(coupon_discount).toFixed({{gen_setting()->decimal}}));
                        }
                    }
                });
                if (!valid)
                    alert('Invalid coupon code!');
            }

            saveDataToLocalStorageForCustomerDisplay('clear_no');
        }

        /**
         * checkDiscount — used only from the EDIT MODAL path (qty change after add).
         * NOT called during initial product add (discount is now in the server response).
         *
         * Converted from async:false to async:true to prevent UI thread blocking.
         * After fetching the updated discount, it recalculates the row inline.
         */
        function checkDiscount(qty, flag, price = 0) {
            const capturedRowindex = rowindex; // capture before async gap
            const customer_id = $('#customer_id').val();
            const warehouse_id = $('#warehouse_id').val();
            const $row = $('table.order-list tbody tr:nth-child(' + (capturedRowindex + 1) + ')');
            const product_id = $row.find('.product-id').val();

            $.ajax({
                type: 'GET',
                async: true, // ✅ Never block the UI thread
                url: '{{url("/")}}/sales/check-discount?qty=' + qty + '&customer_id=' + customer_id + '&product_id=' + product_id + '&warehouse_id=' + warehouse_id,
                success: function (data) {
                    rowindex = capturedRowindex; // restore captured index for downstream calls

                    if (String(product_price[rowindex]).length === 0) {
                        product_price[rowindex] = $row.find('.product_price').val();
                    }
                    if (price > 0) {
                        product_price[rowindex] = parseFloat(price * currency['exchange_rate'])
                            + parseFloat(price * currency['exchange_rate'] * customer_group_rate);
                    }

                    // Update the stored discount for this row
                    product_discount[rowindex] = parseFloat(data[2] || 0) * currency['exchange_rate'];

                    // Update qty on row then recalculate
                    $row.find('.qty').val(qty);
                    checkQuantity(String(qty), true);
                }
            });
        }

        function checkQuantity(sale_qty, flag) {
            // Cache row reference once — avoid repeated nth-child DOM traversals
            const $row = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')');
            const maxQty = parseFloat($row.find('.qty').attr('max'));
            const productType = $row.find('.product_type').val();
            const imeiNumbers = $row.find('.imei-number').val();
            sale_qty = parseFloat(sale_qty);

            if (without_stock == 'no') {
                if (productType && (productType.trim() == 'standard' || productType.trim() == 'combo')) {
                    const operator = unit_operator[rowindex].split(',');
                    const operation_value = unit_operation_value[rowindex].split(',');

                    let total_stock_qty = sale_qty;
                    if (operator[0] == '*')
                        total_stock_qty = sale_qty * operation_value[0];
                    else if (operator[0] == '/')
                        total_stock_qty = sale_qty / operation_value[0];

                    // ✅ Iterative clamp — replaces the old recursive call that risked stack overflow
                    if (total_stock_qty > maxQty && !isNaN(maxQty)) {
                        if (!imeiNumbers || !imeiNumbers.length) {
                            alert('Quantity exceeds stock quantity!');
                            if (flag) {
                                // Clamp directly to max rather than decrement-and-recurse
                                if (operator[0] == '*')
                                    sale_qty = Math.floor(maxQty / operation_value[0]);
                                else if (operator[0] == '/')
                                    sale_qty = Math.floor(maxQty * operation_value[0]);
                                else
                                    sale_qty = maxQty;

                                sale_qty = Math.max(0, sale_qty);
                            } else {
                                edit();
                                return;
                            }
                        }

                        if (sale_qty === 0) {
                            $row.remove();
                            calculateTotal();
                            return;
                        }
                    }
                }
            }

            $row.find('.qty').val(sale_qty);

            if (!flag) {
                $('#editModal').modal('hide');
            }

            calculateRowProductData(sale_qty);
        }

        function unitConversion() {
            var row_unit_operator = unit_operator[rowindex].slice(0, unit_operator[rowindex].indexOf(","));
            var row_unit_operation_value = unit_operation_value[rowindex].slice(0, unit_operation_value[rowindex].indexOf(","));

            if (row_unit_operator == '*') {
                row_product_price = product_price[rowindex] * row_unit_operation_value;
            } else {
                row_product_price = product_price[rowindex] / row_unit_operation_value;
            }
        }

        function calculateRowProductData(quantity) {
            quantity = parseFloat(quantity);

            // ── Unit conversion ───────────────────────────────────────────────────────
            // 'standard' products may use a sale-unit that differs from the base unit.
            const $row = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')');
            const productTypeVal = $row.find('.product_type').val();
            if (productTypeVal && productTypeVal.trim() == 'standard')
                unitConversion();
            else
                row_product_price = product_price[rowindex];

            // ── Tax calculation (all reads from in-memory arrays — zero extra DOM hits) ─
            let net_unit_price, tax, sub_total, sub_total_unit;

            if (tax_method[rowindex] == 1) {
                // Exclusive tax: price already excludes tax
                net_unit_price = row_product_price - product_discount[rowindex];
                tax = net_unit_price * quantity * (tax_rate[rowindex] / 100);
                sub_total = net_unit_price * quantity + tax;
                sub_total_unit = quantity ? sub_total / quantity : sub_total;
            } else {
                // Inclusive tax: price already includes tax
                sub_total_unit = row_product_price - product_discount[rowindex];
                net_unit_price = (100 / (100 + tax_rate[rowindex])) * sub_total_unit;
                tax = (sub_total_unit - net_unit_price) * quantity;
                sub_total = sub_total_unit * quantity;
            }

            // ── Write all DOM fields via single cached $row reference ─────────────────
            const dp = {{gen_setting()->decimal ?? 2}};
            $row.find('.discount-value').val((product_discount[rowindex] * quantity).toFixed(dp));
            $row.find('.tax-rate').val(tax_rate[rowindex].toFixed(dp));
            $row.find('.net_unit_price').val(net_unit_price.toFixed(dp));
            $row.find('.tax-value').val(tax.toFixed(dp));
            //$row.find('.product-price').text(sub_total_unit.toFixed(dp));
            $row.find('.product-price').val(sub_total_unit.toFixed(dp));
            $row.find('.sub-total').text(sub_total.toFixed(dp));
            $row.find('.subtotal-value').val(sub_total.toFixed(dp));

            calculateTotal();
        }

        function calculateTotal() {
            //Sum of quantity
            var total_qty = 0;
            $("table.order-list tbody .qty").each(function (index) {
                if ($(this).val() == '') {
                    total_qty += 0;
                } else {
                    total_qty += parseFloat($(this).val());
                }
            });
            $('input[name="total_qty"]').val(total_qty);

            //Sum of discount
            var total_discount = 0;
            $("table.order-list tbody .discount-value").each(function () {
                total_discount += parseFloat($(this).val()) || 0;
            });

            $('input[name="total_discount"]').val(total_discount.toFixed({{gen_setting()->decimal}}));

            //Sum of tax
            var total_tax = 0;
            $(".tax-value").each(function () {
                total_tax += parseFloat($(this).val()) || 0;
            });

            $('input[name="total_tax"]').val(total_tax.toFixed({{gen_setting()->decimal}}));

            //Sum of subtotal
            var total = 0;
            $(".sub-total").each(function () {
                total += parseFloat($(this).text());
            });

            if ($('#enable_installment').is(':checked')) {
                $('input[name="total_price"]').val($('input[name="installment_plan[total_amount]"]').val());
            } else {
                $('input[name="total_price"]').val(total.toFixed({{gen_setting()->decimal}}));
            }

            calculateGrandTotal();
        }

        function calculateGrandTotal() {
            var item = $('table.order-list tbody tr:last').index();
            if (item == -1) {
                $('#order-discount-val').val(0);
            }
            var total_qty = parseFloat($('input[name="total_qty"]').val());
            var subtotal = parseFloat($('input[name="total_price"]').val());
            var order_tax = parseFloat($('select[name="order_tax_rate_select"]').val());
            var order_discount_type = $('select[name="order_discount_type_select"]').val();
            var order_discount_value = parseFloat($('input[name="order_discount_value"]').val());

                @if (isset($lims_sale_data))
                                if (isNaN(order_discount_value) || order_discount_value < 0) {
                        order_discount_type = @json($lims_sale_data->order_discount_type);
                        order_discount_value = parseFloat(@json($lims_sale_data->order_discount_value));
                    }
                @endif

            if (!order_discount_value)
                order_discount_value = {{number_format(0, gen_setting()->decimal, '.', '')}};

        if (order_discount_type == 'Flat') {
            if (!currencyChange) {
                var order_discount = parseFloat(order_discount_value);
            }
            else
                var order_discount = parseFloat(order_discount_value * currency['exchange_rate']);
        }
        else
            var order_discount = parseFloat(subtotal * (order_discount_value / 100));

        $("#discount").text(order_discount_value.toFixed({{gen_setting()->decimal}}));
        $('input[name="order_discount"]').val(order_discount);
        $('#order-discount-val').val(order_discount_value);
        $('input[name="order_discount_type"]').val(order_discount_type);
        if (!currencyChange)
            var shipping_cost = parseFloat($('input[name="shipping_cost"]').val());
        else
            var shipping_cost = parseFloat($('input[name="shipping_cost"]').val() * currency['exchange_rate']);
        if (!shipping_cost)
            shipping_cost = {{number_format(0, gen_setting()->decimal, '.', '')}};

        item = ++item + '(' + total_qty + ')';
        order_tax = (subtotal - order_discount) * (order_tax / 100);
        var grand_total = (subtotal + order_tax + shipping_cost) - order_discount;
        $('input[name="grand_total"]').val(grand_total.toFixed({{gen_setting()->decimal}}));

        if ($("#coupon-code").val() != '')
            couponDiscount();
        if (!currencyChange)
            var coupon_discount = parseFloat($('input[name="coupon_discount"]').val());
        else
            var coupon_discount = parseFloat($('input[name="coupon_discount"]').val() * currency['exchange_rate']);
        if (!coupon_discount)
            coupon_discount = {{number_format(0, gen_setting()->decimal, '.', '')}};
        grand_total -= coupon_discount;

        $('#item').text(item);
        $('input[name="item"]').val($('table.order-list tbody tr:last').index() + 1);
        $('#subtotal').text(subtotal.toFixed({{gen_setting()->decimal}}));
        $('#tax').text(order_tax.toFixed({{gen_setting()->decimal}}));
        $('input[name="order_tax"]').val(order_tax.toFixed({{gen_setting()->decimal}}));
        $('#shipping-cost').text(shipping_cost.toFixed({{gen_setting()->decimal}}));
        $('input[name="shipping_cost"]').val(shipping_cost);
        $('#grand-total').text(grand_total.toFixed({{gen_setting()->decimal}}));
        $('#grand-total-m').text(grand_total.toFixed({{gen_setting()->decimal}}));
        $('input[name="grand_total"]').val(grand_total.toFixed({{gen_setting()->decimal}}));
        currencyChange = false;

        saveDataToLocalStorageForCustomerDisplay('clear_no');
        $(document).trigger('credit_limit_check_required');
            }



        function cancel(rownumber) {
            while (rownumber >= 0) {
                product_price.pop();
                wholesale_price.pop();
                product_discount.pop();
                tax_rate.pop();
                tax_name.pop();
                tax_method.pop();
                unit_name.pop();
                unit_operator.pop();
                unit_operation_value.pop();
                $('table.order-list tbody tr:last').remove();
                rownumber--;
            }
            $('input[name="shipping_cost"]').val('');
            $('input[name="order_discount_value"]').val('');
            $('select[name="order_tax_rate_select"]').val(0);
            calculateTotal();
        }

        function resetPOS() {
            cancel($('table.order-list tbody tr:last').index());
            
            $('input[name="sale_id"]').val('');
            $('input[name="draft"]').val('');
            @if($isRestaurantWarehouse)
                $('input[name="sale_status"]').val('5');
            @else
                $('input[name="sale_status"]').val('1');
            @endif
            history.replaceState('', '', posUrlForWarehouse($('#warehouse_id').val()));

            $('#customer_id').val(DEFAULT_CUSTOMER_ID).selectpicker('refresh').trigger('change');
            $('#warehouse_id').val(DEFAULT_WAREHOUSE_ID).selectpicker('refresh');
            $('#biller_id').val(DEFAULT_BILLER_ID).selectpicker('refresh');
            
            $('input[name="order_discount_value"]').val('');
            $('input[name="order_discount"]').val('');
            
            $('input[name="coupon_active"]').val('0');
            $('input[name="coupon_id"]').val('');
            $('input[name="coupon_discount"]').val('');
            
            $('#order-tax-rate-select').val(0).selectpicker('refresh');
            $('input[name="order_tax_rate"]').val('0');
            $('input[name="order_tax"]').val('0');
            
            $('input[name="shipping_cost"]').val('');
            
            $('textarea[name="sale_note"]').val('');
            $('textarea[name="staff_note"]').val('');
            
            @if($isRestaurantWarehouse)
                $('#service_id').val(DEFAULT_SERVICE_ID).selectpicker('refresh').trigger('change');
                $('#waiter_id').val('').selectpicker('refresh');
                $('#table_id').val('').selectpicker('refresh').trigger('change');
                $('#visual-table-btn-text').text("{{ __('db.Select Table') }}");
                $('.visual-table-card, .visual-table-canvas-item').removeClass('selected');
            @endif
            
            if (typeof resetPaymentModalUi === "function") {
                resetPaymentModalUi();
            }
            
            $('.paying_amount').val('');
            $('.paid_amount').val('');
            $('.change').text('0.00');
            
            $('input[name="lims_productcodeSearch"]').val('').focus();
            
            calculateTotal();
        }

        function confirmCancel() {
            playSound();
            if (confirm("Are you sure want to cancel?")) {
                cancel($('table.order-list tbody tr:last').index());
            }
            return false;
        }

        /* ── Intercept .payment-form submit when offline (C9 Fix) ─── */
        /* This must run BEFORE the existing submit handler below      */
        $(document).on('submit', '.payment-form', function (e) {
            if (typeof isAppOnline !== 'undefined' && !isAppOnline) {
                e.preventDefault();
                e.stopImmediatePropagation();
                saveOffline($(this));
                return false;
            }
        });

        $(document).on('submit', '.payment-form', function (e) {
            e.preventDefault();

            var hasEmptyQty = false;
            $("table.order-list tbody .qty").each(function () {
                if ($(this).val() == '') { hasEmptyQty = true; return false; }
            });
            if (hasEmptyQty) { alert('One of products has no quantity!'); return; }

            var rownumber = $('table.order-list tbody tr:last').index();
            if (rownumber < 0) {
                alert("Please insert product to order table!")
            }
            else if (parseFloat($('input[name="total_qty"]').val()) <= 0) {
                alert('Product quantity is 0');
            }
            else {
                @if($isRestaurantWarehouse)
                    if ($('input[name="sale_status"]').val() == 1 || $('input[name="sale_status"]').val() == 5) {
                        $("#submit-btn").prop('disabled', true).html('<span class="spinner-border text-light" role="status"></span>');
                    }
                @else
                    if ($('input[name="sale_status"]').val() == 1) {
                        $("#submit-btn").prop('disabled', true).html('<span class="spinner-border text-light" role="status"></span>');
                    }
                @endif

                $('input[name="paid_by_id"]').val($('select[name="paid_by_id_select"]').val());
                $('select[name="paid_by_id_select[]"]').each(function (index) {
                    $('input[name="paid_by_id[]"]').eq(index).val($(this).val());
                });
                $('input[name="order_tax_rate"]').val($('select[name="order_tax_rate_select"]').val());

                clearPosSubmitError();

                $.ajax({
                    url: $('.payment-form').attr('action'), // The form's action URL
                    type: $('.payment-form').attr('method'), // The form's method (GET or POST)
                    data: $('.payment-form').serialize(), // Serialize the form data
                    success: function (response) {


                        @if($isRestaurantWarehouse)
                            if ($('input[name="sale_status"]').val() == 1 || $('input[name="sale_status"]').val() == 5) {
                        @else
                            if ($('input[name="sale_status"]').val() == 1) {
                        @endif

                                var head = $('head').html();
                                $('.ui-helper-hidden-accessible').css('display', 'none');

                                let whatsappChecked = $('#send_whatsapp').is(':checked');
                                let printChecked = $('#print_invoice').is(':checked');
                                if (whatsappChecked) {
                                    let customer_id = $('select[name="customer_id"]').val();
                                    let customer = lims_customer_list.find(c => c.id == customer_id);
                                    let whatsapp_number = customer?.wa_number?.replace(/\D/g, '') || '';
                                    let link = "{{ url('sales/gen_invoice') }}/" + response;

                                    if (whatsapp_number != '') {
                                        $.ajax({
                                            url: link,
                                            type: 'GET',
                                            success: function (data) {
                                                $.ajax({
                                                    url: "{{ route('whatsapp.send') }}",
                                                    type: "POST",
                                                    data: {
                                                        receiver_phone: [whatsapp_number],
                                                        html_content: data,
                                                        message: "{{ __('db.Invoice') }}",
                                                    },
                                                    success: function (res) {
                                                        console.log("WhatsApp message sent!");
                                                    },
                                                    error: function (xhr) {
                                                        console.log("Failed to send message!");
                                                    }
                                                });
                                            }
                                        });
                                    }

                                }
                                if (printChecked) {
                                    let link = "{{ url('sales/gen_invoice') }}/" + response + "?is_print=true";
                                    $.ajax({
                                        url: link,
                                        type: 'GET',
                                        success: function (data) {
                                            if (data.trim() === 'receipt_printer') {
                                                alert("{{ __('db.The receipt has been successfully printed') }}");
                                                if (!whatsappChecked) { location.replace(posUrlForWarehouse($('#warehouse_id').val())); }
                                            } else if (data.trim() === 'invoice_settings_error') {
                                                alert("{{ __('db.Please select either the 58mm or 80mm template as the default in Invoice Settings') }}");
                                                if (!whatsappChecked) { location.replace(posUrlForWarehouse($('#warehouse_id').val())); }
                                            } else {
                                                $("#submit-btn").prop('disabled', false).html("{{__('db.Complete Sale')}}");
                                                
                                                $('#add-payment').modal('hide');
                                                
                                                let printIframe = document.getElementById('receipt-print-iframe');
                                                if (!printIframe) {
                                                    printIframe = document.createElement('iframe');
                                                    printIframe.id = 'receipt-print-iframe';
                                                    printIframe.style.display = 'none';
                                                    document.body.appendChild(printIframe);
                                                }
                                                
                                                let doc = printIframe.contentWindow.document;
                                                doc.open();
                                                doc.write(data);
                                                doc.close();

                                                printIframe.contentWindow.focus();
                                                printIframe.contentWindow.onafterprint = function(event) {
                                                    resetPOS();
                                                };
                                                
                                                setTimeout(function () {
                                                    printIframe.contentWindow.print();
                                                }, 100);
                                            }
                                        },
                                        error: function (xhr, status, error) {
                                            console.error("Error loading invoice:", error);
                                        }
                                    });
                                }
                                if (!whatsappChecked && !printChecked) {
                                    resetPOS();
                                }

                                $("#submit-btn").prop('disabled', false).html("{{__('db.Complete Sale')}}");
                                $('#add-payment').modal('hide');

                                $.get('{{url("sales/recent-sale")}}', function (data) {
                                    populateRecentSale(data);
                                });
                            }
                            else if ($('input[name="sale_status"]').val() == 3) {
                                $('#pos-layout').prepend('<div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{__("db.Sale successfully added to draft")}}</div>');
                                @if($isRestaurantWarehouse)
                                    $('input[name="sale_status"]').val(5);
                                @else
                                    $('input[name="sale_status"]').val(1);
                                @endif
                                cancel($('table.order-list tbody tr:last').index());
                                $.get('{{url("sales/recent-draft")}}', function (data) {
                                    populateRecentDraft(data);
                                });
                            }

                            saveDataToLocalStorageForCustomerDisplay('clear_all');

                        },
                        error: function(xhr) {
                            console.error('Sale submission failed:', xhr.status, xhr.responseText);

                            if (window.SaleRequestFeedback && typeof window.SaleRequestFeedback.showXhrError === 'function') {
                                window.SaleRequestFeedback.showXhrError(xhr, true);
                            } else {
                                const response = xhr.responseJSON || {};
                                alert(response.message || response.error || @json(__('db.generic_error_please_try_again')));
                            }

                            $("#submit-btn")
                                .prop('disabled', false)
                                .html("{{__('db.Complete Sale')}}");
                        }
                    });
                
            }
        });

        @if($isRestaurantWarehouse)
            $('#service_id').change(function () {
                if ($(this).val() == 1) {
                    $('#table_id').prop('disabled', false);
                    $('#table_id').selectpicker('refresh');

                    $('#visual-table-btn').show();
                    $('#table-select-wrapper').hide();

                    $('#waiter_id').prop('disabled', false);
                    $('#waiter_id').selectpicker('refresh');

                    $('#table_id').prop('required', true);
                    $('#waiter_id').prop('required', true);
                }
                else {
                    $('#table_id').prop('disabled', true);
                    $('#table_id').selectpicker('refresh');

                    $('#visual-table-btn').hide();
                    $('#table-select-wrapper').show();

                    $('#waiter_id').prop('disabled', true);
                    $('#waiter_id').selectpicker('refresh');

                    $('#table_id').prop('required', false);
                    $('#waiter_id').prop('required', false);
                }
            });

        @endif

        // Load suppliers when the add supplier payment button is clicked
        $(document).on('click', '.add-supplier-payment', function () {
            $('#add-supplier-payment form')[0].reset();
            $.ajax({
                url: "{{ route('supplier.all') }}", // Laravel route helper
                type: "GET",
                dataType: "json",
                success: function (response) {
                    let $supplierSelect = $('#supplier_list');
                    $supplierSelect.empty(); // Clear existing options

                    $supplierSelect.append('<option value="">Select Supplier</option>');

                    $supplierSelect.append(response);

                    // Refresh bootstrap-select
                    $supplierSelect.selectpicker('refresh');
                },
                error: function (xhr) {
                    console.error("Error loading suppliers:", xhr.responseText);
                }
            });
        });

        $(document).on('change', '#supplier_list', function () {
            $('input[name="balance"]').val('');
            let supplierId = $(this).val();

            if (supplierId) {
                $.ajax({
                    url: "{{ url('supplier-due') }}/" + supplierId,
                    type: "GET",
                    dataType: "json",
                    success: function (response) {
                        // response[0] = supplier data, response[1] = due
                        let due = response[0];
                        $('input[name="balance"]').val(due);
                    },
                    error: function (xhr) {
                        console.error("Error fetching supplier due:", xhr.responseText);
                        $('input[name="balance"]').val('');
                    }
                });
            } else {
                $('input[name="balance"]').val('');
            }
        });



    </script>

    <script>
        const display = document.querySelector('.display');
        const buttons = document.querySelectorAll('.calculator .btn');

        let currentInput = '';
        let operator = null;
        let previousInput = '';

        function updateDisplay() {
            display.value = currentInput || previousInput || '0';
        }

        function calculate() {
            let result;
            const prev = parseFloat(previousInput);
            const current = parseFloat(currentInput);

            if (isNaN(prev) || isNaN(current)) return;

            switch (operator) {
                case '+':
                    result = prev + current;
                    break;
                case '-':
                    result = prev - current;
                    break;
                case 'x':
                    result = prev * current;
                    break;
                case '÷':
                    result = prev / current;
                    break;
                case '%':
                    result = prev % current;
                    break;
                default:
                    return;
            }
            currentInput = result.toString();
            operator = null;
            previousInput = '';
        }

        buttons.forEach(button => {
            button.addEventListener('click', (e) => {
                const value = e.target.textContent;

                if (e.target.classList.contains('number') || value === '.') {
                    currentInput += value;
                } else if (e.target.classList.contains('operator')) {
                    if (currentInput === '') return;
                    if (previousInput !== '') calculate();
                    operator = value;
                    previousInput = currentInput;
                    currentInput = '';
                } else if (e.target.classList.contains('equals')) {
                    calculate();
                } else if (e.target.classList.contains('ac')) {
                    currentInput = '';
                    operator = null;
                    previousInput = '';
                } else if (e.target.classList.contains('ce')) {
                    currentInput = currentInput.slice(0, -1);
                }

                updateDisplay();
            });
        });

        updateDisplay();

        $('#expense-amount').on('input', function () {
            var value = $(this).val();
            if (value < 0) {
                alert('Amount cannot be negative');
                $(this).val('');
            } else if (isNaN(value)) {
                alert('Please enter a valid number');
                $(this).val('');
            } else {
                var cash_register_id = $("#register-details-btn").data('id');
                if (cash_register_id) {
                    $.ajax({
                        url: '{{url("cash-register/getDetails")}}/' + cash_register_id,
                        type: "GET",
                        success: function (data) {
                            if (parseFloat(value) > parseFloat(data['total_cash'])) {
                                alert("{{__('db.Amount exceeds available balance')}}");
                                $('#expense-amount').val('');
                            }
                        }
                    })
                }
            }
        });

        $('#supplier-amount').on('input', function () {
            var value = $(this).val();
            if (value < 0) {
                alert('Amount cannot be negative');
                $(this).val('');
            } else if (isNaN(value)) {
                alert('Please enter a valid number');
                $(this).val('');
            } else {
                var cash_register_id = $("#register-details-btn").data('id');
                if (cash_register_id) {
                    $.ajax({
                        url: '{{url("cash-register/getDetails")}}/' + cash_register_id,
                        type: "GET",
                        success: function (data) {
                            if (parseFloat(value) > parseFloat(data['total_cash'])) {
                                alert("{{__('db.Amount exceeds available balance')}}");
                                $('#supplier-amount').val('');
                            }
                        }
                    })
                }
            }
        });

        $(document).on("click", "#print-last-receipt", function (e) {
            e.preventDefault();
            let link = $(this).attr('href');
            $.ajax({
                url: link,
                type: 'GET',
                success: function (data) {
                    if (data.trim() === 'receipt_printer') {
                        alert("{{ __('db.The receipt has been successfully printed') }}");
                    } else if (data.trim() === 'invoice_settings_error') {
                        alert("{{ __('db.Please select either the 58mm or 80mm template as the default in Invoice Settings') }}");
                    } else {
                        location.href = link;
                    }
                },
                error: function (xhr, status, error) {
                    console.error("Error loading invoice:", error);
                }
            });
        });

        $('#price_type').on('changed.bs.select', function () {

            let selectedType = $(this).val();

            $.ajax({
                url: "{{ route('set.price.type') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    price_type: selectedType
                },
                success: function (response) {

                    // STORE IN JS GLOBAL VARIABLE
                    window.POS_PRICE_TYPE = response.price_type;

                    console.log("Current price type:", window.POS_PRICE_TYPE);
                }
            });

        });

        /*-------------START CUSTOMER DISPLAY-----------*/
        function openCustomerDisplay(url) {
            var dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : window.screenX;
            var dualScreenTop = window.screenTop != undefined ? window.screenTop : window.screenY;

            var width = screen.width;
            var height = screen.height;

            var left = dualScreenLeft + width;
            var top = dualScreenTop;

            window.customerDisplay = window.open(
                url,
                "customer_display",
                "width=" + width + ",height=" + height + ",top=" + top + ",left=" + left
            );
        }

        // toggle button click
        $(document).on("click", "#customer-display", function (e) {
            e.preventDefault();
            var url = $(this).attr("href");

            if (localStorage.getItem("customer_display_enabled") == "yes") {
                // disable
                localStorage.removeItem("customer_display_enabled");

                $(this)
                    .removeClass("active")
                    .addClass("inactive")
                    .attr("title", "{{ __('db.enable_customer_display') }}");

                // close customer display window
                if (window.customerDisplay && !window.customerDisplay.closed) {
                    window.customerDisplay.close();
                }
                // ✅ interval stop
                clearInterval(customerDisplayInterval);
            } else {
                // enable
                localStorage.setItem("customer_display_enabled", "yes");

                $(this)
                    .removeClass("inactive")
                    .addClass("active")
                    .attr("title", "{{ __('db.disable_customer_display') }}");

                if (!window.customerDisplay || window.customerDisplay.closed) {
                    openCustomerDisplay(url);
                } else {
                    window.customerDisplay.focus();
                }

                // 🔧 interval restart
                customerDisplayInterval = setInterval(function () {
                    if (localStorage.getItem("customer_display_enabled") == "yes") {
                        if (!window.customerDisplay || window.customerDisplay.closed) {
                            openCustomerDisplay($("#customer-display").attr("href"));
                        }
                    }
                }, 3000);
            }

            $(this).tooltip('dispose').tooltip();
        });

        var customerDisplayInterval;

        $(document).ready(function () {
            if (localStorage.getItem("customer_display_enabled") == "yes") {
                $("#customer-display")
                    .removeClass("inactive")
                    .addClass("active")
                    .attr("title", "{{ __('db.disable_customer_display') }}");
                // window check
                if (!window.customerDisplay || window.customerDisplay.closed) {
                    openCustomerDisplay($("#customer-display").attr("href"));
                }

            } else {
                $("#customer-display")
                    .removeClass("active")
                    .addClass("inactive")
                    .attr("title", "{{ __('db.enable_customer_display') }}");
            }

            $("#customer-display").tooltip('dispose').tooltip();

            // auto reopen interval
            customerDisplayInterval = setInterval(function () {
                if (localStorage.getItem("customer_display_enabled") == "yes") {
                    if (!window.customerDisplay || window.customerDisplay.closed) {
                        openCustomerDisplay($("#customer-display").attr("href"));
                    }
                }
            }, 3000);
        });
        // POS window close হলে customer display close
        window.addEventListener("beforeunload", function () {
            if (window.customerDisplay && !window.customerDisplay.closed) {
                window.customerDisplay.close();
            }
        });
        /*-------------END CUSTOMER DISPLAY-----------*/

        $('#add-payment').on('shown.bs.modal', function (e) {
            saveDataToLocalStorageForCustomerDisplay('clear_no');
        });
        $('#add-payment').on('hidden.bs.modal', function (e) {
            saveDataToLocalStorageForCustomerDisplay('clear_partial');
        });

        function saveDataToLocalStorageForCustomerDisplay(is_clear_local_storage) {
            if (is_clear_local_storage == 'clear_all') {
                localStorage.setItem("customer_display_data_array", JSON.stringify([]));
                return false;
            }

            let products = [];

            $("#myTable tbody tr").each(function () {
                let name = $(this).find(".product-title strong").clone()
                    .children("svg").remove().end()
                    .text().trim();

                let price = $(this).find(".product-price.d-none.d-md-block").val();
                let qty = $(this).find("input.qty").val();
                let subtotal = $(this).find(".sub-total").text().trim();

                products.push({
                    name: name,
                    price: price,
                    qty: qty,
                    subtotal: subtotal
                });
            });

            let CashReceived = 0;
            $("input[name='paying_amount[]']").each(function () {
                let val = parseFloat($(this).val()) || 0.00;
                CashReceived += val;
            });
            CashReceived = CashReceived.toFixed({{ gen_setting()->decimal }});

            let customer_display_data_array = {
                customer: $("#customer_id option:selected").text(),
                products: products,
                item: $("#item").text(),
                subtotal: $("#subtotal").text(),
                discount: $("#discount").text(),
                couponText: $("#coupon-text").text(),
                tax: $("#tax").text(),
                shippingCost: $("#shipping-cost").text(),
                totalPayable: $("#grand-total").text(),
                CashReceived: CashReceived,
                totalPaying: $(".total_paying").text(),
                change: $(".change").text(),
                due: $(".due").text(),
            };

            if (is_clear_local_storage == 'clear_partial') {
                customer_display_data_array.CashReceived = (0).toFixed({{ gen_setting()->decimal }});
                customer_display_data_array.totalPaying = (0).toFixed({{ gen_setting()->decimal }});
                customer_display_data_array.change = (0).toFixed({{ gen_setting()->decimal }});
                customer_display_data_array.due = (0).toFixed({{ gen_setting()->decimal }});

            }

            localStorage.setItem("customer_display_data_array", JSON.stringify(customer_display_data_array));
        }
        /*-------------End Customer Display-----------*/

    </script>

    <script>
        const POS_SHORTCUTS = {
            focus_customer: {
                keys: 'shift+c',
                action: () => {
                    $('#customer_id').selectpicker('toggle');
                }
            },
            draft_sale: {
                keys: 'shift+d',
                action: () => $('#draft-btn').click()
            },
            discount: {
                keys: 'shift+e',
                action: () => {
                    $('#order-discount-val').focus().select();
                }
            },
            add_payment: {
                keys: 'shift+f',
                action: () => $('#main-pay-btn').click()
            },
            coupon_modal: {
                keys: 'shift+k',
                action: () => {
                    $('#coupon-code').focus().select();
                }
            },
            print_last_receipt: {
                keys: 'shift+p',
                action: () => $('#print-last-receipt').click()
            },
            shipping_cost: {
                keys: 'shift+q',
                action: () => {
                    $('#shipping-cost-val').focus().select();
                }
            },
            cash_register_details: {
                keys: 'shift+r',
                action: () => $('#register-details-btn').click()
            },
            focus_search: {
                keys: 'shift+s',
                action: () => $('#product-search-input').focus()
            },
            order_tax: {
                keys: 'shift+x',
                action: () => {
                    $('#order-tax-rate-select').focus();
                }
            },
            @if($isRestaurantWarehouse)
                focus_table: {
                    keys: 'shift+t',
                    action: () => {
                        $('#table_id').selectpicker('toggle');
                    }
                },
                focus_waiter: {
                    keys: 'shift+w',
                    action: () => {
                        $('#waiter_id').selectpicker('toggle');
                    }
                },
            @endif
            };

        Object.values(POS_SHORTCUTS).forEach(shortcut => {
            Mousetrap.bind(shortcut.keys, function (e) {
                e.preventDefault();
                shortcut.action();
                return false;
            });
        });

        Mousetrap.stopCallback = function (e, element) {

            // Allow Enter key for barcode scanner
            if (e.key === "Enter") return false;

            // Disable shortcuts inside input fields
            return element.tagName === 'INPUT' ||
                element.tagName === 'TEXTAREA' ||
                element.isContentEditable;
        };

        function formatActionName(key) {
            return key
                .replace(/_/g, ' ')
                .replace(/\b\w/g, l => l.toUpperCase());
        }

        function formatKeys(keys) {
            return keys
                .replace('shift+', 'Shift + ')
                .replace('ctrl+', 'Ctrl + ')
                .toUpperCase();
        }

        function renderShortcutDropdown() {

            let container = document.getElementById('shortcut-list');
            container.innerHTML = '';

            Object.entries(POS_SHORTCUTS).forEach(([key, value]) => {

                let item = document.createElement('div');
                item.className = 'dropdown-item d-flex justify-content-between align-items-center';

                item.innerHTML = `
                        <span>${formatActionName(key)}</span>
                        <span class="badge badge-light border">${formatKeys(value.keys)}</span>
                    `;

                container.appendChild(item);
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            renderShortcutDropdown();
        });

    </script>

    <!-- this script is for category / brand search in pos -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {

            document.querySelectorAll(".live-filter").forEach(function (input) {

                input.addEventListener("keyup", function () {

                    let searchValue = this.value.toLowerCase().trim();
                    let targetSelector = this.getAttribute("data-target");
                    let items = document.querySelectorAll(targetSelector);

                    items.forEach(function (item) {

                        let name = item.getAttribute("data-name") || '';

                        if (name.includes(searchValue)) {
                            item.style.display = "";
                        } else {
                            item.style.display = "none";
                        }

                    });

                });

            });

        });
    </script>

    <script>
        /* ============================================================
           POS OFFLINE MODE
           ============================================================ */
        (function () {
            'use strict';

            var STORAGE_KEY = 'pos_offline_sales';
            var isAppOnline = navigator.onLine;
            window.isAppOnline = isAppOnline;
            var isSyncing = false;

            function setOnlineState(online) {
                if (online) {
                    $('#offline-cloud-wrap').removeClass('offline').addClass('online').attr('title', 'Online Sales');
                    if (!isAppOnline) {
                        isAppOnline = true;
                        window.isAppOnline = true;
                        goOnline();
                    }
                } else {
                    $('#offline-cloud-wrap').removeClass('online').addClass('offline').attr('title', 'Offline Sales');
                    if (isAppOnline) {
                        isAppOnline = false;
                        window.isAppOnline = false;
                        goOffline();
                    }
                }
            }

            function checkConnectivity() {
                if (!navigator.onLine) {
                    setOnlineState(false);
                    return;
                }
                $.ajax({
                    url: '{{url("favicon.ico")}}',
                    type: 'HEAD',
                    cache: false,
                    timeout: 5000,
                    success: function() {
                        setOnlineState(true);
                        if (offlineCount() > 0) {
                            syncOfflineSales();
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 0) {
                            setOnlineState(false);
                        } else {
                            setOnlineState(true);
                            if (offlineCount() > 0) {
                                syncOfflineSales();
                            }
                        }
                    }
                });
            }

            // Periodic connectivity check
            setInterval(checkConnectivity, 10000);

            /* ── Helpers ──────────────────────────────────────── */
            function getOfflineSales() {
                try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'); }
                catch (e) { return []; }
            }
            function setOfflineSales(arr) {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(arr));
            }
            function offlineCount() {
                return getOfflineSales().length;
            }
            function refreshBadge() {
                var count = offlineCount();
                var $b = $('#offline-sale-badge');
                if (count > 0) { 
                    $b.text(count).addClass('show'); 
                }
                else { 
                    $b.text('0').removeClass('show'); 
                }
            }
            function offlineMsg() {
                if (isAppOnline) {
                    return 'Online Mode';
                }
                var n = offlineCount();
                return n > 0
                    ? 'Offline Mode. ' + n + ' sale' + (n !== 1 ? 's' : '') + ' saved offline.'
                    : 'Offline mode';
            }

            /* ── Show / hide bars ─────────────────────────────── */
            function goOffline() {
                $('body').addClass('pos-offline');
                $('.pos-offline-hide').hide();
                $('#register-details-btn').addClass('d-none');
                refreshBadge();
                posToast('Offline mode. Sales will be saved locally.', '#fff3cd', '#856404');
            }
            function goOnline() {
                $('body').removeClass('pos-offline');
                $('.pos-offline-hide').show();
                refreshBadge();
            }

            /* ── Browser connectivity events ─────────────────── */
            window.addEventListener('offline', function() {
                setOnlineState(false);
            });
            window.addEventListener('online', checkConnectivity);

            /* ── Cloud button click ───────────────────────────── */
            $(document).on('click', '#offline-cloud-wrap', function () {
                var msg = offlineMsg();
                if (isAppOnline) {
                    posToast(msg, '#d1e7dd', '#0f5132');
                } else {
                    posToast(msg, '#fff3cd', '#856404');
                }
            });

            /* ── Intercept .payment-form submit when offline ─── */
            /* Moved up to fix event registration order (C9) */
            window.saveOffline = function($form){
                /* Set paid_by_id values (mirrors existing logic) */
                $('input[name="paid_by_id"]').val($('select[name="paid_by_id_select"]').val());
                $('select[name="paid_by_id_select[]"]').each(function (i) {
                    $('input[name="paid_by_id[]"]').eq(i).val($(this).val());
                });
                $('input[name="order_tax_rate"]').val($('select[name="order_tax_rate_select"]').val());

                var entry = {
                    id: Date.now(),
                    ts: new Date().toISOString(),
                    formData: $form.serialize()
                };
                var sales = getOfflineSales();
                sales.push(entry);
                setOfflineSales(sales);
                refreshBadge();

                /* Close payment modal */
                $('#add-payment').modal('hide');

                /* Show green success toast */
                posToast('Sale saved offline. It will sync when you are back online.', '#d1e7dd', '#0f5132');

                /* Check if print invoice was requested */
                var printChecked = $('#print_invoice').is(':checked');
                if (printChecked) {
                    printOfflineReceipt();
                }

                /* Clear the cart using existing POS cancel function */
                setTimeout(function() {
                    cancel($('table.order-list tbody tr:last').index());
                }, 400);
            }

            function posToast(msg, bg, color) {
                $('.pos-offline-toast').remove();
                var $t = $(
                    '<div class="hidden-print pos-offline-toast" style="position:fixed;top:60px;right:20px;z-index:999999;' +
                    'min-width:300px;max-width:380px;padding:14px 40px 14px 16px;' +
                    'border-radius:8px;background:' + bg + ';color:' + color + ';' +
                    'box-shadow:0 4px 16px rgba(0,0,0,.15);animation:posSlideDown .3s ease;">' +
                    '<strong>Success</strong><br><span style="font-size:13px;">' + msg + '</span>' +
                    '<button onclick="this.parentElement.remove()" ' +
                    'style="position:absolute;top:8px;right:10px;background:none;border:none;' +
                    'font-size:20px;color:' + color + ';cursor:pointer;">×</button></div>'
                );
                $('body').append($t);
                setTimeout(function () { $t.remove(); }, 5000);
            }

            /* ── Sync offline sales when back online ──────────── */
            function syncOfflineSales() {
                if (isSyncing) return;
                var sales = getOfflineSales();

                if (!sales.length) {
                    posToast('Internet is back.', '#d1e7dd', '#0f5132');
                    return;
                }

                isSyncing = true;
                var total   = sales.length;
                var synced  = 0;
                var failed  = [];
                var token   = $('meta[name="csrf-token"]').attr('content') || '';

                posToast('Syncing ' + total + ' offline sale(s)...', '#d1e7dd', '#0f5132');

                function doNext(i) {
                    if (i >= sales.length) {
                        setOfflineSales(failed);
                        refreshBadge();
                        if (failed.length === 0) {
                            posToast('All offline sales synced successfully!', '#d1e7dd', '#0f5132');
                        } else {
                            posToast(failed.length + ' sales failed to sync.', '#f8d7da', '#842029');
                        }
                        isSyncing = false;
                        return;
                    }

                    var sale = sales[i];
                    /* Append fresh CSRF token to serialized form data */
                    var postData = sale.formData + '&_token=' + encodeURIComponent(token);

                    $.ajax({
                        url:  '{{ route("sales.store") }}',
                        type: 'POST',
                        data: postData,
                        success: function () { synced++; doNext(i + 1); },
                        error:   function (xhr) { 
                            console.error("Sync failed for a sale:", xhr.responseText);
                            if (xhr.status === 0) {
                                setOnlineState(false);
                                isSyncing = false;
                                return;
                            }
                            failed.push(sale); 
                            doNext(i + 1); 
                        }
                    });
                }
                doNext(0);
            }

            /* ── Init on page load ────────────────────────────── */
            $(document).ready(function () {
                refreshBadge();
                checkConnectivity();
            });

            /* ── IndexedDB Offline Product Cache ───────────────── */
            window.POS_DB = null;
            var dbReq = indexedDB.open('posOfflineDB', 1);
            dbReq.onupgradeneeded = function(event) {
                window.POS_DB = event.target.result;
                if (!window.POS_DB.objectStoreNames.contains('products')) {
                    window.POS_DB.createObjectStore('products', { keyPath: 'search_code' });
                }
            };
            dbReq.onsuccess = function(event) {
                window.POS_DB = event.target.result;
                // On load, fetch products for currently selected warehouse if online
                if (isAppOnline) {
                    var whId = $('select[name="warehouse_id"]').val();
                    if (whId) fetchAndCacheWarehouseProducts(whId);
                }
            };

            function fetchAndCacheWarehouseProducts(warehouse_id) {
                if (!isAppOnline) return;
                if (!warehouse_id) return;

                $.ajax({
                    url: '{{url("sales/offline_products")}}/' + warehouse_id,
                    type: 'GET',
                    success: function(res) {
                        if (window.POS_DB) {
                            var tx = window.POS_DB.transaction('products', 'readwrite');
                            var store = tx.objectStore('products');
                            store.clear(); 

                            Object.keys(res).forEach(function(code) {
                                store.put(res[code]);
                            });

                            tx.oncomplete = function() {
                                console.log('Offline products cached for warehouse ' + warehouse_id);
                            };
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 0) {
                            setOnlineState(false);
                        }
                    }
                });
            }

            $('select[name="warehouse_id"]').on('change', function() {
                fetchAndCacheWarehouseProducts($(this).val());
            });

            /* ── Offline Product Filter & Load More ────────────── */
            window.offlineProductList = [];
            window.offlineProductPage = 1;

            function buildOfflineResponse(products, page) {
                var perPage = 15;
                var start = (page - 1) * perPage;
                var paginated = products.slice(start, start + perPage);

                var response = { data: { name:[], code:[], is_imei:[], is_embeded:[], image:[], qty:[], price:[], batch:[] }, next_page_url: null };
                if (products.length > start + perPage) {
                    response.next_page_url = 'offline-page-' + (page + 1);
                }

                paginated.forEach(function(p) {
                    response.data.name.push(p.actual_name || p.name);
                    response.data.code.push(p.search_code || p.code);
                    response.data.is_imei.push(p.is_imei);
                    var imgTokens = (p.image ? p.image.toString().split(',') : []).map(function(s) { return s.trim(); }).filter(function(s) { return s.length > 0; });
                    var img = imgTokens.length > 0 ? imgTokens[0] : 'zummXD2dvAtI.png';
                    if (img === '') img = 'zummXD2dvAtI.png';
                    response.data.image.push(img);
                    response.data.qty.push(p.warehouse_qty || p.qty || 0);
                    response.data.price.push(p.price);
                    response.data.batch.push(p.product_batch_id || null);
                });
                return response;
            }

            window.filterOfflineProducts = function(key, value) {
                if (!window.POS_DB) return;
                var tx = window.POS_DB.transaction('products', 'readonly');
                var store = tx.objectStore('products');
                var req = store.getAll();
                req.onsuccess = function(e) {
                    var products = e.target.result;
                    if (key === 'all') {
                        window.offlineProductList = products;
                    } else {
                        window.offlineProductList = products.filter(function(p) {
                            return p[key] == value;
                        });
                    }
                    // Sort by actual_name or name ascending
                    window.offlineProductList.sort(function(a, b) {
                        var nameA = (a.actual_name || a.name || '').toLowerCase();
                        var nameB = (b.actual_name || b.name || '').toLowerCase();
                        if (nameA < nameB) return -1;
                        if (nameA > nameB) return 1;
                        return 0;
                    });
                    window.offlineProductPage = 1;
                    var response = buildOfflineResponse(window.offlineProductList, 1);
                    populateProduct(response);

                    // Trigger mobile featured layout if needed
                    if (isMobile == true && key === 'featured') {
                        $(".product_list_mobile.table-container").show();
                        $('.product_list_mobile').html('');
                        let featured_products = $(".table-container .product-grid").clone();
                        $('.product_list_mobile').html(featured_products);
                    }
                };
            }

            window.loadMoreOfflineProducts = function() {
                if (window.offlineProductList.length === 0) {
                    var tx = window.POS_DB.transaction('products', 'readonly');
                    var store = tx.objectStore('products');
                    var req = store.getAll();
                    req.onsuccess = function(e) {
                        var products = e.target.result;
                        products.sort(function(a, b) {
                            var nameA = (a.actual_name || a.name || '').toLowerCase();
                            var nameB = (b.actual_name || b.name || '').toLowerCase();
                            if (nameA < nameB) return -1;
                            if (nameA > nameB) return 1;
                            return 0;
                        });

                        // Exclude products already in the DOM
                        var existingCodes = [];
                        $('.table-container .product-img').each(function() {
                            existingCodes.push($(this).data('code').toString());
                        });
                        window.offlineProductList = products.filter(function(p) {
                            var code = (p.search_code || p.code).toString();
                            return existingCodes.indexOf(code) === -1;
                        });

                        window.offlineProductPage = 1;
                        var response = buildOfflineResponse(window.offlineProductList, window.offlineProductPage);
                        appendProduct(response);
                    };
                } else {
                    window.offlineProductPage++;
                    var response = buildOfflineResponse(window.offlineProductList, window.offlineProductPage);
                    appendProduct(response);
                }
            }



            /* ── Offline Print Receipt ────────────────────────── */
            window.printOfflineReceipt = function() {

                /* ── Collect cart data ─────────────────────── */
                var cartItems = [];
                $('table.order-list tbody tr').each(function() {
                    /* Clean product name: clone the first <strong> inside .product-title,
                       strip the SVG edit icon, then read text only */
                    var $titleStrong = $(this).find('.product-title strong:first').clone();
                    $titleStrong.find('svg').remove();
                    var name = $titleStrong.text().trim();
                    if (!name) return;

                    var qty = $(this).find('input.qty').val() || '';

                    /* Unit price: use the standalone td (.product-price.d-none.d-md-block),
                       NOT the hidden <strong> inside product-title cell.
                       Fallback to net_unit_price hidden input. */
                    var unitPrice = $(this).find('td.product-price').val();
                    if (!unitPrice) unitPrice = $(this).find('input.net_unit_price').val() || '';

                    var subtotal = $(this).find('td.sub-total').text().trim();
                    cartItems.push({ name: name, qty: qty, price: unitPrice, subtotal: subtotal });
                });

                var grandTotal = $('input[name="grand_total"]').val() || '0';
                var totalQty   = $('input[name="total_qty"]').val() || '';
                var paidAmt    = parseFloat($('.total_paying').text()) || 0;
                var change     = parseFloat($('.change').text()) || 0;
                var date       = new Date().toLocaleString();
                var size       = (typeof POS_INVOICE_SIZE !== 'undefined') ? POS_INVOICE_SIZE : 'a4';

                var html = '';

                /* ── Scoped Print styles to override layout.top-head block styles ── */
                html += '<style>';
                html += '    #print-layout .offline-invoice-wrapper {';
                html += '        font-family: "Inter", "Arial", sans-serif !important;';
                html += '    }';
                html += '    #print-layout .offline-invoice-wrapper * {';
                html += '        text-transform: none !important;';
                html += '    }';
                html += '    #print-layout .offline-invoice-wrapper table {';
                html += '        width: 100% !important;';
                html += '        border-collapse: collapse !important;';
                html += '    }';
                html += '    #print-layout .offline-invoice-wrapper table.fixed-layout-table {';
                html += '        table-layout: fixed !important;';
                html += '    }';
                html += '    #print-layout .offline-invoice-wrapper tr {';
                html += '        display: table-row !important;';
                html += '        border-bottom: none !important;';
                html += '    }';
                html += '    #print-layout .offline-invoice-wrapper th,';
                html += '    #print-layout .offline-invoice-wrapper td {';
                html += '        display: table-cell !important;';
                html += '        vertical-align: middle !important;';
                html += '    }';
                html += '    ';
                html += '    /* Thermal receipt specific overrides */';
                html += '    #print-layout .offline-invoice-wrapper.thermal {';
                html += '        font-family: "Courier New", Courier, monospace !important;';
                html += '    }';
                html += '    #print-layout .offline-invoice-wrapper.thermal * {';
                html += '        font-family: "Courier New", Courier, monospace !important;';
                html += '    }';
                html += '    ';
                html += '    @media print {';
                html += '        /* A4 print size overrides */';
                html += '        #print-layout .offline-invoice-wrapper.a4-size * {';
                html += '            font-size: 13px !important;';
                html += '            line-height: 1.5 !important;';
                html += '        }';
                html += '        #print-layout .offline-invoice-wrapper.a4-size th {';
                html += '            font-size: 13px !important;';
                html += '            font-weight: 700 !important;';
                html += '        }';
                html += '        #print-layout .offline-invoice-wrapper.a4-size td {';
                html += '            font-size: 13px !important;';
                html += '        }';
                html += '        ';
                html += '        /* Thermal receipt print overrides */';
                html += '        #print-layout .offline-invoice-wrapper.thermal * {';
                html += '            font-size: 11px !important;';
                html += '            line-height: 1.4 !important;';
                html += '        }';
                html += '        #print-layout .offline-invoice-wrapper.thermal.size-58mm * {';
                html += '            font-size: 10px !important;';
                html += '        }';
                html += '        #print-layout .offline-invoice-wrapper.thermal tr {';
                html += '            display: table-row !important;';
                html += '            border-bottom: none !important;';
                html += '        }';
                html += '        #print-layout .offline-invoice-wrapper.thermal td {';
                html += '            display: table-cell !important;';
                html += '            padding: 3px 0 !important;';
                html += '        }';
                html += '    }';
                html += '</style>';

                /* ═══════════════════════════════════════════════
                   A4 Layout
                 ═══════════════════════════════════════════════ */
                if (size === 'a4') {
                    html += '<div class="offline-invoice-wrapper a4-size" style="max-width:740px;margin:0 auto;font-family:\'Inter\',\'Arial\',sans-serif;font-size:13px;color:#111;padding:20px 30px;">';

                    /* Header */
                    html += '<div style="text-align:center;border-bottom:2px solid #111;padding-bottom:12px;margin-bottom:16px;">';
                    html += '<h2 style="margin:0;font-size:22px;font-weight:700;letter-spacing:1px;">OFFLINE INVOICE</h2>';
                    html += '<p style="margin:4px 0 0;font-size:12px;color:#555;">Pending Sync &bull; ' + date + '</p>';
                    html += '</div>';

                    /* Items table — fixed layout so columns stay proportional */
                    html += '<table class="fixed-layout-table" style="width:100%;border-collapse:collapse;margin-bottom:12px;table-layout:fixed;">';
                    /* colgroup: Item=auto, Qty=55px, UnitPrice=110px, Subtotal=110px */
                    html += '<colgroup>';
                    html += '<col style="width:auto;">';
                    html += '<col style="width:55px;">';
                    html += '<col style="width:110px;">';
                    html += '<col style="width:110px;">';
                    html += '</colgroup>';
                    html += '<thead><tr style="background:#f3f3f3;">';
                    html += '<th style="text-align:left;padding:7px 8px;border:1px solid #ddd;overflow:hidden;">Item</th>';
                    html += '<th style="text-align:center;padding:7px 8px;border:1px solid #ddd;">Qty</th>';
                    html += '<th style="text-align:right;padding:7px 8px;border:1px solid #ddd;">Unit Price</th>';
                    html += '<th style="text-align:right;padding:7px 8px;border:1px solid #ddd;">Subtotal</th>';
                    html += '</tr></thead><tbody>';
                    for (var i = 0; i < cartItems.length; i++) {
                        var bg = (i % 2 === 0) ? '#fff' : '#fafafa';
                        html += '<tr style="background:' + bg + ';">';
                        html += '<td style="padding:6px 8px;border:1px solid #ddd;word-break:break-word;overflow-wrap:break-word;">' + cartItems[i].name + '</td>';
                        html += '<td style="padding:6px 8px;border:1px solid #ddd;text-align:center;">' + cartItems[i].qty + '</td>';
                        html += '<td style="padding:6px 8px;border:1px solid #ddd;text-align:right;">' + cartItems[i].price + '</td>';
                        html += '<td style="padding:6px 8px;border:1px solid #ddd;text-align:right;font-weight:600;">' + cartItems[i].subtotal + '</td>';
                        html += '</tr>';
                    }
                    html += '</tbody></table>';

                    /* Totals block — right-aligned, 280px wide */
                    html += '<table style="width:280px;margin-left:auto;border-collapse:collapse;">';
                    html += '<tr style="background:#f3f3f3;"><td style="padding:6px 10px;border:1px solid #ddd;font-weight:600;">Grand Total</td><td style="padding:6px 10px;border:1px solid #ddd;text-align:right;font-weight:700;">' + grandTotal + '</td></tr>';
                    if (paidAmt > 0) {
                        html += '<tr><td style="padding:5px 10px;border:1px solid #ddd;">Paid Amount</td><td style="padding:5px 10px;border:1px solid #ddd;text-align:right;">' + paidAmt.toFixed(2) + '</td></tr>';
                        html += '<tr><td style="padding:5px 10px;border:1px solid #ddd;">Change</td><td style="padding:5px 10px;border:1px solid #ddd;text-align:right;">' + change.toFixed(2) + '</td></tr>';
                    }
                    html += '</table>';

                    /* Footer */
                    html += '<div style="text-align:center;margin-top:24px;border-top:1px dashed #aaa;padding-top:10px;color:#555;font-size:12px;">';
                    html += '<strong>Thank you for your purchase!</strong><br><small>This is an offline receipt. It will sync when back online.</small>';
                    html += '</div>';

                    html += '</div>';


                /* ═══════════════════════════════════════════════
                   58mm Thermal Layout
                 ═══════════════════════════════════════════════ */
                } else if (size === '58mm') {
                    html += '<div class="offline-invoice-wrapper thermal size-58mm" style="width:200px;margin:0 auto;font-family:\'Courier New\',monospace;font-size:10px;color:#000;line-height:1.4;">';

                    /* Header */
                    html += '<div style="text-align:center;border-bottom:1px dashed #000;padding-bottom:4px;margin-bottom:4px;">';
                    html += '<strong style="font-size:13px;">OFFLINE RECEIPT</strong><br>';
                    html += '<span>' + date + '</span>';
                    html += '</div>';

                    /* Items */
                    for (var i = 0; i < cartItems.length; i++) {
                        html += '<div style="margin:2px 0;">';
                        html += '<div>' + cartItems[i].name + '</div>';
                        var line = cartItems[i].qty + ' x ' + cartItems[i].price;
                        var pad  = 26 - line.length - cartItems[i].subtotal.length;
                        if (pad < 1) pad = 1;
                        html += '<div><span>' + line + '</span><span style="float:right;">' + cartItems[i].subtotal + '</span></div>';
                        html += '<div style="clear:both;"></div>';
                        html += '</div>';
                    }

                    /* Totals */
                    html += '<div style="border-top:1px dashed #000;margin-top:4px;padding-top:4px;">';
                    html += '<div><span>Grand Total</span><span style="float:right;font-weight:bold;">' + grandTotal + '</span></div>';
                    html += '<div style="clear:both;"></div>';
                    if (paidAmt > 0) {
                        html += '<div><span>Paid</span><span style="float:right;">' + paidAmt.toFixed(2) + '</span></div>';
                        html += '<div style="clear:both;"></div>';
                        html += '<div><span>Change</span><span style="float:right;">' + change.toFixed(2) + '</span></div>';
                        html += '<div style="clear:both;"></div>';
                    }
                    html += '</div>';

                    /* Footer */
                    html += '<div style="text-align:center;border-top:1px dashed #000;margin-top:4px;padding-top:4px;font-size:9px;">';
                    html += 'Thank you!<br>Pending Server Sync';
                    html += '</div>';

                    html += '</div>';

                /* ═══════════════════════════════════════════════
                   80mm Thermal Layout
                 ═══════════════════════════════════════════════ */
                } else {
                    /* 80mm fallback */
                    html += '<div class="offline-invoice-wrapper thermal size-80mm" style="width:280px;margin:0 auto;font-family:\'Courier New\',monospace;font-size:11px;color:#000;line-height:1.5;">';

                    /* Header */
                    html += '<div style="text-align:center;border-bottom:1px dashed #000;padding-bottom:5px;margin-bottom:5px;">';
                    html += '<strong style="font-size:15px;">OFFLINE RECEIPT</strong><br>';
                    html += '<span style="font-size:10px;">' + date + '</span>';
                    html += '</div>';
                    /* Items table */
                    html += '<table style="width:100%;border-collapse:collapse;">';
                    for (var i = 0; i < cartItems.length; i++) {
                        html += '<tr>';
                        html += '<td style="padding:5px 0;vertical-align:top;width:60%;border-top:1px dotted #aaa;">' + cartItems[i].name + '<br><small>' + cartItems[i].qty + ' &times; ' + cartItems[i].price + '</small></td>';
                        html += '<td style="padding:5px 0;text-align:right;vertical-align:bottom;border-top:1px dotted #aaa;">' + cartItems[i].subtotal + '</td>';
                        html += '</tr>';
                    }
                    html += '</table>';

                    /* Totals */
                    html += '<table style="width:100%;border-collapse:collapse;border-top:1px dashed #000;margin-top:4px;">';
                    html += '<tr><th style="text-align:left;padding:4px 0;">Grand Total</th><th style="text-align:right;padding:4px 0;">' + grandTotal + '</th></tr>';
                    if (paidAmt > 0) {
                        html += '<tr><td style="padding:2px 0;">Paid</td><td style="text-align:right;padding:2px 0;">' + paidAmt.toFixed(2) + '</td></tr>';
                        html += '<tr><td style="padding:2px 0;">Change</td><td style="text-align:right;padding:2px 0;">' + change.toFixed(2) + '</td></tr>';
                    }
                    html += '</table>';

                    /* Footer */
                    html += '<div style="text-align:center;border-top:1px dashed #000;margin-top:6px;padding-top:5px;font-size:10px;">';
                    html += '<strong>Thank you for shopping with us!</strong><br>';
                    html += '<small>Pending Sync to Server</small>';
                    html += '</div>';

                    html += '</div>';
                }

                /* ── Inject & print ────────────────────────── */
                $('#print-layout').html(html);
                setTimeout(function() {
                    window.print();
                    $('#print-layout').html('');
                }, 500);
            }

        })();

        // Variant Modal Logic
        $(document).on('click', '.variant-parent-btn', function() {
            var variantsData = $(this).attr('data-variants');
            var parentName = $(this).attr('title') || $(this).find('p').text();
            if (parentName) {
                $('#variant-modal-product-name').text(parentName);
            }
            if (variantsData) {
                var variants = JSON.parse(variantsData);
                var html = '';
                var inStockText = "{{trans('file.In stock') == 'file.In stock' ? 'In stock' : trans('file.In stock')}}";
                var outOfStockText = "{{trans('file.Out of stock') == 'file.Out of stock' ? 'Out of stock' : trans('file.Out of stock')}}";
                var selectText = "{{trans('file.Select') == 'file.Select' ? 'Select' : trans('file.Select')}}";
                
                $.each(variants, function(index, variant) {
                    var variantOnlyName = variant.name;
                    var match = variant.name.match(/\[(.*?)\]/);
                    if (match && match[1]) {
                        variantOnlyName = match[1];
                    }
                    
                    var isAvailable = variant.qty > 0 || parseFloat(variant.qty) > 0;
                    var stockStr = isAvailable ? variant.qty + ' ' + inStockText : outOfStockText;
                    var rowClass = isAvailable ? 'variant-row' : 'text-muted';
                    var actionBtn = isAvailable ? '<button class="btn btn-sm btn-primary py-0 px-2">' + selectText + '</button>' : '';
                    var pointerStyle = isAvailable ? 'cursor:pointer;' : 'cursor:not-allowed; opacity: 0.6;';
                    
                    html += '<tr class="' + rowClass + '" style="' + pointerStyle + '" data-variant=\'' + JSON.stringify(variant) + '\' tabindex="' + (isAvailable ? '0' : '-1') + '">';
                    html += '<td class="font-weight-bold" style="font-size: 1.1em;">' + variantOnlyName + '</td>';
                    html += '<td class="text-secondary">' + variant.code + '</td>';
                    html += '<td class="' + (isAvailable ? '' : 'text-danger') + '">' + stockStr + '</td>';
                    html += '<td>' + (variant.price !== null ? parseFloat(variant.price).toFixed(2) : 'N/A') + '</td>';
                    html += '<td class="text-right">' + actionBtn + '</td>';
                    html += '</tr>';
                });
                $('#variant-modal-table-body').html(html);
                $('#variantSelectionModal').modal('show');
            }
        });

        $(document).on('click keydown', '.variant-row', function(e) {
            if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') return;
            if (e.type === 'keydown') e.preventDefault();
            
            if ($(this).hasClass('locked')) return;
            $(this).addClass('locked');
            var $row = $(this);
            var variantData = JSON.parse($(this).attr('data-variant'));
            
            var searchData = {
                code: variantData.code,
                product_id: variantData.product_id,
                product_variant_id: variantData.product_variant_id,
                variant_id: variantData.variant_id,
                warehouse_id: variantData.warehouse_id,
                qty: variantData.qty,
                imei: variantData.is_imei,
                embedded: variantData.is_embeded,
                batch: variantData.batch,
                price: variantData.price !== null ? variantData.price : ''
            };
            
            try {
                productSearch(searchData);
                $('#variantSelectionModal').modal('hide');
            } catch (e) {
                console.error(e);
            } finally {
                setTimeout(function() {
                    $row.removeClass('locked');
                }, 500);
            }
        });

        // UI State Handlers for Payment Modes
        function resetPaymentModalUi() {
            ismultiplepayment = 0;
            // General reset that brings it to a neutral state
            $('.pos-payment-grid').show();
            $('#payment-select-row').show();
            $('.paying-amount-container').show(); // Cash Received
            
            // Multiple payment specific
            $('select[name="paid_by_id_select[]"]').closest('.row').find('.paid_amount').parent().addClass('d-none');
            $('select[name="paid_by_id_select[]"]').parent().parent().addClass('d-none');
            $('.add-more').parent().addClass('d-none');

            // Submit text
            $('#submit-btn').html("{{__('db.Complete Sale')}}");
        }

        function showNormalPayUi() {
            resetPaymentModalUi();
            ismultiplepayment = 0;
            // In normal pay, payment methods grid is active.
            // If Cash was selected previously, ensure it's still active.
            if ($('#cash-btn').hasClass('active-method')) {
                setTimeout(function() {
                    $('.paying_amount:visible').first().focus().select();
                }, 300);
            }
        }

        function showMultiplePaymentUi() {
            resetPaymentModalUi();
            ismultiplepayment = 1;
            $('.pos-payment-grid').hide(); // Hide the top grid
            $('select[name="paid_by_id_select[]"]').parent().parent().removeClass('d-none'); // Show per-row Paid By selects
            $('.add-more').parent().removeClass('d-none'); // Show Add More Payment
            $('select[name="paid_by_id_select[]"]').closest('.row').find('.paid_amount').parent().removeClass('d-none');
            
            // Note: Paid amount and paying amount (cash received) stay visible
            $('.paying-amount-container').show();
        }

        function showCreditSaleUi() {
            resetPaymentModalUi();
            ismultiplepayment = 0;
            $('.pos-payment-grid').hide(); // Hide top grid
            $('select[name="paid_by_id_select[]"]').parent().parent().addClass('d-none'); // Hide per-row
            $('.paying-amount-container').hide(); // Hide cash received
            $('.add-more').parent().addClass('d-none'); // Hide add more
            $('#submit-btn').html("{{__('db.Complete Credit Sale')}}");
        }

        $('#main-pay-btn').on('click', function() {
            showNormalPayUi();
        });

        $('#multiple-payment-btn').on('click', function() {
            showMultiplePaymentUi();
        });

        $('#credit-sale-btn').on('click', function() {
            showCreditSaleUi();
        });

        // Focus Cash input when Cash is selected in Normal mode
        $(document).on('click', '#cash-btn', function() {
            setTimeout(function() {
                $('.paying_amount:visible').first().focus().select();
            }, 100);
        });
        // Visual Table Logic
        function openVisualTableModal() {
            $('#visual-table-modal').modal('show');
            loadFloorplanStatus();
        }

        function loadFloorplanStatus() {
            var warehouse_id = $('#warehouse_id').val();
            $.ajax({
                url: '{{route("tables.floorplan_status")}}',
                type: 'GET',
                data: { warehouse_id: warehouse_id },
                success: function(response) {
                    var floors = response.floors;
                    var tableStatus = response.tables;
                    
                    var tabsHtml = '';
                    var contentHtml = '';
                    
                    if (floors.length === 0) {
                        tabsHtml = '<li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#floor-default">Default</a></li>';
                        contentHtml = '<div class="tab-pane fade show active" id="floor-default"><div class="row">';
                        // Fallback grid
                        $.each(tableStatus, function(id, table) {
                            contentHtml += renderTableBox(table);
                        });
                        contentHtml += '</div></div>';
                    } else {
                        $.each(floors, function(index, floor) {
                            var activeClass = index === 0 ? 'active' : '';
                            var showClass = index === 0 ? 'show active' : '';
                            tabsHtml += '<li class="nav-item"><a class="nav-link '+activeClass+'" data-toggle="tab" href="#floor-'+floor.id+'">'+floor.name+'</a></li>';
                            
                            contentHtml += '<div class="tab-pane fade '+showClass+'" id="floor-'+floor.id+'">';
                            
                            var floorplan = floor.floorplan || [];
                            var isV2 = floorplan.length > 0 && floorplan[0].hasOwnProperty('layout_version') && floorplan[0].layout_version == 2;
                            var isUnconfigured = false;
                            
                            if (!isV2 && floorplan.length > 0) {
                                isUnconfigured = true;
                                $.each(floorplan, function(i, table) {
                                    if (table.x != 0 || table.y != 0 || table.layout_version) {
                                        isUnconfigured = false;
                                        return false;
                                    }
                                });
                            }
                            
                            if (isUnconfigured) {
                                contentHtml += '<div class="row mt-3">';
                                $.each(floorplan, function(i, floorTable) {
                                    var tableData = tableStatus[floorTable.id];
                                    if (tableData) {
                                        contentHtml += renderTableBox(tableData);
                                    }
                                });
                                contentHtml += '</div>';
                            } else if (isV2) {
                                contentHtml += '<div class="v2-canvas-container" style="position: relative; width: 100%; overflow: hidden; background-color: var(--theme-bg-color); border: 1px solid #ddd; border-radius: 5px; margin-top: 15px;">';
                                contentHtml += '<div class="v2-logical-canvas" style="position: relative; width: 1200px; height: 700px; transform-origin: top left;">';
                                $.each(floorplan, function(i, floorTable) {
                                    if(floorTable.placed !== false) {
                                        var tableData = tableStatus[floorTable.id];
                                        if (tableData) {
                                            contentHtml += renderCanvasTable(floorTable, tableData, true);
                                        }
                                    }
                                });
                                contentHtml += '</div></div>';
                            } else {
                                contentHtml += '<div style="position: relative; width: 100%; height: 600px; border: 1px solid #ddd; border-radius: 5px; margin-top: 15px; overflow: auto; background-color: var(--theme-bg-color);">';
                                $.each(floorplan, function(i, floorTable) {
                                    var tableData = tableStatus[floorTable.id];
                                    if (tableData) {
                                        contentHtml += renderCanvasTable(floorTable, tableData, false);
                                    }
                                });
                                contentHtml += '</div>';
                            }
                            
                            contentHtml += '</div>';
                        });
                    }
                    
                    $('#floor-tabs').html(tabsHtml);
                    $('#floor-tabs-content').html(contentHtml);
                },
                error: function(xhr) {
                    console.error("Error loading floorplan status", xhr);
                }
            });
        }
        
        function getTableColorClass(status, readiness) {
            if (status === 'occupied') {
                if (readiness === 'ready') {
                    return 'bg-warning text-dark font-weight-bold';
                }
                return 'bg-danger text-white';
            }
            return 'bg-success text-white';
        }
        
        function renderTableBox(tableData) {
            var colorClass = getTableColorClass(tableData.status, tableData.readiness);
            var badgeText = tableData.status === 'occupied' ? (tableData.readiness === 'ready' ? 'Ready for Service' : 'Occupied') : 'Available';
            var html = '<div class="col-md-3 col-sm-4 mb-3">';
            html += '<div class="card visual-table-card '+colorClass+'" style="cursor:pointer;" onclick="handleTableClick('+tableData.id+', \''+tableData.status+'\', '+(tableData.sale_id || 'null')+')">';
            html += '<div class="card-body text-center">';
            html += '<h5 class="card-title mb-1">' + tableData.name + '</h5>';
            html += '<span class="badge '+(tableData.status === 'occupied' ? (tableData.readiness === 'ready' ? 'badge-dark' : 'badge-light text-danger') : 'badge-light text-success')+' mb-2">'+badgeText+'</span>';
            if (tableData.status === 'occupied') {
                html += '<p class="card-text mb-0"><small>'+(tableData.item_count || 0)+' items | '+(tableData.total || '0.00')+'</small></p>';
                if (tableData.waiter_name) {
                    html += '<p class="card-text mb-0"><small><i class="ti ti-user"></i> '+tableData.waiter_name+'</small></p>';
                }
            }
            html += '</div></div></div>';
            return html;
        }
        
        function renderCanvasTable(floorTable, tableData, isV2) {
            var colorClass = getTableColorClass(tableData.status, tableData.readiness);
            var shapeClass = (isV2 && floorTable.shape === 'round') ? 'rounded-circle' : 'rounded';
            var badgeText = tableData.status === 'occupied' ? (tableData.readiness === 'ready' ? 'Ready' : 'Occupied') : 'Available';
            var html = '<div class="visual-table-canvas-item '+colorClass+' d-flex align-items-center justify-content-center flex-column text-center '+shapeClass+'" ';
            html += 'style="position:absolute; left: '+floorTable.x+'px; top: '+floorTable.y+'px; width: '+floorTable.width+'px; height: '+floorTable.height+'px; cursor:pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.2);" ';
            html += 'onclick="handleTableClick('+tableData.id+', \''+tableData.status+'\', '+(tableData.sale_id || 'null')+')">';
            html += '<strong>' + tableData.name + '</strong>';
            html += '<small class="badge '+(tableData.status === 'occupied' ? (tableData.readiness === 'ready' ? 'badge-dark' : 'badge-light text-danger') : 'badge-light text-success')+' mt-1">'+badgeText+'</small>';
            if (tableData.status === 'occupied') {
                html += '<small>'+(tableData.item_count || 0)+' items</small>';
            }
            html += '</div>';
            return html;
        }
        
        function handleTableClick(table_id, status, sale_id) {
            if (status === 'available') {
                $('#table_id').val(table_id).selectpicker('refresh').trigger('change');
                var tableName = $('#table_id option:selected').data('name');
                if (tableName) {
                    $('#visual-table-btn-text').text(tableName);
                }
                $('#visual-table-modal').modal('hide');
            } else if (status === 'occupied') {
                if (sale_id) {
                    window.location.href = '{{url("pos")}}' + '/' + sale_id;
                } else {
                    alert('Multiple orders exist on this table. Please use the drafted sales list to resume.');
                }
            }
        }
        
        var modalResizeObserver = null;
        $('#visual-table-modal').on('shown.bs.modal', function () {
            if (modalResizeObserver) modalResizeObserver.disconnect();
            
            function updateScales() {
                $('.v2-canvas-container').each(function() {
                    var container = $(this);
                    var canvas = container.find('.v2-logical-canvas');
                    if (container.width() > 0) {
                        var scale = container.width() / 1200;
                        canvas.css('transform', 'scale(' + scale + ')');
                        container.css('height', (700 * scale) + 'px');
                    }
                });
            }
            
            updateScales();
            
            modalResizeObserver = new ResizeObserver(function() {
                updateScales();
            });
            
            $('.v2-canvas-container').each(function() {
                modalResizeObserver.observe(this);
            });
        });
        
        $('#table_id').change(function() {
            var text = $(this).find('option:selected').data('name');
            if(text) {
                $('#visual-table-btn-text').text(text);
            } else {
                $('#visual-table-btn-text').text('Select Table');
            }
        });

        // Initialize button visibility based on initial service_id
        $(document).ready(function() {
            if ($('#service_id').val() == 1) {
                $('#visual-table-btn').show();
                $('#table-select-wrapper').hide();
                $('#table_id').trigger('change');
            }
        });

        </script>

@if(in_array("payhere",$options) || in_array("moyasar",$options))
<script>
    // Shared QR Payment Modal (PayHere / Moyasar) — Close button
    $(document).on('click', '#payhere-modal-close, #ph-retry-btn', function() {
        $('#payhere-modal').removeClass('ph-visible');
    });
</script>
@endif

@endpush


