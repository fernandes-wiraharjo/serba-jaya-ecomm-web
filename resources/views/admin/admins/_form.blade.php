@csrf

<div class="admin-field">
    <label for="name">Name</label>
    <input
        id="name"
        type="text"
        name="name"
        value="{{ old('name', $admin->name ?? '') }}"
        required
        autofocus
        autocomplete="name"
    >
    @error('name')
        <p class="admin-field-error">{{ $message }}</p>
    @enderror
</div>

<div class="admin-field">
    <label for="email">Email</label>
    <input
        id="email"
        type="email"
        name="email"
        value="{{ old('email', $admin->email ?? '') }}"
        required
        autocomplete="username"
    >
    @error('email')
        <p class="admin-field-error">{{ $message }}</p>
    @enderror
</div>

<div class="admin-field">
    <label for="password">
        Password
        @isset($admin)
            <span>(leave blank to keep current)</span>
        @endisset
    </label>
    <input
        id="password"
        type="password"
        name="password"
        @isset($admin) @else required @endisset
        autocomplete="new-password"
    >
    @error('password')
        <p class="admin-field-error">{{ $message }}</p>
    @enderror
</div>

<div class="admin-field">
    <label for="password_confirmation">Confirm password</label>
    <input
        id="password_confirmation"
        type="password"
        name="password_confirmation"
        @isset($admin) @else required @endisset
        autocomplete="new-password"
    >
</div>
