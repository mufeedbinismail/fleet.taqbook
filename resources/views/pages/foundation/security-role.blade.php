<?php

use App\Foundation\Auth\Constant\Permission;
use App\Foundation\Framework\Facade\ClientData;
use Illuminate\Support\Str;
use Illuminate\Support\Js;

$totalCount = $groups->sum(fn ($group) => $group->permissions->count());

ClientData::registry()
    ->put('roleEditor', [
        'state' => $state,
        'roles' => $roles,
        // Lowercased once here rather than per keystroke in the filter, and separate from `groups`
        // because it answers a different question: what the filter reads, not what a group holds.
        'catalog' => $groups->map(fn ($group) => [
            'group_name' => Str::lower($group->name),
            'permissions' => $group->permissions->pluck('name')->map(fn ($label) => Str::lower($label))->values()->all(),
        ])->values()->all(),
        'groups' => $groups->map(fn ($group) => [
            'group_name' => $group->name,
            'keys' => $group->permissions->pluck('key')->values()->all(),
        ])->values()->all(),
    ])
    ->routes([
        'access.roles.index',
        'access.roles.show',
        'access.roles.store',
        'access.roles.update',
        'access.roles.destroy',
    ])
    ->translations([
        'foundation.role.delete.title',
        'foundation.role.delete.text',
        'foundation.role.delete.confirm'
    ]);
?>

@extends('layout.app')
@section('title', $title)
@section('content')
{{-- Not a <form> submit target: every action here is an XHR against the access/roles routes. The form
     element is kept only so Enter saves and the browser validates the name field. --}}
{{-- Everything this component needs is staged server-side and read back by the factory, so there is
     no JSON inlined here and no quoting to get wrong. --}}
