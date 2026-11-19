@php
    $state = $getState();
    $display = filled($state) ? $state : ($getPlaceholder() ?? '—');
@endphp

@if ($isDisabled())
    <span>{{ $display }}</span>
@else
    <div
        wire:ignore.self
        x-data="{
            column: @js($getName()),
            recordKey: @js((string) $getRecordKey()),
            placeholder: @js((string) ($getPlaceholder() ?? '—')),
            editing: false,
            saving: false,
            draft: '',
            error: '',
            justSaved: false,
            savedTimer: null,
            lastTap: 0,
            begin() {
                if (this.saving || this.editing) return

                this.draft = this.$refs.serverValue.value
                this.error = ''
                this.justSaved = false
                this.editing = true

                this.$nextTick(() => {
                    this.$refs.input.focus()
                    this.$refs.input.select()
                })
            },
            click(event) {
                if (event.detail === 0) {
                    this.begin()
                    return
                }

                if (event.pointerType === 'touch') {
                    const now = Date.now()
                    if (now - this.lastTap < 450) {
                        this.begin()
                        this.lastTap = 0
                    } else {
                        this.lastTap = now
                    }
                }
            },
            cancel() {
                if (this.saving) return

                this.editing = false
                this.error = ''
                this.$nextTick(() => this.$refs.trigger.focus())
            },
            triggers(scope) {
                return Array.from(scope?.querySelectorAll('[data-inline-edit-column]') ?? [])
                    .filter(trigger => trigger.dataset.inlineEditColumn === this.column)
            },
            tab(event) {
                const scope = this.$root.closest('table')
                const triggers = this.triggers(scope)
                const index = triggers.indexOf(this.$refs.trigger)
                const next = triggers[index + (event.shiftKey ? -1 : 1)]

                if (index === -1 || !next) return

                event.preventDefault()
                this.save({ scope, recordKey: next.dataset.inlineEditRecord }, true)
            },
            finish(next, restoreFocus) {
                this.editing = false
                this.$nextTick(() => {
                    if (next) {
                        const trigger = this.triggers(next.scope)
                            .find(trigger => trigger.dataset.inlineEditRecord === next.recordKey)
                        trigger?.dispatchEvent(new CustomEvent('inline-edit-start'))
                    } else if (restoreFocus) {
                        this.$refs.trigger?.focus()
                    }
                })
            },
            async save(next = null, restoreFocus = false) {
                if (!this.editing || this.saving) return

                const value = this.draft.trim()

                if (value === this.$refs.serverValue.value) {
                    this.finish(next, restoreFocus)
                    return
                }

                this.saving = true
                this.error = ''

                try {
                    const response = await this.$wire.updateTableColumnState(this.column, this.recordKey, value)

                    if (response?.saved !== true) {
                        this.error = response?.error ?? 'Unable to save. Try again.'
                        return
                    }

                    if (this.$refs.serverValue) {
                        this.$refs.serverValue.value = response.value ?? ''
                    }

                    if (this.$refs.display) {
                        this.$refs.display.textContent = response.value ?? this.placeholder
                    }

                    this.justSaved = true
                    clearTimeout(this.savedTimer)
                    this.savedTimer = setTimeout(() => this.justSaved = false, 1500)
                    this.finish(next, restoreFocus)
                } catch {
                    this.error = 'Unable to save. Try again.'
                } finally {
                    this.saving = false

                    if (this.error && restoreFocus) {
                        this.$nextTick(() => this.$refs.input?.focus())
                    }
                }
            },
            destroy() {
                clearTimeout(this.savedTimer)
            },
        }"
        x-on:click.stop
        x-on:click.outside="save()"
        style="width: {{ $getWidth() ?? '100%' }}; max-width: 100%"
    >
        <input type="hidden" x-ref="serverValue" value="{{ $state ?? '' }}">

        <button
            type="button"
            x-ref="trigger"
            x-show="!editing"
            x-on:click.stop.prevent="click($event)"
            x-on:dblclick.stop.prevent="begin()"
            x-on:inline-edit-start="begin()"
            data-inline-edit-column="{{ $getName() }}"
            data-inline-edit-record="{{ $getRecordKey() }}"
            title="{{ $display }} · Double-click to edit (Enter or Space on keyboard)"
            aria-label="Edit {{ $getLabel() }}: {{ $display }}"
            style="display: inline-flex; align-items: center; gap: 3px; max-width: 100%; cursor: pointer; user-select: none; touch-action: manipulation; outline-offset: 2px"
        >
            <span x-ref="display" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap">{{ $display }}</span>
            <span x-cloak x-show="justSaved" role="status" aria-label="Saved" style="color: #16a34a; font-weight: 600">✓</span>
        </button>

        <div
            x-cloak
            x-show="editing"
            class="fi-input-wrp"
            x-bind:class="{ 'fi-invalid': error !== '', 'fi-disabled': saving }"
            x-tooltip="error ? { content: error, theme: $store.theme } : false"
        >
            <div class="fi-input-wrp-content-ctn">
                <input
                    type="text"
                    inputmode="{{ $getInputMode() }}"
                    class="fi-input"
                    x-ref="input"
                    x-model="draft"
                    x-bind:readonly="saving"
                    x-bind:aria-invalid="error !== ''"
                    x-bind:aria-busy="saving"
                    x-bind:title="error || ''"
                    x-on:keydown.enter.stop.prevent="save(null, true)"
                    x-on:keydown.escape.stop.prevent="cancel()"
                    x-on:keydown.tab="tab($event)"
                    x-on:blur="save()"
                    aria-label="{{ $getLabel() }}"
                    style="width: 100%; padding-inline: 6px"
                >
            </div>
        </div>

        <span x-cloak x-show="error" x-text="error" role="alert" style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%)"></span>
    </div>
@endif
