@props([
    'name' => 'item-editor',
])

{{--
    The item editor in a modal of its own: it opens the editor in the mode its opener asked for and closes when the editor is done.

        <x-inventory.item-editor-modal name="item-editor" @item-editor:created="…" />

        x-modal:open="'item-editor'"                                     a new item
        x-modal:open="{ name: 'item-editor', with: { item: code } }"     that item, to change
        x-modal:open="{ name: 'item-editor', with: { clone: code } }"    a new item from that one
        App.modal('item-editor').show({ item: code })                    the same, outside Alpine

    The host only opens it and listens: every item-editor:* event still reaches the host, and the modal closes itself on
    created, updated and cancelled.
--}}
<x-ui.modal :name="$name" size="xl" static {{ $attributes }} x-data="itemEditorModal({{ \Illuminate\Support\Js::from($name) }})">
    <x-slot:header>
        <span x-text="mode === 'edit' ? @js(__('inventory.item.editor.title_edit')) : @js(__('inventory.item.editor.title_new'))"></span>
    </x-slot>

    <x-inventory.item-editor :name="$name" />
</x-ui.modal>
