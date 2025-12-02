{{-- Google Drive Section --}}
<div class="flex w-full flex-col gap-2 border-b border-gray-200 p-4 dark:border-gray-800">
    <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold dark:text-white">
            <span class="icon-google-drive text-xl"></span>
            Google Drive
        </h3>
        
        @if($lead->client_number)
            <span class="rounded-md bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                #{{ $lead->client_number }}
            </span>
        @endif
    </div>

    {{-- Drive Actions --}}
    <div class="flex flex-col gap-2">
        @if(!$lead->google_drive_folder_id)
            {{-- Create Folder Button --}}
            <button
                type="button"
                id="create-drive-folder-btn"
                class="secondary-button w-full"
                data-lead-id="{{ $lead->id }}"
            >
                <span class="icon-folder text-lg"></span>
                @lang('admin::app.leads.view.google-drive.create-folder')
            </button>
        @else
            {{-- Open in Drive Button --}}
            <a
                href="{{ $lead->google_drive_folder_url }}"
                target="_blank"
                class="secondary-button w-full text-center"
            >
                <span class="icon-external-link text-lg"></span>
                @lang('admin::app.leads.view.google-drive.open-drive')
            </a>

            {{-- Move to Projects Button (if not moved yet) --}}
            @if(!$lead->folder_moved_at && $lead->lead_pipeline_stage_id == config('lead.won_stage_id'))
                <button
                    type="button"
                    id="move-projects-btn"
                    class="secondary-button w-full"
                    data-lead-id="{{ $lead->id }}"
                >
                    <span class="icon-move text-lg"></span>
                    @lang('admin::app.leads.view.google-drive.move-to-projects')
                </button>
            @endif

            {{-- Files Count --}}
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <span class="icon-file text-base"></span>
                <span id="files-count-{{ $lead->id }}">...</span> files
            </div>
        @endif
    </div>

    {{-- Projects Section --}}
    @if($lead->google_drive_folder_id)
        <div class="mt-4">
            <div class="flex items-center justify-between mb-2">
                <h4 class="text-sm font-semibold dark:text-white">
                    @lang('admin::app.leads.view.google-drive.projects')
                </h4>
                <button
                    type="button"
                    class="text-xs text-blue-600 hover:text-blue-800"
                    onclick="showCreateProjectModal({{ $lead->id }})"
                >
                    + @lang('admin::app.leads.view.google-drive.add-project')
                </button>
            </div>

            <div id="projects-list-{{ $lead->id }}" class="flex flex-col gap-1">
                {{-- Projects will be loaded via JavaScript --}}
            </div>
        </div>
    @endif
</div>

{{-- JavaScript for Google Drive Actions --}}
<script>
    console.log('✅ Google Drive script loaded for lead {{ $lead->id }}');
    
    // Use event delegation to handle clicks, as Vue might re-render the DOM
    document.addEventListener('click', function(e) {
        console.log('🖱️ Click detected:', e.target.id, e.target.className);
        
        // Create Folder Button
        if (e.target && (e.target.id === 'create-drive-folder-btn' || e.target.closest('#create-drive-folder-btn'))) {
            console.log('📁 Create folder button clicked!');
            const btn = e.target.id === 'create-drive-folder-btn' ? e.target : e.target.closest('#create-drive-folder-btn');
            const leadId = btn.getAttribute('data-lead-id');
            console.log('Lead ID:', leadId);
            submitCreateFolder(leadId);
        }
        
        // Move to Projects Button
        if (e.target && (e.target.id === 'move-projects-btn' || e.target.closest('#move-projects-btn'))) {
            console.log('📦 Move to projects button clicked!');
            const btn = e.target.id === 'move-projects-btn' ? e.target : e.target.closest('#move-projects-btn');
            const leadId = btn.getAttribute('data-lead-id');
            submitMoveToProjects(leadId);
        }
    });

    function submitCreateFolder(leadId) {
        console.log('submitCreateFolder called for lead:', leadId);
        
        console.log('Getting CSRF token...');
        // Get CSRF token
        const token = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
        console.log('CSRF token:', token ? 'Found' : 'NOT FOUND');
        
        console.log('Sending request to:', `/admin/leads/${leadId}/drive/create-folder`);
        
        // Send AJAX request
        fetch(`/admin/leads/${leadId}/drive/create-folder`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                alert(data.message);
                // Reload page to show updated UI
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to create folder. Check console for details.');
        });
    }

    function submitMoveToProjects(leadId) {
        if (!confirm('@lang('admin::app.leads.view.google-drive.confirm-move')')) {
            return;
        }
        
        const token = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
        
        fetch(`/admin/leads/${leadId}/drive/move-to-projects`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to move folder.');
        });
    }

    function showCreateProjectModal(leadId) {
        const projectName = prompt('@lang('admin::app.leads.view.google-drive.project-name-prompt')');
        
        if (!projectName) {
            return;
        }

        const token = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

        fetch(`/admin/leads/${leadId}/drive/projects`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({ project_name: projectName })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Project created: ' + data.project.project_name);
                loadProjects(leadId);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to create project');
        });
    }

    function loadProjects(leadId) {
        fetch(`/admin/leads/${leadId}/drive/projects`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.projects.length > 0) {
                    const projectsList = document.getElementById(`projects-list-${leadId}`);
                    projectsList.innerHTML = data.projects.map(project => `
                        <a href="${project.google_drive_folder_url}" 
                           target="_blank"
                           class="flex items-center justify-between p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-800 text-sm">
                            <span class="dark:text-white">
                                <span class="icon-folder text-base"></span>
                                ${project.project_number}. ${project.project_name}
                            </span>
                            <span class="icon-external-link text-xs text-gray-400"></span>
                        </a>
                    `).join('');
                }
            });
    }

    function loadFilesCount(leadId) {
        fetch(`/admin/leads/${leadId}/drive/files`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const countEl = document.getElementById(`files-count-${leadId}`);
                    if (countEl) {
                        countEl.textContent = data.files.length;
                    }
                }
            });
    }

    // Load data on page load
    document.addEventListener('DOMContentLoaded', function() {
        const leadId = {{ $lead->id }};
        
        @if($lead->google_drive_folder_id)
            loadFilesCount(leadId);
            loadProjects(leadId);
        @endif
    });
</script>
