const tableElement = $('.table');
const tbodyElement = $('#logs')[0];
const spinnerElement = $('.spinner');
const errorModalElement = $('#errorModal');
const usersElement = $('#users');
const ipsElement = $('#ips');
const initialDateElement = $('#initial_date');
const endDateElement = $('#end_date');
const searchElement = $('#search');
const pageElement = $('#page');

const errorModal = new bootstrap.Modal(errorModalElement);

errorModalElement.bind('hidden.bs.modal', event => {
    search();
});

let currentPage = 1;
// let lastPage = 1;
let limit = 50;

function loadLogs(page = 1, limit = 50, filters = {}) {
    clearListenButtons();

    let params = '';
    currentPage = page;

    Object.keys(filters).forEach((key) => {
        const value = filters[key];

        params += `${key}=${value}&`
    });

    tableElement.addClass('d-none');
    spinnerElement.removeClass('d-none');
    pageElement.html(`<strong>Página atual:</strong> ${page}`);

    axios.get(`/report.php?page=${page}&limit=${limit}&${params}`.slice(0, -1))
        .then(function (response) {
            // lastPage = Math.ceil(response.data.total / limit);
            tbodyElement.innerHTML = '';

            response.data.items.forEach((data) => {
                tbodyElement.innerHTML += `
                        <tr>
                            <th scope="row">${data.date}</th>
                            <td data-bs-toggle="tooltip" data-bs-title="${data.ip}">${data.user}</small></td>
                            <td data-bs-toggle="tooltip" data-bs-title="${formatUrl(data.url)}">
                                <a href="${formatUrl(data.url)}" target="_blank">${data.host}</a>
                            </td>
                            <td>${data.code}</i></td>
                            <td>${formatBytes(data.size)}</td>
                        </tr>
                    `;
            });

            spinnerElement.addClass('d-none');
            tableElement.removeClass('d-none');

            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltipTriggerList.forEach(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

            listenButtons();
        })
        .catch(function (error) {
            console.error(error);
            errorModal.show();
        });
}

function loadIps() {
    axios.get('/ips.php')
        .then(function (response) {
            ipsElement.find('option').not(':first').remove();

            response.data.forEach((ip) => {
                ipsElement.append(`<option value="${ip}">${ip}</option>`);
            });
        })
        .catch(function (error) {
            console.error(error);
        });
}

function loadUsers() {
    axios.get('/users.php')
        .then(function (response) {
            usersElement.find('option').not(':first').remove();

            response.data.forEach((user) => {
                usersElement.append(`<option value="${user}">${user}</option>`);
            });
        })
        .catch(function (error) {
            console.error(error);
        });
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';

    const units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    const factor = 1024;
    const index = Math.floor(Math.log(bytes) / Math.log(factor));
    const formattedValue = (bytes / Math.pow(factor, index)).toFixed(2);

    return `${formattedValue} ${units[index]}`;
}

function formatUrl(url) {
    const hasHttp = url.startsWith('http://');
    const hasHttps = url.startsWith('https://');
    const port = url.match(/:(\d+)$/)?.[1];

    url = url.replace(':443', '').replace(':80', '');

    if (port === '443' && !hasHttps) {
        url = `https://${url.replace('http://', '')}`;
    }

    if (port === '80' && !hasHttp) {
        url = `http://${url.replace('https://', '')}`;
    }

    if (!port && !hasHttp && !hasHttps) {
        url = `https://${url}`;
    }

    return url;
}

function listenButtons() {
    $('#btnSearch').bind('click', () => {
        searchWithParams(1, limit);
    });

    $('#search').bind('keyup', (e) => {
        if (e.key === 'Enter' || e.keyCode === 13) {
            searchWithParams(1, limit);
        }
    });

    $('#users').bind('change', () => {
        searchWithParams(1, limit);
    });

    $('#ips').bind('change', () => {
        searchWithParams(1, limit);
    });
}

function searchWithParams(page, limit) {
    const ip = ipsElement.val();
    const user = usersElement.val();
    const search = searchElement.val();
    const initialDate = initialDateElement.val();
    const endDate = endDateElement.val();

    loadLogs(page, limit, { ip, user, search, initialDate, endDate });
}

function clearListenButtons() {
    $('#btnSearch').off('click');
    $('#search').off('keyup');
    $('#users').off('change');
    $('#ips').off('change');
}

function first() {
    if (currentPage === 1) return;

    searchWithParams(1, limit);
}

// function last() {
//     if (currentPage === lastPage) return;
//
//     searchWithParams(lastPage, limit);
// }

function next() {
    // if (currentPage === lastPage) return;

    searchWithParams(currentPage+1, limit);
}

function previous() {
    if (currentPage === 1) return;

    searchWithParams(currentPage-1, limit);
}

function search() {
    errorModal.hide();

    loadLogs(currentPage);
}

$(() => {
    errorModalElement.bind('hidden.bs.modal', event => {
        search();
    });

    search();

    loadIps();
    loadUsers();

    $('#initial_date').flatpickr({
        enableTime: true,
        dateFormat: 'Y-m-d H:i',
        altInput: true,
        altFormat: 'd/m/Y H:i',
        ariaDateFormat: 'd/m/Y H:i',
        time_24hr: true,
        maxDate: new Date(),
        locale: 'pt',
        allowInput: true,
        onClose: () => searchWithParams(1, limit),
    });
    $('#end_date').flatpickr({
        enableTime: true,
        dateFormat: 'Y-m-d H:i',
        altInput: true,
        altFormat: 'd/m/Y H:i',
        ariaDateFormat: 'd/m/Y H:i',
        time_24hr: true,
        maxDate: new Date(),
        locale: 'pt',
        allowInput: true,
        onClose: () => searchWithParams(1, limit),
    });
});