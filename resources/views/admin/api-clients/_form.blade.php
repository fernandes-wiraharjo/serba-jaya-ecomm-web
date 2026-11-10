@csrf

<div class="admin-field">
    <label for="name">Name</label>
    <input
        id="name"
        type="text"
        name="name"
        value="{{ old('name', $apiClient->name ?? '') }}"
        required
        autofocus
        autocomplete="off"
    >
    @error('name')
        <p class="admin-field-error">{{ $message }}</p>
    @enderror
</div>
