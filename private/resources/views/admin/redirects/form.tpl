<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0">{$title}</h1>
</div>

{if $error}
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {$error}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
{/if}

<div class="card border-0 shadow-sm" style="max-width:640px">
    <div class="card-body p-4">
        {if $redirect}
            {assign var=action value="{$admin_path}/redirects/{$redirect.id}"}
        {else}
            {assign var=action value="{$admin_path}/redirects"}
        {/if}

        <form method="POST" action="{$action}">
            <input type="hidden" name="_csrf_token" value="{$csrf_token}">

            <div class="mb-3">
                <label for="from_path" class="form-label fw-medium">Откуда <span class="text-danger">*</span></label>
                <input type="text" class="form-control font-monospace" id="from_path" name="from_path"
                       value="{$redirect.from_path|default:''}"
                       placeholder="/old-page" required>
                <div class="form-text">Относительный путь, начиная с /</div>
            </div>

            <div class="mb-3">
                <label for="to_path" class="form-label fw-medium">Куда <span class="text-danger">*</span></label>
                <input type="text" class="form-control font-monospace" id="to_path" name="to_path"
                       value="{$redirect.to_path|default:''}"
                       placeholder="/new-page" required>
                <div class="form-text">Относительный или абсолютный путь назначения</div>
            </div>

            <div class="mb-3">
                <label for="status_code" class="form-label fw-medium">Код статуса</label>
                <select class="form-select" id="status_code" name="status_code">
                    <option value="301" {if !$redirect || $redirect.status_code == 301}selected{/if}>
                        301 — Постоянный
                    </option>
                    <option value="302" {if $redirect && $redirect.status_code == 302}selected{/if}>
                        302 — Временный
                    </option>
                </select>
            </div>

            <div class="mb-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch"
                           id="is_active" name="is_active" value="1"
                           {if !$redirect || $redirect.is_active}checked{/if}>
                    <label class="form-check-label" for="is_active">Активен</label>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Сохранить</button>
                <a href="{$admin_path}/redirects" class="btn btn-outline-secondary">Отмена</a>
            </div>
        </form>
    </div>
</div>
