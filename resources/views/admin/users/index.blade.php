{{-- Admin user management and role overview with CSV export and delete actions. --}}
<x-layout :title="__('Users & roles')" :admin="true">
    <section class="shell page-space">
        <div class="section-heading">
            <div>
                <p class="eyebrow">USER & ROLE MANAGEMENT</p>
                <h1>{!! __('Users & Roles.') !!}</h1>
                <p class="muted">{{ __('Manage registered accounts, switch admin/customer roles, and update user records.') }}</p>
            </div>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <a class="button secondary" href="{{ route('admin.customers.export') }}">
                    {{ __('Export CSV ⤓') }}
                </a>
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
                <input name="q" value="{{ request('q') }}" placeholder="{{ __('Search name, username, or email...') }}" aria-label="Cari pengguna" maxlength="100">
            </label>
            <select name="role" aria-label="Filter peran">
                <option value="">{{ __('All roles') }}</option>
                <option value="admin" @selected(request('role') === 'admin')>{{ __('Admin') }}</option>
                <option value="customer" @selected(request('role') === 'customer')>{{ __('Customer (Pelanggan)') }}</option>
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
                        <th>Pengguna</th>
                        <th>Peran (Role)</th>
                        <th>Jumlah Pesanan</th>
                        <th>Total Pembelian Lunas</th>
                        <th>Bergabung</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>
                                <strong>{{ $user->name }}</strong>
                                <small style="display: block; color: var(--strong);">&#64;{{ $user->username ?? 'belum-set' }}</small>
                                <small class="muted">{{ $user->email }}</small>
                            </td>
                            <td>
                                @if($user->isAdmin())
                                    <span class="badge badge-published">Admin</span>
                                @else
                                    <span class="badge badge-draft">Pelanggan</span>
                                @endif
                                @if(auth()->id() === $user->id)
                                    <small class="muted" style="display: block;">(Akun Anda)</small>
                                @endif
                            </td>
                            <td>{{ $user->orders_count }}</td>
                            <td>Rp{{ number_format($user->spent ?? 0, 0, ',', '.') }}</td>
                            <td>{{ $user->created_at->format('d M Y') }}</td>
                            <td>
                                <div class="table-actions">
                                    <a class="text-link" href="{{ route('admin.users.edit', $user) }}">Edit & Role</a>
                                    @if(auth()->id() !== $user->id)
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="post" data-confirm="Hapus akun {{ $user->name }} (&#64;{{ $user->username }})? Seluruh riwayat pesanan dan tiketnya akan ikut dibersihkan.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" style="color: #b91c1c;">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">Tidak ada pengguna yang cocok dengan kriteria pencarian.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination">{{ $users->links() }}</div>
    </section>
</x-layout>
