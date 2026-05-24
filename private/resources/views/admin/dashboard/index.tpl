<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0">Дашборд</h1>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="admin-stat-icon bg-primary bg-opacity-10 text-primary rounded-3 p-3">
                    <i class="bi bi-people fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Пользователи</div>
                    <div class="fs-4 fw-bold">{$users|@count}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0"><i class="bi bi-people me-2 text-primary"></i>Пользователи</h5>
    </div>
    <div class="card-body p-0">
        {if $users}
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width:60px">ID</th>
                        <th>Имя</th>
                        <th>Email</th>
                        <th>Дата регистрации</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $users as $user}
                    <tr>
                        <td class="ps-4 text-muted small">{$user.id}</td>
                        <td class="fw-medium">{$user.name}</td>
                        <td class="text-muted">{$user.email}</td>
                        <td class="text-muted small">{$user.created_at}</td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        {else}
        <div class="p-4 text-muted text-center">
            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
            Пользователей пока нет
        </div>
        {/if}
    </div>
</div>
