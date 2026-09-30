@can('restaurant-floor')
<li><a class="{{ request()->is('restaurant/floor') ? 'active' : '' }}" href="{{route('restaurant.floor.index')}}">{{__('db.Floors')}}</a></li>
@endcan
@can('restaurant-table')
<li id="table-menu"><a class="{{ request()->is('tables') ? 'active' : '' }}" href="{{route('tables.index')}}">{{__('db.Tables')}}</a></li>
@endcan
@can('restaurant-reservation')
<li><a class="{{ request()->is('restaurant/reservation') ? 'active' : '' }}" href="{{route('restaurant.reservation.index')}}">{{__('db.reservation')}}</a></li>
@endcan
@can('restaurant-menu-type')
<li><a class="{{ request()->is('restaurant/menutype') ? 'active' : '' }}" href="{{route('restaurant.menutype.index')}}">{{__('db.Menu Type')}}</a></li>
@endcan
@can('restaurant-modifier-group')
<li><a class="{{ request()->is('restaurant/modifier-group') ? 'active' : '' }}" href="{{route('restaurant.modifier-group.index')}}">{{__('db.modifier_group')}}</a></li>
@endcan
@can('restaurant-kitchen')
<li><a class="{{ request()->is('restaurant/kitchen') ? 'active' : '' }}" href="{{route('restaurant.kitchen.index')}}">{{__('db.Kitchen')}}</a></li>
@endcan
@can('restaurant-kitchen-dashboard')
<li><a class="{{ request()->is('restaurant/kitchen/dashboard') ? 'active' : '' }}" href="{{route('restaurant.kitchen.dashboard')}}">{{__('db.kitchen Dashboard')}}</a></li>
@endcan
@can('restaurant-pos')
<li><a href="{{url('pos')}}">{{__('db.restaurant_pos')}}</a></li>
@endcan
