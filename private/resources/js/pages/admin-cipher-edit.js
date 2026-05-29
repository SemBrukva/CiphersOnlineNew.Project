/**
 * Инициализирует страницу админского редактирования шифра и связанного контента.
 */
export function initAdminCipherEdit() {
    const root = document.querySelector('[data-page="admin-cipher-edit"]')

    if (!root) {
        return
    }

    const form = root.querySelector('form')
    const saveButton = root.querySelector('[data-role="save-cipher"]')
    const alertBox = document.querySelector('[data-role="save-alert"]')

    if (!(form instanceof HTMLFormElement) || !(saveButton instanceof HTMLButtonElement) || !(alertBox instanceof HTMLElement)) {
        return
    }

    const cipherId = Number(root.getAttribute('data-cipher-id') || '0')
    const csrfToken = String(root.getAttribute('data-csrf-token') || '')

    let newItemCounter = 0
    /** @type {Set<number>} */
    const deletedIds = { block: new Set(), faq: new Set(), example: new Set(), tag: new Set() }

    // ── Алерт ─────────────────────────────────────────────────────────

    const showAlert = (kind, message) => {
        alertBox.className = `alert alert-${kind} mb-4`
        alertBox.textContent = message
        alertBox.classList.remove('d-none')
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
    }

    const clearAlert = () => {
        alertBox.classList.add('d-none')
        alertBox.textContent = ''
    }

    // ── Получение языков из DOM ────────────────────────────────────────

    const getLanguages = () =>
        Array.from(root.querySelectorAll('[data-language]'))
            .map((el) => el.getAttribute('data-language'))
            .filter(Boolean)

    // ── Генераторы HTML для новых сущностей ───────────────────────────

    const entityHeadHtml = (tempId) => `
        <div class="cipher-entity-head d-flex align-items-center gap-3 px-3 py-2 bg-warning-subtle border-bottom" style="border-radius: calc(var(--bs-border-radius) - 1px) calc(var(--bs-border-radius) - 1px) 0 0">
            <span class="badge bg-warning text-dark small">Новый</span>
            <div class="d-flex align-items-center gap-1 ms-auto">
                <span class="text-muted small me-1">Сорт.</span>
                <input type="number" class="form-control form-control-sm entity-sort-input"
                       min="0" max="999999" data-meta-field="sort_order" value="0">
            </div>
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" role="switch"
                       data-meta-field="published" checked>
                <label class="form-check-label small text-muted">Вкл.</label>
            </div>
            <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-1"
                    data-action="delete-item" title="Удалить">
                <i class="bi bi-trash3"></i>
            </button>
        </div>`

    const newBlockHtml = (tempId, language) => `
        <div class="cipher-entity border rounded" data-entity="block" data-new-id="${tempId}">
            ${entityHeadHtml(tempId)}
            <div class="p-3">
                <div class="mb-3">
                    <label class="form-label fw-medium">Заголовок</label>
                    <input type="text" class="form-control" data-translation-field="title" value="">
                </div>
                <div class="mb-0">
                    <label class="form-label fw-medium">Текст</label>
                    <textarea class="form-control" rows="4" data-translation-field="text"></textarea>
                </div>
            </div>
        </div>`

    const newFaqHtml = (tempId, language) => `
        <div class="cipher-entity border rounded" data-entity="faq" data-new-id="${tempId}">
            ${entityHeadHtml(tempId)}
            <div class="p-3">
                <div class="mb-3">
                    <label class="form-label fw-medium">Вопрос</label>
                    <input type="text" class="form-control" data-translation-field="question" value="">
                </div>
                <div class="mb-0">
                    <label class="form-label fw-medium">Ответ</label>
                    <textarea class="form-control" rows="4" data-translation-field="answer"></textarea>
                </div>
            </div>
        </div>`

    const newExampleHtml = (tempId, language) => `
        <div class="cipher-entity border rounded" data-entity="example" data-new-id="${tempId}">
            ${entityHeadHtml(tempId)}
            <div class="p-3">
                <div class="mb-3">
                    <label class="form-label fw-medium">Ключ (key)</label>
                    <input type="text" class="form-control" data-translation-field="key" value="">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium">Заголовок</label>
                    <input type="text" class="form-control" data-translation-field="title" value="">
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Input</label>
                        <textarea class="form-control" rows="3" data-translation-field="input"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Output</label>
                        <textarea class="form-control" rows="3" data-translation-field="output"></textarea>
                    </div>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-medium">Описание</label>
                    <textarea class="form-control" rows="3" data-translation-field="description"></textarea>
                </div>
            </div>
        </div>`

    const newTagHtml = (tempId, language) => `
        <div class="cipher-entity border rounded" data-entity="tag" data-new-id="${tempId}">
            ${entityHeadHtml(tempId)}
            <div class="p-3">
                <label class="form-label fw-medium">Tag (${language.toUpperCase()})</label>
                <input type="text" class="form-control" maxlength="100"
                       data-translation-field="tag" value="">
            </div>
        </div>`

    const generators = {
        block: newBlockHtml,
        faq: newFaqHtml,
        example: newExampleHtml,
        tag: newTagHtml,
    }

    // ── Добавление новых элементов ─────────────────────────────────────

    const expandCollapse = (collapseEl, toggleEl) => {
        if (!collapseEl || collapseEl.classList.contains('show')) return
        collapseEl.classList.add('show')
        if (toggleEl) toggleEl.setAttribute('aria-expanded', 'true')
    }

    const addNewItem = (entityType) => {
        newItemCounter++
        const tempId = `new-${newItemCounter}`
        const generator = generators[entityType]

        root.querySelectorAll('[data-language]').forEach((section) => {
            const language = section.getAttribute('data-language') || 'en'
            const list = section.querySelector(`[data-entity-list="${entityType}s"]`)
            if (!list) return
            list.insertAdjacentHTML('beforeend', generator(tempId, language))

            // Раскрыть collapse в активной вкладке
            if (section.classList.contains('show')) {
                const collapseEl = list.closest('.collapse')
                const toggleEl = collapseEl
                    ? root.querySelector(`[data-bs-target="#${collapseEl.id}"]`)
                    : null
                expandCollapse(collapseEl, toggleEl)
            }
        })
    }

    // ── Удаление элементов ────────────────────────────────────────────

    const deleteItem = (button) => {
        const item = button.closest('[data-entity]')
        if (!item) return

        const entityType = item.getAttribute('data-entity')
        const id = Number(item.getAttribute('data-id') || '0')
        const newId = item.getAttribute('data-new-id')

        if (newId) {
            root.querySelectorAll(`[data-entity="${entityType}"][data-new-id="${newId}"]`).forEach((el) => el.remove())
            return
        }

        if (id > 0 && deletedIds[entityType]) {
            deletedIds[entityType].add(id)
            root.querySelectorAll(`[data-entity="${entityType}"][data-id="${id}"]`).forEach((el) => {
                el.style.opacity = '0.35'
                el.style.pointerEvents = 'none'
                el.setAttribute('data-deleted', 'true')
            })
        }
    }

    // ── Делегирование кликов ──────────────────────────────────────────

    root.addEventListener('click', (e) => {
        const actionEl = e.target.closest('[data-action]')
        if (!actionEl) return

        e.preventDefault()
        const action = actionEl.getAttribute('data-action')

        switch (action) {
            case 'add-block':   addNewItem('block');   break
            case 'add-faq':     addNewItem('faq');     break
            case 'add-example': addNewItem('example'); break
            case 'add-tag':     addNewItem('tag');     break
            case 'delete-item': deleteItem(actionEl);  break
        }
    })

    // ── Сбор payload ──────────────────────────────────────────────────

    const collectPayload = () => {
        const payload = {
            settings: {
                alias:       String(root.querySelector('[name="alias"]')?.value ?? '').trim(),
                category_id: Number(root.querySelector('[name="category_id"]')?.value ?? 0),
                calculation_mode: String(root.querySelector('[name="calculation_mode"]')?.value ?? 'client').trim().toLowerCase(),
                sort_order:  Number(root.querySelector('[name="sort_order"]')?.value ?? 0),
                published:   Boolean(root.querySelector('[name="published"]')?.checked ?? false),
            },
            translations: {},
            blocks:    [],
            faq:       [],
            examples:  [],
            tags:      [],
            new_blocks:    [],
            new_faq:       [],
            new_examples:  [],
            new_tags:      [],
            delete_blocks:   [...deletedIds.block],
            delete_faq:      [...deletedIds.faq],
            delete_examples: [...deletedIds.example],
            delete_tags:     [...deletedIds.tag],
        }

        root.querySelectorAll('[data-language]').forEach((section) => {
            const language = String(section.getAttribute('data-language') || '').trim().toLowerCase()
            if (!language) return

            payload.translations[language] = {
                name:             String(section.querySelector('[data-cipher-field="name"]')?.value ?? '').trim(),
                name_short:       String(section.querySelector('[data-cipher-field="name_short"]')?.value ?? '').trim(),
                description:      String(section.querySelector('[data-cipher-field="description"]')?.value ?? '').trim(),
                description_stort: String(section.querySelector('[data-cipher-field="description_stort"]')?.value ?? '').trim(),
                meta_title:       String(section.querySelector('[data-cipher-field="meta_title"]')?.value ?? '').trim(),
                meta_description: String(section.querySelector('[data-cipher-field="meta_description"]')?.value ?? '').trim(),
            }

            section.querySelectorAll('[data-entity="block"]:not([data-deleted])').forEach((item) => {
                const id    = Number(item.getAttribute('data-id') || '0')
                const newId = item.getAttribute('data-new-id')

                if (newId) {
                    let row = payload.new_blocks.find((r) => r.temp_id === newId)
                    if (!row) {
                        row = {
                            temp_id:    newId,
                            sort_order: Number(item.querySelector('[data-meta-field="sort_order"]')?.value ?? 0),
                            published:  item.querySelector('[data-meta-field="published"]')?.checked ? 1 : 0,
                            translations: {},
                        }
                        payload.new_blocks.push(row)
                    }
                    row.translations[language] = {
                        title: String(item.querySelector('[data-translation-field="title"]')?.value ?? '').trim(),
                        text:  String(item.querySelector('[data-translation-field="text"]')?.value ?? '').trim(),
                    }
                } else if (id) {
                    let row = payload.blocks.find((r) => r.id === id)
                    if (!row) {
                        row = {
                            id,
                            sort_order: Number(item.querySelector('[data-meta-field="sort_order"]')?.value ?? 0),
                            published:  item.querySelector('[data-meta-field="published"]')?.checked ? 1 : 0,
                            translations: {},
                        }
                        payload.blocks.push(row)
                    }
                    row.translations[language] = {
                        title: String(item.querySelector('[data-translation-field="title"]')?.value ?? '').trim(),
                        text:  String(item.querySelector('[data-translation-field="text"]')?.value ?? '').trim(),
                    }
                }
            })

            section.querySelectorAll('[data-entity="faq"]:not([data-deleted])').forEach((item) => {
                const id    = Number(item.getAttribute('data-id') || '0')
                const newId = item.getAttribute('data-new-id')

                if (newId) {
                    let row = payload.new_faq.find((r) => r.temp_id === newId)
                    if (!row) {
                        row = {
                            temp_id:    newId,
                            sort_order: Number(item.querySelector('[data-meta-field="sort_order"]')?.value ?? 0),
                            published:  item.querySelector('[data-meta-field="published"]')?.checked ? 1 : 0,
                            translations: {},
                        }
                        payload.new_faq.push(row)
                    }
                    row.translations[language] = {
                        question: String(item.querySelector('[data-translation-field="question"]')?.value ?? '').trim(),
                        answer:   String(item.querySelector('[data-translation-field="answer"]')?.value ?? '').trim(),
                    }
                } else if (id) {
                    let row = payload.faq.find((r) => r.id === id)
                    if (!row) {
                        row = {
                            id,
                            sort_order: Number(item.querySelector('[data-meta-field="sort_order"]')?.value ?? 0),
                            published:  item.querySelector('[data-meta-field="published"]')?.checked ? 1 : 0,
                            translations: {},
                        }
                        payload.faq.push(row)
                    }
                    row.translations[language] = {
                        question: String(item.querySelector('[data-translation-field="question"]')?.value ?? '').trim(),
                        answer:   String(item.querySelector('[data-translation-field="answer"]')?.value ?? '').trim(),
                    }
                }
            })

            section.querySelectorAll('[data-entity="example"]:not([data-deleted])').forEach((item) => {
                const id    = Number(item.getAttribute('data-id') || '0')
                const newId = item.getAttribute('data-new-id')

                if (newId) {
                    let row = payload.new_examples.find((r) => r.temp_id === newId)
                    if (!row) {
                        row = {
                            temp_id:    newId,
                            sort_order: Number(item.querySelector('[data-meta-field="sort_order"]')?.value ?? 0),
                            published:  item.querySelector('[data-meta-field="published"]')?.checked ? 1 : 0,
                            translations: {},
                        }
                        payload.new_examples.push(row)
                    }
                    row.translations[language] = {
                        title:       String(item.querySelector('[data-translation-field="title"]')?.value ?? '').trim(),
                        input:       String(item.querySelector('[data-translation-field="input"]')?.value ?? '').trim(),
                        output:      String(item.querySelector('[data-translation-field="output"]')?.value ?? '').trim(),
                        description: String(item.querySelector('[data-translation-field="description"]')?.value ?? '').trim(),
                        key:         String(item.querySelector('[data-translation-field="key"]')?.value ?? '').trim(),
                    }
                } else if (id) {
                    let row = payload.examples.find((r) => r.id === id)
                    if (!row) {
                        row = {
                            id,
                            sort_order: Number(item.querySelector('[data-meta-field="sort_order"]')?.value ?? 0),
                            published:  item.querySelector('[data-meta-field="published"]')?.checked ? 1 : 0,
                            translations: {},
                        }
                        payload.examples.push(row)
                    }
                    row.translations[language] = {
                        title:       String(item.querySelector('[data-translation-field="title"]')?.value ?? '').trim(),
                        input:       String(item.querySelector('[data-translation-field="input"]')?.value ?? '').trim(),
                        output:      String(item.querySelector('[data-translation-field="output"]')?.value ?? '').trim(),
                        description: String(item.querySelector('[data-translation-field="description"]')?.value ?? '').trim(),
                        key:         String(item.querySelector('[data-translation-field="key"]')?.value ?? '').trim(),
                    }
                }
            })

            section.querySelectorAll('[data-entity="tag"]:not([data-deleted])').forEach((item) => {
                const id    = Number(item.getAttribute('data-id') || '0')
                const newId = item.getAttribute('data-new-id')

                if (newId) {
                    let row = payload.new_tags.find((r) => r.temp_id === newId)
                    if (!row) {
                        row = {
                            temp_id:    newId,
                            sort_order: Number(item.querySelector('[data-meta-field="sort_order"]')?.value ?? 0),
                            published:  item.querySelector('[data-meta-field="published"]')?.checked ? 1 : 0,
                            translations: {},
                        }
                        payload.new_tags.push(row)
                    }
                    row.translations[language] = {
                        tag: String(item.querySelector('[data-translation-field="tag"]')?.value ?? '').trim(),
                    }
                } else if (id) {
                    let row = payload.tags.find((r) => r.id === id)
                    if (!row) {
                        row = {
                            id,
                            sort_order: Number(item.querySelector('[data-meta-field="sort_order"]')?.value ?? 0),
                            published:  item.querySelector('[data-meta-field="published"]')?.checked ? 1 : 0,
                            translations: {},
                        }
                        payload.tags.push(row)
                    }
                    row.translations[language] = {
                        tag: String(item.querySelector('[data-translation-field="tag"]')?.value ?? '').trim(),
                    }
                }
            })
        })

        return payload
    }

    // ── Обновление DOM после сохранения ───────────────────────────────

    const applyCreated = (created) => {
        const entityMap = {
            blocks:   'block',
            faq:      'faq',
            examples: 'example',
            tags:     'tag',
        }

        Object.entries(entityMap).forEach(([key, entityType]) => {
            const items = created?.[key] ?? []
            items.forEach(({ temp_id, id }) => {
                root.querySelectorAll(`[data-entity="${entityType}"][data-new-id="${temp_id}"]`).forEach((el) => {
                    el.setAttribute('data-id', id)
                    el.removeAttribute('data-new-id')

                    const head = el.querySelector('.cipher-entity-head')
                    if (head) {
                        head.classList.remove('bg-warning-subtle')
                        head.classList.add('bg-light')
                        head.style.borderRadius = ''
                        const badge = head.querySelector('.badge')
                        if (badge) {
                            badge.className = 'badge bg-secondary-subtle text-secondary font-monospace'
                            badge.innerHTML = `#<span data-role="entity-id">${id}</span>`
                        }
                    }
                })
            })
        })

        deletedIds.block.clear()
        deletedIds.faq.clear()
        deletedIds.example.clear()
        deletedIds.tag.clear()
    }

    // ── Сохранение ────────────────────────────────────────────────────

    form.addEventListener('submit', async (event) => {
        event.preventDefault()
        clearAlert()

        if (!cipherId || !csrfToken) {
            showAlert('danger', 'Не удалось определить служебные параметры формы.')
            return
        }

        saveButton.disabled = true
        const originalHtml = saveButton.innerHTML
        saveButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Сохранение…'

        try {
            const payload = collectPayload()
            const response = await window.api.admin.saveCipher(cipherId, payload, csrfToken)
            applyCreated(response?.created)
            showAlert('success', 'Шифр и контент сохранены.')
        } catch (error) {
            const message = error?.response?.error?.message || error?.message || 'Ошибка сохранения.'
            showAlert('danger', message)
        } finally {
            saveButton.disabled = false
            saveButton.innerHTML = originalHtml
        }
    })
}
