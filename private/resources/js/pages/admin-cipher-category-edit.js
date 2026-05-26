/**
 * Инициализирует страницу комплексного редактирования категории шифров и переводов.
 */
export function initAdminCipherCategoryEdit() {
    const root = document.querySelector('[data-page="admin-cipher-category-edit"]')

    if (!root) {
        return
    }

    let newItemCounter = 0
    /** @type {{block:Set<number>, task:Set<number>, used_together:Set<number>, faq:Set<number>}} */
    const deletedIds = { block: new Set(), task: new Set(), used_together: new Set(), faq: new Set() }

    const saveButton = root.querySelector('[data-role="save-category"]')
    const alertBox = root.querySelector('[data-role="save-alert"]')
    const form = root.querySelector('form')

    if (!(saveButton instanceof HTMLButtonElement) || !(alertBox instanceof HTMLElement) || !(form instanceof HTMLFormElement)) {
        return
    }

    const categoryId = Number(root.getAttribute('data-category-id') || '0')
    const csrfToken = String(root.getAttribute('data-csrf-token') || '')

    const showAlert = (kind, message) => {
        alertBox.className = `alert alert-${kind}`
        alertBox.textContent = message
        alertBox.classList.remove('d-none')
    }

    const clearAlert = () => {
        alertBox.classList.add('d-none')
        alertBox.textContent = ''
    }

    const entityHeadHtml = () => `
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

    const newBlockHtml = (tempId) => `
        <div class="cipher-entity border rounded" data-entity="block" data-new-id="${tempId}">
            ${entityHeadHtml()}
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

    const taskOptionsHtml = () => {
        const options = ['<option value="0">Не выбран</option>']

        root.querySelectorAll('[data-role="task-cipher-option"]').forEach((option) => {
            const id = String(option.getAttribute('data-id') || '0')
            const label = String(option.getAttribute('data-label') || '')
            options.push(`<option value="${id}">${label}</option>`)
        })

        return options.join('')
    }

    const newTaskHtml = (tempId) => `
        <div class="cipher-entity border rounded" data-entity="task" data-new-id="${tempId}">
            ${entityHeadHtml()}
            <div class="p-3">
                <div class="mb-3">
                    <label class="form-label fw-medium">Связанный шифр</label>
                    <select class="form-select" data-meta-field="relation_cipher_id">
                        ${taskOptionsHtml()}
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium">Title</label>
                    <input type="text" class="form-control" data-translation-field="title" value="">
                </div>
                <div class="mb-0">
                    <label class="form-label fw-medium">Description</label>
                    <textarea class="form-control" rows="3" data-translation-field="description"></textarea>
                </div>
            </div>
        </div>`

    const newUsedTogetherHtml = (tempId) => `
        <div class="cipher-entity border rounded" data-entity="used_together" data-new-id="${tempId}">
            ${entityHeadHtml()}
            <div class="p-3">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Первый шифр</label>
                        <select class="form-select" data-meta-field="relation_cipher_first_id">
                            ${taskOptionsHtml()}
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Второй шифр</label>
                        <select class="form-select" data-meta-field="relation_cipher_second_id">
                            ${taskOptionsHtml()}
                        </select>
                    </div>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-medium">Title</label>
                    <textarea class="form-control" rows="3" data-translation-field="title"></textarea>
                </div>
            </div>
        </div>`

    const newFaqHtml = (tempId) => `
        <div class="cipher-entity border rounded" data-entity="faq" data-new-id="${tempId}">
            ${entityHeadHtml()}
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

    const addNewBlock = () => {
        newItemCounter++
        const tempId = `new-${newItemCounter}`

        root.querySelectorAll('[data-language]').forEach((section) => {
            const list = section.querySelector('[data-entity-list="blocks"]')
            if (!list) {
                return
            }

            list.insertAdjacentHTML('beforeend', newBlockHtml(tempId))
        })
    }

    const addNewTask = () => {
        newItemCounter++
        const tempId = `new-${newItemCounter}`

        root.querySelectorAll('[data-language]').forEach((section) => {
            const list = section.querySelector('[data-entity-list="tasks"]')
            if (!list) {
                return
            }

            list.insertAdjacentHTML('beforeend', newTaskHtml(tempId))
        })
    }

    const addNewUsedTogether = () => {
        newItemCounter++
        const tempId = `new-${newItemCounter}`

        root.querySelectorAll('[data-language]').forEach((section) => {
            const list = section.querySelector('[data-entity-list="used_together"]')
            if (!list) {
                return
            }

            list.insertAdjacentHTML('beforeend', newUsedTogetherHtml(tempId))
        })
    }

    const addNewFaq = () => {
        newItemCounter++
        const tempId = `new-${newItemCounter}`

        root.querySelectorAll('[data-language]').forEach((section) => {
            const list = section.querySelector('[data-entity-list="faq"]')
            if (!list) {
                return
            }

            list.insertAdjacentHTML('beforeend', newFaqHtml(tempId))
        })
    }

    const deleteItem = (button) => {
        const item = button.closest('[data-entity]')
        if (!item) {
            return
        }

        const entity = String(item.getAttribute('data-entity') || '')
        const id = Number(item.getAttribute('data-id') || '0')
        const newId = item.getAttribute('data-new-id')

        if (newId) {
            root.querySelectorAll(`[data-entity="${entity}"][data-new-id="${newId}"]`).forEach((el) => el.remove())
            return
        }

        if (id > 0 && (entity === 'block' || entity === 'task' || entity === 'used_together' || entity === 'faq')) {
            deletedIds[entity].add(id)
            root.querySelectorAll(`[data-entity="${entity}"][data-id="${id}"]`).forEach((el) => {
                el.style.opacity = '0.35'
                el.style.pointerEvents = 'none'
                el.setAttribute('data-deleted', 'true')
            })
        }
    }

    root.addEventListener('click', (event) => {
        const actionEl = event.target.closest('[data-action]')
        if (!actionEl) {
            return
        }

        event.preventDefault()
        const action = actionEl.getAttribute('data-action')

        if (action === 'add-block') {
            addNewBlock()
        }

        if (action === 'add-task') {
            addNewTask()
        }

        if (action === 'add-used-together') {
            addNewUsedTogether()
        }

        if (action === 'add-faq') {
            addNewFaq()
        }

        if (action === 'delete-item') {
            deleteItem(actionEl)
        }
    })

    form.addEventListener('submit', async (event) => {
        event.preventDefault()
        clearAlert()

        if (!categoryId || !csrfToken) {
            showAlert('danger', 'Не удалось определить служебные параметры формы.')
            return
        }

        const payload = {
            settings: {
                alias: String(root.querySelector('[name="alias"]')?.value ?? '').trim(),
                category: String(root.querySelector('[name="category"]')?.value ?? 'cipher').trim().toLowerCase(),
                sort_order: Number(root.querySelector('[name="sort_order"]')?.value ?? 0),
                published: Boolean(root.querySelector('[name="published"]')?.checked ?? false),
            },
            translations: {},
            blocks: [],
            new_blocks: [],
            delete_blocks: [...deletedIds.block],
            tasks: [],
            new_tasks: [],
            delete_tasks: [...deletedIds.task],
            used_together: [],
            new_used_together: [],
            delete_used_together: [...deletedIds.used_together],
            faq: [],
            new_faq: [],
            delete_faq: [...deletedIds.faq],
        }

        root.querySelectorAll('[data-language]').forEach((section) => {
            const language = String(section.getAttribute('data-language') || '').trim().toLowerCase()

            if (!language) {
                return
            }

            payload.translations[language] = {
                name: String(section.querySelector('[data-field="name"]')?.value ?? '').trim(),
                description: String(section.querySelector('[data-field="description"]')?.value ?? '').trim(),
                meta_title: String(section.querySelector('[data-field="meta_title"]')?.value ?? '').trim(),
                meta_description: String(section.querySelector('[data-field="meta_description"]')?.value ?? '').trim(),
            }

            section.querySelectorAll('[data-entity="block"]:not([data-deleted])').forEach((item) => {
                const id = Number(item.getAttribute('data-id') || '0')
                const newId = item.getAttribute('data-new-id')

                if (newId) {
                    let row = payload.new_blocks.find((r) => r.temp_id === newId)
                    if (!row) {
                        row = {
                            temp_id: newId,
                            sort_order: Number(item.querySelector('[data-meta-field="sort_order"]')?.value ?? 0),
                            published: item.querySelector('[data-meta-field="published"]')?.checked ? 1 : 0,
                            translations: {},
                        }
                        payload.new_blocks.push(row)
                    }

                    row.translations[language] = {
                        title: String(item.querySelector('[data-translation-field="title"]')?.value ?? '').trim(),
                        text: String(item.querySelector('[data-translation-field="text"]')?.value ?? '').trim(),
                    }
                } else if (id > 0) {
                    let row = payload.blocks.find((r) => r.id === id)
                    if (!row) {
                        row = {
                            id,
                            sort_order: Number(item.querySelector('[data-meta-field="sort_order"]')?.value ?? 0),
                            published: item.querySelector('[data-meta-field="published"]')?.checked ? 1 : 0,
                            translations: {},
                        }
                        payload.blocks.push(row)
                    }

                    row.translations[language] = {
                        title: String(item.querySelector('[data-translation-field="title"]')?.value ?? '').trim(),
                        text: String(item.querySelector('[data-translation-field="text"]')?.value ?? '').trim(),
                    }
                }
            })

            section.querySelectorAll('[data-entity="task"]:not([data-deleted])').forEach((item) => {
                const id = Number(item.getAttribute('data-id') || '0')
                const newId = item.getAttribute('data-new-id')

                if (newId) {
                    let row = payload.new_tasks.find((r) => r.temp_id === newId)
                    if (!row) {
                        row = {
                            temp_id: newId,
                            relation_cipher_id: Number(item.querySelector('[data-meta-field="relation_cipher_id"]')?.value ?? 0),
                            sort_order: Number(item.querySelector('[data-meta-field="sort_order"]')?.value ?? 0),
                            published: item.querySelector('[data-meta-field="published"]')?.checked ? 1 : 0,
                            translations: {},
                        }
                        payload.new_tasks.push(row)
                    }

                    row.translations[language] = {
                        title: String(item.querySelector('[data-translation-field="title"]')?.value ?? '').trim(),
                        description: String(item.querySelector('[data-translation-field="description"]')?.value ?? '').trim(),
                    }
                } else if (id > 0) {
                    let row = payload.tasks.find((r) => r.id === id)
                    if (!row) {
                        row = {
                            id,
                            relation_cipher_id: Number(item.querySelector('[data-meta-field="relation_cipher_id"]')?.value ?? 0),
                            sort_order: Number(item.querySelector('[data-meta-field="sort_order"]')?.value ?? 0),
                            published: item.querySelector('[data-meta-field="published"]')?.checked ? 1 : 0,
                            translations: {},
                        }
                        payload.tasks.push(row)
                    }

                    row.translations[language] = {
                        title: String(item.querySelector('[data-translation-field="title"]')?.value ?? '').trim(),
                        description: String(item.querySelector('[data-translation-field="description"]')?.value ?? '').trim(),
                    }
                }
            })

            section.querySelectorAll('[data-entity="used_together"]:not([data-deleted])').forEach((item) => {
                const id = Number(item.getAttribute('data-id') || '0')
                const newId = item.getAttribute('data-new-id')

                if (newId) {
                    let row = payload.new_used_together.find((r) => r.temp_id === newId)
                    if (!row) {
                        row = {
                            temp_id: newId,
                            relation_cipher_first_id: Number(item.querySelector('[data-meta-field="relation_cipher_first_id"]')?.value ?? 0),
                            relation_cipher_second_id: Number(item.querySelector('[data-meta-field="relation_cipher_second_id"]')?.value ?? 0),
                            sort_order: Number(item.querySelector('[data-meta-field="sort_order"]')?.value ?? 0),
                            published: item.querySelector('[data-meta-field="published"]')?.checked ? 1 : 0,
                            translations: {},
                        }
                        payload.new_used_together.push(row)
                    }

                    row.translations[language] = {
                        title: String(item.querySelector('[data-translation-field="title"]')?.value ?? '').trim(),
                    }
                } else if (id > 0) {
                    let row = payload.used_together.find((r) => r.id === id)
                    if (!row) {
                        row = {
                            id,
                            relation_cipher_first_id: Number(item.querySelector('[data-meta-field="relation_cipher_first_id"]')?.value ?? 0),
                            relation_cipher_second_id: Number(item.querySelector('[data-meta-field="relation_cipher_second_id"]')?.value ?? 0),
                            sort_order: Number(item.querySelector('[data-meta-field="sort_order"]')?.value ?? 0),
                            published: item.querySelector('[data-meta-field="published"]')?.checked ? 1 : 0,
                            translations: {},
                        }
                        payload.used_together.push(row)
                    }

                    row.translations[language] = {
                        title: String(item.querySelector('[data-translation-field="title"]')?.value ?? '').trim(),
                    }
                }
            })

            section.querySelectorAll('[data-entity="faq"]:not([data-deleted])').forEach((item) => {
                const id = Number(item.getAttribute('data-id') || '0')
                const newId = item.getAttribute('data-new-id')

                if (newId) {
                    let row = payload.new_faq.find((r) => r.temp_id === newId)
                    if (!row) {
                        row = {
                            temp_id: newId,
                            sort_order: Number(item.querySelector('[data-meta-field="sort_order"]')?.value ?? 0),
                            published: item.querySelector('[data-meta-field="published"]')?.checked ? 1 : 0,
                            translations: {},
                        }
                        payload.new_faq.push(row)
                    }

                    row.translations[language] = {
                        question: String(item.querySelector('[data-translation-field="question"]')?.value ?? '').trim(),
                        answer: String(item.querySelector('[data-translation-field="answer"]')?.value ?? '').trim(),
                    }
                } else if (id > 0) {
                    let row = payload.faq.find((r) => r.id === id)
                    if (!row) {
                        row = {
                            id,
                            sort_order: Number(item.querySelector('[data-meta-field="sort_order"]')?.value ?? 0),
                            published: item.querySelector('[data-meta-field="published"]')?.checked ? 1 : 0,
                            translations: {},
                        }
                        payload.faq.push(row)
                    }

                    row.translations[language] = {
                        question: String(item.querySelector('[data-translation-field="question"]')?.value ?? '').trim(),
                        answer: String(item.querySelector('[data-translation-field="answer"]')?.value ?? '').trim(),
                    }
                }
            })
        })

        saveButton.disabled = true
        const originalText = saveButton.textContent
        saveButton.textContent = 'Сохранение...'

        try {
            const response = await window.api.admin.saveCipherCategory(categoryId, payload, csrfToken)
            const createdBlocks = response?.created?.blocks ?? []
            const createdTasks = response?.created?.tasks ?? []
            const createdUsedTogether = response?.created?.used_together ?? []
            const createdFaq = response?.created?.faq ?? []

            createdBlocks.forEach(({ temp_id, id }) => {
                root.querySelectorAll(`[data-entity="block"][data-new-id="${temp_id}"]`).forEach((el) => {
                    el.setAttribute('data-id', String(id))
                    el.removeAttribute('data-new-id')
                    const badge = el.querySelector('.badge')
                    if (badge) {
                        badge.className = 'badge bg-secondary-subtle text-secondary font-monospace'
                        badge.textContent = `#${id}`
                    }
                    const head = el.querySelector('.cipher-entity-head')
                    if (head) {
                        head.classList.remove('bg-warning-subtle')
                        head.classList.add('bg-light')
                        head.style.borderRadius = ''
                    }
                })
            })

            createdTasks.forEach(({ temp_id, id }) => {
                root.querySelectorAll(`[data-entity="task"][data-new-id="${temp_id}"]`).forEach((el) => {
                    el.setAttribute('data-id', String(id))
                    el.removeAttribute('data-new-id')
                    const badge = el.querySelector('.badge')
                    if (badge) {
                        badge.className = 'badge bg-secondary-subtle text-secondary font-monospace'
                        badge.textContent = `#${id}`
                    }
                    const head = el.querySelector('.cipher-entity-head')
                    if (head) {
                        head.classList.remove('bg-warning-subtle')
                        head.classList.add('bg-light')
                        head.style.borderRadius = ''
                    }
                })
            })

            createdUsedTogether.forEach(({ temp_id, id }) => {
                root.querySelectorAll(`[data-entity="used_together"][data-new-id="${temp_id}"]`).forEach((el) => {
                    el.setAttribute('data-id', String(id))
                    el.removeAttribute('data-new-id')
                    const badge = el.querySelector('.badge')
                    if (badge) {
                        badge.className = 'badge bg-secondary-subtle text-secondary font-monospace'
                        badge.textContent = `#${id}`
                    }
                    const head = el.querySelector('.cipher-entity-head')
                    if (head) {
                        head.classList.remove('bg-warning-subtle')
                        head.classList.add('bg-light')
                        head.style.borderRadius = ''
                    }
                })
            })

            createdFaq.forEach(({ temp_id, id }) => {
                root.querySelectorAll(`[data-entity="faq"][data-new-id="${temp_id}"]`).forEach((el) => {
                    el.setAttribute('data-id', String(id))
                    el.removeAttribute('data-new-id')
                    const badge = el.querySelector('.badge')
                    if (badge) {
                        badge.className = 'badge bg-secondary-subtle text-secondary font-monospace'
                        badge.textContent = `#${id}`
                    }
                    const head = el.querySelector('.cipher-entity-head')
                    if (head) {
                        head.classList.remove('bg-warning-subtle')
                        head.classList.add('bg-light')
                        head.style.borderRadius = ''
                    }
                })
            })

            showAlert('success', 'Категория и переводы сохранены.')
        } catch (error) {
            const message = error?.response?.error?.message || error?.message || 'Ошибка сохранения.'
            showAlert('danger', message)
        } finally {
            saveButton.disabled = false
            saveButton.textContent = originalText || 'Сохранить'
        }
    })
}
