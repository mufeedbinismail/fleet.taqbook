<?php

use App\Trade\Sale\Component\Select\CustomerSelect;
use App\Fleet\Component\Table\DeploymentTable;
use App\Fleet\Constant\DeploymentAlias;
use App\Foundation\Framework\Facade\ClientData;
use App\Foundation\Shared\ValueObject\DomainDateTime;

ClientData::registry()
    ->put('deploymentRegister', [
        'hostings' => $hostings,
        'statuses' => $statuses,
        'dateTimeFormat' => DomainDateTime::userDateTimeFormat(),
    ])
    ->routes([
        DeploymentTable::routeName(),
        CustomerSelect::routeName(),
        'fleet.deployments.store',
        'fleet.deployments.update',
        'fleet.deployments.rename',
        'fleet.deployments.status',
        'fleet.deployments.ping',
        'fleet.deployments.destroy',
        'fleet.deployments.erase',
    ])
    ->translations([
        'fleet.deployment.remove.title',
        'fleet.deployment.status_change_of',
        'fleet.deployment.remove.erase.confirm_title',
        'fleet.deployment.remove.erase.confirm_text',
        'fleet.deployment.remove.erase.confirm_action',
    ]);
?>

@extends('layout.app')
@section('title', $title)
@section('content')
<div class="mx-auto max-w-7xl p-4 md:p-6" x-cloak x-data="deploymentRegister()">

    <x-ui.table
        :definition="$definition"
        :caption="$title"
        route="fleet.deployments.list"
        :initial="$initial"
        row-key="uuid"
        height="34rem"
        column-search
        :export="['csv', 'xlsx']"
    >
        <x-slot:toolbar>
            <x-ui.button data-action="new" icon="plus-circle" x-modal:open="'deployment-editor'">
                {{ __('fleet.deployment.action.new') }}
            </x-ui.button>
        </x-slot>

        {{-- The number is what somebody reads out over a phone, so it is drawn as one thing to be
             read rather than as prose. --}}
        <x-slot:cell_number>
            <span class="font-mono" x-text="row.number"></span>
        </x-slot>

        <x-slot:cell_url>
            <a x-show="row.url" :href="row.url" target="_blank" rel="noopener" x-text="row.url"
               class="text-link-txt hover:underline"></a>
            <span x-show="!row.url" class="text-card-txt">&mdash;</span>
        </x-slot>

        <x-slot:cell_last_pushed_at>
            <span x-show="row.last_pushed_at" x-text="row.last_pushed_at"></span>
            <span x-show="!row.last_pushed_at" class="text-card-txt">&mdash;</span>
        </x-slot>

        <x-slot:actions sticky width="3.5rem" :label="__('fleet.deployment.column.actions')">
            <div class="flex justify-end" x-dropdown>
                <button type="button" class="ghost px-2 text-lg leading-none" x-dropdown:trigger.bare
                        title="{{ __('fleet.deployment.action.menu') }}" aria-label="{{ __('fleet.deployment.action.menu') }}">
                    <span x-show="!App.isBusy(row.uuid)" class="font-bold" aria-hidden="true">&#8942;</span>
                    <span x-show="App.isBusy(row.uuid)" class="icon icon-spinner animate-spin" aria-hidden="true"></span>
                </button>

                <template x-teleport="body">
                    <ul x-dropdown:panel.bottom-end x-transition x-cloak>
                        <li role="none">
                            <button type="button" x-dropdown:item class="w-full cursor-pointer border-0 bg-transparent text-start"
                                    x-modal:open="{ name: 'deployment-editor', with: row }">
                                <span class="icon icon-cog text-warning-accent" aria-hidden="true"></span>
                                <span>{{ __('fleet.deployment.action.edit') }}</span>
                            </button>
                        </li>
                        <li role="none">
                            <button type="button" x-dropdown:item class="w-full cursor-pointer border-0 bg-transparent text-start"
                                    x-modal:open="{ name: 'deployment-rename', with: row }">
                                <span class="icon icon-rename text-primary-accent" aria-hidden="true"></span>
                                <span>{{ __('fleet.deployment.action.rename') }}</span>
                            </button>
                        </li>
                        <li role="none">
                            <button type="button" x-dropdown:item class="w-full cursor-pointer border-0 bg-transparent text-start"
                                    x-modal:open="{ name: 'deployment-status', with: row }">
                                <span class="icon icon-refresh" aria-hidden="true"></span>
                                <span>{{ __('fleet.deployment.action.change_status') }}</span>
                            </button>
                        </li>
                        <li role="none">
                            <button type="button" x-dropdown:item
                                    class="w-full cursor-pointer border-0 bg-transparent text-start disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="!row.url || App.isBusy(row.uuid)"
                                    @click="ping(row)">
                                <span class="icon icon-globe text-success-accent" aria-hidden="true"></span>
                                <span>{{ __('fleet.deployment.action.ping') }}</span>
                                <span x-show="!row.url" class="ms-auto ps-4 text-xs">{{ __('fleet.deployment.hint.no_address') }}</span>
                            </button>
                        </li>
                        <li role="none" class="mt-1 border-0 border-t border-solid border-panel-border pt-1">
                            <button type="button" x-dropdown:item class="w-full cursor-pointer border-0 bg-transparent text-start text-button-danger-txt"
                                    x-modal:open="{ name: 'deployment-removal', with: row }">
                                <span class="icon icon-trash" aria-hidden="true"></span>
                                <span>{{ __('fleet.deployment.action.remove') }}</span>
                            </button>
                        </li>
                    </ul>
                </template>
            </div>
        </x-slot>
    </x-ui.table>

    {{-- static, because a half-filled registration is not something Escape should be able to drop. --}}
    <x-ui.modal id="deployment-editor" name="deployment-editor" size="lg" static @modal:showing="open($event.detail)">
        <x-slot:header>
            <span x-text="form.uuid ? @js(__('fleet.deployment.edit')) : @js(__('fleet.deployment.new'))"></span>
        </x-slot>

        {{-- Bound as it is drawn, not as it is submitted: binding is what hands validation over from
         the browser, and a browser still holding it refuses the submit before anything here runs. --}}
        <form data-form x-init="App.parsley.bind($el)" @submit.prevent="save($event)" class="grid gap-6">
            <div class="grid gap-x-4 gap-y-3 md:grid-cols-[11rem_minmax(0,1fr)] md:items-center">
                <label for="deployment-customer" class="text-sm font-semibold text-card-title-txt">{{ __('fleet.deployment.field.customer') }}</label>
                <div data-validator-field class="grid gap-1">
                    {{-- Pushed the form's value silently: announced, x-model would write it straight
                         back and the two would chase each other. --}}
                    <x-ui.select
                        id="deployment-customer"
                        name="debtor_no"
                        class="w-full"
                        required
                        :placeholder="__('fleet.deployment.placeholder.customer')"
                        :channel="$customers"
                        {{-- Drawn inside the dialog, which is in the top layer: a panel left in the
                             page behind it is painted under the modal and never seen. --}}
                        panel-parent="#deployment-editor"
                        x-model.number="form.debtor_no"
                        x-effect="$el.__xSelect?.setValue(form.debtor_no, { silent: true })"
                    />
                    <p class="text-sm font-semibold text-error-accent" x-show="errorFor('debtor_no')" x-text="errorFor('debtor_no')"></p>
                </div>

                {{-- A field only while a deployment is being registered. Past that the alias is
                     what people and records call it by, so it is changed deliberately and on its
                     own screen. --}}
                <template x-if="form.uuid === null">
                    <label for="deployment-alias" class="text-sm font-semibold text-card-title-txt">{{ __('fleet.deployment.field.alias') }}</label>
                </template>
                <template x-if="form.uuid === null">
                    <div data-validator-field class="grid gap-1">
                        <input type="text" id="deployment-alias" required maxlength="{{ DeploymentAlias::LENGTH }}"
                               pattern="{{ DeploymentAlias::PATTERN }}" data-validator-pattern-message="{{ __('fleet.deployment.hint.alias_shape') }}"
                               x-model="form.alias"
                               class="field" :class="errorFor('alias') ? 'border-error-accent' : 'border-field-border'">
                        <p class="text-xs text-card-txt" x-show="!errorFor('alias')">{{ __('fleet.deployment.hint.alias') }}</p>
                        <p class="text-sm font-semibold text-error-accent" x-show="errorFor('alias')" x-text="errorFor('alias')"></p>
                    </div>
                </template>

                <template x-if="form.uuid !== null">
                    <span class="text-sm font-semibold text-card-title-txt">{{ __('fleet.deployment.column.number') }}</span>
                </template>
                <template x-if="form.uuid !== null">
                    <div>
                        <p class="px-3 py-2 font-mono font-semibold text-card-title-txt" x-text="form.number"></p>
                        <p class="px-3 text-xs text-card-txt">{{ __('fleet.deployment.hint.number') }}</p>
                    </div>
                </template>

                <label for="deployment-hosting" class="text-sm font-semibold text-card-title-txt">{{ __('fleet.deployment.field.hosting') }}</label>
                <div data-validator-field class="grid gap-1">
                    <select id="deployment-hosting" required x-model="form.hosting" class="field border-field-border">
                        <template x-for="hosting in hostings" :key="hosting.value">
                            <option :value="hosting.value" x-text="hosting.label"></option>
                        </template>
                    </select>
                </div>

                {{-- A field only while a deployment is being registered: an install already
                     delivered or live goes on the register where it stands, and every move after
                     that is its own dated operation. --}}
                <template x-if="form.uuid === null">
                    <label for="deployment-status" class="text-sm font-semibold text-card-title-txt">{{ __('fleet.deployment.field.status') }}</label>
                </template>
                <template x-if="form.uuid === null">
                    <div data-validator-field class="grid gap-1">
                        <select id="deployment-status" required x-model="form.status" class="field border-field-border">
                            <template x-for="status in statuses" :key="status.value">
                                <option :value="status.value" x-text="status.label"></option>
                            </template>
                        </select>
                        <p class="text-sm font-semibold text-error-accent" x-show="errorFor('status')" x-text="errorFor('status')"></p>
                    </div>
                </template>

                <label for="deployment-created" class="text-sm font-semibold text-card-title-txt">{{ __('fleet.deployment.field.instance_created') }}</label>
                <div data-validator-field class="grid gap-1">
                    <x-ui.date
                        id="deployment-created"
                        name="instance_created_date"
                        class="w-full"
                        required
                        today
                        panel-parent="#deployment-editor"
                        x-model.lazy="form.instance_created_date"
                    />
                    <p class="text-xs text-card-txt" x-show="!errorFor('instance_created_date')">{{ __('fleet.deployment.hint.instance_created') }}</p>
                    <p class="text-sm font-semibold text-error-accent" x-show="errorFor('instance_created_date')" x-text="errorFor('instance_created_date')"></p>
                </div>

                <label for="deployment-url" class="text-sm font-semibold text-card-title-txt">{{ __('fleet.deployment.field.address') }}</label>
                <div data-validator-field class="grid gap-1">
                    <input type="url" id="deployment-url" maxlength="255" placeholder="{{ __('fleet.deployment.placeholder.address') }}"
                           x-model="form.url"
                           class="field" :class="errorFor('url') ? 'border-error-accent' : 'border-field-border'">
                    <p class="text-sm font-semibold text-error-accent" x-show="errorFor('url')" x-text="errorFor('url')"></p>
                </div>
            </div>

            <div data-error x-show="messages.length">
                <div class="rounded-lg border border-banner-error-border bg-banner-error-bg px-4 py-3 text-sm text-banner-error-txt">
                    <div class="flex items-center gap-2 font-semibold">
                        <span class="icon icon-warning"></span>
                        <span class="grow">{{ __('fleet.deployment.error.heading') }}</span>
                    </div>
                    <ul class="mt-1 list-disc ps-6">
                        <template x-for="message in messages" :key="message">
                            <li x-text="message"></li>
                        </template>
                    </ul>
                </div>
            </div>

            {{-- Inside the form rather than in the footer slot, so Enter submits and the fields
                 above are validated before anything is sent. --}}
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.button type="submit" data-save icon="button-ok">
                    <span x-text="form.uuid ? @js(__('fleet.deployment.action.save')) : @js(__('fleet.deployment.action.create'))"></span>
                </x-ui.button>

                <button type="button" x-modal:close
                        class="ms-auto cursor-pointer border-0 bg-transparent text-sm text-card-txt transition hover:text-card-title-txt">
                    {{ __('fleet.deployment.action.cancel') }}
                </button>
            </div>
        </form>
    </x-ui.modal>

    <x-ui.modal name="deployment-removal" size="sm" @modal:showing="openRemoval($event.detail)">
        <x-slot:header>
            <span x-text="App.i18n('fleet.deployment.remove.title', { alias: removing.alias })"></span>
        </x-slot>

        <div class="grid gap-4">
            <div class="grid gap-1">
                <x-ui.button variant="outline" class="justify-center" @click="trash()">
                    {{ __('fleet.deployment.remove.trash.action') }}
                </x-ui.button>
                <p class="text-xs text-card-txt">{{ __('fleet.deployment.remove.trash.text') }}</p>
            </div>

            <div class="grid gap-1">
                <x-ui.button variant="danger" class="justify-center" @click="erase()">
                    {{ __('fleet.deployment.remove.erase.action') }}
                </x-ui.button>
                <p class="text-xs text-card-txt">{{ __('fleet.deployment.remove.erase.text') }}</p>
            </div>

            <button type="button" x-modal:close
                    class="cursor-pointer border-0 bg-transparent text-sm text-card-txt transition hover:text-card-title-txt">
                {{ __('fleet.deployment.action.cancel') }}
            </button>
        </div>
    </x-ui.modal>

    <x-ui.modal id="deployment-status" name="deployment-status" size="sm" @modal:showing="openStatus($event.detail)">
        <x-slot:header>
            <span x-text="App.i18n('fleet.deployment.status_change_of', { alias: changing.alias })"></span>
        </x-slot>

        <form data-form x-init="App.parsley.bind($el)" @submit.prevent="changeStatus($event)" class="grid gap-4">
            <label data-validator-field class="grid gap-1">
                <span class="text-sm font-semibold text-card-title-txt">{{ __('fleet.deployment.field.status') }}</span>
                <select required x-model="changing.status" class="field border-field-border">
                    <template x-for="status in statuses" :key="status.value">
                        <option :value="status.value" x-text="status.label" :disabled="status.value === changing.from"></option>
                    </template>
                </select>
                <p class="text-sm font-semibold text-error-accent" x-show="errorFor('status')" x-text="errorFor('status')"></p>
            </label>

            <label data-validator-field class="grid gap-1">
                <span class="text-sm font-semibold text-card-title-txt">{{ __('fleet.deployment.field.changed_at') }}</span>
                <x-ui.date
                    name="changed_at"
                    class="w-full"
                    required
                    time
                    today
                    panel-parent="#deployment-status"
                    x-model.lazy="changing.changed_at"
                />
                <p class="text-xs text-card-txt" x-show="!errorFor('changed_at')">{{ __('fleet.deployment.hint.changed_at') }}</p>
                <p class="text-sm font-semibold text-error-accent" x-show="errorFor('changed_at')" x-text="errorFor('changed_at')"></p>
            </label>

            <div class="flex flex-wrap items-center gap-2">
                <x-ui.button type="submit" icon="button-ok">{{ __('fleet.deployment.action.change_status') }}</x-ui.button>

                <button type="button" x-modal:close
                        class="ms-auto cursor-pointer border-0 bg-transparent text-sm text-card-txt transition hover:text-card-title-txt">
                    {{ __('fleet.deployment.action.cancel') }}
                </button>
            </div>
        </form>
    </x-ui.modal>

    <x-ui.modal name="deployment-rename" size="sm" @modal:showing="openRename($event.detail)">
        <x-slot:header>{{ __('fleet.deployment.rename') }}</x-slot>

        <form data-form x-init="App.parsley.bind($el)" @submit.prevent="rename($event)" class="grid gap-4">
            <label data-validator-field class="grid gap-1">
                <span class="text-sm font-semibold text-card-title-txt">{{ __('fleet.deployment.field.alias') }}</span>
                <input type="text" required autofocus maxlength="{{ DeploymentAlias::LENGTH }}"
                       pattern="{{ DeploymentAlias::PATTERN }}" data-validator-pattern-message="{{ __('fleet.deployment.hint.alias') }}"
                       x-model="renaming.alias"
                       class="field" :class="errorFor('alias') ? 'border-error-accent' : 'border-field-border'">
                <p class="text-sm font-semibold text-error-accent" x-show="errorFor('alias')" x-text="errorFor('alias')"></p>
            </label>

            <div class="flex flex-wrap items-center gap-2">
                <x-ui.button type="submit" icon="button-ok">{{ __('fleet.deployment.action.rename') }}</x-ui.button>

                <button type="button" x-modal:close
                        class="ms-auto cursor-pointer border-0 bg-transparent text-sm text-card-txt transition hover:text-card-title-txt">
                    {{ __('fleet.deployment.action.cancel') }}
                </button>
            </div>
        </form>
    </x-ui.modal>
</div>
@endsection

@pageScript('resources/js/pages/fleet/deployment-register.js')
