<section>
    <header>
        <h2 class="text-lg font-semibold text-slate-900">{{ __('Profile information') }}</h2>
        <p class="mt-1 text-sm text-slate-600">Update your account details and the photo shown in your CityCare workspace.</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">@csrf</form>

    <form id="profile-information-form" method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="flex flex-col gap-5 rounded-2xl border border-slate-200 bg-slate-50 p-5 sm:flex-row sm:items-center">
            <img id="profile-photo-preview" src="{{ $user->avatarUrl() }}" alt="Current profile photo" class="size-24 rounded-2xl border border-white bg-teal-100 object-cover shadow-sm">
            <div class="min-w-0 flex-1">
                <x-input-label for="avatar" value="Profile photo" class="font-semibold text-slate-800" />
                <input id="avatar" name="avatar" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="mt-2 block w-full cursor-pointer rounded-xl border border-slate-300 bg-white text-sm text-slate-700 file:mr-4 file:border-0 file:bg-teal-50 file:px-4 file:py-2.5 file:font-semibold file:text-teal-800 hover:file:bg-teal-100" aria-describedby="avatar-guidance" />
                <p id="avatar-guidance" class="mt-2 text-xs leading-5 text-slate-500">JPG, PNG, or WebP. Maximum 2 MB. Image dimensions must be between 96 and 2000 pixels wide and high.</p>
                <x-input-error class="mt-2" :messages="$errors->get('avatar')" />
            </div>
        </div>

        <div>
            <x-input-label for="name" value="Name" class="font-medium text-slate-700" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="Email address" class="font-medium text-slate-700" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full rounded-xl border-slate-300" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <p class="mt-2 text-sm text-slate-600">Your email address is unverified.
                    <button form="send-verification" class="font-semibold text-teal-800 underline hover:text-teal-950">Resend the verification email</button>
                </p>
                @if (session('status') === 'verification-link-sent')
                    <script>document.addEventListener('DOMContentLoaded', () => window.CityCareToast('A new verification link has been sent to your email address.', 'success'));</script>
                @endif
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save changes') }}</x-primary-button>
        </div>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('avatar');
    const preview = document.getElementById('profile-photo-preview');
    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    const maximumSize = 2 * 1024 * 1024;
    let previewObjectUrl = null;

    input.addEventListener('change', () => {
        const file = input.files[0];
        if (!file) return;

        if (!allowedTypes.includes(file.type)) {
            input.value = '';
            window.CityCareToast('Choose a JPG, PNG, or WebP image.', 'warning');
            return;
        }

        if (file.size > maximumSize) {
            input.value = '';
            window.CityCareToast('Profile photos must be 2 MB or smaller.', 'warning');
            return;
        }

        const candidateUrl = URL.createObjectURL(file);
        const candidateImage = new Image();
        candidateImage.onload = () => {
            const withinDimensions = candidateImage.naturalWidth >= 96
                && candidateImage.naturalHeight >= 96
                && candidateImage.naturalWidth <= 2000
                && candidateImage.naturalHeight <= 2000;

            if (!withinDimensions) {
                input.value = '';
                URL.revokeObjectURL(candidateUrl);
                window.CityCareToast('Photo dimensions must be between 96 and 2000 pixels in each direction.', 'warning');
                return;
            }

            if (previewObjectUrl) URL.revokeObjectURL(previewObjectUrl);
            previewObjectUrl = candidateUrl;
            preview.src = previewObjectUrl;
        };
        candidateImage.onerror = () => {
            input.value = '';
            URL.revokeObjectURL(candidateUrl);
            window.CityCareToast('The selected photo could not be read.', 'warning');
        };
        candidateImage.src = candidateUrl;
    });
});
</script>
