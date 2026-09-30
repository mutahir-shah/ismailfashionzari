<li>
    <a href="#project" aria-expanded="false" data-toggle="collapse">
        <i class="ti ti-briefcase"></i><span>Project Management</span>
    </a>

    <ul id="project" class="collapse list-unstyled">
        @can('project_project_list')
        <li>
            <a href="{{ route('projects.index') }}">
                {{ __('db.project') }}
            </a>
        </li>
        @endcan

        @can('project_category_list')
        <li>
            <a href="{{ route('project_categories.index') }}">
                {{ __('db.Project Category') }}
            </a>
        </li>
        @endcan

        @can('project_task_list')
        <li>
            <a href="{{ route('tasks.index') }}">
                {{ __('db.Task') }}
            </a>
        </li>
        @endcan
    </ul>
</li>