<div class="mx-auto max-w-5xl p-4 pb-28 md:p-6 md:pb-28" data-role-editor x-cloak x-data="roleEditor()">

    {{-- Picking a role replaces the form wholesale, so an unsaved edit can never be written to the
         wrong role. --}}
    <div class="mb-5 flex flex-wrap items-end gap-4 rounded-xl border border-card-border bg-card-bg p-4 shadow-sm">
        {{-- Prefers 16rem, grows into free space, and min-w-0 lets it shrink below that rather
             than force the row to overflow on a narrow screen. --}}
        <div class="grow basis-64 min-w-0">
            <label for="role-picker" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-card-txt">
                {{ __('foundation.role.picker.label') }}
            </label>
            <select id="role-picker" data-picker x-ref="picker" @change="switchTo($event.target.value)"
                    class="field border-field-border">
                <option value="">{{ __('foundation.role.new') }}</option>
                {{-- The selected role stays listed even when inactive and the filter is off, otherwise
                     the picker would silently disagree with the form below it. --}}
                <template x-for="role in roles.filter((role) => showInactive || !role.inactive || role.id === id)" :key="role.id">
                    <option :value="role.id" :selected="role.id === id"
                            x-text="role.role_name + (role.inactive ? ' — {{ __('foundation.role.picker.inactive') }}' : '')"></option>
                </template>
            </select>
        </div>

        <label class="flex cursor-pointer items-center gap-2 py-2 text-sm text-card-txt">
            <input type="checkbox" class="tick" x-model="showInactive">
            <span>{{ __('foundation.role.picker.show_inactive') }}</span>
        </label>
    </div>

    <form data-form @submit.prevent="save()">
        {{-- Details --}}
        <div class="mb-5 rounded-xl border border-card-border bg-card-bg shadow-sm">
            <div class="flex flex-wrap items-center gap-2 border-b border-card-divider px-5 py-3">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-card-title-txt"
                    x-text="id ? @js(__('foundation.role.details')) : @js(__('foundation.role.new'))"></h2>

                {{-- Warns before a rejected save has to: this is the role the current user holds. --}}
                <span x-show="own"
                      class="inline-flex items-center gap-1 rounded-full bg-banner-warning-bg px-2 py-0.5 text-xs font-semibold text-banner-warning-txt">
                    <span class="icon icon-lock text-xs"></span>
                    {{ __('foundation.role.own') }}
                </span>
            </div>

            <div class="grid gap-4 p-5 md:grid-cols-2">
                <div>
                    <label for="role-name" class="mb-1 block text-sm font-semibold text-card-title-txt">
                        {{ __('foundation.role.field.name') }}
                    </label>
                    <input type="text" id="role-name" data-role-name maxlength="30" required autofocus x-ref="roleName"
                           x-model="role_name" :class="roleNameError !== null ? 'border-error-accent' : 'border-field-border'"
                           class="field">
                    <div class="mt-1 flex items-center justify-between gap-2">
                        <p data-name-error x-show="roleNameError !== null" x-text="roleNameError"
                           class="text-sm font-semibold text-error-accent"></p>
                        <span class="ms-auto text-xs text-card-txt" x-text="role_name.length + ' / 30'"></span>
                    </div>
                </div>

                <div>
                    <span class="mb-1 block text-sm font-semibold text-card-title-txt">{{ __('foundation.role.field.status') }}</span>
                    <div class="inline-flex overflow-hidden rounded-lg border border-field-border">
                        @foreach ([['false', __('foundation.role.field.active')], ['true', __('foundation.role.field.inactive')]] as [$value, $label])
                            <label class="cursor-pointer">
                                <input type="radio" name="inactive" value="{{ $value }}"
                                       x-model.boolean="inactive" class="peer sr-only">
                                <span class="block px-4 py-2 text-sm font-semibold text-card-txt transition peer-checked:bg-segment-selected-bg peer-checked:text-segment-selected-txt">
                                    {{ $label }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Permissions --}}
        <div class="rounded-xl border border-card-border bg-card-bg shadow-sm" x-collapse>
            <div class="flex flex-wrap items-center gap-3 border-b border-card-divider px-5 py-3">
                <h2 class="me-auto flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-card-title-txt">
                    {{ __('foundation.role.permission.heading') }}
                    <span class="badge" x-text="permissions.length + ' / ' + {{ $totalCount }}"></span>
                </h2>

                <div class="relative w-full sm:w-64">
                    <span class="icon icon-search pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-field-icon"></span>
                    {{-- Bound after x-model's own input listener, so `filter` already carries what
                         was typed by the time the groups are reconciled against it. --}}
                    <input type="search" data-filter x-model="filter" @input="syncGroups()"
                           class="field border-field-border ps-9"
                           placeholder="{{ __('foundation.role.permission.filter_placeholder') }}"
                           aria-label="{{ __('foundation.role.permission.filter') }}">
                </div>

                <div class="flex items-center gap-1 text-sm">
                    <button type="button" @click="showAll()" class="ghost text-link-txt">
                        {{ __('foundation.role.permission.expand_all') }}
                    </button>
                    <span class="text-card-divider">|</span>
                    <button type="button" @click="hideAll()" class="ghost text-link-txt">
                        {{ __('foundation.role.permission.collapse_all') }}
                    </button>
                    <span class="text-card-divider">|</span>
                    <button type="button" @click="permissions = []" class="ghost text-button-danger-txt">
                        {{ __('foundation.role.permission.clear') }}
                    </button>
                </div>
            </div>

            {{-- The catalog is static, so it is rendered once here; only which boxes are ticked and
                 which groups are open are left to Alpine. --}}
            <div class="divide-y divide-card-divider">
                @foreach ($groups as $group)
                    @php
                        $groupKeys = $group->permissions->pluck('key')->values()->all();
                        $groupOpen = count(array_intersect($groupKeys, $state['permissions'])) > 0;
                    @endphp
                    {{--
                        A scope of its own, holding only what every expression below would otherwise
                        restate: the group's keys and the name the filter reads. Everything else —
                        permissions, matches(), own — is found in the scopes further out, and
                        assigning to one of those names still writes where that name lives rather
                        than shadowing it here. `label` rather than `name`, which the page above
                        already uses for the role being edited.

                        @json() is unsafe in these attributes — its literal double quotes would end
                        the attribute early. Js::from() wraps the same data as JSON.parse('...'),
                        which only ever needs single quotes to delimit itself.
                    --}}
                    <section data-group x-collapse:item="{{ $group->name }}" @class(['x-collapse__item--open' => $groupOpen])
                             x-show="groupVisible({{ $loop->index }})"
                             x-data="{ keys: {{ Js::from($groupKeys) }}, group_name: {{ Js::from(Str::lower($group->name)) }} }">
                        <div class="flex items-center gap-3 px-5 py-3 transition hover:bg-card-hover-bg">
                            <input type="checkbox" class="tick"
                                   :checked="keys.every((key) => permissions.includes(key))"
                                   x-effect="$el.indeterminate = keys.some((key) => permissions.includes(key)) && !keys.every((key) => permissions.includes(key))"
                                   @change="$event.target.checked
                                       ? permissions = [...new Set([...permissions, ...keys])]
                                       : permissions = permissions.filter((key) => !keys.includes(key))"
                                   aria-label="{{ __('foundation.role.permission.toggle_group', ['group' => $group->name]) }}">

                            <button type="button" x-collapse:trigger
                                    @class(['flex grow cursor-pointer items-center gap-2 border-0 bg-transparent p-0 text-start text-card-txt', 'x-collapse__trigger--open' => $groupOpen])>
                                <span class="text-sm font-semibold text-card-title-txt">{{ $group->name }}</span>
                                <span class="badge"
                                      x-text="keys.filter((key) => permissions.includes(key)).length + ' / ' + keys.length"></span>
                            </button>
                        </div>

                        {{-- The panel and the grid are two elements, not one: x-collapse:panel shuts
                             through display, which on the grid itself would take the columns with
                             it. A filter running over shut groups is reconciled by opening them, so
                             nothing here has to second-guess what the panel already decides. --}}
                        <div @class(['x-collapse__panel--open' => $groupOpen]) x-collapse:panel>
                            <div class="grid gap-x-6 gap-y-1 bg-card-sunken-bg px-5 pb-4 pt-1 sm:grid-cols-2 xl:grid-cols-3">
                                @foreach ($group->permissions as $permission)
                                    @php $isSelfLock = $permission->key === Permission::MANAGE_ROLE; @endphp
                                    <label data-permission
                                           x-show="matches(group_name) || matches({{ Js::from(Str::lower($permission->name)) }})"
                                           class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm text-card-txt transition hover:bg-card-row-hover-bg">
                                        <input type="checkbox" class="tick" value="{{ $permission->key }}" x-model="permissions"
                                               @if ($isSelfLock) :disabled="own" @endif>
                                        <span>{{ $permission->name }}</span>
                                        {{-- The grant guarding this screen. Disabled rather than left
                                             tickable-then-rejected, so the lock is discovered before
                                             the user acts on it instead of after a failed save; the
                                             label makes the reason visible without a hover, which a
                                             title-only tooltip never is on touch. --}}
                                        @if ($isSelfLock)
                                            <span x-show="own" class="inline-flex items-center gap-1 text-xs font-semibold text-warning-accent">
                                                <span class="icon icon-lock text-xs" aria-hidden="true"></span>
                                                {{ __('foundation.role.permission.self_lock') }}
                                            </span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endforeach

                <p x-show="!catalog.some((group, i) => groupVisible(i))"
                   class="px-5 py-8 text-center text-sm text-card-txt">
                    {{ __('foundation.role.permission.empty') }}
                </p>
            </div>
        </div>

        {{-- Actions --}}
        {{-- Sticky, so Save stays reachable through 36 permission groups. It overlays the card
             while scrolling, hence the z-10 and the container's bottom padding — without the
             latter the final rows would sit permanently behind it. --}}
        <div class="sticky bottom-4 z-10 mt-5 rounded-xl border border-card-border bg-card-sticky-bg shadow-md backdrop-blur">
            <div data-error x-show="errors.length"
                 class="rounded-t-xl border-b border-banner-error-border bg-banner-error-bg px-4 py-3 text-sm text-banner-error-txt">
                <div class="flex items-center gap-2 font-semibold">
                    <span class="icon icon-warning"></span>
                    <span class="grow">{{ __('foundation.role.error.heading') }}</span>
                    <button type="button" @click="errors = []"
                            class="icon icon-close shrink-0 cursor-pointer border-0 bg-transparent text-banner-error-txt hover:text-banner-error-close-hover-txt"
                            aria-label="{{ __('foundation.role.action.dismiss') }}"></button>
                </div>
                <ul class="mt-1 list-disc ps-6">
                    <template x-for="error in errors" :key="error">
                        <li x-text="error"></li>
                    </template>
                </ul>
            </div>

            <div class="flex flex-wrap items-center gap-2 px-4 py-3">
                <x-button type="submit" data-save icon="button-ok">
                    <span x-text="id ? @js(__('foundation.role.action.save')) : @js(__('foundation.role.action.create'))"></span>
                </x-button>

                <x-button data-action="clone" variant="outline" icon="data" x-show="id" @click="clone()">
                    {{ __('foundation.role.action.clone') }}
                </x-button>

                <x-button data-action="delete" variant="danger" icon="trash" x-show="id"
                        @click="$confirm({
                            title: App.i18n('foundation.role.delete.title'),
                            text: App.i18n('foundation.role.delete.text', { role: role_name }),
                            confirmText: App.i18n('foundation.role.delete.confirm'),
                            danger: true,
                        }).then((ok) => ok && deleteRole())">
                    {{ __('foundation.role.action.delete') }}
                </x-button>

                <button type="button" data-action="cancel" @click="cancel()"
                        class="ms-auto cursor-pointer border-0 bg-transparent text-sm text-card-txt transition hover:text-button-outline-txt">
                    {{ __('foundation.role.action.cancel') }}
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@pageScript('resources/js/pages/foundation/role-editor.js')
