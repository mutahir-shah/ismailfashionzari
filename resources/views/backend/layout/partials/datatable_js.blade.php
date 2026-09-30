@php
    $asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
@endphp
<!-- table sorter js-->
@if (Config::get('app.locale') == 'ar')
    <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/datatable/pdfmake_arabic.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/datatable/vfs_fonts_arabic.js') }}"></script>
@else
    <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/datatable/pdfmake.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/datatable/vfs_fonts.js') }}"></script>
@endif
<script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/datatable/jquery.dataTables.min.js') }}"></script>
<script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/datatable/dataTables.bootstrap4.min.js') }}"></script>
<script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/datatable/dataTables.buttons.min.js') }}"></script>
<script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/datatable/jszip.min.js') }}"></script>
<script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/datatable/buttons.bootstrap4.min.js') }}"></script>
<script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/datatable/buttons.colVis.min.js') }}"></script>
<script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/datatable/buttons.html5.min.js') }}"></script>
<script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/datatable/buttons.printnew.js') }}"></script>

<script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/datatable/sum().js') }}"></script>
<script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/datatable/dataTables.checkboxes.min.js') }}"></script>
<script type="text/javascript" src="https://cdn.datatables.net/fixedheader/3.1.6/js/dataTables.fixedHeader.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
