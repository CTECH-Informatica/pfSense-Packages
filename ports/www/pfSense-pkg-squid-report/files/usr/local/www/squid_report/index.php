<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>CTECH - Relatório de uso proxy</title>
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap-icons.min.css">
    <link rel="stylesheet" type="text/css" href="assets/css/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="assets/css/report.css">
</head>
<body>
<header>
    <div class="px-3 py-2 text-bg-dark border-bottom">
        <div class="container">
            <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-lg-start">
                <a href="/"
                   class="d-flex align-items-center my-2 my-lg-0 me-lg-auto text-white text-decoration-none">
                    <img src="assets/images/logo.png" alt="CTECH" width="128" height="40">
                </a>

<!--                <ul class="nav col-12 col-lg-auto my-2 justify-content-center my-md-0 text-small">-->
<!--                    <li>-->
<!--                        <a href="#" class="nav-link text-secondary">-->
<!--                            <i class="bi bi-speedometer2"></i>-->
<!--                            Relatório-->
<!--                        </a>-->
<!--                    </li>-->
<!--                </ul>-->
            </div>
        </div>
    </div>
</header>
<div class="container-fluid d-flex flex-column align-items-center mt-3">
    <div class="row mb-5">
        <div class="col-2"></div>
        <div class="col-4 mb-2">
            <div class="input-group">
                <label for="users" class="input-group-text">Usuário</label>
                <select class="form-select" aria-label="Usuários" id="users">
                    <option value="" selected>Selecione um usuário para filtrar</option>
                </select>
            </div>
        </div>
        <div class="col-4 mb-2">
            <div class="input-group">
                <label for="ips" class="input-group-text">IP</label>
                <select class="form-select" aria-label="IPs" id="ips">
                    <option value="" selected>Selecione um IP para filtrar</option>
                </select>
            </div>
        </div>
        <div class="col-2"></div>
        <div class="col-2"></div>
        <div class="col-4 mb-2">
            <div class="input-group">
                <label for="initial_date" class="input-group-text">Data início</label>
                <input type="text" id="initial_date" class="form-control" aria-label="Data início">
            </div>
        </div>
        <div class="col-4 mb-2">
            <div class="input-group">
                <label for="end_date" class="input-group-text">Data fim</label>
                <input type="text" id="end_date" class="form-control" aria-label="Data fim">
            </div>
        </div>
        <div class="col-2"></div>
        <div class="col-2"></div>
        <div class="col-8">
            <div class="input-group">
                <label for="search" class="input-group-text">Endereço</label>
                <input type="text" id="search" class="form-control" aria-label="Busca">
                <button type="button" class="btn btn-success" id="btnSearch">
                    <i class="bi bi-search"></i> Buscar
                </button>
            </div>
        </div>
    </div>

    <div class="spinner spinner-border" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>

    <div>
        <table class="table table-striped d-none">
            <thead class="sticky-top table-primary">
            <tr>
                <th scope="col">Data</th>
                <th scope="col">Usuário&nbsp;<i class="bi bi-info-circle-fill" data-bs-toggle="tooltip" data-bs-title="Nem todas requisições possuem usuário. Algumas podem ter sido realizadas sem autenticação."></th>
                <th scope="col">Endereço</th>
                <th scope="col">Status&nbsp;<i class="bi bi-info-circle-fill" data-bs-toggle="tooltip" data-bs-title="O status indica a situação da requisição. Códigos 2xx e 3xx indicam sucesso no acesso. Códigos 4xx e 5xx indicam alguma erro na requisição. O código 407, por exemplo, indica que foi bloqueado o acesso pelo Proxy por falta de autenticação."></th>
                <th scope="col">Tamanho&nbsp;<i class="bi bi-info-circle-fill" data-bs-toggle="tooltip" data-bs-title="Esse tamanho indica a soma total dos dados transmitidos e recebidos em uma mesma requisição."></th>
            </tr>
            </thead>
            <tbody id="logs">
            </tbody>
        </table>
    </div>

    <div class="mt-2">
        <nav aria-label="Páginas">
            <ul class="pagination pagination-lg">
                <li class="page-item" onclick="first()" data-bs-toggle="tooltip" data-bs-title="Primeira página"><a class="page-link" href="#">&laquo;</a></li>
                <li class="page-item" onclick="previous()" data-bs-toggle="tooltip" data-bs-title="Página anterior"><a class="page-link" href="#">&lt;</a></li>
                <li class="page-item" onclick="next()" data-bs-toggle="tooltip" data-bs-title="Próxima página"><a class="page-link" href="#">&gt;</a></li>
<!--                <li class="page-item" onclick="last()" data-bs-toggle="tooltip" data-bs-title="Última página"><a class="page-link" href="#">&raquo;</a></li>-->
            </ul>
            <p id="page" class="text-center"></p>
        </nav>
    </div>
</div>
<div>
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Erro ao buscar registros</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body d-flex flex-column align-items-center">
                    <div class="alert alert-danger" role="alert">
                        <strong>Erro ao buscar registros!</strong> Por favor, tente novamente.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal" aria-label="Close">Tentar novamente</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript" src="assets/js/jquery.min.js"></script>
<script type="text/javascript" src="assets/js/bootstrap.bundle.min.js"></script>
<script type="text/javascript" src="assets/js/axios.min.js"></script>
<script type="text/javascript" src="assets/js/flatpickr.min.js"></script>
<script type="text/javascript" src="assets/js/l10n/flatpickr/pt.js"></script>
<script type="text/javascript" src="assets/js/report.js"></script>
</body>
</html>
