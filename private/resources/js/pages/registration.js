/**
 * Инициализирует UI регистрации: валидацию полей и отправку запроса в API.
 */
export function initRegistrationPage() {
    const root = document.getElementById('registrationApp')
    if (!root) {
        return
    }

    const submitButton = document.getElementById('registrationSubmit')
    const alertNode = document.getElementById('registrationAlert')
    const nameNode = document.getElementById('regName')
    const emailNode = document.getElementById('regEmail')
    const passwordNode = document.getElementById('regPassword')
    const confirmationNode = document.getElementById('regPasswordConfirmation')
    const policyNode = document.getElementById('regPolicyAgreement')

    const fields = [
        { input: nameNode, error: document.getElementById('regNameError') },
        { input: emailNode, error: document.getElementById('regEmailError') },
        { input: passwordNode, error: document.getElementById('regPasswordError') },
        { input: confirmationNode, error: document.getElementById('regPasswordConfirmationError') },
        { input: policyNode, error: document.getElementById('regPolicyAgreementError') },
    ]

    const text = {
        language: root.dataset.language ?? 'en',
        cabinetUrl: root.dataset.cabinetUrl ?? '/cabinet',
        registering: root.dataset.registering ?? 'Creating your account...',
        success: root.dataset.success ?? 'Registration completed successfully.',
        nameRequired: root.dataset.errorNameRequired ?? 'Name is required.',
        nameLength: root.dataset.errorNameLength ?? 'Name must be between 2 and 100 characters.',
        emailRequired: root.dataset.errorEmailRequired ?? 'Email is required.',
        emailInvalid: root.dataset.errorEmailInvalid ?? 'Please enter a valid email.',
        passwordRequired: root.dataset.errorPasswordRequired ?? 'Password is required.',
        passwordLength: root.dataset.errorPasswordLength ?? 'Password must be at least 8 characters.',
        confirmationRequired: root.dataset.errorConfirmationRequired ?? 'Password confirmation is required.',
        confirmationMismatch: root.dataset.errorConfirmationMismatch ?? 'Passwords do not match.',
        policyRequired: root.dataset.errorPolicyRequired ?? 'You must agree to the privacy policy and terms of service.',
    }

    const showAlert = (message, type) => {
        alertNode.className = 'alert alert-' + type
        alertNode.textContent = message
    }

    const clearFieldError = (field) => {
        if (!field.input || !field.error) {
            return
        }

        field.input.classList.remove('is-invalid')
        field.error.textContent = ''
    }

    const setFieldError = (field, message) => {
        if (!field.input || !field.error) {
            return
        }

        field.input.classList.add('is-invalid')
        field.error.textContent = message
    }

    const fieldMap = {
        name: fields[0],
        email: fields[1],
        password: fields[2],
        confirmation: fields[3],
        policy: fields[4],
    }

    const validateName = () => {
        const value = nameNode.value.trim()
        if (value === '') return text.nameRequired
        if (value.length < 2 || value.length > 100) return text.nameLength
        return null
    }

    const validateEmail = () => {
        const value = emailNode.value.trim()
        if (value === '') return text.emailRequired
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
        if (!emailRegex.test(value)) return text.emailInvalid
        return null
    }

    const validatePassword = () => {
        const value = passwordNode.value
        if (value === '') return text.passwordRequired
        if (value.length < 8) return text.passwordLength
        return null
    }

    const validateConfirmation = () => {
        const value = confirmationNode.value
        if (value === '') return text.confirmationRequired
        if (value !== passwordNode.value) return text.confirmationMismatch
        return null
    }

    const validatePolicy = () => {
        if (!policyNode.checked) return text.policyRequired
        return null
    }

    const validateAll = () => {
        const errors = {
            name: validateName(),
            email: validateEmail(),
            password: validatePassword(),
            confirmation: validateConfirmation(),
            policy: validatePolicy(),
        }

        Object.entries(errors).forEach(([key, message]) => {
            const field = fieldMap[key]
            clearFieldError(field)
            if (message !== null) {
                setFieldError(field, message)
            }
        })

        return Object.values(errors).every((value) => value === null)
    }

    nameNode.addEventListener('input', () => {
        clearFieldError(fieldMap.name)
        const error = validateName()
        if (error !== null) setFieldError(fieldMap.name, error)
    })

    emailNode.addEventListener('input', () => {
        clearFieldError(fieldMap.email)
        const error = validateEmail()
        if (error !== null) setFieldError(fieldMap.email, error)
    })

    passwordNode.addEventListener('input', () => {
        clearFieldError(fieldMap.password)
        const error = validatePassword()
        if (error !== null) setFieldError(fieldMap.password, error)

        clearFieldError(fieldMap.confirmation)
        const confirmationError = validateConfirmation()
        if (confirmationError !== null && confirmationNode.value !== '') {
            setFieldError(fieldMap.confirmation, confirmationError)
        }
    })

    confirmationNode.addEventListener('input', () => {
        clearFieldError(fieldMap.confirmation)
        const error = validateConfirmation()
        if (error !== null) setFieldError(fieldMap.confirmation, error)
    })

    policyNode.addEventListener('change', () => {
        clearFieldError(fieldMap.policy)
        const error = validatePolicy()
        if (error !== null) setFieldError(fieldMap.policy, error)
    })

    submitButton?.addEventListener('click', async () => {
        showAlert('', 'secondary')
        alertNode.classList.add('d-none')

        if (!validateAll()) {
            return
        }

        submitButton.disabled = true
        showAlert(text.registering, 'secondary')

        try {
            await window.api.guest.register({
                name: nameNode.value.trim(),
                email: emailNode.value.trim(),
                password: passwordNode.value,
                password_confirmation: confirmationNode.value,
                language: text.language,
                policy_agreement: policyNode.checked,
            })

            showAlert(text.success, 'success')
            window.location.href = text.cabinetUrl
        } catch (error) {
            const message = error?.message ?? error?.response?.error?.message ?? 'Internal Server Error'
            showAlert(message, 'danger')
        } finally {
            submitButton.disabled = false
        }
    })
}
