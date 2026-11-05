{{-- Admin user management and role overview with CSV export and delete actions. --}}
<x-layout :title="__('Users & roles')" :admin="true">
    <section class="shell page-space">
        <div class="section-heading">
            <div>
                <h1>{!! __('Users & Roles.') !!}</h1>
                <p class="muted">{{ __('Manage registered accounts, switch admin/customer roles, and update user records.') }}</p>
            </div>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <a class="button secondary" href="{{ route('admin.reset.index') }}" style="color: #b91c1c;">
                    ⚠️ {{ __('Reset Buyer Data') }}
                </a>
            </div>
        </div>

        <div class="stat-grid mb-8">
            <div class="stat-card">
                <p>{{ __('Total Admins') }}</p>
                <strong>{{ $totalAdmins }}</strong>
            </div>
            <div class="stat-card">
                <p>{{ __('Total Customers') }}</p>
                <strong>{{ $totalCustomers }}</strong>
            </div>
        </div>

        <form class="filter-bar" method="get">
            <label class="search-field">
                <span>⌕</span>
                <input name="q" value="{{ request('q') }}" placeholder="{{ __('Search name, username, or email...') }}" aria-label="{{ __('Search user') }}" maxlength="100">
            </label>
            <select name="role" aria-label="{{ __('Filter role') }}">
                <option value="">{{ __('All roles') }}</option>
                <option value="admin" @selected(request('role') === 'admin')>{{ __('Admin') }}</option>
                <option value="customer" @selected(request('role') === 'customer')>{{ __('Customer') }}</option>
            </select>
            <button class="button secondary" type="submit">{{ __('Filter') }}</button>
            @if(request()->hasAny(['q', 'role']))
                <a class="text-link" href="{{ route('admin.users.index') }}">{{ __('Reset') }}</a>
            @endif
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('User') }}</th>
                        <th>{{ __('Role') }}</th>
                        <th>{{ __('Order count') }}</th>
                        <th>{{ __('Paid spending') }}</th>
                        <th>{{ __('Joined') }}</th>
                        <th>{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>
                                <strong>{{ $user->name }}</strong>
                                <small class="muted" style="display: block;">&#64;{{ $user->username ?? 'belum-set' }}</small>
                                <small class="muted">{{ $user->email }}</small>
                            </td>
                            <td>
                                @if($user->isAdmin())
                                    <span class="badge badge-published">{{ __('Admin') }}</span>
                                @else
                                    <span class="badge badge-draft">{{ __('Customer') }}</span>
                                @endif
                                @if(auth()->id() === $user->id)
                                    <small class="muted" style="display: block;">({{ __('Your account') }})</small>
                                @endif
                            </td>
                            <td>{{ $user->orders_count }}</td>
                            <td>Rp{{ number_format($user->spent ?? 0, 0, ',', '.') }}</td>
                            <td>{{ $user->created_at->format('d M Y') }}</td>
                            <td>
                                <div class="table-actions">
                                    <a class="text-link" href="{{ route('admin.users.edit', $user) }}">{{ __('Edit & Role') }}</a>
                                    @if(auth()->id() !== $user->id)
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="post" data-confirm="{{ __('Delete account :name (@:username)? All associated orders and tickets will also be cleared.', ['name' => $user->name, 'username' => $user->username]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" style="color: #b91c1c;">{{ __('Delete') }}</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">{{ __('No users found matching search criteria.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination">{{ $users->links() }}</div>
    </section>
</x-layout>
