<div class="row">
    <div class="col-lg-4 mb-4 mb-lg-0">
        <div class="card shadow-sm text-center">
            <div class="card-body py-4">
                <i class="bi bi-person-circle display-3 text-primary mb-3 d-block"></i>
                <h4 class="card-title mb-1">{$auth_user.name}</h4>
                <p class="text-muted small mb-3">{$auth_user.email}</p>
                <span class="badge bg-success">{$t.CABINET_STATUS}</span>
            </div>
            <div class="card-footer bg-transparent">
                <form method="POST" action="{$locale_prefix}/logout">
                    <input type="hidden" name="_csrf_token" value="{$csrf_token}">
                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                        <i class="bi bi-box-arrow-right me-1"></i>{$t.CABINET_SIGN_OUT}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <h1 class="mb-4">{$t.CABINET_TITLE}</h1>

        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <div class="card text-center border-0 bg-primary bg-opacity-10">
                    <div class="card-body">
                        <i class="bi bi-hash fs-2 text-primary mb-2 d-block"></i>
                        <div class="fw-bold">{$t.CABINET_ID}</div>
                        <div class="text-muted small">#{$auth_user.id}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card text-center border-0 bg-success bg-opacity-10">
                    <div class="card-body">
                        <i class="bi bi-envelope-check fs-2 text-success mb-2 d-block"></i>
                        <div class="fw-bold">Email</div>
                        <div class="text-muted small text-truncate">{$auth_user.email}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card text-center border-0 bg-warning bg-opacity-10">
                    <div class="card-body">
                        <i class="bi bi-shield-check fs-2 text-warning mb-2 d-block"></i>
                        <div class="fw-bold">{$t.CABINET_STATUS_LBL}</div>
                        <div class="text-muted small">{$t.CABINET_ROLE}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>{$t.CABINET_ACTIVITY}</h6>
            </div>
            <div class="card-body text-muted small">
                {$t.CABINET_ACTIVITY_TXT}
            </div>
        </div>
    </div>
</div>
