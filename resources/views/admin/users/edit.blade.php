{{-- Admin edit user and role form. --}}
<x-layout :title="__('Edit User')" :admin="true">
    <section class="shell page-space">
        <a class="back-link" href="{{ route('admin.users.index') }}">← {{ __('User List') }}</a>

        <div class="section-heading">
            <div>
                <p class="eyebrow">USER PROFILE & PERMISSIONS</p>
                <h1>{{ __('Edit User Data.') }}</h1>
                <p class="muted">{{ __('Update user contact info, password, and system role permissions.') }}</p>
            </div>
        </div>

        <form class="admin-form" action="{{ route('admin.users.update', $user) }}" method="post">
            @csrf
            @method('PUT')

            <div class="panel">
                <h2>{{ __('Account Identity') }}</h2>

                <x-field :label="__('Full name')" 
                         name="name" 
                         :value="$user->name" 
                         required 
                         maxlength="100" />

                <x-field :label="__('Username (unique handle for login)')" 
                         name="username" 
                         :value="$user->username" 
                         required 
                         maxlength="50" />

                <x-field :label="__('Email Address')" 
                         name="email" 
                         type="email" 
                         :value="$user->email" 
                         required 
                         maxlength="255" />

                <label class="field">
                    <span>{{ __('Access Role') }}</span>
                    <select name="role" @disabled(auth()->id() === $user->id)>
                        <option value="customer" @selected(old('role', $user->role) === 'customer')>
                            {{ __('Customer — Ticket bookings, order history') }}
                        </option>
                        <option value="admin" @selected(old('role', $user->role) === 'admin')>
                            {{ __('Admin — Full access to dashboard, events, discounts & users') }}
                        </option>
                    </select>
                    @if(auth()->id() === $user->id)
                        <input type="hidden" name="role" value="admin">
                        <small class="muted">{{ __('You cannot change the role of your own admin account.') }}</small>
                    @else
                        <small class="muted">{{ __('Select \'Admin\' to grant full management permissions.') }}</small>
                    @endif
                </label>
            </div>

            <div class="panel">
                <h2>{{ __('Change Password (Optional)') }}</h2>
                <p class="muted small-copy mb-4">{{ __('Leave blank if you do not wish to change this user\'s password.') }}</p>

                <div class="field">
                    <span>{{ __('New Password') }}</span>
                    <input type="password" name="password" minlength="10" maxlength="200" autocomplete="new-password">
                    <small>{{ __('At least 10 characters, containing letters and numbers.') }}</small>
                </div>

                <div class="field">
                    <span>{{ __('Confirm New Password') }}</span>
                    <input type="password" name="password_confirmation" autocomplete="new-password">
                </div>

                <div class="mt-6">
                    <button class="button full" type="submit">{{ __('Save User Changes →') }}</button>
                </div>
            </div>
        </form>

        @if($user->role === 'customer' && $user->orders_count > 0)
            <div class="panel panel-danger mt-8">
                <h3>⚠️ {{ __('Reset Orders for this Customer') }}</h3>
                <p class="muted small-copy mb-4">
                    {{ __('This customer has :count recorded orders. You can reset their order history without deleting the account.', ['count' => $user->orders_count]) }}
                </p>
                <form action="{{ route('admin.reset.customer', $user) }}" method="post" data-confirm="{{ __('Are you sure you want to reset all order history for :name? Reserved ticket stock will be released.', ['name' => $user->name]) }}">
                    @csrf
                    <label class="field mb-4">
                        <span>{{ __('Enter Your Admin Password to Confirm:') }}</span>
                        <input type="password" name="current_password" required placeholder="{{ __('Active admin password') }}">
                    </label>
                    <button class="button danger" type="submit">
                        {{ __('Reset Orders for :name Only', ['name' => $user->name]) }}
                    </button>
                </form>
            </div>
        @endif
    </section>
</x-layout>
