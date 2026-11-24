<x-layouts.admin title="Web templates">
    <div class="space-y-6">
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-muted">Developer</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-brand-navy">Web templates</h1>
                <p class="mt-1 text-sm text-muted">Register template projects and push one to a client's GitHub repository under agreement.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex min-h-8 items-center rounded-control px-2.5 text-xs font-bold {{ $githubReady ? 'bg-brand-yellow text-brand-navy' : 'border border-brand-red-strong bg-surface text-brand-red-strong' }}">
                    {{ $githubReady ? 'GitHub connected' : 'GITHUB_TOKEN not set' }}
                </span>
                <a href="{{ route('developer.dashboard') }}" class="inline-flex min-h-11 items-center rounded-control border border-line bg-surface px-4 text-sm font-bold text-brand-navy hover:bg-surface-muted">Developer console</a>
            </div>
        </header>

        @if (session('status'))
            <div class="rounded-card border border-brand-yellow bg-surface p-4 text-sm font-semibold text-brand-navy" role="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-card border border-brand-red-strong bg-surface p-4 text-sm text-brand-navy" role="alert">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[22rem_1fr]">
            <form action="{{ route('developer.templates.store') }}" method="POST" class="h-fit rounded-card border border-line bg-surface p-5">
                @csrf
                <h2 class="text-lg font-extrabold text-brand-navy">Add web template</h2>
                <p class="mt-1 text-xs leading-relaxed text-muted">Register a local git repository or a remote git URL. Pushing clones the source and pushes it to the client repository.</p>

                <div class="mt-4 space-y-3">
                    <div>
                        <label for="name" class="block text-sm font-bold text-brand-navy">Name</label>
                        <input id="name" name="name" type="text" required value="{{ old('name') }}" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                    </div>
                    <div>
                        <label for="slug" class="block text-sm font-bold text-brand-navy">Slug</label>
                        <input id="slug" name="slug" type="text" required value="{{ old('slug') }}" placeholder="mall-template" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-bold text-brand-navy">Description</label>
                        <textarea id="description" name="description" rows="2" class="mt-1 w-full rounded-control border border-line px-3 py-2 text-sm">{{ old('description') }}</textarea>
                    </div>
                    <div>
                        <label for="source_path" class="block text-sm font-bold text-brand-navy">Local git path</label>
                        <input id="source_path" name="source_path" type="text" value="{{ old('source_path') }}" placeholder="/var/www/templates/mall" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                    </div>
                    <div>
                        <label for="source_git_url" class="block text-sm font-bold text-brand-navy">Or git URL</label>
                        <input id="source_git_url" name="source_git_url" type="url" value="{{ old('source_git_url') }}" placeholder="https://github.com/you/template.git" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label for="source_branch" class="block text-sm font-bold text-brand-navy">Source branch</label>
                            <input id="source_branch" name="source_branch" type="text" value="{{ old('source_branch', 'main') }}" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                        </div>
                        <div>
                            <label for="version" class="block text-sm font-bold text-brand-navy">Version</label>
                            <input id="version" name="version" type="text" value="{{ old('version', '1.0.0') }}" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                        </div>
                    </div>
                </div>

                <button type="submit" class="mt-4 min-h-10 w-full rounded-control bg-brand-yellow px-4 text-sm font-bold text-brand-navy hover:bg-brand-yellow-strong">Add template</button>
            </form>

            <div class="space-y-3">
                @forelse ($templates as $template)
                    <div class="rounded-card border border-line bg-surface p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-bold text-brand-navy">{{ $template->name }} <span class="text-xs font-semibold text-muted">v{{ $template->version }}</span></p>
                                <p class="mt-0.5 break-all text-xs text-muted">{{ $template->sourceReference() }} @if ($template->source_branch) · {{ $template->source_branch }} @endif</p>
                                @if ($template->description)
                                    <p class="mt-1 max-w-2xl text-sm text-muted">{{ $template->description }}</p>
                                @endif
                                <p class="mt-1 text-xs text-muted">{{ $template->last_pushed_at ? 'Last pushed '.$template->last_pushed_at->diffForHumans() : 'Never pushed' }}</p>
                            </div>
                            <form action="{{ route('developer.templates.destroy', $template) }}" method="POST" onsubmit="return confirm('Remove this web template?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="min-h-8 rounded-control border border-brand-red-strong px-2 text-xs font-bold text-brand-red-strong hover:bg-surface-muted">Remove</button>
                            </form>
                        </div>

                        <form action="{{ route('developer.templates.push', $template) }}" method="POST" class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-6">
                            @csrf
                            <div class="lg:col-span-2">
                                <label for="owner-{{ $template->id }}" class="block text-xs font-bold uppercase tracking-wide text-muted">GitHub owner</label>
                                <input id="owner-{{ $template->id }}" name="owner" type="text" required placeholder="client-org" class="mt-1 h-10 w-full rounded-control border border-line px-2 text-sm">
                            </div>
                            <div class="lg:col-span-2">
                                <label for="repository-{{ $template->id }}" class="block text-xs font-bold uppercase tracking-wide text-muted">Repository</label>
                                <input id="repository-{{ $template->id }}" name="repository" type="text" required placeholder="{{ $template->slug }}" class="mt-1 h-10 w-full rounded-control border border-line px-2 text-sm">
                            </div>
                            <div>
                                <label for="branch-{{ $template->id }}" class="block text-xs font-bold uppercase tracking-wide text-muted">Branch</label>
                                <input id="branch-{{ $template->id }}" name="branch" type="text" required value="main" class="mt-1 h-10 w-full rounded-control border border-line px-2 text-sm">
                            </div>
                            <div class="flex items-end gap-2">
                                <label class="flex min-h-10 items-center gap-1.5 text-xs font-semibold text-muted">
                                    <input type="checkbox" name="private" value="1" class="h-4 w-4 rounded-control border-line"> Private
                                </label>
                                <button type="submit" class="min-h-10 rounded-control bg-brand-navy px-3 text-sm font-bold text-white hover:bg-brand-navy-soft">Push</button>
                            </div>
                            <div class="sm:col-span-2 lg:col-span-6">
                                <label for="message-{{ $template->id }}" class="sr-only">Push note</label>
                                <input id="message-{{ $template->id }}" name="message" type="text" placeholder="Agreement / release note (optional)" class="h-10 w-full rounded-control border border-line px-2 text-sm">
                            </div>
                        </form>
                    </div>
                @empty
                    <div class="rounded-card border border-line bg-surface p-8 text-center">
                        <p class="text-sm font-bold text-brand-navy">No web templates registered.</p>
                        <p class="mt-1 text-sm text-muted">Add the local repository path or git URL for a template you issue to clients.</p>
                    </div>
                @endforelse

                {{ $templates->links() }}
            </div>
        </div>

        <section class="rounded-card border border-line bg-surface p-5">
            <h2 class="text-lg font-extrabold text-brand-navy">Recent pushes</h2>
            <div class="mt-3 overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead class="border-b border-line bg-surface-muted">
                        <tr>
                            <th class="px-3 py-2 text-xs font-bold uppercase tracking-wide text-muted">Template</th>
                            <th class="px-3 py-2 text-xs font-bold uppercase tracking-wide text-muted">Repository</th>
                            <th class="px-3 py-2 text-xs font-bold uppercase tracking-wide text-muted">Branch</th>
                            <th class="px-3 py-2 text-xs font-bold uppercase tracking-wide text-muted">Status</th>
                            <th class="px-3 py-2 text-xs font-bold uppercase tracking-wide text-muted">When</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($pushes as $push)
                            <tr>
                                <td class="px-3 py-2 text-brand-navy">{{ $push->template?->name }}</td>
                                <td class="px-3 py-2 text-muted">{{ $push->owner }}/{{ $push->repository }}</td>
                                <td class="px-3 py-2 text-muted">{{ $push->branch }}</td>
                                <td class="px-3 py-2">
                                    <span class="inline-flex min-h-6 items-center rounded-control px-2 text-xs font-bold {{ $push->status === 'success' ? 'bg-brand-yellow text-brand-navy' : 'border border-brand-red-strong bg-surface text-brand-red-strong' }}">{{ ucfirst($push->status) }}</span>
                                </td>
                                <td class="px-3 py-2 text-muted">{{ $push->created_at?->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-3 py-6 text-center text-sm text-muted">No pushes yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts.admin>
