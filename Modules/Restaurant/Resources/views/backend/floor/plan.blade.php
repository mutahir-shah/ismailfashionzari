@extends('backend.layout.main') @section('content')

@if(session()->has('message'))
<div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('message') }}</div>
@endif
@if(session()->has('not_permitted'))
<div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif

@push('css')
<style>
    #canvas {
        width: 100%;
        height: 600px;
        border: 1px dashed #ccc;
        position: relative;
        background-color: #f7f7f7;
    }

    .draggable {
        width: 100px;
        height: 100px;
        background-color: #3498db;
        color: #fff;
        text-align: center;
        line-height: 100px;
        border-radius: 5px;
        position: absolute;
        cursor: move;
    }

    .resizable {
        resize: both;
        overflow: hidden;
    }
</style>
@endpush
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4>{{__('db.Floorplan')}} ({{$floor->name}})  <x-info title="{{ __('db.floorplan_save_info_message') }}" type="info" /></h4>

                        <a data-id="{{$floor->id}}" data-toggle="modal" data-target="#tableModal" class="btn btn-primary open-TableDialog" href="#">
                            <i class="ti ti-plus"></i> {{ __('db.Add Table') }}
                        </a>
                    </div>
                    <div class="card-body">
                        @if(isset($floor->floorplan))
                        <div id="canvas">
                        </div>
                        @else
                        <div id="canvas">
                            @foreach($tables as $key=>$table)
                            <div class="draggable resizable" 
                                id="{{$table->id}}"
                                @if($key > 0)
                                style="transform:translate({{160 * $key}}px)" data-x="{{160 * $key}}"
                                @endif
                                >
                                {{$table->name}} ({{$table->number_of_person}})
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@include('restaurant::backend.floor.table-partial')
@endsection

@push('scripts')
<script>
{!! file_get_contents(Module::find('Restaurant')->getPath(). "/assets/js/interact.min.js") !!}
</script>
<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    function loadCanvasFromJSON(jsonData) {
        const canvasData = JSON.parse(jsonData);

        const canvas = document.getElementById('canvas');
        canvas.innerHTML = ''; // Clear existing elements

        canvasData.forEach((item) => {
            // Create element
            const element = document.createElement('div');
            element.classList.add('draggable', 'resizable');
            element.id = item.id;
            element.textContent = item.name; // Table name (optional)

            // Apply styles
            element.style.width = `${item.width}px`;
            element.style.height = `${item.height}px`;
            element.style.transform = `translate(${item.x}px, ${item.y}px)`;
            element.setAttribute('data-x', item.x);
            element.setAttribute('data-y', item.y);

            // Append to canvas
            canvas.appendChild(element);
        });

        // Reinitialize Interact.js for the new elements
        initializeInteract();
    }

    @if(isset($floor->floorplan) && !empty($floor->floorplan))
    loadCanvasFromJSON(@json($floor->floorplan));
    @endif

    function initializeInteract() {
    interact('.draggable')
        .draggable({
            listeners: {
                move(event) {
                    const target = event.target;

                    const dataX = parseFloat(target.getAttribute('data-x')) || 0;
                    const dataY = parseFloat(target.getAttribute('data-y')) || 0;

                    const x = dataX + event.dx;
                    const y = dataY + event.dy;

                    target.style.transform = `translate(${x}px, ${y}px)`;

                    target.setAttribute('data-x', x);
                    target.setAttribute('data-y', y);
                },

                end(event) {
                    saveCanvasAsJSON(); // Auto save after drag ends
                }
            },
        })
        .resizable({
            edges: { left: true, right: true, bottom: true, top: true },
        })
        .on('resizemove', function(event) {

            const target = event.target;

            let { width, height } = event.rect;

            target.style.width = `${width}px`;
            target.style.height = `${height}px`;

            const x = (parseFloat(target.getAttribute('data-x')) || 0) + event.deltaRect.left;
            const y = (parseFloat(target.getAttribute('data-y')) || 0) + event.deltaRect.top;

            target.style.transform = `translate(${x}px, ${y}px)`;

            target.setAttribute('data-x', x);
            target.setAttribute('data-y', y);

        })
        .on('resizeend', function(event) {
            autoSave(); // Auto save after resize ends
        });
    }

    function saveCanvasAsJSON() {
        const elements = document.querySelectorAll('.draggable');
        const canvasData = [];

        elements.forEach((element) => {
            const id = element.id;
            const x = parseFloat(element.getAttribute('data-x')) || 0;
            const y = parseFloat(element.getAttribute('data-y')) || 0;
            const width = parseFloat(window.getComputedStyle(element).width);
            const height = parseFloat(window.getComputedStyle(element).height);
            const name = element.textContent.trim(); // Table name (optional)

            // Push element data to the canvasData array
            canvasData.push({
                id,
                x,
                y,
                width,
                height,
                name,
            });
        });

        // Convert canvasData to JSON
        const jsonData = JSON.stringify(canvasData);
        const floor_id = '{{$floor->id}}';
        const url =  '{{route("restaurant.floorplan.update")}}';
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        console.log('Canvas Data:', jsonData);

        // Example: Save JSON data via AJAX (optional)
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ floor_id: floor_id, plan: jsonData }),
        })
            .then((response) => response.json())
            .then((data) => console.log('Saved:', data))
            .catch((error) => console.error('Error:', error));
    }

    let saveTimeout;

    function autoSave() {
        clearTimeout(saveTimeout);

        saveTimeout = setTimeout(() => {
            saveCanvasAsJSON();
        }, 500);
    }

    $(document).ready(function() {

        initializeInteract();

        $('.open-TableDialog').on('click', function() {
            var id = $(this).data('id').toString();
            $("input[name='floor_id']").val($(this).data('id'));

        });
    });

</script>
@endpush