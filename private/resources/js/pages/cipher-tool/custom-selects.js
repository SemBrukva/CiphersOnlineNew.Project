/**
 * Заменяет все `.ciphers-settings-select` на кастомные dropdown,
 * сохраняя нативный select скрытым для совместимости с JS-логикой.
 */
export function initCustomSelects() {
  let documentListenerAdded = false

  document.querySelectorAll('.ciphers-settings-select').forEach((nativeSelect) => {
    if (nativeSelect.dataset.customSelectInit) return
    nativeSelect.dataset.customSelectInit = '1'

    const wrapper = document.createElement('div')
    wrapper.className = 'ciphers-custom-select'
    nativeSelect.parentNode.insertBefore(wrapper, nativeSelect)
    nativeSelect.style.display = 'none'
    wrapper.appendChild(nativeSelect)

    const trigger = document.createElement('button')
    trigger.type = 'button'
    trigger.className = 'ciphers-custom-select__trigger'
    trigger.setAttribute('aria-haspopup', 'listbox')
    trigger.setAttribute('aria-expanded', 'false')

    const dropdown = document.createElement('div')
    dropdown.className = 'ciphers-custom-select__dropdown'
    dropdown.setAttribute('role', 'listbox')

    wrapper.appendChild(trigger)
    wrapper.appendChild(dropdown)

    const updateTrigger = () => {
      const opt = nativeSelect.options[nativeSelect.selectedIndex]
      trigger.textContent = opt ? opt.text : ''
    }

    const refreshOptions = () => {
      dropdown.innerHTML = ''
      Array.from(nativeSelect.options).forEach((opt) => {
        const item = document.createElement('div')
        item.className = 'ciphers-custom-select__option'
        item.setAttribute('role', 'option')
        const isSelected = opt.value === nativeSelect.value
        if (isSelected) item.classList.add('ciphers-custom-select__option--selected')
        item.setAttribute('aria-selected', isSelected ? 'true' : 'false')
        item.dataset.value = opt.value
        item.textContent = opt.text

        item.addEventListener('click', () => {
          nativeSelect.value = opt.value
          nativeSelect.dispatchEvent(new Event('change', { bubbles: true }))
          updateTrigger()
          close()
        })

        dropdown.appendChild(item)
      })
    }

    const open = () => {
      document.querySelectorAll('.ciphers-custom-select--open').forEach((el) => {
        if (el !== wrapper) el.classList.remove('ciphers-custom-select--open')
      })
      refreshOptions()
      wrapper.classList.add('ciphers-custom-select--open')
      trigger.setAttribute('aria-expanded', 'true')
    }

    const close = () => {
      wrapper.classList.remove('ciphers-custom-select--open')
      trigger.setAttribute('aria-expanded', 'false')
    }

    trigger.addEventListener('click', (e) => {
      e.stopPropagation()
      wrapper.classList.contains('ciphers-custom-select--open') ? close() : open()
    })

    dropdown.addEventListener('click', (e) => e.stopPropagation())

    nativeSelect.addEventListener('change', updateTrigger)

    updateTrigger()

    if (!documentListenerAdded) {
      documentListenerAdded = true
      document.addEventListener('click', () => {
        document.querySelectorAll('.ciphers-custom-select--open').forEach((el) => {
          el.classList.remove('ciphers-custom-select--open')
        })
      })
    }
  })
}
