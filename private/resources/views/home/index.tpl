<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="mb-4">{$heading}</h1>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-rocket-takeoff me-2 text-primary"></i>{$t.HOME_WELCOME_TITLE}
                </h5>
                <p class="card-text text-muted mb-0">{$t.HOME_WELCOME_TEXT}</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-sm-6">
                <div class="card h-100 border-primary">
                    <div class="card-body">
                        <h6 class="card-title text-primary">
                            <i class="bi bi-shield-lock me-1"></i>{$t.FEAT_AUTH}
                        </h6>
                        <p class="card-text small text-muted mb-0">{$t.FEAT_AUTH_DESC}</p>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="card h-100 border-success">
                    <div class="card-body">
                        <h6 class="card-title text-success">
                            <i class="bi bi-database me-1"></i>{$t.FEAT_DB}
                        </h6>
                        <p class="card-text small text-muted mb-0">{$t.FEAT_DB_DESC}</p>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="card h-100 border-warning">
                    <div class="card-body">
                        <h6 class="card-title text-warning">
                            <i class="bi bi-arrow-left-right me-1"></i>{$t.FEAT_ROUTER}
                        </h6>
                        <p class="card-text small text-muted mb-0">{$t.FEAT_ROUTER_DESC}</p>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="card h-100 border-info">
                    <div class="card-body">
                        <h6 class="card-title text-info">
                            <i class="bi bi-file-earmark-code me-1"></i>{$t.FEAT_TEMPLATES}
                        </h6>
                        <p class="card-text small text-muted mb-0">{$t.FEAT_TEMPLATES_DESC}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            {if $auth_user === null}
                <a href="{$locale_prefix}/login" class="btn btn-primary">
                    <i class="bi bi-box-arrow-in-right me-1"></i>{$t.HOME_GO_LOGIN}
                </a>
            {else}
                <a href="/cabinet" class="btn btn-primary">
                    <i class="bi bi-person-circle me-1"></i>{$t.HOME_GO_CABINET}
                </a>
            {/if}
        </div>
    </div>
</div>
