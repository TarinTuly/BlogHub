// pagination.js

export function renderPaginatedTable(items, containerId, columns, perPage = 10, currentPage = 1, options = {}) {
    const totalPages = Math.ceil(items.length / perPage);

    function renderPage(page) {
        currentPage = page;
        localStorage.setItem('currentPage', currentPage); // store current page

        const start = (page - 1) * perPage;
        const end = start + perPage;
        const pageItems = items.slice(start, end);

        let html = options.addTopHtml || '';
        html += `<table class="table-auto w-full border border-gray-300 text-center">
                    <thead><tr class="bg-blue-600 text-white font-bold">`;

        columns.forEach(col => html += `<th class="border p-2">${col.header}</th>`);
        if (options.addRowActions) html += `<th class="border p-2">Action</th>`;
        html += `</tr></thead><tbody>`;

        pageItems.forEach((item, i) => {
            html += `<tr class="hover:bg-gray-100">`;
            columns.forEach(col => {
                html += `<td class="border p-2">${col.render ? col.render(item, i, currentPage, perPage) : item[col.key] ?? '-'}</td>`;
            });
            if (options.addRowActions) html += `<td class="border p-2">${options.addRowActions(item)}</td>`;
            html += `</tr>`;
        });

        html += `</tbody></table>`;

        // Pagination controls
        if (totalPages > 1) {
            html += `<div class="flex justify-center items-center gap-2 mt-4">
                        <button id="prevPage" class="px-3 py-1 bg-gray-300 rounded hover:bg-gray-400" ${currentPage === 1 ? 'disabled' : ''}>Prev</button>
                        <span class="text-gray-700">Page ${currentPage} of ${totalPages}</span>
                        <button id="nextPage" class="px-3 py-1 bg-gray-300 rounded hover:bg-gray-400" ${currentPage === totalPages ? 'disabled' : ''}>Next</button>
                     </div>`;
        }

        document.getElementById(containerId).innerHTML = html;

        if (totalPages > 1) {
            document.getElementById('prevPage').addEventListener('click', () => {
                if (currentPage > 1) renderPage(currentPage - 1);
            });
            document.getElementById('nextPage').addEventListener('click', () => {
                if (currentPage < totalPages) renderPage(currentPage + 1);
            });
        }
    }

    renderPage(currentPage);
}
