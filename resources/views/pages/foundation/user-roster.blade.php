<?php

use App\Foundation\Auth\Component\Table\UserTable;
use App\Foundation\Framework\Facade\ClientData;

ClientData::registry()
    ->put('userRoster', [
        'roles' => $roles->toArray(),
        'salesPoints' => $salesPoints,
    ])
    ->routes([
        UserTable::routeName(),
        'access.users.store',
        'access.users.update',
        'access.users.destroy',
        'access.users.status',
    ])
    ->translations([
        'foundation.user.delete.title',
        'foundation.user.delete.text',
        'foundation.user.delete.confirm',
    ]);
?>

@extends('layout.app')
@section('title', $title)
@section('content')
<div class="mx-auto max-w-7xl p-4 md:p-6" x-cloak x-data="userRoster()">

    <x-ui.table
        :definition="$definition"
        :caption="$title"
        route="access.users.list"
        :initial="$initial"
        row-key="id"
        height="34rem"
        column-search
        :export="['csv', 'xlsx']"
    >
        <x-slot:toolbar>
            <label class="flex cursor-pointer items-center gap-2 text-sm text-card-txt">
                {{-- Absent rather than false: "either" is not a value this filter can carry, so
                     showing everybody means asking for no filter at all. --}}
                <input type="checkbox" class="tick" data-show-inactive
                       :checked="filterValue('inactive') === ''"
                       @change="setFilter('inactive', $event.target.checked ? null : false)">
                <span>{{ __('foundation.user.filter.show_inactive') }}</span>
            </label>

            <x-ui.button data-action="new" icon="plus-circle" x-modal:open="'user-editor'">
                {{ __('foundation.user.action.new') }}
            </x-ui.button>
        </x-slot>

        {{-- A login on its own does not say whether the account still works, and this is the one
             screen where that is the question being asked. --}}
        <x-slot:cell_user_id>
            <span x-text="row.user_id" :class="row.inactive && 'text-card-txt line-through'"></span>
            <span x-show="row.inactive" class="badge ms-1">{{ __('foundation.user.badge.inactive') }}</span>
        </x-slot>

        <x-slot:cell_email>
            <a x-show="row.email" :href="'mailto:' + row.email" x-text="row.email"
               class="text-link-txt hover:underline"></a>
        </x-slot>

        <x-slot:actions sticky width="9rem" :label="__('foundation.user.column.actions')">
            <div class="flex items-center justify-end gap-1">
                <button type="button" class="ghost text-lg text-warning-accent" x-show="row.is_editable"
                        x-modal:open="{ name: 'user-editor', with: row }"
                        title="{{ __('foundation.user.action.edit') }}" aria-label="{{ __('foundation.user.action.edit') }}">
                    <span class="icon icon-pencil" aria-hidden="true"></span>
                </button>

                <button type="button" class="ghost text-lg text-button-danger-txt" x-show="row.is_switchable"
                        @click="setStatus(row, !row.inactive)"
                        :title="row.inactive ? @js(__('foundation.user.action.activate')) : @js(__('foundation.user.action.deactivate'))"
                        :aria-label="row.inactive ? @js(__('foundation.user.action.activate')) : @js(__('foundation.user.action.deactivate'))">
                    <span class="icon" :class="row.inactive ? 'icon-circle-check' : 'icon-block'" aria-hidden="true"></span>
                </button>

                <button type="button" class="ghost text-lg text-button-danger-txt" x-show="row.is_deletable"
                        @click="$confirm({
                            title: App.i18n('foundation.user.delete.title'),
                            text: App.i18n('foundation.user.delete.text', { user: row.user_id }),
                            confirmText: App.i18n('foundation.user.delete.confirm'),
                            danger: true,
                        }).then((ok) => ok && remove(row))"
                        title="{{ __('foundation.user.action.delete') }}" aria-label="{{ __('foundation.user.action.delete') }}">
                    <span class="icon icon-trash" aria-hidden="true"></span>
                </button>
            </div>
        </x-slot>
    </x-ui.table>

    {{-- static, because a half-filled account is not something Escape should be able to drop. --}}
    <x-ui.modal name="user-editor" size="lg" static @modal:showing="open($event.detail)">
        <x-slot:header>
            <span x-text="form.id ? @js(__('foundation.user.edit')) : @js(__('foundation.user.new'))"></span>
        </x-slot>

        <form data-form @submit.prevent="save()" class="grid gap-6">
            <fieldset>
                <legend>{{ __('foundation.user.section.sign_in') }}</legend>
                <div class="grid gap-x-4 gap-y-3 md:grid-cols-[11rem_minmax(0,1fr)] md:items-center">
                    <span class="text-sm font-semibold text-card-title-txt">{{ __('foundation.user.field.login') }}</span>
                    {{-- A field only while an account is being opened. Past that the login is what
                         somebody types to sign in, so moving it would change how they get in without
                         telling them. --}}
                    <template x-if="form.id === null">
                        <input type="text" data-login maxlength="60" required autofocus x-model="form.user_id"
                               class="field" :class="errorFor('user_id') ? 'border-error-accent' : 'border-field-border'">
                    </template>
                    <template x-if="form.id !== null">
                        <p data-login-label class="px-3 py-2 font-semibold text-card-title-txt" x-text="form.user_id"></p>
                    </template>
                    <p class="text-sm font-semibold text-error-accent md:col-start-2" x-show="errorFor('user_id')" x-text="errorFor('user_id')"></p>

                    <label for="user-password" class="text-sm font-semibold text-card-title-txt">{{ __('foundation.user.field.password') }}</label>
                    <input type="password" id="user-password" data-password maxlength="100" autocomplete="new-password"
                           x-model="form.password" :required="form.id === null"
                           class="field" :class="errorFor('password') ? 'border-error-accent' : 'border-field-border'">
                    <p class="text-xs text-card-txt md:col-start-2" x-show="form.id !== null">
                        {{ __('foundation.user.field.password_hint') }}
                    </p>
                    <p class="text-sm font-semibold text-error-accent md:col-start-2" x-show="errorFor('password')" x-text="errorFor('password')"></p>
                </div>
            </fieldset>

            <fieldset>
                <legend>{{ __('foundation.user.section.person') }}</legend>
                <div class="grid gap-x-4 gap-y-3 md:grid-cols-[11rem_minmax(0,1fr)] md:items-center">
                    <label for="user-real-name" class="text-sm font-semibold text-card-title-txt">{{ __('foundation.user.field.real_name') }}</label>
                    <input type="text" id="user-real-name" maxlength="100" required x-model="form.real_name"
                           class="field" :class="errorFor('real_name') ? 'border-error-accent' : 'border-field-border'">
                    <p class="text-sm font-semibold text-error-accent md:col-start-2" x-show="errorFor('real_name')" x-text="errorFor('real_name')"></p>

                    <label for="user-email" class="text-sm font-semibold text-card-title-txt">{{ __('foundation.user.field.email') }}</label>
                    <input type="email" id="user-email" maxlength="100" x-model="form.email"
                           class="field" :class="errorFor('email') ? 'border-error-accent' : 'border-field-border'">
                    <p class="text-sm font-semibold text-error-accent md:col-start-2" x-show="errorFor('email')" x-text="errorFor('email')"></p>

                    <label for="user-phone" class="text-sm font-semibold text-card-title-txt">{{ __('foundation.user.field.phone') }}</label>
                    <input type="text" id="user-phone" maxlength="30" x-model="form.phone" class="field border-field-border">
                </div>
            </fieldset>

            <fieldset>
                <legend>{{ __('foundation.user.section.access') }}</legend>
                <div class="grid gap-x-4 gap-y-3 md:grid-cols-[11rem_minmax(0,1fr)] md:items-center">
                    <label for="user-role" class="text-sm font-semibold text-card-title-txt">{{ __('foundation.user.field.role') }}</label>
                    {{-- Pushed the form's value silently: announced, x-model would write it straight
                         back and the two would chase each other. --}}
                    <x-ui.select
                        id="user-role"
                        name="role_id"
                        class="w-full"
                        required
                        :channel="$roles"
                        x-model.number="form.role_id"
                        x-effect="$el.__xSelect?.setValue(form.role_id, { silent: true })"
                    />

                    <label for="user-pos" class="text-sm font-semibold text-card-title-txt">{{ __('foundation.user.field.pos') }}</label>
                    <select id="user-pos" required x-model.number="form.pos" class="field border-field-border">
                        <template x-for="point in salesPoints.filter((point) => !point.inactive || point.id === form.pos)"
                                  :key="point.id">
                            <option :value="point.id" x-text="point.name"></option>
                        </template>
                    </select>
                </div>
            </fieldset>

            <div data-error x-show="messages.length">
                <div class="rounded-lg border border-banner-error-border bg-banner-error-bg px-4 py-3 text-sm text-banner-error-txt">
                    <div class="flex items-center gap-2 font-semibold">
                        <span class="icon icon-warning"></span>
                        <span class="grow">{{ __('foundation.user.error.heading') }}</span>
                    </div>
                    <ul class="mt-1 list-disc ps-6">
                        <template x-for="message in messages" :key="message">
                            <li x-text="message"></li>
                        </template>
                    </ul>
                </div>
            </div>

            {{-- Inside the form rather than in the footer slot, so Enter submits and the browser
                 validates the fields above before anything is sent. --}}
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.button type="submit" data-save icon="button-ok">
                    <span x-text="form.id ? @js(__('foundation.user.action.save')) : @js(__('foundation.user.action.create'))"></span>
                </x-ui.button>

                <button type="button" x-modal:close
                        class="ms-auto cursor-pointer border-0 bg-transparent text-sm text-card-txt transition hover:text-card-title-txt">
                    {{ __('foundation.user.action.cancel') }}
                </button>
            </div>
        </form>
    </x-ui.modal>
</div>
@endsection

@pageScript('resources/js/pages/foundation/user-roster.js')
