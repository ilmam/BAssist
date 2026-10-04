@extends(ui_layout())

@section('main')
    <x-card title="{{ __('ui.api_tokens') }}">
        <p class="text-sm text-secondary-foreground mb-5">{{ __('ui.api_tokens_intro') }}</p>

        @if ($errors->any())
            <div class="kt-alert kt-alert-destructive mb-5">
                <ul class="list-disc ps-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($newToken)
            <div class="kt-alert kt-alert-success mb-5">
                <div class="w-full space-y-2">
                    <p class="font-medium">{{ __('ui.api_token_copy_now') }}</p>
                    <input type="text" readonly class="kt-input font-mono w-full" value="{{ $newToken }}" onfocus="this.select()" aria-label="{{ __('ui.api_token_new') }}" />
                </div>
            </div>
        @elseif (session('status'))
            <div class="kt-alert kt-alert-success mb-5">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('profile.api-tokens.store') }}" class="grid gap-5 md:grid-cols-4 md:items-end mb-7.5">
            @csrf

            <div class="kt-form-item md:col-span-2">
                <label class="kt-form-label" for="token_name">{{ __('ui.api_token_name') }}</label>
                <input id="token_name" type="text" name="name" value="{{ old('name') }}" required maxlength="100" class="kt-input" placeholder="{{ __('ui.api_token_name_placeholder') }}" />
            </div>

            <div class="kt-form-item">
                <label class="kt-form-label" for="token_expires_in">{{ __('ui.api_token_expires') }}</label>
                @php
                    [$selectAttrs] = ui_form_select_attrs();
                @endphp
                <select id="token_expires_in" name="expires_in" required @foreach ($selectAttrs as $attr => $attrValue) {{ $attr }}="{{ $attrValue }}" @endforeach>
                    @foreach ($expiryOptions as $days)
                        <option value="{{ $days }}" @selected((int) old('expires_in', $defaultExpiry) === $days)>
                            {{ trans_choice('ui.api_token_days', $days, ['count' => $days]) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="kt-form-item">
                <label class="flex items-center gap-2 text-sm" for="token_write">
                    <input type="hidden" name="write" value="0" />
                    <input id="token_write" type="checkbox" name="write" value="1" class="kt-checkbox" @checked(old('write')) />
                    <span>{{ __('ui.api_token_allow_write') }}</span>
                </label>
            </div>

            <div class="md:col-span-4 flex justify-end">
                <x-button type="submit" color="primary">{{ __('ui.api_token_create') }}</x-button>
            </div>
        </form>

        <div class="kt-card-table">
            <div class="kt-table-wrapper">
                <table class="kt-table kt-table-border w-full">
                    <thead>
                        <tr>
                            <th>{{ __('ui.api_token_name') }}</th>
                            <th>{{ __('ui.api_token_access') }}</th>
                            <th>{{ __('ui.api_token_last_used') }}</th>
                            <th>{{ __('ui.api_token_expires') }}</th>
                            <th class="text-end" style="width: 110px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tokens as $token)
                            @php
                                $expired = $token->expires_at !== null && $token->expires_at->isPast();
                            @endphp
                            <tr>
                                <td class="font-medium">{{ $token->name }}</td>
                                <td>{{ in_array('write', $token->abilities ?? [], true) ? __('ui.api_token_read_write') : __('ui.api_token_read_only') }}</td>
                                <td>{{ $token->last_used_at?->diffForHumans() ?? __('ui.api_token_never_used') }}</td>
                                <td>
                                    @if ($expired)
                                        <span class="text-destructive">{{ __('ui.api_token_expired') }}</span>
                                    @else
                                        {{ $token->expires_at?->toDateString() ?? '—' }}
                                    @endif
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('profile.api-tokens.destroy', $token->id) }}" class="flex justify-end">
                                        @csrf
                                        @method('DELETE')
                                        <x-button type="submit" color="outline" size="sm">{{ __('ui.api_token_revoke') }}</x-button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-secondary-foreground">{{ __('ui.api_tokens_empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-card>
@endsection
